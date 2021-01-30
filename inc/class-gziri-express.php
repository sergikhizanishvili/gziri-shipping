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

function gziri_express_init() {
	if (!class_exists('WC_Gziri_Express')) {
		class WC_Gziri_Express extends WC_Shipping_Method {
			
			/**
			 * Constructor Gziri Standard shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct($instance_id = 0) {
				$this->id = 'gziri_express';
				$this->instance_id  = absint($instance_id);
				$this->method_title = __('GZIRI Express', 'gziri');				
				$this->method_description = __('GZIRI Express package shipping method. Estimated delivery in Tbilisi is same business day if ordered before 17:00 (Within 2 hours). This method is not available in regions.', 'gziri');
				$this->supports = array(
					'shipping-zones',
					'instance-settings',
                    'instance-settings-modal'
				);
				
				$this->enabled = 'yes';				
				$this->init();				
				$this->title = $this->get_option('title');
				$this->tax_status = $this->get_option('tax_status');
			}
			
			/**
			 * Init Gziri Standard shipping settings
			 *
			 * @access public
			 * @return void
			 */
			function init() {
				$this->init_form_fields();
				$this->init_settings();

				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}
			
			public function init_form_fields() {
                $this->instance_form_fields = array(
                    'title' => array(
                        'title'       => __('Title', 'gziri'),
                        'type'        => 'text',
                        'description' => __('Title to be display on site', 'gziri'),
                        'default'     => __('GZIRI Express', 'gziri'),
                        'desc_tip'    => true,
                    ),
					'tax_status' => array(
                        'title'       => __('Tax Status', 'gziri'),
                        'type'        => 'select',
                        'default'     => 'taxable',
						'options' => array(
							'taxable' => 'Taxable',
							'none' => 'None',
						)
                    )
                );
            }
			
			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 * @param mixed $package
			 * @return void
			 */
			public function calculate_shipping($package = array()) {
				
				$weight = 0;
				$cost = 0;
				$country = $package['destination']['country'];
				$city = $package['destination']['city'];
				
				foreach ($package['contents'] as $item_id => $values) { 
					$_product = $values['data']; 
					if (!filter_var($_product->get_weight(), FILTER_VALIDATE_FLOAT)) {
						$wh = 0;
					} else {
						$wh = $_product->get_weight();
					}
					
					$weight = $weight + $wh * $values['quantity'];
				}
				$weight = wc_get_weight($weight, 'kg');
				
				if ($country == GZIRI_REQUIRED_COUNTRY && in_array(strtolower($city), GZIRI_REQUIRED_CITY)) {
					
					$gziri_settings_info = new Gziri_Settings_Info;
					$token = $gziri_settings_info->get_token();
					
					if (!empty($token['response'])) {
						
						$body = 
						'{
							"token": "' . $token['response'] . '",
							"city": "' . $city . '",
							"weight": "' . $weight . '",
							"package": "' . 'express' . '"
						}';
						
						$rate = gziri_response(GZIRI_RATE_GTW, $body);												
						if (!empty($rate['response'])) {
							
							$rate = array(
								'id' => $this->id,
								'label' => $this->title,
								'cost' => $rate['response'],
								'calc_tax' => 'per_order'
							);
							$this->add_rate($rate);							
							
						} else {
							$log = '[' . date('Y-m-d H:i:s') . ']: Could not get rates from API' . PHP_EOL;
							file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
						}
						
					} else {
						$log = '[' . date('Y-m-d H:i:s') . ']: Could not get token from API' . PHP_EOL;
						file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
					}			
				} else {
					$log = '[' . date('Y-m-d H:i:s') . ']: Country is not Georgia or city is not Tbilisi' . PHP_EOL;
					file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
				}
				
			}
			
		}
	}
}
add_action('woocommerce_shipping_init', 'gziri_express_init');

function add_gziri_express($methods) {
	$methods['gziri_express'] = 'WC_Gziri_Express';
	return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_gziri_express');