<?php
if (!defined('ABSPATH')) exit;

class BeanST_Lazy_Load {
    public function __construct() {
        $options = get_option('beanst_options', array());
        $lazy_load = isset($options['lazy_load']) ? $options['lazy_load'] : '1';

        if ($lazy_load && !is_admin()) {
            add_filter('the_content', array($this, 'add_lazy_load_attributes'), 110);
            add_filter('post_thumbnail_html', array($this, 'add_lazy_load_attributes'), 110);
        }
    }

    public function add_lazy_load_attributes($content) {
        if (empty($content)) return $content;

        // Use native WP function for optimization attributes if available (WP 6.3+)
        $use_native = function_exists('wp_get_loading_optimization_attributes');

        return preg_replace_callback('/<img\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i', function($matches) use ($use_native) {
            $attr_before = $matches[1];
            $src = $matches[2];
            $attr_after = $matches[3];
            $tag = $matches[0];

            if ($use_native) {
                // Get optimization attributes from core
                $attrs = wp_get_loading_optimization_attributes('img', array('src' => $src), 'the_content');
                if (!empty($attrs['loading']) && stripos($tag, 'loading=') === false) {
                    return str_replace('<img ', '<img loading="' . esc_attr($attrs['loading']) . '" ', $tag);
                }
            } else {
                // Fallback for older versions
                if (stripos($attr_before, 'loading=') === false && stripos($attr_after, 'loading=') === false) {
                    return sprintf('<img %sloading="lazy" src="%s"%s>', $attr_before, $src, $attr_after);
                }
            }
            return $tag;
        }, $content);
    }
}
