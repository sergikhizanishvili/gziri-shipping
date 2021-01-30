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

function gziri_admin_settings() {
	add_submenu_page(
		'woocommerce',
		'Gziri Settings',
		'Gziri Settings',
		'manage_options',
		'gziri-settings',
		'gziri_settings_callback'
	);
}
add_action('admin_menu', 'gziri_admin_settings');

function gziri_settings_callback() {
	
	$gziri_message = array('', '');
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['update_gziri_settings'])) {
			
			if (!empty($_POST['gziri_username']) && !empty($_POST['gziri_password'])) {
				
				if (get_option('gziri_username', 'gziri_false') == 'gziri_false') {
					add_option('gziri_username', $_POST['gziri_username']);
				} else {
					update_option('gziri_username', $_POST['gziri_username']);
				}
				
				if (get_option('gziri_password', 'gziri_false') == 'gziri_false') {
					add_option('gziri_password', $_POST['gziri_password']);
				} else {					
					update_option('gziri_password', $_POST['gziri_password']);
				}
				
				if (get_option('gziri_auto_order', 'gziri_false') == 'gziri_false') {
					$gziri_auto_order_post = (isset($_POST['gziri_auto_order'])) ? 1 : 0;
					add_option('gziri_auto_order', $gziri_auto_order_post);					
				} else {
					$gziri_auto_order_post = (isset($_POST['gziri_auto_order'])) ? 1 : 0;
					update_option('gziri_auto_order', $gziri_auto_order_post);					
				}
				
				$gziri_message = array('success', __('Settings Saved', 'gziri'));
				
			} else {
				$gziri_message = array('error', __('Please enter username or/and password', 'gziri'));
			}
			
		}
	}
	
	$gziri_username = get_option('gziri_username', '');
	$gziri_password = get_option('gziri_password', '');
	$gziri_auto_order = get_option('gziri_auto_order', 0);
	
?>

<div class="wrap">
	<h1><?php echo __('GZIRI Settings', 'gziri'); ?></h1>
	<p><?php echo __('Please enter email address and password you use to access your account on <a target="_blank" href="https://gziri.ge">https://gziri.ge</a>. If you dont have those credentials - first you have to register on <a target="_blank" href="https://gziri.ge">https://gziri.ge</a>', 'gziri'); ?></p>
	
	<?php if (!empty($gziri_message[0])) { ?>
	<div class="notice notice-<?php echo $gziri_message[0]; ?> settings-error"> 
		<p>
			<strong><?php echo $gziri_message[1]; ?></strong>
		</p>
	</div>
	<?php } ?>
	
	
	<form method="post" action="" autocomplete="off">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="gziri_username">
							<?php echo __('Gziri Username', 'gziri'); ?>
						</label>
					</th>

					<td>
						<input name="gziri_username" type="text" id="gziri_username" value="<?php echo $gziri_username; ?>" class="regular-text">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<label for="gziri_password">
							<?php echo __('Gziri Password', 'gziri'); ?>
						</label>
					</th>

					<td>
						<input name="gziri_password" type="password" id="gziri_password" value="<?php echo $gziri_password; ?>" class="regular-text">
					</td>
				</tr>
				
				<tr>
					<th scope="row">
						<?php echo __('Auto Order', 'gziri'); ?>
					</th>

					<td>
						<label for="gziri_auto_order">
							<input name="gziri_auto_order" type="checkbox" id="gziri_auto_order" <?php echo ($gziri_auto_order == 1) ? 'checked' : ''; ?>>
							<?php echo __('Shipping orders will be automatically sent to Gziri on checkout', 'gziri'); ?>
						</label>
					</td>
				</tr>				
			</tbody>	
		</table>
		
		<p class="submit">
			<input type="submit" name="update_gziri_settings" id="update_gziri_settings" class="button button-primary" value="Save Changes">
		</p>
	</form>
</div>

<?php }

