<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Ipaymu_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'ipaymu';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'woocommerce_ipaymu_settings', [] );
        $this->gateway = new WC_Gateway_iPaymu();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'ipaymu-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
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
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'ipaymu-blocks-integration');
            
        }
        return [ 'ipaymu-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title'       => 'Pembayaran iPaymu',
            'description' => 'Pembayaran melalui Virtual Account (VA), QRIS, Alfamart Indomaret, Kartu Kredit, Direct Debit, dan COD',
            'icon'        => plugins_url('/ipaymu_badge.png', __FILE__)
        ];
    }

}
?>