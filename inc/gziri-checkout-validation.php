<?php

/**
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

function gziri_validate_order($posted) {
	
	$gziri_settings_info = new Gziri_Settings_Info;
	$gziri_account_preconditions = $gziri_settings_info->check_account_preconditions();
	$gziri_sender_preconditions = $gziri_settings_info->check_sender_preconditions();
	if ($gziri_settings_info->gziri_auto_order == 1) {
		
		$packages = WC()->shipping->get_packages();
		$chosen_methods = wc_get_chosen_shipping_method_ids();
		
		if (is_array($chosen_methods) && (in_array('gziri_standard', $chosen_methods) || in_array('gziri_fast', $chosen_methods) || in_array('gziri_express', $chosen_methods))) {
			
			foreach ($packages as $i => $package) {				
				
				if ($chosen_methods[$i] != 'gziri_standard' && $chosen_methods[$i] != 'gziri_fast' && $chosen_methods[$i] != 'gziri_express') {
					continue;				
				} // Continue if not Gziri shipping method
				
				$gziri_method = $chosen_methods[$i];
				if ($gziri_account_preconditions === true && $gziri_sender_preconditions === true) {
					
					if ($gziri_method == 'gziri_standard') {
						$destination_city_check = (!empty($package['destination']['city'])) ? true : false;
					} else {
						$destination_city_check = (in_array(strtolower($package['destination']['city']), GZIRI_REQUIRED_CITY)) ? true : false;
					}
					
					if ($package['destination']['country'] == GZIRI_REQUIRED_COUNTRY && $destination_city_check === true && !empty($package['destination']['address_1'])) {
						
						if (!empty($posted['billing_first_name']) && !empty($posted['billing_last_name'])) {
							if (strlen(preg_replace('/[^0-9]/', '', $posted['billing_phone'])) == 9 && substr(preg_replace('/[^0-9]/', '', $posted['billing_phone']), 0, 1) == 5) {
								
								// Continue to checkout
								
							} else {
								wc_add_notice(__('<strong>Error</strong>: Invalid phone number format. It should be Georgian mobile phone number (i.e. 599112233)', 'gziri'), 'error');
							}				
						} else {
							wc_add_notice(__('<strong>Error</strong>: First name and Last name are required', 'gziri'), 'error');
						}				
						
					} else {
						wc_add_notice(__('<strong>Error</strong>: Invalid Country or City', 'gziri'), 'error');
					}
					
				} else {
					wc_add_notice(__('<strong>Error</strong>: Gziri Shipping in unavailable', 'gziri'), 'error');
					
					$log = '[' . date('Y-m-d H:i:s') . ']: Preconditions are not set' . PHP_EOL;
					file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
				}
				
				
			}
			
		}	
		
	}
}
add_action('woocommerce_after_checkout_validation', 'gziri_validate_order', 10);