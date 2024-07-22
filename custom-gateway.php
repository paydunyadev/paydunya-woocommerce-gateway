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

if (!defined('ABSPATH')) {
    exit;
}


// if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

//     exit;
// }

add_action('admin_init', 'paydunya_check_woocommerce_active');
function paydunya_check_woocommerce_active()
{
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        deactivate_plugins(plugin_basename(__FILE__));
        paydunya_woocommerce_inactive_notice();
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
        wp_safe_redirect(admin_url('plugins.php'));
    }
}

// Affiche un message d'erreur si WooCommerce n'est pas actif
function paydunya_woocommerce_inactive_notice()
{
    echo '<div class="error"><p><strong>Erreur :</strong> WooCommerce doit être activé pour que le plugin "Passerelle de paiement PAYDUNYA pour WooCommerce" fonctionne.</p></div>';
}



// string $links;

add_action('plugins_loaded', 'woocommerce_myplugin', 0);
function woocommerce_myplugin()
{
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class 

    include(plugin_dir_path(__FILE__) . 'WC_Paydunya.php');
}


add_filter('woocommerce_payment_gateways', 'add_my_custom_gateway');

function add_my_custom_gateway($gateways)
{
    $gateways[] = 'WC_Paydunya';
    return $gateways;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'plugin_action_links');
function plugin_action_links($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paydunya') . '">Paramètres</a>';
    array_push($links, $settings_link);
    return $links;
}


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function declare_cart_checkout_blocks_compatibility()
{
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'oawoo_register_order_approval_payment_method_type');


/**
 * Custom function to register a payment method type

 */
function oawoo_register_order_approval_payment_method_type()
{
    // Check if the required class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'PaydunyaBlocks.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            // Register an instance of My_Custom_Gateway_Blocks
            $payment_method_registry->register(new Paydunya_Blocks);
        }
    );
}
