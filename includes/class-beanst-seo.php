<?php
if (!defined('ABSPATH')) exit;

class BeanST_SEO {

    /**
     * Get SEO suggestions for an attachment
     */
    public static function get_suggestions($attachment_id) {
        $file = get_attached_file($attachment_id);
        if (!$file) return false;

        $info = pathinfo($file);
        $current_name = $info['filename'];
        $current_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

        // Logic for proposed name: Take attachment title, or parent post title
        $attachment = get_post($attachment_id);
        $parent_id = $attachment->post_parent;
        $title = $attachment->post_title;

        if (empty($title) || is_numeric($title) || stripos($title, 'IMG_') !== false) {
            if ($parent_id) {
                $title = get_the_title($parent_id);
            }
        }

        $proposed_name = sanitize_title($title);
        if (empty($proposed_name)) {
            $proposed_name = $current_name; // Fallback
        }

        // Logic for proposed Alt: Use title if alt is empty
        $proposed_alt = empty($current_alt) ? $title : $current_alt;

        return array(
            'current_name'  => $current_name,
            'proposed_name' => $proposed_name,
            'current_alt'   => $current_alt,
            'proposed_alt'  => $proposed_alt,
            'needs_rename'  => ($current_name !== $proposed_name),
            'needs_alt'     => (empty($current_alt) && !empty($proposed_alt))
        );
    }

    /**
     * Apply SEO changes (destructive)
     */
    public function apply_changes($attachment_id, $new_name = '', $new_alt = '') {
        $suggestions = self::get_suggestions($attachment_id);
        if (!$suggestions) return false;

        $name_to_apply = !empty($new_name) ? sanitize_title($new_name) : $suggestions['proposed_name'];
        $alt_to_apply  = !empty($new_alt) ? sanitize_text_field($new_alt) : $suggestions['proposed_alt'];

        // 1. Update Alt Text
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_to_apply);

        // 2. Rename Physical Files
        if ($suggestions['needs_rename']) {
            return $this->rename_attachment_files($attachment_id, $name_to_apply);
        }
        
        return true;
    }

    	/**
	 * Rename attachment files and update database
	 */
	private function rename_attachment_files($attachment_id, $new_filename) {
		global $wp_filesystem;
		
		// Initialize WP_Filesystem
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		$old_path = get_attached_file($attachment_id);
		if (!$old_path || !file_exists($old_path)) return false;

		$old_info = pathinfo($old_path);
		$dirname  = $old_info['dirname'];
		$ext      = $old_info['extension'];
		$new_path = $dirname . '/' . $new_filename . '.' . $ext;

		if (file_exists($new_path)) {
			$new_filename .= '-' . time();
			$new_path = $dirname . '/' . $new_filename . '.' . $ext;
		}

		// Rename Main File using WP_Filesystem
		if ( ! $wp_filesystem->move( $old_path, $new_path, true ) ) {
			return false;
		}
		update_attached_file($attachment_id, $new_path);

		// Rename Thumbnails
		$metadata = wp_get_attachment_metadata($attachment_id);
		if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
			foreach ($metadata['sizes'] as $size => &$size_info) {
				$old_size_path = $dirname . '/' . $size_info['file'];
				if (file_exists($old_size_path)) {
					// Try to keep dimensions in name
					$size_ext = pathinfo($size_info['file'], PATHINFO_EXTENSION);
					$new_size_file = $new_filename . '-' . $size_info['width'] . 'x' . $size_info['height'] . '.' . $size_ext;
					$new_size_path = $dirname . '/' . $new_size_file;
					
					if ( $wp_filesystem->move( $old_size_path, $new_size_path, true ) ) {
						$size_info['file'] = $new_size_file;
					}
				}
			}
			wp_update_attachment_metadata($attachment_id, $metadata);
		}

		// Update Post Title (Optional but good for SEO)
		wp_update_post(array(
			'ID'         => $attachment_id,
			'post_title' => str_replace('-', ' ', $new_filename)
		));

		return true;
	}    

    /**
     * Get aggregate SEO health stats for the entire media library
     */
    public static function get_seo_audit_stats() {
        $query = new WP_Query(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        $bad_names = 0;
        $missing_alt = 0;
        $total = $query->found_posts;

        if ($total > 0) {
            foreach ($query->posts as $id) {
                // Check Alt
                $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
                if (empty($alt)) {
                    $missing_alt++;
                }

                // Check Filename (generic patterns)
                $file = get_attached_file($id);
                if ($file) {
                    $filename = pathinfo($file, PATHINFO_FILENAME);
                    if (preg_match('/^(DSC|IMG|PANO|screenshot|image|wp-|background)/i', $filename) || is_numeric($filename)) {
                        $bad_names++;
                    }
                }
            }
        }

        $score = 100;
        if ($total > 0) {
            $deduction = (($bad_names + $missing_alt) / ($total * 2)) * 100;
            $score = max(0, round(100 - $deduction));
        }

        return array(
            'total'       => $total,
            'bad_names'   => $bad_names,
            'missing_alt' => $missing_alt,
            'score'       => $score
        );
    }
}
