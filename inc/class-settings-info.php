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

class Gziri_Settings_Info {
	
	public $base_city;
	public $base_address;
	public $base_address_2;
	private $gziri_username;
	private $gziri_password;
	public $gziri_auto_order;
	public $base_currency;
	
	/**
	 * Constructor for Gziri settings info class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->base_city = get_option('woocommerce_store_city', '');
		$this->base_address = get_option('woocommerce_store_address', '');
		$this->base_address_2 = get_option('woocommerce_store_address_2', '');
		$this->gziri_username = get_option('gziri_username', '');
		$this->gziri_password = get_option('gziri_password', '');
		$this->gziri_auto_order = get_option('gziri_auto_order', '');
		$this->base_currency = get_option('woocommerce_currency', '');
	}
	
	/**
	 * Get base country
	 *
	 * @access public
	 * @return void
	 */
	public function get_base_country() {
		$store_raw_country = get_option('woocommerce_default_country', '');
		$split_country = explode(':', $store_raw_country);
		$store_country = $split_country[0];
		
		return $store_country;
	}
	
	/**
	 * Build sender body
	 *
	 * @access public
	 * @return void
	 */
	public function get_sender_body() {		
		$body = '
			"order_sender_street": "' . $this->base_address . '",
			"order_sender_building_number": "' . $this->base_address_2 . '",
			"order_sender_address_additional": "' . '' . '",
		';
		
		return $body;
	}
	
	/**
	 * Generate token using Gziri API
	 *
	 * @access public
	 * @return void
	 */
	public function get_token() {
		$body = 
		'{
			"username": "' . $this->gziri_username . '",
			"password": "' . $this->gziri_password . '"
		}';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_URL, GZIRI_TOKEN_GTW);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-type: application/json; charset=utf-8',
			'Accept: application/json'
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response, JSON_UNESCAPED_UNICODE);
		return $response;
	}
	
	/**
	 * Check store address settings
	 *
	 * @access public
	 * @return void
	 */
	public function check_sender_preconditions() {		
		if ($this->get_base_country() == GZIRI_REQUIRED_COUNTRY && in_array(strtolower($this->base_city), GZIRI_REQUIRED_CITY) && !empty($this->base_address) && $this->base_currency == 'GEL') {
			$out = true;
		} else {
			$out = false;
		}
		
		return $out;
	}
	
	/**
	 * Check connection to Gziri API
	 *
	 * @access public
	 * @return void
	 */
	public function check_account_preconditions() {		
		if (!empty($this->gziri_username) && !empty($this->gziri_password)) {			
			$token = $this->get_token();			
			if (!empty($token) && empty($token['error']) && !empty($token['response'])) {
				$out = true;
			} else {
				$out = false;
			}			
		} else {
			$out = false;
		}
		
		return $out;
	}
	
}

function gziri_no_woocommerce() {
	echo 
	' <div class="error notice">' . 
		'<p>' . __( 'In order to use GZIRI Shipping Method, Woocommerce should be installed and activated', 'gziri' ) . '</p>' . 
	'</div>';
}

function gziri_no_store_address() {
	echo 
	' <div class="error notice">' . 
		'<p>' . __( 'In order to use GZIRI Shipping Method - you should set up currency to <strong>GEL</strong> and store address in WooCommerce settings (Country, City, Address). Also please be informed that country should be Georgia and city should be Tbilisi or თბილისი', 'gziri' ) . '</p>' . 
	'</div>';
}

function gziri_no_api_connection() {
	echo 
	' <div class="error notice">' . 
		'<p>' . __( 'In order to use GZIRI Shipping Method - you should set up <a href="admin.php?page=gziri-settings">Gziri Settings</a> and connect plugin to Gziri API', 'gziri' ) . '</p>' . 
	'</div>';
}