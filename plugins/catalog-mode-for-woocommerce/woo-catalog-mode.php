<?php
/*
Plugin Name: Catalog Mode For WooCommerce
Plugin URI: https://zetamatic.com
Description: The plugin allows store owners to hide add to cart button. Product prices, cart and checkout. Which makes your store as a catalog mode.
Version: 0.3
Author: zetamatic
Author URI: https://zetamatic.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

define('WCPCM', __FILE__);
define('WCPCM_PATH', plugin_dir_path(__FILE__));
define('WCPCM_BASE', plugin_basename(__FILE__));
define('WCPCM_OPTIONS_FRAMEWORK_DIRECTORY', plugins_url( '/inc/', __FILE__ ));
define('WCPCM_OPTIONS_FRAMEWORK_PATH', dirname( __FILE__ ) . '/inc/');
define('WCPCM_Plugin_Name', 'Catalog Mode For WooCommerce');

/**
 * Plugin Localization
 */
add_action('plugins_loaded', 'wc_catalog_mode_text_domain');

function wc_catalog_mode_text_domain() {
	load_plugin_textdomain('catalog_mode_for_woocommerce', false, basename( dirname( __FILE__ ) ) . '/lang' );
}

require_once dirname( __FILE__ ) . '/inc/options-framework.php';

require_once dirname( __FILE__ ) . '/inc/woocommerce-catalog-mode.php';

new WooCommerce_Catalog_Mode();
