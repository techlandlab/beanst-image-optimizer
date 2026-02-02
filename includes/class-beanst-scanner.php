<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BeanST_Scanner {

	/**
	 * Scan directories for optimizable images
	 * 
	 * @return array List of found file paths relative to ABSPATH
	 */
	public function scan_directories() {
		$options = get_option( 'beanst_options', array() );
		$paths_string = isset( $options['scan_paths'] ) ? $options['scan_paths'] : '';
		
		$scan_dirs = array();
		if ( empty( $paths_string ) ) {
			// Default to active theme directory
			$scan_dirs[] = get_stylesheet_directory();
		} else {
			$lines = explode( "\n", str_replace( "\r", "", $paths_string ) );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( empty( $line ) ) continue;
				
				// Handle relative paths from ABSPATH
				if ( strpos( $line, ABSPATH ) === false ) {
					$full_path = ABSPATH . ltrim( $line, '/\\' );
				} else {
					$full_path = $line;
				}
				
				if ( is_dir( $full_path ) ) {
					$scan_dirs[] = $full_path;
				}
			}
		}

		$found_files = array();
		$extensions = array( 'jpg', 'jpeg', 'png', 'pdf' );

		foreach ( $scan_dirs as $dir ) {
			$this->recursive_scan( $dir, $extensions, $found_files );
		}

		// Update registry
		$this->update_registry( $found_files );

		return $found_files;
	}

	/**
	 * Recursively find files with specific extensions
	 */
	private function recursive_scan( $dir, $extensions, &$found_files ) {
		$items = @scandir( $dir );
		if ( ! $items ) return;

		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) continue;

			$full_path = $dir . DIRECTORY_SEPARATOR . $item;
			
			if ( is_dir( $full_path ) ) {
				// Don't scan backups or hidden folders
				if ( strpos( $item, 'beanst-backups' ) !== false || $item[0] === '.' ) continue;
				$this->recursive_scan( $full_path, $extensions, $found_files );
			} else {
				$ext = strtolower( pathinfo( $full_path, PATHINFO_EXTENSION ) );
				if ( in_array( $ext, $extensions ) ) {
					// Store relative path to ABSPATH for portability
					$found_files[] = str_replace( ABSPATH, '', $full_path );
				}
			}
		}
	}

	/**
	 * Update the external files registry
	 */
	private function update_registry( $found_files ) {
		$registry = get_option( 'beanst_external_registry', array() );
		$new_registry = array();

		foreach ( $found_files as $rel_path ) {
			if ( isset( $registry[ $rel_path ] ) ) {
				$new_registry[ $rel_path ] = $registry[ $rel_path ];
			} else {
				$full_path = ABSPATH . $rel_path;
				$new_registry[ $rel_path ] = array(
					'optimized' => false,
					'orig_size' => @filesize( $full_path ),
					'savings'   => 0
				);
			}
		}

		update_option( 'beanst_external_registry', $new_registry );
	}

	/**
	 * Find orphaned optimized files
	 */
	public function find_orphaned_files() {
		$upload_dir = wp_upload_dir();
		$path = $upload_dir['basedir'];
		$orphans = array();
		
		$this->scan_for_orphans( $path, $orphans );
		
		return $orphans;
	}

	private function scan_for_orphans( $dir, &$orphans ) {
		$items = @scandir( $dir );
		if ( ! $items ) return;

		$images_in_dir = array();
		$opt_files = array();

		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) continue;
			$full_path = $dir . DIRECTORY_SEPARATOR . $item;

			if ( is_dir( $full_path ) ) {
				if ( strpos( $item, 'beanst-backups' ) !== false ) continue;
				$this->scan_for_orphans( $full_path, $orphans );
			} else {
				$ext = strtolower( pathinfo( $item, PATHINFO_EXTENSION ) );
				$name = pathinfo( $item, PATHINFO_FILENAME );

				if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif' ) ) ) {
					$images_in_dir[] = $name;
				} elseif ( in_array( $ext, array( 'webp', 'avif' ) ) ) {
					$opt_files[] = array( 'name' => $name, 'path' => $full_path );
				} elseif ( $ext === 'pdf' && strpos( $item, '.tmp.pdf' ) !== false ) {
					// Abandoned PDF temp files
					$orphans[] = $full_path;
				}
			}
		}

		// Cross-reference
		foreach ( $opt_files as $opt ) {
			if ( ! in_array( $opt['name'], $images_in_dir ) ) {
				$orphans[] = $opt['path'];
			}
		}
	}

	/**
	 * Sync registry by removing non-existent files
	 */
	public function sync_registry() {
		$registry = get_option( 'beanst_external_registry', array() );
		$changed = false;

		foreach ( $registry as $rel_path => $data ) {
			if ( ! file_exists( ABSPATH . $rel_path ) ) {
				unset( $registry[ $rel_path ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( 'beanst_external_registry', $registry );
		}
	}

	/**
	 * Physically delete a list of files
	 */
	public function delete_orphans( $files ) {
		$count = 0;
		$freed = 0;

		foreach ( $files as $file ) {
			if ( file_exists( $file ) && is_file( $file ) ) {
				$freed += filesize( $file );
				@unlink( $file );
				$count++;
			}
		}

		return array( 'count' => $count, 'freed' => $freed );
	}

	/**
	 * Get total savings from external files
	 */
	public static function get_external_stats() {
		$registry = get_option( 'beanst_external_registry', array() );
		$total_savings = 0;
		$optimized_count = 0;

		foreach ( $registry as $data ) {
			if ( ! empty( $data['optimized'] ) ) {
				$optimized_count++;
				$total_savings += ( isset( $data['savings'] ) ? $data['savings'] : 0 );
			}
		}

		return array(
			'count' => count( $registry ),
			'optimized' => $optimized_count,
			'savings' => $total_savings
		);
	}
}
