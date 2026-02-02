<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BeanST_Frontend {

	public function __construct() {
		$options = get_option( 'beanst_options', array() );
		$lqip_enabled = isset( $options['lqip_blur'] ) ? $options['lqip_blur'] : '0';

		if ( $lqip_enabled ) {
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_lqip_attributes' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		}
	}

	/**
	 * Inject LQIP Base64 as a background image and add transition class
	 */
	public function add_lqip_attributes( $attr, $attachment ) {
		$lqip = get_post_meta( $attachment->ID, '_beanst_lqip', true );

		if ( $lqip ) {
			// Add a custom class for the transition
			$attr['class'] .= ' beanst-lqip';
			
			// Inject the placeholder as an inline background-image
			// We use background-size: cover to ensure the blur covers the area
			$style = isset( $attr['style'] ) ? $attr['style'] : '';
			$attr['style'] = $style . ' background-image: url(' . $lqip . '); background-size: cover; filter: blur(10px); transition: filter 0.6s ease-in-out;';
			
			// Add an onload handler to remove the blur once the full image is ready
			$attr['onload'] = "this.style.filter='none';";
		}

		return $attr;
	}

	/**
	 * Enqueue minimal frontend CSS for the transition
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'beanst-frontend', plugins_url( 'admin/css/frontend.css', BEANST_FILE ), array(), BEANST_VERSION );
	}
}
