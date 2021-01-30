<?php

/**
 * Plugin Name: GZIRI - Shipping Method for WooCommerce
 * Plugin URI:  https://gziri.ge/
 * Description: Integrate GZIRI API on your site and show users shipping live rates and submit shipping orders automatically
 * Version:     1.0.2
 * Author:      Sergi Khizanishvili
 * Author URI:  https://sweb.ge/
 * License:     https://gziri.ge/eula
 * Domain Path: /lang
 * Text Domain: gziri
 * WC requires at least: 3.0.0
 * WC tested up to: 4.9.2
 *
 * Intellectual Property rights, and copyright, reserved by Gziri, Ltd. as allowed by law include,
 * but are not limited to, the working concept, function, and behavior of this software,
 * the logical code structure and expression as written.
 *
 * @package     GZIRI - Shipping Method for WooCommerce
 * @author      Sergi Khizanishvili. https://sweb.ge/
 * @copyright   Copyright (c) Gziri Ltd. (info@gziri.ge)
 * @since       1.0.0
 * @license     https://gziri.ge/eula
 */

if (!defined('ABSPATH')) {
	exit;
}

require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://gziri.ge/wp-update-server/?action=get_metadata&slug=gziri-shipping',
	plugin_dir_path( __FILE__ ) . 'gziri-shipping.php', //Full path to the main plugin file or functions.php.
	'gziri_shipping'
);

define('GZIRI_SHIPPING_VERSION', '1.0.1' );
define('GZIRI_REQUIRED_CITY', array('tbilisi', 'თბილისი'));
define('GZIRI_REQUIRED_COUNTRY', 'GE');

define('GZIRI_TOKEN_GTW', 'https://gziri.ge/api/v1/gettoken');
define('GZIRI_RATE_GTW', 'https://gziri.ge/api/v1/calculateorder');
define('GZIRI_ORDER_GTW', 'https://gziri.ge/api/v1/placeorder');
define('GZIRI_STATUS_GTW', 'https://gziri.ge/api/v1/getorderstatus');

require plugin_dir_path( __FILE__ ) . 'admin/gziri-settings.php';
require plugin_dir_path( __FILE__ ) . 'inc/class-settings-info.php';
require plugin_dir_path( __FILE__ ) . 'inc/gziri-gateway.php';
require plugin_dir_path( __FILE__ ) . 'inc/class-gziri-standard.php';
require plugin_dir_path( __FILE__ ) . 'inc/class-gziri-fast.php';
require plugin_dir_path( __FILE__ ) . 'inc/class-gziri-express.php';
require plugin_dir_path( __FILE__ ) . 'inc/gziri-checkout-validation.php';
require plugin_dir_path( __FILE__ ) . 'inc/gziri-order.php';

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	
	$gziri_settings_info = new Gziri_Settings_Info;
	
	if ($gziri_settings_info->check_account_preconditions() !== true) {
		add_action('admin_notices', 'gziri_no_api_connection');
	}
	
	if ($gziri_settings_info->check_sender_preconditions() !== true) {
		add_action('admin_notices', 'gziri_no_store_address');
	}

} else {
	add_action('admin_notices', 'gziri_no_woocommerce');
}