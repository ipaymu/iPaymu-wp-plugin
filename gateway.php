<?php

/*
  Plugin Name: iPaymu - WooCommerce Payment Gateway
  Plugin URI: http://ipaymu.com
  Description: iPaymu Indonesia Online Payment - Plug & Play, Within 30 seconds ready for LOCAL & INTERNASIONAL. Directly Connected 150 Payment Channels Reach more than 95% of consumers which provides the payment methods they use every day
  Version: 2.0
  Author: iPaymu Development Team
  Author URI: http://ipaymu.com
  License: MIT
  WC requires at least: 8.0.0
  WC tested up to: 8.6.0
*/

declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, false);
    }
});

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'ipaymu_add_gateway_class');

function ipaymu_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Ipaymu_Gateway'; // your class name is here
    return $gateways;
}
class Ipaymu extends WC_Payment_Gateway
{
    public $id;
    public $method_title;
    public $method_description;
    public $has_fields;
    public $icon;
    public $redirect_url;
    public $auto_redirect;
    public $return_url;
    public $expired_time;
    public $url;
    public $va;
    public $secret;
    public $settings;


    // Constructor method
    public function __construct()
    {

        $this->id                 = 'ipaymu';
        //Payment Gateway title
        $this->method_title       = 'iPaymu Payment Gateway';
        $this->method_description = 'Payment Gateway Terlengkap & Terbaik';
        
        //true only in case of direct payment method, false in our case
        $this->has_fields         = false;
        //payment gateway logo
        $this->icon               = plugins_url('/ipaymu_badge.png', __FILE__);

        //redirect URL
        $returnUrl                = home_url('/checkout/order-received/');

        $this->redirect_url      = add_query_arg('wc-api', 'WC_Gateway_iPaymu', home_url('/'));

        //thank you page URL

        //Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->enabled         = $this->settings['enabled'] ?? 'no';
        $this->auto_redirect   = $this->settings['auto_redirect'] ?? 120;
        $this->return_url      = $this->settings['return_url'] ?? $returnUrl;
        $this->expired_time    = $this->settings['expired_time'] ?? 24;
        $this->title           = "iPaymu Payment";
        $this->description     = $this->settings['description'] ?? '';

        if ($this->settings['testmode'] == 'yes') {
            $this->url = 'https://sandbox.ipaymu.com/api/v2/payment';
            $this->va     = $this->settings['sandbox_va'];
            $this->secret = $this->settings['sandbox_key'];
        } else {
            $this->url    = 'https://my.ipaymu.com/api/v2/payment';
            $this->va     = $this->settings['production_va'];
            $this->secret = $this->settings['production_key'];
        }

        // $this->password     = $this->settings['password'] ?? '';
        // $this->processor_id = $this->settings['processor_id'] ?? '';
        // $this->salemethod   = $this->settings['salemethod'] ?? '';
        // $this->gatewayurl   = $this->settings['gatewayurl'] ?? '';
        // $this->order_prefix = $this->settings['order_prefix'] ?? '';
        // $this->debugon      = $this->settings['debugon'] ?? '';
        // $this->debugrecip   = $this->settings['debugrecip'] ?? '';
        // $this->cvv          = $this->settings['cvv'] ?? '';


        // Actions
        add_action('woocommerce_receipt_ipaymu', array(&$this, 'receipt_page'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_ipaymu', array($this, 'check_ipaymu_response'));

        // Other initialization code goes here
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'woothemes'),
                'label'       => 'Enable iPaymu Payment Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woothemes'),
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'iPaymu Payment',
            ),
            'description' => array(
                'title' => __('Description', 'woothemes'),
                'type'        => 'textarea',
                'description' => '',
                'default'     => 'Pay with your credit card via our super-cool payment gateway.',
            ),
            'testmode' => array(
                'title'   => __('Mode Test/Sandbox', 'woothemes'),
                'label'       => 'Enable Test Mode / Sandbox',
                'type'        => 'checkbox',
                'description' => '<small>Mode Sandbox/Development digunakan untuk testing transaksi, jika mengaktifkan mode sandbox Anda harus memasukan API Key Sandbox (<a href="https://sandbox.ipaymu.com/integration" target="_blank">dapatkan API Key Sandbox</a>)</small>',
                'default'     => 'yes',
            ),
            'sandbox_va' => array(
                'title'       => 'VA Sandbox',
                'type'        => 'text',
                'description' => '<small>Dapatkan VA Sandbox <a href="https://sandbox.ipaymu.com/integration" target="_blank">di sini</a></small>',
                'default'     => ''
            ),
            'sandbox_key' => array(
                'title'       => 'API Key Sandbox',
                'type'        => 'password',
                'description' => '<small>Dapatkan API Key Sandbox <a href="https://sandbox.ipaymu.com/integration" target="_blank">di sini</a></small>',
                'default'     => ''
            ),
            'production_va' => array(
                'title'       => 'VA Live/Production',
                'type'        => 'text',
                'description' => '<small>Dapatkan VA Production <a href="https://my.ipaymu.com/integration" target="_blank">di sini</a></small>',
                'default'     => ''
            ),
            'production_key' => array(
                'title'       => 'API Key Live/Production',
                'type'        => 'password',
                'description' => '<small>Dapatkan API Key Production <a href="https://my.ipaymu.com/integration" target="_blank">di sini</a></small>',
                'default'     => ''
            ),
            'auto_redirect' => array(
                'title' => __('Waktu redirect ke Thank You Page (time of redirect to Thank You Page in seconds)', 'woothemes'),
                'type' => 'text',
                'description' => __('<small>Dalam hitungan detik. Masukkan -1 untuk langsung redirect ke halaman Anda</small>.', 'woothemes'),
                'default' => '60'
            ),
            'return_url' => array(
                'title' => __('Url Thank You Page', 'woothemes'),
                'type' => 'text',
                'description' => __('<small>Link halaman setelah pembeli melakukan checkout pesanan</small>.', 'woothemes'),
                'default' => home_url('/checkout/order-received/')
            ),
            'expired_time' => array(
                'title' => __('Expired kode pembayaran (expiry time of payment code)', 'woothemes'),
                'type' => 'text',
                'description' => __('<small>Dalam hitungan jam (in hours)</small>.', 'woothemes'),
                'default' => '24'
            )
        );
    }

    function process_payment($order_id)
    {

        $order = new WC_Order($order_id);

        $buyerName  = $order->get_billing_first_name() . $order->get_billing_last_name();
        $buyerEmail = $order->get_billing_email();
        $buyerPhone = $order->get_billing_phone();

        $body['product'] = [];
        $body['qty']     = [];
        $body['price']   = [];

        $width  = array();
        $height = array();
        $length = array();
        $weight = array();

        foreach ($order->get_items() as $kitem => $item) {
            $itemQty = $item->get_quantity();
            if (!$itemQty) {
                continue;
            }

            $itemWeight = is_numeric($item->get_product()->get_weight()) ? $item->get_product()->get_weight() : 0;
            if ($itemWeight) {
                // $weightVal = wc_get_weight($itemWeight * $itemQty, 'kg');
                array_push($weight, $itemWeight * $itemQty);
            }

            $itemWidth = is_numeric($item->get_product()->get_width()) ? $item->get_product()->get_width() : 0;
            if ($itemWidth) {
                // $widthVal = wc_get_dimension($itemWidth, 'cm');
                array_push($width, $itemWidth);
            }

            $itemHeight = is_numeric($item->get_product()->get_height()) ? $item->get_product()->get_height() : 0;
            if ($itemHeight) {
                // $heightVal = wc_get_dimension($itemHeight, 'cm');
                array_push($height, $itemHeight);
            }

            $itemLength = is_numeric($item->get_product()->get_length()) ? $item->get_product()->get_length() : 0;
            if ($itemLength) {
                // $lengthVal = wc_get_dimension($itemLength, 'cm');
                array_push($length, $itemLength);
            }
        }

        $weightVal = 0;
        $lengthVal = 0;
        $widthVal  = 0;
        $heightVal = 0;
        if (!empty($weight)) {
            $weightVal      = ceil(floatval(wc_get_weight(array_sum($weight), 'kg')));
        }

        if (!empty($length)) {
            $lengthVal      = ceil(floatval(wc_get_dimension(max($length), 'cm')));
        }

        if (!empty($width)) {
            $widthVal      = ceil(floatval(wc_get_dimension(max($width), 'cm')));
        }

        if (!empty($height)) {
            $heightVal      = ceil(floatval(wc_get_dimension(max($height), 'cm')));
        }


        $body['weight'][0]      = $weightVal;
        $body['length'][0]      = $lengthVal;
        $body['width'][0]       = $widthVal;
        $body['height'][0]      = $heightVal;
        $body['dimension'][0]   = $lengthVal . ':' . $widthVal . ':' . $heightVal;

        $body['product'][0]     = 'Order #' . trim(strval($order_id));
        $body['qty'][0]         = 1;
        $body['price'][0]       = $order->get_total();


        if (!empty($buyerName)) {
            $body['buyerName']          = trim($buyerName ?? null);
        } else {
            $body['buyerName']          = null;
        }

        if (!empty($buyerPhone)) {
            $body['buyerPhone']          = trim($buyerPhone ?? null);
        } else {
            $body['buyerPhone']          = null;
        }

        if (!empty($buyerEmail)) {
            $body['buyerEmail']          = trim($buyerEmail ?? null);
        } else {
            $body['buyerEmail']          = null;
        }

        $body['referenceId']         = trim(strval($order_id));
        $body['returnUrl']           = trim($this->return_url);
        $body['notifyUrl']           = trim($this->redirect_url . '&id_order=' . $order_id . '&param=notify');
        $body['cancelUrl']           = trim($this->redirect_url . '&id_order=' . $order_id . '&param=cancel');
        $body['expired']             = $this->expied_time ?? 24;
        $body['expiredType']         = 'hours';

        $bodyJson     = json_encode($body, JSON_UNESCAPED_SLASHES);
        $requestBody  = strtolower(hash('sha256', $bodyJson));
        $stringToSign = 'POST:' . $this->va . ':' . $requestBody . ':' . $this->secret;
        $signature    = hash_hmac('sha256', $stringToSign, $this->secret);

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'va: ' . $this->va,
            'signature: ' . $signature,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $err = curl_error($ch);
        $res = curl_exec($ch);
        // $health = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!empty($err)) {
            throw new Exception('Invalid Response from iPaymu. Please contact support@ipaymu.com');
            exit;
            // return new WP_Error( 'ipaymu_request', 'Invalid request: ' . $err);
        }
        if (empty($res)) {
            // return new WP_Error( 'ipaymu_request', 'Invalid request');
            throw new Exception('Request Failed: Invalid Response from iPaymu. Please contact support@ipaymu.com');
            exit;
        }

        $response = json_decode($res);
        if (empty($response->Data->Url)) {
            throw new Exception('Invalid request. Response iPaymu: ' . $response->Message);
            exit;
        }

        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $response->Data->Url
        );
    }


    function check_ipaymu_response()
    {
        $order = new WC_Order($_REQUEST['id_order']);

        $order_received_url = wc_get_endpoint_url('order-received', $_REQUEST['id_order'], wc_get_page_permalink('checkout'));

        if ('yes' === get_option('woocommerce_force_ssl_checkout') || is_ssl()) {
            $order_received_url = str_replace('http:', 'https:', $order_received_url);
        }
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if ($_REQUEST['status'] == 'berhasil') {
                $order->add_order_note(__('Payment Success iPaymu ID ' . $_REQUEST['trx_id'], 'woocommerce'));
                $order->update_status('completed');
                $order->update_status('processing');
                $order->payment_complete();
                echo 'completed';
                exit;
            } else if ($_REQUEST['status'] == 'pending') {
                $order->add_order_note(__('Waiting Payment iPaymu ID ' . $_REQUEST['trx_id'], 'woocommerce'));
                $order->update_status('on-hold');
                echo 'on-hold';
                exit;
            } else if ($_REQUEST['status'] == 'expired') {
                $order->add_order_note(__('Payment Expired iPaymu ID ' . $_REQUEST['trx_id'] . ' expired', 'woocommerce'));
                $order->update_status('cancelled');
                echo 'cancelled';
                exit;
            } else {
                echo 'invalid status';
                exit;
            }
        }

        $order_received_url = add_query_arg('key', $order->get_order_key(), $order_received_url);
        $redirect =  apply_filters('woocommerce_get_checkout_order_received_url', $order_received_url, $this);

        wp_redirect($redirect);
    }
}
