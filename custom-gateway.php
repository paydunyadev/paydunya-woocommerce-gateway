<?php
/*
Plugin Name: Passerelle de paiement PAYDUNYA pour WooCommerce
Plugin URI: https://paydunya.com/developers/wordpress
Description: Intégrer facilement des paiements via les Wallets Mobiles et les Cartes Bancaires dans votre site WooCommerce et commencer à accepter les paiements depuis le Sénégal, la Côte d'Ivoire et le Bénin.
Version: 1.1.7
Author: PAYDUNYA
Author URI: https://paydunya.com
*/

// Your plugin code goes here

// if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
//     exit;
// }

add_action('plugins_loaded', 'woocommerce_myplugin', 0);
function woocommerce_myplugin(){
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class 

    include(plugin_dir_path(__FILE__) . 'class-gateway.php');
}


add_filter('woocommerce_payment_gateways', 'add_my_custom_gateway');

function add_my_custom_gateway($gateways) {
  $gateways[] = 'WC_Paydunya';
  return $gateways;
}

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'oawoo_register_order_approval_payment_method_type' );


/**
 * Custom function to register a payment method type

 */
function oawoo_register_order_approval_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of My_Custom_Gateway_Blocks
            $payment_method_registry->register( new My_Custom_Gateway_Blocks );
        }
    );
}
?>