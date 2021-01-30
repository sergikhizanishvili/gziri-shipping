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

function gziri_place_order($order_id) {
	
	if (!$order_id) {
		return;
	}
	
	$order = wc_get_order($order_id);
	
	if(!get_post_meta($order_id, '_thankyou_action_done', true)) {
	
		$unsuccessful_gziri_order = __('Unsuccessful Gziri Order', 'gziri');
		$problem_placing_gziri_order = __('There was a problem while placing order via GZIRI API on order # ', 'gziri');

		$gziri_settings_info = new Gziri_Settings_Info;

		/* Check if auto order is turned of from settings */
		if ($gziri_settings_info->gziri_auto_order == 1) {
			
			$shipping_methods = $order->get_items('shipping');

			foreach($order->get_items('shipping') as $item_id => $item) {

				$shipping_method = $item->get_method_id();
				if ($shipping_method != 'gziri_standard' && $shipping_method != 'gziri_fast' && $shipping_method != 'gziri_express') {
					continue;
				}

				/** 
				 * Placing order via Gziri API is possible if there is only one shipping package selected
				 * Otherwise order should be placed manualy via https://gziri.ge/
				 */
				if (count($shipping_methods) == 1) {

					/** 
					 * Check sender address preconditions and get token from Gziri API
					 */
					$token = $gziri_settings_info->get_token();
					if ($gziri_settings_info->check_sender_preconditions() === true && !empty($token['response'])) {

						/** 
						 * Check receiver address
						 */					
						if ($shipping_method == 'gziri_standard') {
							$destination_city_check = (!empty($order->get_shipping_city())) ? true : false;
						} else {
							$destination_city_check = (in_array(strtolower($order->get_shipping_city()), GZIRI_REQUIRED_CITY)) ? true : false;
						}

						if ($order->get_shipping_country() == GZIRI_REQUIRED_COUNTRY && $destination_city_check === true && !empty($order->get_shipping_address_1())) {

							/** 
							 * Check receiver name and phone
							 */
							if (!empty($order->get_billing_first_name()) && !empty($order->get_billing_last_name())) {

								/** 
								 * Check phone number format
								 */
								if (strlen(preg_replace('/[^0-9]/', '', $order->get_billing_phone())) == 9 && substr(preg_replace('/[^0-9]/', '', $order->get_billing_phone()), 0, 1) == 5) {

									$billing_company = (!empty($order->get_billing_company())) ? ' (' . $order->get_billing_company() . ')' : '';

									$receiver = array('receiver' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . $billing_company, 'receiverPhone' => $order->get_billing_phone());

									$product_weights = array();
									$product_titles = array();
									foreach ($order->get_items() as $item_id => $item) {									
										$_product = $item->get_product();
										$qty = $item->get_quantity();
										if (!$_product->is_virtual()) {
											if (!filter_var($_product->get_weight(), FILTER_VALIDATE_FLOAT)) {
												$wh = 0;
											} else {
												$wh = $_product->get_weight();
											}
										} else {
											$wh = 0;
										}
										$weight = '"' . $wh * $qty . '"';
										$title = '"' . $_product->get_title() . '"';

										array_push($product_weights, $weight);
										array_push($product_titles, $title);
									}

									$titles = implode(',', $product_titles);
									$weights = implode(',', $product_weights);

									$order_items = '
										"1": [
											' . $titles . '
										],
										"2": [
											' . $weights . '
										]
									';

									if ($order->get_payment_method() == 'cod') {
										$order_client_pays = 1;
										$take_payment = 1;
										$take_payment_amount = $order->get_total() - $order->get_shipping_total();
									} else {
										$order_client_pays = 0;
										$take_payment = 0;
										$take_payment_amount = 0;
									}
									
									$order_receiver_building_number = (empty($order->get_shipping_address_2())) ? 'N/A' : $order->get_shipping_address_2();

									$body = '
									{
										"token": "' . $token['response'] . '",
										"order": {
											' . $gziri_settings_info->get_sender_body() . '
											"receiver_name": "' . $receiver['receiver'] . '",
											"receiver_phone": "' . $receiver['receiverPhone'] . '",
											"order_receiver_city": "' . $order->get_shipping_city() . '",
											"order_receiver_street": "' . $order->get_shipping_address_1() . '",
											"order_receiver_building_number": "' . $order_receiver_building_number . '",
											"order_receiver_address_additional": "' . $order->get_shipping_state() . ' ' . $order->get_shipping_postcode() . '",
											"order_items": {
												' . $order_items . '
											},
											"order_package": "' . str_replace('gziri_', '', $shipping_method) . '",
											"order_client_pays": "' . $order_client_pays . '",
											"take_payment": "' . $take_payment . '",
											"take_payment_amount": "' . $take_payment_amount . '",
											"order_additiona_info": "' . $order->customer_message . '"
										}
									}
									';
									
									
									$auto_order = gziri_response(GZIRI_ORDER_GTW, $body);
									if (!empty($auto_order['response'])) {
										
										$order->update_meta_data('gziri_tracking_id', $auto_order['response']);
  										$order->save();
										
									} else {
										wp_mail(bloginfo('admin_email'), $unsuccessful_gziri_order, $problem_placing_gziri_order . $order_id . ': ' . __('Unexpected error! Check logs', 'gziri'), array('Content-Type: text/html; charset=UTF-8'));

										$log = '[' . date('Y-m-d H:i:s') . ']: ' . $auto_order['error'] . PHP_EOL;
										file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
									}


								} else {
									wp_mail(bloginfo('admin_email'), $unsuccessful_gziri_order, $problem_placing_gziri_order . $order_id . ': ' . __('Invalid phone number format', 'gziri'), array('Content-Type: text/html; charset=UTF-8'));

									$log = '[' . date('Y-m-d H:i:s') . ']: Invalid phone number format' . PHP_EOL;
									file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
								}							

							} else {
								wp_mail(bloginfo('admin_email'), $unsuccessful_gziri_order, $problem_placing_gziri_order . $order_id . ': ' . __('First name and Last name are required', 'gziri'), array('Content-Type: text/html; charset=UTF-8'));

								$log = '[' . date('Y-m-d H:i:s') . ']: First name and Last name are required' . PHP_EOL;
								file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
							}

						} else {
							wp_mail(bloginfo('admin_email'), $unsuccessful_gziri_order, $problem_placing_gziri_order . $order_id .  ': ' . __('Invalid Country or City', 'gziri'), array('Content-Type: text/html; charset=UTF-8'));

							$log = '[' . date('Y-m-d H:i:s') . ']: Invalid Country or City' . PHP_EOL;
							file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
						}					

					} else {
						wp_mail(bloginfo('admin_email'), $unsuccessful_gziri_order, $problem_placing_gziri_order . $order_id . ': ' . __('Preconditions are not met or cannot get token', 'gziri'), array('Content-Type: text/html; charset=UTF-8'));

						$log = '[' . date('Y-m-d H:i:s') . ']: Preconditions are not met or cannot get token' . PHP_EOL;
						file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);
					}

				} else {				
					wp_mail(bloginfo('admin_email'), $unsuccessful_gziri_order, $problem_placing_gziri_order . $order_id . ': ' . __('Multiple shipping packages', 'gziri'), array('Content-Type: text/html; charset=UTF-8'));

					$log = '[' . date('Y-m-d H:i:s') . ']: Multiple shipping packages' . PHP_EOL;
					file_put_contents(plugin_dir_path(__FILE__) . 'logs.log', $log, FILE_APPEND);				
				}

			}

		}
	}
	
	$order->update_meta_data( '_thankyou_action_done', true );
    $order->save();
	
}
add_action( 'woocommerce_thankyou', 'gziri_place_order' );


function gziri_tracking_code_thankyou_page($order_id) { ?>
	<?php $trackingCode = get_post_meta($order_id, 'gziri_tracking_id', true); ?>
	<?php if (!empty($trackingCode)) { ?>
    <h2><?php echo __('Shipping', 'gziri'); ?></h2>
    <table class="woocommerce-table shop_table gziri-tracking-code">
        <tbody>
            <tr>
                <th><?php echo __('Tracking Code', 'gziri'); ?></th>
                <td><a target="_blank" href="https://gziri.ge?trackorder=<?php echo $trackingCode; ?>"><?php echo $trackingCode; ?></td>
            </tr>
        </tbody>
    </table>
	<?php } ?>
<?php }
add_action( 'woocommerce_thankyou', 'gziri_tracking_code_thankyou_page', 20 );
add_action( 'woocommerce_view_order', 'gziri_tracking_code_thankyou_page', 20 );

