<?php
if (!defined('ABSPATH')) exit;

class BeanST_Stats {

    /**
     * Get overall optimization statistics
     */
    public static function get_overall_stats() {
        global $wpdb;

        $query = new WP_Query(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => array('image', 'application/pdf'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        $total_count = $query->found_posts;
        $optimized_count = 0;
        $total_savings = 0;

        foreach ($query->posts as $id) {
            $stats = self::get_attachment_stats($id);
            if ($stats['is_optimized']) {
                $optimized_count++;
                $total_savings += $stats['savings_bytes'];
            }
        }

        $external_stats = BeanST_Scanner::get_external_stats();
        $final_savings = $total_savings + $external_stats['savings'];

        return array(
            'total'         => $total_count + $external_stats['count'],
            'optimized'     => $optimized_count + $external_stats['optimized'],
            'savings_bytes' => $final_savings,
            'savings_human' => size_format($final_savings, 2)
        );
    }

    /**
     * Get stats for a single attachment
     */
    public static function get_attachment_stats($attachment_id) {
        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            return array('is_optimized' => false, 'savings_bytes' => 0);
        }

        $is_optimized = get_post_meta($attachment_id, '_beanst_optimized', true) ? true : false;
        $orig_size = filesize($file);
        $optimized_size = $orig_size;
        $savings = 0;

        if ($is_optimized) {
            // For images, the WebP/AVIF might be smaller. For PDFs, the original was replaced.
            $info = pathinfo($file);
            $dir = $info['dirname'];
            $name = $info['filename'];

            $formats = array('webp', 'avif');
            $best_opt_size = $orig_size;

            foreach ($formats as $ext) {
                $opt_file = $dir . '/' . $name . '.' . $ext;
                if (file_exists($opt_file)) {
                    $opt_size = filesize($opt_file);
                    if ($opt_size < $best_opt_size) {
                        $best_opt_size = $opt_size;
                    }
                }
            }
            
            $optimized_size = $best_opt_size;
            $savings = ($orig_size > $optimized_size) ? ($orig_size - $optimized_size) : 0;
            
            // Special case for PDFs (where the original is actually the optimized one now)
            // Note: Currently we don't store original size for PDFs before optimization. 
            // In V3 we could add '_beanst_original_size' meta.
        }

        return array(
            'is_optimized' => $is_optimized,
            'orig_size' => $orig_size,
            'opt_size' => $optimized_size,
            'savings_bytes' => $savings,
            'percent' => $orig_size > 0 ? round(($savings / $orig_size) * 100, 1) : 0
        );
    }
}
