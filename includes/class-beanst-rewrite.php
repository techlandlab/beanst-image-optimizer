<?php
if (!defined('ABSPATH')) exit;

class BeanST_Rewrite {
    
    public function __construct() {
        if ( ! is_admin() ) {
            add_filter( 'the_content', array( $this, 'replace_images' ), 100 );
            add_filter( 'post_thumbnail_html', array( $this, 'replace_images' ), 100 );
        }
    }
    
    /**
     * Replace <img> with <picture> tags
     */
    public function replace_images( $content ) {
        if ( empty( $content ) ) {
            return $content;
        }

        // Avoid matching images already inside <picture> tags
        // We use a regex that looks for <img> tags NOT preceded by <picture
        // However, a simple way is to check if we are already inside one.
        // For simplicity in a regex callback, we'll just check if the match is part of a picture.
        
        $pattern = '/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i';
        
        return preg_replace_callback( $pattern, array( $this, 'callback' ), $content );
    }

    private function callback( $matches ) {
        $img_tag = $matches[0];
        $url     = $matches[1];

        // Skip if already inside a picture tag (very basic check)
        // In a real scenario, we might want a more advanced DOM parser.
        
        // Basic check: is it a local image from uploads?
        $upload_dir = wp_upload_dir();
        if ( strpos( $url, $upload_dir['baseurl'] ) === false ) {
            return $img_tag;
        }

        // Get relative path
        $rel_path = str_replace( $upload_dir['baseurl'], '', $url );
        // Remove query strings if any (e.g. ?v=1.2)
        $rel_path = explode('?', $rel_path)[0];
        $abs_path = $upload_dir['basedir'] . $rel_path;
        
        if ( ! file_exists( $abs_path ) ) {
            return $img_tag;
        }

        $info = pathinfo( $abs_path );
        $dir  = $info['dirname'];
        $name = $info['filename'];
        $orig_ext = $info['extension'] ?? '';

        if ( empty( $orig_ext ) ) {
            return $img_tag;
        }

        $sources = '';
        
        // Define formats to check
        $formats = array(
            'avif' => 'image/avif',
            'webp' => 'image/webp',
        );

        foreach ( $formats as $ext => $type ) {
            $ext_path = $dir . '/' . $name . '.' . $ext;
            if ( file_exists( $ext_path ) ) {
                // Only replace extension at the end of the URL (before optional query string)
                $ext_url = preg_replace( '/\.' . preg_quote( $orig_ext, '/' ) . '(\?.*)?$/i', '.' . $ext . '$1', $url );
                
                // Add srcset for current format. 
                // In WP 6.5+, we might want to also handle source-specific sizes, but for now we keep it simple.
                $sources .= sprintf( '<source srcset="%s" type="%s">', esc_url( $ext_url ), $type );
            }
        }

        if ( ! empty( $sources ) ) {
            // Ensure we don't double wrap if the user already has a picture tag
            // This is harder with regex, but we can look for "picture" in the surrounding 50 chars.
            // But for now, let's just return the picture tag.
            return '<picture>' . $sources . $img_tag . '</picture>';
        }

        return $img_tag;
    }
}
