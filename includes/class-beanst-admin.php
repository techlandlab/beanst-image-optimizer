<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BeanST_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Media Library Columns
		add_filter( 'manage_media_columns', array( $this, 'add_media_columns' ) );
		add_action( 'manage_media_custom_column', array( $this, 'render_media_columns' ), 10, 2 );

		// AJAX Actions for SEO & Comparison
		add_action( 'wp_ajax_beanst_apply_seo', array( $this, 'ajax_apply_seo' ) );
		add_action( 'wp_ajax_beanst_get_comparison_data', array( $this, 'ajax_get_comparison_data' ) );

		// Cleanup Actions
		add_action( 'wp_ajax_beanst_scan_orphans', array( $this, 'ajax_scan_orphans' ) );
		add_action( 'wp_ajax_beanst_delete_orphans', array( $this, 'ajax_delete_orphans' ) );
		
		// V2 UX Actions
		add_action( 'wp_ajax_beanst_update_option', array( $this, 'ajax_update_option' ) );
	}

	public function add_admin_menu() {
		add_options_page(
			__( 'BeanST Optimizer', 'beanst-image-optimizer' ),
			__( 'BeanST Optimizer', 'beanst-image-optimizer' ),
			'manage_options',
			'beanst-image-optimizer',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'beanst_options_group', 'beanst_options', array( $this, 'sanitize_options' ) );

		add_settings_section(
			'beanst_main_section',
			__( 'Optimization Settings', 'beanst-image-optimizer' ),
			null,
			'beanst-image-optimizer'
		);

		add_settings_field(
			'auto_convert',
			__( 'Auto-Convert on Upload', 'beanst-image-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'auto_convert', 'label' => __( 'Automatically optimize new images', 'beanst-image-optimizer' ) )
		);

		add_settings_field(
			'formats',
			__( 'Output Formats', 'beanst-image-optimizer' ),
			array( $this, 'render_formats_field' ),
			'beanst-image-optimizer',
			'beanst_main_section'
		);

		add_settings_field(
			'quality',
			__( 'Image Quality', 'beanst-image-optimizer' ),
			array( $this, 'render_number_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'quality', 'min' => 1, 'max' => 100, 'default' => 80 )
		);

		add_settings_field(
			'max_width',
			__( 'Max Image Width', 'beanst-image-optimizer' ),
			array( $this, 'render_number_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'max_width', 'min' => 0, 'max' => 9999, 'default' => 2560, 'label' => __( 'px (0 to disable)', 'beanst-image-optimizer' ) )
		);

		add_settings_field(
			'strip_metadata',
			__( 'Strip Metadata (EXIF)', 'beanst-image-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'strip_metadata', 'label' => __( 'Remove all EXIF and metadata for smaller files', 'beanst-image-optimizer' ) )
		);

		add_settings_field(
			'lazy_load',
			__( 'Enable Lazy Loading', 'beanst-image-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'lazy_load', 'label' => __( 'Add loading="lazy" to all images', 'beanst-image-optimizer' ) )
		);

		add_settings_field(
			'keep_backups',
			__( 'Backup Original Images', 'beanst-image-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'keep_backups', 'label' => __( 'Keep a copy of the original image before resizing/optimizing', 'beanst-image-optimizer' ) )
		);

		add_settings_field(
			'scan_paths',
			__( 'Directory Janitor (Scan Paths)', 'beanst-image-optimizer' ),
			array( $this, 'render_textarea_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'scan_paths', 'label' => __( 'Paths outside Media Library to scan (one per line). Absolute paths or relative to WordPress root.', 'beanst-image-optimizer' ), 'placeholder' => "wp-content/themes/\ncustom-images/" )
		);

		add_settings_field(
			'heic_convert',
			__( 'Smart HEIC Support', 'beanst-image-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'heic_convert', 'label' => __( 'Automatically convert iPhone (HEIC) uploads to JPEG/WebP', 'beanst-image-optimizer' ) )
		);

		add_settings_field(
			'bulk_delay',
			__( 'Bulk Processing Delay', 'beanst-image-optimizer' ),
			array( $this, 'render_number_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'bulk_delay', 'min' => 0, 'max' => 5000, 'default' => 0, 'label' => __( 'ms (delay between each image)', 'beanst-image-optimizer' ) )
		);

		add_settings_field(
			'lqip_blur',
			__( 'Ultra-Premium Blur-Up (LQIP)', 'beanst-image-optimizer' ),
			array( $this, 'render_checkbox_field' ),
			'beanst-image-optimizer',
			'beanst_main_section',
			array( 'key' => 'lqip_blur', 'label' => __( 'Generate tiny blurred placeholders for smooth loading transitions', 'beanst-image-optimizer' ) )
		);
	}

	/**
	 * Sanitize all options
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();

		if ( isset( $input['auto_convert'] ) ) {
			$sanitized['auto_convert'] = '1';
		} else {
			$sanitized['auto_convert'] = '0';
		}

		if ( isset( $input['formats'] ) && is_array( $input['formats'] ) ) {
			$sanitized['formats'] = array_intersect( $input['formats'], array( 'webp', 'avif' ) );
		} else {
			$sanitized['formats'] = array( 'webp' );
		}

		if ( isset( $input['quality'] ) ) {
			$sanitized['quality'] = max( 1, min( 100, intval( $input['quality'] ) ) );
		}

		if ( isset( $input['max_width'] ) ) {
			$sanitized['max_width'] = max( 0, intval( $input['max_width'] ) );
		}

		if ( isset( $input['strip_metadata'] ) ) {
			$sanitized['strip_metadata'] = '1';
		} else {
			$sanitized['strip_metadata'] = '0';
		}

		if ( isset( $input['lazy_load'] ) ) {
			$sanitized['lazy_load'] = '1';
		} else {
			$sanitized['lazy_load'] = '0';
		}

		if ( isset( $input['keep_backups'] ) ) {
			$sanitized['keep_backups'] = '1';
		} else {
			$sanitized['keep_backups'] = '0';
		}

		if ( isset( $input['scan_paths'] ) ) {
			$paths = explode( "\n", $input['scan_paths'] );
			$clean_paths = array();
			foreach ( $paths as $path ) {
				$path = trim( sanitize_text_field( $path ) );
				if ( ! empty( $path ) ) {
					$clean_paths[] = $path;
				}
			}
			$sanitized['scan_paths'] = implode( "\n", $clean_paths );
		}

		if ( isset( $input['heic_convert'] ) ) {
			$sanitized['heic_convert'] = '1';
		} else {
			$sanitized['heic_convert'] = '0';
		}

		$sanitized['bulk_delay']      = isset( $input['bulk_delay'] ) ? intval( $input['bulk_delay'] ) : 0;
		$sanitized['lqip_blur']      = isset( $input['lqip_blur'] ) ? 1 : 0;

		return $sanitized;
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_beanst-image-optimizer' !== $hook && 'upload.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'beanst-admin-css',
			BEANST_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			time()
		);

		wp_enqueue_script(
			'beanst-admin-js',
			BEANST_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			time(),
			true
		);
		
		wp_localize_script( 'beanst-admin-js', 'beanst_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'beanst_bulk_action' ),
			'i18n'     => array(
				'confirm_optimize_all' => __( 'Are you sure you want to optimize all images? This might take a while.', 'beanst-image-optimizer' ),
				/* translators: 1: number of processed images, 2: total number of images */
				'paused_status'        => __( 'Paused. %1$s / %2$s images processed.', 'beanst-image-optimizer' ),
				'resuming'             => __( 'Resuming Engine...', 'beanst-image-optimizer' ),
				'initializing'         => __( 'Initializing systems...', 'beanst-image-optimizer' ),
				/* translators: %s: number of images found */
				'found_images'         => __( 'Found %s images. Launching optimization...', 'beanst-image-optimizer' ),
				'error_fetching'       => __( 'Error fetching image data', 'beanst-image-optimizer' ),
				'unknown_file'         => __( 'Unknown file', 'beanst-image-optimizer' ),
				'optimization_complete'=> __( 'Optimization Complete! 🎉 All images are now optimized.', 'beanst-image-optimizer' ),
				'optimize_library_again'=> __( 'Optimize Library Again', 'beanst-image-optimizer' ),
				'applying'             => __( 'Applying...', 'beanst-image-optimizer' ),
				'apply_seo'            => __( 'Apply SEO', 'beanst-image-optimizer' ),
				'loading'              => __( 'Loading...', 'beanst-image-optimizer' ),
				'scanning_uploads'     => __( 'Scanning uploads...', 'beanst-image-optimizer' ),
				'scan_orphans'         => __( 'Scan for Unused Files', 'beanst-image-optimizer' ),
				'error_scanning'       => __( 'Error scanning: ', 'beanst-image-optimizer' ),
				'unknown_error'        => __( 'An unknown error occurred.', 'beanst-image-optimizer' ),
				'no_orphans'           => __( 'No orphaned files found.', 'beanst-image-optimizer' ),
				'select_one_delete'    => __( 'Please select at least one file to delete.', 'beanst-image-optimizer' ),
				/* translators: %s: number of files to delete */
				'confirm_delete'       => __( 'Are you sure you want to delete %s selected files? This cannot be undone.', 'beanst-image-optimizer' ),
				'deleting'             => __( 'Deleting...', 'beanst-image-optimizer' ),
				'error_deleting'       => __( 'Error deleting: ', 'beanst-image-optimizer' ),
				'clear_selected'       => __( 'Clear Selected', 'beanst-image-optimizer' ),
				/* translators: 1: number of files cleaned, 2: amount of space freed */
				'success_cleaned'      => __( 'Success! Cleaned %1$s files, freed %2$s space.', 'beanst-image-optimizer' ),
				'enabled'              => __( 'Enabled', 'beanst-image-optimizer' ),
				'disabled'             => __( 'Disabled', 'beanst-image-optimizer' )
			)
		) );
	}

	public function render_settings_page() {
		// Load V2 view with simplified UX
		include BEANST_PLUGIN_DIR . 'admin/views/settings-page-v2.php';
	}

	public function render_checkbox_field( $args ) {
		$options = get_option( 'beanst_options' );
		$value = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : '1';
		?>
		<label>
			<input type="checkbox" name="beanst_options[<?php echo esc_attr( $args['key'] ); ?>]" value="1" <?php checked( $value, '1' ); ?>>
			<?php echo esc_html( $args['label'] ); ?>
		</label>
		<?php
	}

	public function render_formats_field() {
		$options = get_option( 'beanst_options' );
		$formats = isset( $options['formats'] ) ? $options['formats'] : array( 'webp' );
		?>
		<fieldset>
			<label><input type="checkbox" name="beanst_options[formats][]" value="webp" <?php checked( in_array( 'webp', $formats ) ); ?>> WebP</label><br>
			<label><input type="checkbox" name="beanst_options[formats][]" value="avif" <?php checked( in_array( 'avif', $formats ) ); ?>> AVIF (Requires Imagick + AVIF support)</label>
		</fieldset>
		<?php
	}

	public function render_number_field( $args ) {
		$options = get_option( 'beanst_options' );
		$value = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : $args['default'];
		?>
		<input type="number" name="beanst_options[<?php echo esc_attr( $args['key'] ); ?>]" value="<?php echo esc_attr( $value ); ?>" min="<?php echo esc_attr( $args['min'] ); ?>" max="<?php echo esc_attr( $args['max'] ); ?>">
		<?php if ( isset( $args['label'] ) ) echo esc_html( $args['label'] ); ?>
		<?php
	}

	public function render_textarea_field( $args ) {
		$options = get_option( 'beanst_options' );
		$value = isset( $options[ $args['key'] ] ) ? $options[ $args['key'] ] : '';
		?>
		<textarea name="beanst_options[<?php echo esc_attr( $args['key'] ); ?>]" rows="3" style="width: 100%; max-width: 400px;" placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html( $args['label'] ); ?></p>
		<?php
	}

	public function add_media_columns( $columns ) {
		$columns['beanst_status'] = __( 'BeanST Status', 'beanst-image-optimizer' );
		$columns['beanst_seo']    = __( 'SEO Review', 'beanst-image-optimizer' );
		return $columns;
	}

	public function render_media_columns( $column_name, $id ) {
		if ( 'beanst_status' === $column_name ) {
			$stats = BeanST_Stats::get_attachment_stats( $id );
			if ( $stats['is_optimized'] ) {
				echo '<span class="beanst-status-ok" style="color: #46b450; font-weight: bold;">' . esc_html__( '✓ Optimized', 'beanst-image-optimizer' ) . '</span>';
				/* translators: 1: percentage saved, 2: size saved */
				echo '<br><small>' . esc_html( sprintf( __( 'Saved: %1$s%% (%2$s)', 'beanst-image-optimizer' ), $stats['percent'], size_format( $stats['savings_bytes'], 1 ) ) ) . '</small>';
				
				$mime = get_post_mime_type( $id );
				if ( strpos( $mime, 'image' ) !== false ) {
					echo '<br><a href="#" class="beanst-compare-link" data-id="' . esc_attr( $id ) . '" style="font-size: 11px; text-decoration: underline; color: #2271b1;">' . esc_html__( 'Compare Original', 'beanst-image-optimizer' ) . '</a>';
				}
			} else {
				echo '<span class="beanst-status-none" style="color: #999;">' . esc_html__( 'Not optimized', 'beanst-image-optimizer' ) . '</span>';
			}
		} elseif ( 'beanst_seo' === $column_name ) {
			$suggestions = BeanST_SEO::get_suggestions( $id );
			if ( ! $suggestions ) return;

			if ( $suggestions['needs_rename'] || $suggestions['needs_alt'] ) {
				echo '<div class="beanst-seo-suggestion" data-id="' . esc_attr( $id ) . '">';
				if ( $suggestions['needs_rename'] ) {
					echo '<div style="margin-bottom: 5px;"><strong>' . esc_html__( 'Rename:', 'beanst-image-optimizer' ) . '</strong><br><del style="color:#999; font-size:10px;">' . esc_html( $suggestions['current_name'] ) . '</del> → <span style="color:#2271b1;">' . esc_html( $suggestions['proposed_name'] ) . '</span></div>';
				}
				if ( $suggestions['needs_alt'] ) {
					echo '<div style="margin-bottom: 5px;"><strong>' . esc_html__( 'Alt:', 'beanst-image-optimizer' ) . '</strong> <span style="color:#2271b1;">' . esc_html( $suggestions['proposed_alt'] ) . '</span></div>';
				}
				echo '<button class="button button-small beanst-apply-seo" data-id="' . esc_attr( $id ) . '">' . esc_html__( 'Apply SEO', 'beanst-image-optimizer' ) . '</button>';
				echo '</div>';
			} else {
				echo '<span style="color: #46b450;">' . esc_html__( '✓ Optimized for SEO', 'beanst-image-optimizer' ) . '</span>';
			}
		}
	}

	public function ajax_apply_seo() {
		check_ajax_referer( 'beanst_bulk_action', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'beanst-image-optimizer' ) );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID', 'beanst-image-optimizer' ) );
		}

		$seo = new BeanST_SEO();
		if ( $seo->apply_changes( $id ) ) {
			wp_send_json_success( __( 'SEO Applied', 'beanst-image-optimizer' ) );
		} else {
			wp_send_json_error( __( 'Failed to apply SEO', 'beanst-image-optimizer' ) );
		}
	}

	public function ajax_get_comparison_data() {
		check_ajax_referer( 'beanst_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'beanst-image-optimizer' ) );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid ID', 'beanst-image-optimizer' ) );
		}

		$file = get_attached_file( $id );
		if ( ! $file || ! file_exists( $file ) ) {
			wp_send_json_error( __( 'File not found', 'beanst-image-optimizer' ) );
		}

		$original_url = wp_get_attachment_url( $id );
		$optimized_url = '';

		// Check for True Original Backup first
		$upload_dir = wp_upload_dir();
		$rel_path   = str_replace( $upload_dir['basedir'], '', dirname( $file ) );
		$backup_file = $upload_dir['basedir'] . '/beanst-backups' . $rel_path . '/' . basename( $file );
		
		if ( file_exists( $backup_file ) ) {
			$original_url = $upload_dir['baseurl'] . '/beanst-backups' . $rel_path . '/' . basename( $file );
		}

		$info = pathinfo( $file );
		$dir_url = str_replace( basename( wp_get_attachment_url($id) ), '', wp_get_attachment_url($id) );
		$name = $info['filename'];

		// Check for WebP or AVIF
		foreach ( array( 'webp', 'avif' ) as $ext ) {
			$opt_file = $info['dirname'] . '/' . $name . '.' . $ext;
			if ( file_exists( $opt_file ) ) {
				$optimized_url = $dir_url . $name . '.' . $ext;
				break;
			}
		}

		if ( ! $optimized_url ) {
			wp_send_json_error( __( 'Optimized version not found', 'beanst-image-optimizer' ) );
		}

		wp_send_json_success( array(
			'original'  => $original_url,
			'optimized' => $optimized_url
		) );
	}

	public function ajax_delete_orphans() {
		check_ajax_referer( 'beanst_bulk_action', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'beanst-image-optimizer' ) );
		}

		$files_to_delete = isset( $_POST['files'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['files'] ) ) : array();
		if ( empty( $files_to_delete ) ) {
			wp_send_json_error( __( 'No files selected', 'beanst-image-optimizer' ) );
		}

		$scanner = new BeanST_Scanner();
		$result = $scanner->delete_orphans( $files_to_delete );
		
		// Sync registry too
		$scanner->sync_registry();

		wp_send_json_success( array(
			'count' => $result['count'],
			'freed' => size_format( $result['freed'], 2 )
		) );
	}

	public function ajax_scan_orphans() {
		check_ajax_referer( 'beanst_bulk_action', 'nonce' );
		
		$scanner = new BeanST_Scanner();
		$orphans = $scanner->find_orphaned_files();
		$upload_dir = wp_upload_dir();
		
		$data = array();
		$total_size = 0;
		
		foreach ( $orphans as $file ) {
			$size = @filesize( $file );
			$total_size += $size;
			
			// Generate preview URL
			$rel_path = str_replace( $upload_dir['basedir'], '', $file );
			$url = $upload_dir['baseurl'] . $rel_path;
			
			$data[] = array(
				'path' => $file,
				'url'  => $url,
				'name' => basename( $file ),
				'size' => size_format( $size, 2 )
			);
		}

		wp_send_json_success( array(
			'count' => count( $orphans ),
			'size'  => size_format( $total_size, 2 ),
			'files' => $data
		) );
	}

	public function ajax_update_option() {
		check_ajax_referer( 'beanst_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'beanst-image-optimizer' ) );
		}

		if ( ! isset( $_POST['option'] ) || ! isset( $_POST['value'] ) ) {
			wp_send_json_error( __( 'Missing parameters', 'beanst-image-optimizer' ) );
		}

		$option = sanitize_text_field( wp_unslash( $_POST['option'] ) );
		$value  = sanitize_text_field( wp_unslash( $_POST['value'] ) );

		// Get current options
		$options = get_option( 'beanst_options', array() );

		// Update specific option
		$options[ $option ] = $value;

		// Save updated options
		update_option( 'beanst_options', $options );

		wp_send_json_success( array(
			'option' => $option,
			'value'  => $value
		) );
	}
}
