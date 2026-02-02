<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BeanST_Bulk {

	public function __construct() {
		add_action( 'wp_ajax_beanst_get_stats', array( $this, 'get_sync_stats' ) );
		add_action( 'wp_ajax_beanst_process_batch', array( $this, 'process_batch' ) );
	}

	public function get_sync_stats() {
		check_ajax_referer( 'beanst_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'beanst-image-optimizer' ) );
		}

		$query = new WP_Query( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => array('image', 'application/pdf'),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		// Run Directory Scanner
		$scanner = new BeanST_Scanner();
		$external_files = $scanner->scan_directories();
		
		$all_ids = $query->posts;
		foreach ( $external_files as $rel_path ) {
			$all_ids[] = 'ext:' . $rel_path;
		}

		$stats = BeanST_Stats::get_overall_stats();
		$seo_stats = BeanST_SEO::get_seo_audit_stats();

		wp_send_json_success( array(
			'total'     => $query->found_posts + count( $external_files ),
			'ids'       => $all_ids,
			'optimized' => $stats['optimized'],
			'savings'   => $stats['savings_human'],
			'seo_score' => $seo_stats['score'],
			'bad_names' => $seo_stats['bad_names'],
			'missing_alt' => $seo_stats['missing_alt']
		) );
	}

	public function process_batch() {
		check_ajax_referer( 'beanst_bulk_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'beanst-image-optimizer' ) );
		}

		$converter = new BeanST_Converter();
		
		// Sanitize and validate inputs
		$force = isset( $_POST['force'] ) && sanitize_text_field( wp_unslash( $_POST['force'] ) ) === 'true';
		
		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( __( 'Missing ID parameter', 'beanst-image-optimizer' ) );
		}
		
		$id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
		
		// Handle External File (from Directory Scanner)
		if ( is_string( $id ) && strpos( $id, 'ext:' ) === 0 ) {
			$rel_path = str_replace( 'ext:', '', $id );
			$full_path = ABSPATH . $rel_path;

			if ( ! file_exists( $full_path ) ) {
				wp_send_json_error( sprintf( __( 'External file not found: %s', 'beanst-image-optimizer' ), esc_html( $rel_path ) ) );
			}

			if ( ! $this->check_memory_safety() ) {
				wp_send_json_error( __( 'Server memory low.', 'beanst-image-optimizer' ) );
			}

			$orig_size = filesize( $full_path );
			// We process as if it's a standalone file (no attachment meta)
			$converter->process_image( $full_path, 0, $force );
			
			$new_size = filesize( $full_path );
			$savings = ( $orig_size > $new_size ) ? ( $orig_size - $new_size ) : 0;

			// Update Registry
			$registry = get_option( 'beanst_external_registry', array() );
			if ( isset( $registry[ $rel_path ] ) ) {
				$registry[ $rel_path ]['optimized'] = true;
				$registry[ $rel_path ]['savings'] = $savings;
				update_option( 'beanst_external_registry', $registry );
			}

			wp_send_json_success( array(
				'message'  => sprintf( __( 'Optimized External: %s', 'beanst-image-optimizer' ), esc_html( $rel_path ) ),
				'filename' => basename( $full_path ),
				'memory'   => $this->get_memory_usage()
			) );
			return;
		}

		// Handle Media Library Attachment
		$attachment_id = intval( $id );
		
		if ( ! $attachment_id ) {
			wp_send_json_error( __( 'Invalid attachment ID', 'beanst-image-optimizer' ) );
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );
		$mime = get_post_mime_type( $attachment_id );
		
		if ( $metadata || $mime === 'application/pdf' ) {
			// Memory Guard: Skip if less than 10MB free (simple heuristic)
			if ( ! $this->check_memory_safety() ) {
				wp_send_json_error( __( 'Server memory low. Pausing for safety.', 'beanst-image-optimizer' ) );
			}

			$converter->auto_convert_attachment( $metadata, $attachment_id, $force );
			
			wp_send_json_success( array(
				'message'  => sprintf( __( 'Processed ID: %d', 'beanst-image-optimizer' ), $attachment_id ),
				'filename' => basename( get_attached_file( $attachment_id ) ),
				'memory'   => $this->get_memory_usage()
			) );
		} else {
			wp_send_json_error( sprintf( __( 'No metadata for ID: %d', 'beanst-image-optimizer' ), $attachment_id ) );
		}
	}

	private function check_memory_safety() {
		$limit = $this->get_memory_limit();
		$current = memory_get_usage( true );
		
		// If limit is -1, it's unlimited (rare in shared hosting)
		if ( $limit <= 0 ) return true;

		// If we use more than 85% of the limit, stop
		if ( ( $current / $limit ) > 0.85 ) {
			return false;
		}

		return true;
	}

	private function get_memory_usage() {
		$usage = memory_get_usage( true );
		return size_format( $usage, 2 );
	}

	private function get_memory_limit() {
		$limit = ini_get( 'memory_limit' );
		if ( ! $limit || $limit === '-1' ) return 0;

		$value = (int) $limit;
		$unit  = strtoupper( substr( $limit, -1 ) );

		switch ( $unit ) {
			case 'G': $value *= 1024 * 1024 * 1024; break;
			case 'M': $value *= 1024 * 1024; break;
			case 'K': $value *= 1024; break;
		}

		return $value;
	}
}