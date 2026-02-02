<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BeanST_Converter {

	public function __construct() {
		// Hook into image upload process
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'auto_convert_attachment' ), 10, 2 );

		// Hook into early upload for HEIC conversion
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'heic_prefilter' ) );
	}

	/**
	 * Automatically convert images on upload
	 */
	public function auto_convert_attachment( $metadata, $attachment_id, $force = false ) {
		// Check if auto-convert is enabled in options (default: on)
		$options = get_option( 'beanst_options', array() );
		$auto_convert = isset( $options['auto_convert'] ) ? $options['auto_convert'] : '1';

		if ( ! $auto_convert && ! $force ) {
			return $metadata;
		}

		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return $metadata;
		}

		$mime = get_post_mime_type( $attachment_id );

		// Process PDF if enabled
		if ( $mime === 'application/pdf' ) {
			$pdf_opt = isset( $options['pdf_optimization'] ) ? $options['pdf_optimization'] : '0';
			if ( $pdf_opt || $force ) {
				$this->optimize_pdf( $file );
			}
			return $metadata;
		}

		// Handle Original Resizing and Backup for Images
		$this->maybe_resize_original( $file );

		// Convert Original to WebP/AVIF
		$this->process_image( $file, $attachment_id, $force );

		// Convert Sizes
		if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			$dirname = dirname( $file );
			foreach ( $metadata['sizes'] as $size_info ) {
				$size_path = $dirname . '/' . $size_info['file'];
				$this->process_image( $size_path, $attachment_id, $force );
			}
		}

		return $metadata;
	}

	/**
	 * Process a single image or PDF file
	 */
	public function process_image( $file_path, $id, $force = false ) {
		$options = get_option( 'beanst_options', array() );
		$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( $ext === 'pdf' ) {
			return $this->optimize_pdf( $file_path, $id );
		}

		$formats = isset( $options['formats'] ) ? $options['formats'] : array( 'webp' );
		$quality = isset( $options['quality'] ) ? intval( $options['quality'] ) : 80;

		foreach ( $formats as $format ) {
			if ( $this->convert( $file_path, $format, $quality, $force ) ) {
				update_post_meta( $id, '_beanst_optimized', time() );
			}
		}

		// Generate LQIP for the original image
		$this->generate_lqip( $file_path, $id );
	}

	/**
	 * Core PDF Optimization using Ghostscript (Primary) or Imagick (Fallback)
	 */
	public function optimize_pdf( $file_path, $id ) {
		if ( ! file_exists( $file_path ) ) return false;

		// Check if it's actually optimized (we use a simple flag in filename? No, let's just use size)
		$orig_size = filesize( $file_path );
		$temp_file = $file_path . '.tmp.pdf';

		try {
			// Method 1: Ghostscript CLI (Most efficient)
			if ( $this->is_ghostscript_available() ) {
				// Use 'ebook' profile (150dpi) for a good balance of quality and size
				$cmd = sprintf(
					'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s',
					escapeshellarg( $temp_file ),
					escapeshellarg( $file_path )
				);
				exec( $cmd );
			} 
			// Method 2: Imagick (Requires Ghostscript delegate anyway)
			elseif ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
				$image = new Imagick();
				$image->setResolution( 150, 150 );
				$image->readImage( $file_path );
				$image->setImageFormat( 'pdf' );
				$image->writeImages( $temp_file, true );
				$image->clear();
				$image->destroy();
			}

			if ( file_exists( $temp_file ) ) {
				$new_size = filesize( $temp_file );
				if ( $new_size < $orig_size ) {
					copy( $temp_file, $file_path );
					wp_delete_file( $temp_file );
					update_post_meta( $id, '_beanst_optimized', time() );
					return true;
				}
				wp_delete_file( $temp_file );
			}
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Essential for debugging PDF optimization failures
			error_log( 'BeanST PDF Optimization Error: ' . $e->getMessage() );
		}

		return false;
	}

	public function is_ghostscript_available() {
		if ( ! function_exists( 'exec' ) ) return false;
		$out = array();
		$res = -1;
		exec( 'gs --version', $out, $res );
		return $res === 0;
	}

	/**
	 * Early intercept for HEIC files
	 */
	public function heic_prefilter( $file ) {
		$options = get_option( 'beanst_options', array() );
		$heic_opt = isset( $options['heic_convert'] ) ? $options['heic_convert'] : '1';

		if ( ! $heic_opt ) return $file;

		$ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
		if ( strtolower( $ext ) !== 'heic' ) return $file;

		// Check if server supports HEIC
		if ( ! $this->is_heic_supported() ) return $file;

		// Convert to JPEG
		$new_file = $this->convert_heic_to_jpeg( $file['tmp_name'] );
		if ( $new_file ) {
			$file['tmp_name'] = $new_file;
			$file['name'] = pathinfo( $file['name'], PATHINFO_FILENAME ) . '.jpg';
			$file['type'] = 'image/jpeg';
		}

		return $file;
	}

	public function is_heic_supported() {
		if ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick' ) ) {
			return false;
		}
		
		try {
			$formats = Imagick::queryFormats( 'HEIC' );
			return ! empty( $formats );
		} catch ( Exception $e ) {
			return false;
		}
	}

	private function convert_heic_to_jpeg( $tmp_path ) {
		try {
			$image = new Imagick( $tmp_path );
			$image->setImageFormat( 'jpeg' );
			$image->setImageCompressionQuality( 90 );
			
			$new_tmp = $tmp_path . '.jpg';
			$image->writeImage( $new_tmp );
			$image->clear();
			$image->destroy();
			
			return $new_tmp;
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Essential for debugging HEIC conversion failures
			error_log( 'BeanST HEIC Conversion Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Core conversion logic
	 */
	private function convert( $source, $format, $quality, $force = false ) {
		if ( ! file_exists( $source ) ) {
			return false;
		}

		$info = pathinfo( $source );
		$ext  = strtolower( $info['extension'] );

		// Skip if already in target format
		if ( $ext === $format ) {
			return false;
		}

		// Skip unsupported source formats
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png' ) ) ) {
			return false;
		}

		$options        = get_option( 'beanst_options', array() );
		$strip_metada   = isset( $options['strip_metadata'] ) ? $options['strip_metadata'] : '1';
		$max_width      = isset( $options['max_width'] ) ? intval( $options['max_width'] ) : 2560;

		$destination = $info['dirname'] . '/' . $info['filename'] . '.' . $format;
		
		// Don't overwrite existing optimized files manually unless forced
		if ( ! $force && file_exists( $destination ) ) {
			return true; 
		}

		try {
			if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
				$image = new Imagick( $source );
				
				// Optional Resizing
				if ( $max_width > 0 ) {
					$w = $image->getImageWidth();
					$h = $image->getImageHeight();
					if ( $w > $max_width ) {
						$new_h = ( $max_width / $w ) * $h;
						$image->scaleImage( $max_width, $new_h );
					}
				}

				$image->setImageFormat( $format );
				$image->setImageCompressionQuality( $quality );
				
				if ( $strip_metada ) {
					$image->stripImage();
				}

				$image->writeImage( $destination );
				$image->clear();
				$image->destroy();
				return true;
			} elseif ( extension_loaded( 'gd' ) ) {
				// Basic GD fallback for WebP (AVIF in GD is PHP 8.1+)
				if ( $format === 'webp' ) {
					$image = null;
					if ( $ext === 'png' ) {
						$image = imagecreatefrompng( $source );
						imagepalettetotruecolor( $image );
						imagealphablending( $image, true );
						imagesavealpha( $image, true );
					} elseif ( in_array( $ext, array( 'jpg', 'jpeg' ) ) ) {
						$image = imagecreatefromjpeg( $source );
					}

					if ( $image ) {
						// Optional Resizing for GD
						if ( $max_width > 0 ) {
							$w = imagesx( $image );
							$h = imagesy( $image );
							if ( $w > $max_width ) {
								$new_h = ( $max_width / $w ) * $h;
								$resized = imagecreatetruecolor( $max_width, $new_h );
								if ( $ext === 'png' ) {
									imagealphablending( $resized, false );
									imagesavealpha( $resized, true );
								}
								imagecopyresampled( $resized, $image, 0, 0, 0, 0, $max_width, $new_h, $w, $h );
								imagedestroy( $image );
								$image = $resized;
							}
						}

						imagewebp( $image, $destination, $quality );
						imagedestroy( $image );
						return true;
					}
				}
			}
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Essential for debugging image conversion failures
			error_log( 'BeanST Conversion Error: ' . $e->getMessage() );
			return false;
		}

		return false;
	}

	/**
	 * Resize the original image and optionally backup
	 */
	private function maybe_resize_original( $file_path ) {
		$options    = get_option( 'beanst_options', array() );
		$max_width  = isset( $options['max_width'] ) ? intval( $options['max_width'] ) : 2560;
		$backups    = isset( $options['keep_backups'] ) ? $options['keep_backups'] : '1';

		if ( $max_width <= 0 ) {
			return;
		}

		try {
			$resized = false;
			if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
				$image = new Imagick( $file_path );
				$w = $image->getImageWidth();
				$h = $image->getImageHeight();

				if ( $w > $max_width ) {
					// Backup before destructive operation
					if ( $backups ) {
						$this->backup_file( $file_path );
					}

					$new_h = ( $max_width / $w ) * $h;
					$image->scaleImage( $max_width, $new_h );
					$image->writeImage( $file_path );
					$resized = true;
				}
				$image->clear();
				$image->destroy();
			} elseif ( extension_loaded( 'gd' ) ) {
				$info = getimagesize( $file_path );
				if ( ! $info ) return;
				
				$w = $info[0];
				$h = $info[1];
				$type = $info[2];

				if ( $w > $max_width ) {
					if ( $backups ) {
						$this->backup_file( $file_path );
					}

					$image = null;
					if ( $type === IMAGETYPE_JPEG ) $image = imagecreatefromjpeg( $file_path );
					elseif ( $type === IMAGETYPE_PNG ) $image = imagecreatefrompng( $file_path );

					if ( $image ) {
						$new_h = ( $max_width / $w ) * $h;
						$new_image = imagecreatetruecolor( $max_width, $new_h );
						
						if ( $type === IMAGETYPE_PNG ) {
							imagealphablending( $new_image, false );
							imagesavealpha( $new_image, true );
						}

						imagecopyresampled( $new_image, $image, 0, 0, 0, 0, $max_width, $new_h, $w, $h );
						
						if ( $type === IMAGETYPE_JPEG ) imagejpeg( $new_image, $file_path, 90 );
						elseif ( $type === IMAGETYPE_PNG ) imagepng( $new_image, $file_path );
						
						imagedestroy( $image );
						imagedestroy( $new_image );
						$resized = true;
					}
				}
			}
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Essential for debugging backup/resize failures
			error_log( 'BeanST Backup/Resize Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Copy file to backup directory
	 */
	private function backup_file( $file_path ) {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'] . '/beanst-backups';
		
		if ( ! file_exists( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
		}

		$filename = basename( $file_path );
		$rel_path = str_replace( $upload_dir['basedir'], '', dirname( $file_path ) );
		$target_dir = $base_dir . $rel_path;

		if ( ! file_exists( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		copy( $file_path, $target_dir . '/' . $filename );
	}

	/**
	 * Generate a tiny Base64 placeholder (LQIP)
	 */
	public function generate_lqip( $file_path, $attachment_id ) {
		if ( ! file_exists( $file_path ) ) return;

		$lqip_width = 20;
		$lqip_data = '';

		try {
			if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
				$image = new Imagick( $file_path );
				$image->scaleImage( $lqip_width, 0 );
				$image->setImageFormat( 'webp' );
				$image->setImageCompressionQuality( 20 );
				$image->blurImage( 5, 2 );
				$lqip_data = 'data:image/webp;base64,' . base64_encode( $image->getImageBlob() );
				$image->clear();
				$image->destroy();
			} elseif ( extension_loaded( 'gd' ) ) {
				$info = getimagesize( $file_path );
				if ( ! $info ) return;
				
				$src = null;
				if ( $info[2] === IMAGETYPE_JPEG ) $src = imagecreatefromjpeg( $file_path );
				elseif ( $info[2] === IMAGETYPE_PNG ) $src = imagecreatefrompng( $file_path );

				if ( $src ) {
					$w = imagesx( $src );
					$h = imagesy( $src );
					$new_h = ( $lqip_width / $w ) * $h;
					$tmp = imagecreatetruecolor( $lqip_width, $new_h );
					
					imagecopyresampled( $tmp, $src, 0, 0, 0, 0, $lqip_width, $new_h, $w, $h );
					
					ob_start();
					imagewebp( $tmp, null, 20 );
					$data = ob_get_clean();
					$lqip_data = 'data:image/webp;base64,' . base64_encode( $data );
					
					imagedestroy( $src );
					imagedestroy( $tmp );
				}
			}

			if ( $lqip_data ) {
				update_post_meta( $attachment_id, '_beanst_lqip', $lqip_data );
			}
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Essential for debugging LQIP generation failures
			error_log( 'BeanST LQIP Error: ' . $e->getMessage() );
		}
	}
}