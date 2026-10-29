<?php
/*
Plugin Name: Passerelle de paiement PAYDUNYA pour WooCommerce
Plugin URI: https://paydunya.com/developers/wordpress
Description: PAYDUNYA est la plateforme qui facilite le paiement pour l'achat de biens et services via mobile money et cartes bancaires.
Version: 2.0.0
Author: PAYDUNYA
Author URI: https://paydunya.com
Text Domain: paydunya
Requires at least: 6.0
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 8.0
WC tested up to: 11.2
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit;
}

define('PAYDUNYA_VERSION', '2.0.0');
define('PAYDUNYA_PLUGIN_FILE', __FILE__);
define('PAYDUNYA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYDUNYA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Declares compatibility with HPOS order storage and the block-based checkout.
add_action('before_woocommerce_init', 'paydunya_declare_compatibility');
function paydunya_declare_compatibility()
{
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

add_action('plugins_loaded', 'paydunya_init', 0);
function paydunya_init()
{
    if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'paydunya_woocommerce_inactive_notice');
        return;
    }

    require_once PAYDUNYA_PLUGIN_DIR . 'WC_Paydunya.php';
    // Order status "Payée" applied after a successful PayDunya payment.
    require_once PAYDUNYA_PLUGIN_DIR . 'paydunya-order-status.php';

    add_filter('woocommerce_payment_gateways', 'paydunya_add_gateway');
    add_filter('woocommerce_currencies', 'paydunya_add_fcfa_currency');
    add_filter('woocommerce_currency_symbols', 'paydunya_add_fcfa_currency_symbol');
    // wp_loaded rather than template_redirect : the return is processed before the page loading.
    add_action('wp_loaded', 'paydunya_handle_return', 20);
    add_action('admin_notices', 'paydunya_currency_notice');
    add_action('admin_enqueue_scripts', 'paydunya_admin_scripts');
}

add_action('init', 'paydunya_load_textdomain');
function paydunya_load_textdomain()
{
    load_plugin_textdomain('paydunya', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Displays an error message if WooCommerce is not active.
function paydunya_woocommerce_inactive_notice()
{
    echo '<div class="notice notice-error"><p><strong>' . esc_html__('Erreur :', 'paydunya') . '</strong> ' . esc_html__('WooCommerce doit être activé pour que le plugin "Passerelle de paiement PAYDUNYA pour WooCommerce" fonctionne.', 'paydunya') . '</p></div>';
}

function paydunya_add_gateway($gateways)
{
    $gateways[] = 'WC_Paydunya';
    return $gateways;
}

// Adds the "BCEAO XOF" currency (FCFA code), the only currency accepted by the gateway.
function paydunya_add_fcfa_currency($currencies)
{
    $currencies['FCFA'] = __('BCEAO XOF', 'paydunya');
    return $currencies;
}

function paydunya_add_fcfa_currency_symbol($currency_symbols)
{
    $currency_symbols['FCFA'] = 'FCFA';
    return $currency_symbols;
}

// Reminder of the currency to use, on the PayDunya settings page.
function paydunya_currency_notice()
{
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'woocommerce_page_wc-settings' || !isset($_GET['section']) || $_GET['section'] !== 'paydunya') {
        return;
    }
    echo '<div class="notice notice-warning is-dismissible"><p>';
    echo esc_html__('Paydunya n\'accepte que la devise en BCEAO XOF (FCFA) --FCFA. Assurez-vous d\'ajouter BCEAO XOF (FCFA) --FCFA comme devise par défaut.', 'paydunya');
    echo '</p></div>';
}

// Script of the PayDunya settings page (enables/disables the SMS fields).
function paydunya_admin_scripts()
{
    if (!isset($_GET['page'], $_GET['section']) || $_GET['page'] !== 'wc-settings' || $_GET['section'] !== 'paydunya') {
        return;
    }
    wp_enqueue_script('paydunya_admin_option_js', PAYDUNYA_PLUGIN_URL . 'assets/js/settings.js', array('jquery'), PAYDUNYA_VERSION, true);
}

// Customer return from the PayDunya payment page: updates the order status.
function paydunya_handle_return()
{
    if (!isset($_GET['paydunya_return']) || is_admin() || wp_doing_ajax()) {
        return;
    }

    $gateways = WC()->payment_gateways()->payment_gateways();
    if (isset($gateways['paydunya'])) {
        $gateways['paydunya']->handle_return();
    }
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'paydunya_plugin_action_links');
function paydunya_plugin_action_links($links)
{
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=paydunya')) . '">' . esc_html__('Paramètres', 'paydunya') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

// Registers PayDunya in the WooCommerce block-based checkout. Hooked outside paydunya_init() because
// WooCommerce may trigger 'woocommerce_blocks_loaded' very early during 'plugins_loaded'.
add_action('woocommerce_blocks_loaded', 'paydunya_register_blocks_payment_method');
function paydunya_register_blocks_payment_method()
{
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once PAYDUNYA_PLUGIN_DIR . 'PaydunyaBlocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new Paydunya_Blocks());
        }
    );
}
