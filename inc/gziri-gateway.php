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

function gziri_response($url, $body) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	curl_setopt($ch, CURLOPT_URL, $url);
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