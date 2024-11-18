<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Paydunya_Blocks extends AbstractPaymentMethodType
{

    private $gateway;
    protected $name = 'paydunya_gateway'; // your payment gateway name

    public function initialize()
    {
        $this->settings = get_option('woocommerce_paydunya_gateway_settings', []);
        $this->gateway = new WC_Paydunya();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {

        wp_register_script(
            'paydunya-blocks-integration',
            plugin_dir_url(__FILE__) . 'js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('paydunya-blocks-integration');
        }
        return ['paydunya-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'icon' => plugin_dir_url(__DIR__) . 'assets/images/logo.png',
        ];
    }
}
