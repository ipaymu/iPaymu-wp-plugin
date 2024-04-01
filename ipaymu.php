<?php

/*
  Plugin Name: iPaymu - Payment Gateway
  Plugin URI: https://github.com/ipaymu/iPaymu-wp-plugin
  Description: iPaymu Indonesia Online Payment - Plug & Play, Within 30 seconds ready for LOCAL & INTERNASIONAL. Directly Connected 150 Payment Channels Reach more than 95% of consumers which provides the payment methods they use every day
  Version: 2.0.1
  Author: iPaymu Development Team
  Author URI: https://ipaymu.com
  License: GPLv2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  WC requires at least: 8.0.0
  WC tested up to: 8.6.0
*/

add_action('plugins_loaded', 'woocommerce_myplugin', 0);

function woocommerce_myplugin()
{
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class 

    include(plugin_dir_path(__FILE__) . 'gateway.php');
}


function add_ipaymu_gateway($methods)
{
    $methods[] = 'WC_Gateway_iPaymu';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_ipaymu_gateway');

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
 */
function declare_cart_checkout_blocks_compatibility()
{
    // Check if the required class exists
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, false);
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
    require_once plugin_dir_path(__FILE__) . 'block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            // Register an instance of My_Custom_Gateway_Blocks
            $payment_method_registry->register(new Ipaymu_Blocks);
        }
    );
}
