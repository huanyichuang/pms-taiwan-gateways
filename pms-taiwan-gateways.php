<?php
/**
 * Plugin Name:       Paid Member Subscriptions Taiwan Gateways
 * Plugin URI:        https://www.applemint.tech/
 * Description:       The extension that helps you to add gateways in Taiwan to the plugin Paid Member Subscriptions.
 * Version:           1.0.2
 * Requires at least: 5.2
 * Requires PHP:      7.3
 * Author:            applemint Ltd.
 * Author URI:        https://www.applemint.tech/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pms-taiwan-gateways
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PMSTWGATE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pms-taiwan-gateways-activator.php
 */

 /**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pms-taiwan-gateways-deactivator.php
 * To-dos
 */

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pms-taiwan-gateways.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_pms_taiwan_gateways() {
	$plugin = new PMS_Taiwan_Gateways();
	$plugin->run();
}
run_pms_taiwan_gateways();

/**
 * Add custom gateway for pms.
 * 
 * @since 1.0.0
 * 
 * @param array $arr will retrieve the default gateways, 
 * and can add other gateways with the hook `pms_payment_gateways`
 */
if ( !function_exists( 'pms_custom_gateway' ) ) {
	function pms_custom_gateway( $arr ){
		$arr['ECPay'] = array(
			'display_name_user'  => __( 'ECPay', 'pms-taiwan-gateways' ),
			'display_name_admin' => __( 'ECPay', 'pms-taiwan-gateways' ),
			'class_name'         => 'PMS_Payment_Gateway_TW_ECPay'
		);
		return $arr;
	}
}
add_filter( 'pms_payment_gateways', 'pms_custom_gateway', 101, 1 );

if ( !function_exists( 'pms_add_ecpay_payment_type' ) ) {
	function pms_add_ecpay_payment_type( $arr ){
		$arr['web_accept_ecpay'] = __( 'ECPay - One-Time Payment', 'pms-taiwan-gateways' );
		return $arr;
	}
}
add_filter( 'pms_payment_types', 'pms_add_ecpay_payment_type', 10, 1 );