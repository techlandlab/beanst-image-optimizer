<?php
/**
 * Plugin Name: BeanST Image Optimizer
 * Description: Local AVIF & WebP converter for WordPress. Zero limits, local processing.
 * Version:           1.1.2
 * Author:            TechLandLab
 * Author URI:        https://techlandlab.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       beanst-image-optimizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Version
if ( ! defined( 'BEANST_VERSION' ) ) {
	define( 'BEANST_VERSION', '1.1.2' );
}

// 1. Define Plugin Constants
// The BEANST_VERSION constant is now defined conditionally above.
define( 'BEANST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEANST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BEANST_FILE', __FILE__ );

// Autoloader or Includes
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-converter.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-admin.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-bulk.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-rewrite.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-lazy-load.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-stats.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-scanner.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-seo.php';
require_once BEANST_PLUGIN_DIR . 'includes/class-beanst-frontend.php';

/**
 * Main Plugin Class
 */
class BeanST_Image_Optimizer {

	private static $instance = null;
	public $converter;
	public $admin;
	public $bulk;
	public $rewrite;
	public $lazy_load;
	public $frontend;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_classes();
	}


	private function init_classes() {
		$this->converter = new BeanST_Converter();
		$this->admin     = new BeanST_Admin();
		$this->bulk      = new BeanST_Bulk();
		$this->rewrite   = new BeanST_Rewrite();
		$this->lazy_load = new BeanST_Lazy_Load();
		$this->frontend  = new BeanST_Frontend();
	}
}

// Initialize the plugin
function beanst_image_optimizer_init() {
	return BeanST_Image_Optimizer::get_instance();
}
add_action( 'plugins_loaded', 'beanst_image_optimizer_init' );
