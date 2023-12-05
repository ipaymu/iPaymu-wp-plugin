<?php

/*
  Plugin Name: iPaymu - WooCommerce Payment Gateway
  Plugin URI: http://ipaymu.com
  Description: iPaymu Indonesia Online Payment - Plug & Play, Within 30 seconds ready for LOCAL & INTERNASIONAL. Directly Connected 150 Payment Channels Reach more than 95% of consumers which provides the payment methods they use every day
  Version: 2.0
  Author: iPaymu Development Team
  Author URI: http://ipaymu.com
  License: GPLv2 or later
  WC requires at least: 2.0.0
  WC tested up to: 6.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; 

add_action('plugins_loaded', 'woocommerce_ipaymu_init', 0);
// require_once('wp-includes/template-loader.php');
// require('wp-blog-header.php');
function woocommerce_ipaymu_init() {

    if (!class_exists('WC_Payment_Gateway'))
        return;

    class WC_Gateway_iPaymu extends WC_Payment_Gateway {

        public function __construct() {
            
            //plugin id
            $this->id = 'ipaymu';
            //Payment Gateway title
            $this->method_title = 'iPaymu Payment Gateway';
            //true only in case of direct payment method, false in our case
            $this->has_fields = false;
            //payment gateway logo
            $this->icon = plugins_url('/ipaymu_badge.png', __FILE__);
            
            //redirect URL
            // $this->redirect_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_iPaymu', home_url( '/' ) ) );
            $this->redirect_url = add_query_arg( 'wc-api', 'WC_Gateway_iPaymu', home_url( '/' ) );
		
	    //thank you page URL
   	    $returnUrl = home_url('/checkout/order-received/');
            
            //Load settings
            $this->init_form_fields();
            $this->init_settings();
            
            // Define user set variables
            $this->enabled      = $this->settings['enabled'] ?? '';
            $this->sandbox_mode    = $this->settings['sandbox_mode'] ?? 'no';
            $this->auto_redirect   = $this->settings['auto_redirect'] ?? '60';
            $this->return_url      = $this->settings['return_url'] ?? $returnUrl;
            $this->expired_time    = $this->settings['expired_time'] ?? '24';
            $this->title        = "iPaymu Payment";
            $this->description  = $this->settings['description'] ?? '';
            $this->apikey       = $this->settings['apikey'] ?? '';
            $this->ipaymu_va    = $this->settings['ipaymu_va'] ?? '';
            $this->password     = $this->settings['password'] ?? '';
            $this->processor_id = $this->settings['processor_id'] ?? '';
            $this->salemethod   = $this->settings['salemethod'] ?? '';
            $this->gatewayurl   = $this->settings['gatewayurl'] ?? '';
            $this->order_prefix = $this->settings['order_prefix'] ?? '';
            $this->debugon      = $this->settings['debugon'] ?? '';
            $this->debugrecip   = $this->settings['debugrecip'] ?? '';
            $this->cvv          = $this->settings['cvv'] ?? '';
		
            
            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            add_action('woocommerce_receipt_ipaymu', array(&$this, 'receipt_page'));
            
            // Payment listener/API hook
            add_action( 'woocommerce_api_wc_gateway_ipaymu', array( $this, 'check_ipaymu_response' ) );
        }

        function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                                'title' => __( 'Enable/Disable', 'woothemes' ), 
                                'label' => __( 'Enable iPaymu', 'woothemes' ), 
                                'type' => 'checkbox', 
                                'description' => '', 
                                'default' => 'no'
                            ), 
                'title' => array(
                                'title' => __( 'Title', 'woothemes' ), 
                                'type' => 'text', 
                                'description' => __( '', 'woothemes' ), 
                                'default' => __( 'Pembayaran iPaymu', 'woothemes' )
                            ), 
                'description' => array(
                                'title' => __( 'Description', 'woothemes' ), 
                                'type' => 'textarea', 
                                'description' => __( '', 'woothemes' ), 
                                'default' => 'Sistem pembayaran menggunakan iPaymu.'
                            ),  
                'sandbox_mode' => array(
                                'title' => __( 'Mode Sandbox/Development', 'woothemes' ), 
                                'label' => __( 'Aktifkan Mode Sandbox/Development', 'woothemes' ), 
                                'type' => 'checkbox', 
                                'description' => '<small>Mode Sandbox/Development digunakan untuk testing transaksi, jika mengaktifkan mode sandbox Anda harus memasukan API Key Sandbox (<a href="https://sandbox.ipaymu.com/integration" target="_blank">dapatkan API Key Sandbox</a>)</small>', 
                                'default' => 'no'
                            ),
                'apikey' => array(
                                'title' => __( 'iPaymu API Key', 'woothemes' ), 
                                'type' => 'text', 
                                'description' => __( '<small>Dapatkan API Key Production <a href="https://my.ipaymu.com/integration" target="_blank">di sini</a>, atau API Key Sandbox <a href="https://sandbox.ipaymu.com/integration" target="_blank">di sini</a></small>.', 'woothemes' ),
                                'default' => ''
                            ),
                'ipaymu_va' => array(
                                'title' => __( 'iPaymu VA', 'woothemes' ), 
                                'type' => 'text', 
                                'description' => __( '<small>Dapatkan VA Production <a href="https://my.ipaymu.com/integration" target="_blank">di sini</a>, atau API Key Sandbox <a href="https://sandbox.ipaymu.com/integration" target="_blank">di sini</a></small>.', 'woothemes' ),
                                'default' => ''
                            ),
                'auto_redirect' => array(
                                'title' => __( 'Waktu redirect ke Thank You Page (time of redirect to Thank You Page in seconds)', 'woothemes' ), 
                                'type' => 'text', 
                                'description' => __( '<small>Dalam hitungan detik. Masukkan -1 untuk langsung redirect ke halaman Anda</small>.', 'woothemes' ),
                                'default' => '60'
                            ),
                'return_url' => array(
                                        'title' => __( 'Url Thank You Page', 'woothemes' ), 
                                        'type' => 'text', 
                                        'description' => __( '<small>Link halaman setelah pembeli melakukan checkout pesanan</small>.', 'woothemes' ),
                                        'default' => home_url('/checkout/order-received/')
                                    ),
                'expired_time' => array(
                                        'title' => __( 'Expired kode pembayaran (expiry time of payment code)', 'woothemes' ), 
                                        'type' => 'text', 
                                        'description' => __( '<small>Dalam hitungan jam (in hours)</small>.', 'woothemes' ),
                                        'default' => '24'
                                    ),
                    
                /*'debugrecip' => array(
                                'title' => __( 'Debugging Email', 'woothemes' ), 
                                'type' => 'text', 
                                'description' => __( 'Who should receive the debugging emails.', 'woothemes' ), 
                                'default' =>  get_option('admin_email')
                            ),*/
            );
        }

        public function admin_options() {
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }

        function payment_fields() {
            if ($this->description)
                echo wpautop(wptexturize($this->description));
        }

        
        function receipt_page($order) {
            echo $this->generate_ipaymu_form($order);
        }

        
        public function generate_ipaymu_formV1($order_id) {

            global $woocommerce;
            
            $order = new WC_Order($order_id);
            
            
            $url = 'https://my.ipaymu.com/payment';
            if ($this->sandbox_mode == 'yes') {
                $url = 'https://sandbox.ipaymu.com/payment';    
            }
            
            $auto_redirect = $this->auto_redirect ?? 60;
            //for cod
            $width  = array();
            $height = array();
            $length = array();
            $weight = array();

            $lengthTotal   = 0;
            $widthTotal    = 0;
            $heightTotal   = 0;
            $weightTotal   = 0;

            foreach ($order->get_items() as $item_key => $item) {
                $itemQty = $item->get_quantity();
                if (!$itemQty) {
                    continue;
                }

                $itemWeight = is_numeric($item->get_product()->get_weight() ) ? $item->get_product()->get_weight() : 0;
                if ($itemWeight) {
                    array_push( $weight, $itemWeight * $itemQty );
                }

                $itemWidth = is_numeric($item->get_product()->get_width() ) ? $item->get_product()->get_width() : 0;
                if ($itemWeight) {
                    array_push( $width, $itemWidth);
                }

                $itemHeight = is_numeric($item->get_product()->get_height() ) ? $item->get_product()->get_height() : 0;
                if ($itemHeight) {
                    array_push( $height, $itemWidth);
                }

                $itemLength = is_numeric($item->get_product()->get_length() ) ? $item->get_product()->get_length() : 0;
                if ($itemLength) {
                    array_push( $length, $itemLength);
                }
            }

            $buyer_name = $order->get_billing_first_name() . $order->get_billing_last_name();
            $buyer_email = $order->get_billing_email();
            $buyer_phone = $order->get_billing_phone();

            if ($weight) {
                $weightTotal = wc_get_weight(array_sum($weight), 'kg');
            }

            if ($width) {
                $widthTotal = wc_get_dimension(max($width), 'cm');
            }
    
            if ($length) {
                $lengthTotal = wc_get_dimension(max($length), 'cm' );
            }
    
            if ($height) {
                $heightTotal = wc_get_dimension(array_sum($height), 'cm' );
            }
           
            if ($weightTotal > 0) {
                 // Prepare Parameters
                $params = array(
                    'key'      => $this->apikey, // API Key Merchant / Penjual
                    'account'  => $this->ipaymu_va,
                    'action'   => 'payment',
                    'auto_redirect' => $auto_redirect,
                    'product'  => 'Order : #' . $order_id,
                    'price'    => $order->get_total(),
                    'quantity' => 1,
                    'weight'   => $weightTotal,
                    'dimensi'  => $lengthTotal . ":" . $widthTotal . ":" . $heightTotal,
                    'reference_id' => $order_id,
                    'comments' => '', // Optional           
                    // 'ureturn'  => $this->redirect_url.'&id_order='.$order_id,
                    'ureturn'  => $this->return_url,
                    'unotify'  => $this->redirect_url.'&id_order='.$order_id.'&param=notify',
                    'ucancel'  => $this->redirect_url.'&id_order='.$order_id.'&param=cancel',
                    'buyer_name' => $buyer_name ?? '',
                    'buyer_phone' => $buyer_phone ?? '',
                    'buyer_email' => $buyer_email ?? '',
                    'expired' => $this->expied_time ?? 24,
                    'expired_type' => 'hours',
                    'format'   => 'json' // Format: xml / json. Default: xml 
                );
            } else {
                 // Prepare Parameters
                $params = array(
                    'key'      => $this->apikey, // API Key Merchant / Penjual
                    'action'   => 'payment',
                    'auto_redirect' => $auto_redirect,
                    'product'  => 'Order : #' . $order_id,
                    // 'price'    => $order->order_total ?? $order->total, // Total Harga
                    'price'    => $order->get_total(),
                    'quantity' => 1,
                    // 'weight'   => $weight,
                    // 'dimensi'  => $length . ":" . $width . ":" . $height,
                    'reference_id' => $order_id,
                    'comments' => '', // Optional           
                    // 'ureturn'  => $this->redirect_url.'&id_order='.$order_id,
                    'ureturn'  => $this->return_url,
                    'unotify'  => $this->redirect_url.'&id_order='.$order_id.'&param=notify',
                    'ucancel'  => $this->redirect_url.'&id_order='.$order_id.'&param=cancel',
                    'buyer_name' => $buyer_name ?? '',
                    'buyer_phone' => $buyer_phone ?? '',
                    'buyer_email' => $buyer_email ?? '',
                    'expired' => $this->expied_time ?? 24,
                    'expired_type' => 'hours',
                    'format'   => 'json' // Format: xml / json. Default: xml 
                );
            }
            $params_string = http_build_query($params);

            //open connection
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            //execute post
            $request = curl_exec($ch);

            if ( $request === false ) {
                echo 'Curl Error: ' . curl_error($ch);
            } else {
                
                $result = json_decode($request, true);

                if ( isset($result['url']) )
                    wp_redirect($result['url']);
                else {
                    //echo "Request Error ". $result['Status'] .": ". $result['Keterangan'];
			
                    echo "Request Error : " . json_encode($result);
                }
            }

            //close connection
            curl_close($ch);

        }

        function generate_ipaymu_form($order_id) {
            global $woocommerce;
            
            $order = new WC_Order($order_id);
            
            
            $url = 'https://my.ipaymu.com/api/v2/payment';
            if ($this->sandbox_mode == 'yes') {
                $url = 'https://sandbox.ipaymu.com/api/v2/payment';    
            }

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

            $totalPrice = 0;
            $i = 0;

            foreach ($order->get_items() as $kitem => $item) {
                $itemQty = $item->get_quantity();
                if (!$itemQty) {
                    continue;
                }

                $product        = $item->get_product();
               
                $width  = 0;
                $height = 0;
                $length = 0;
                $weight = 0;

                $itemWeight = is_numeric($item->get_product()->get_weight() ) ? $item->get_product()->get_weight() : 0;
                if ($itemWeight) {
                    $weight = wc_get_weight($itemWeight * $itemQty, 'kg');
                }

                $itemWidth = is_numeric($item->get_product()->get_width() ) ? $item->get_product()->get_width() : 0;
                if ($itemWidth) {
                    $width = wc_get_dimension($itemWidth, 'cm');
                }

                $itemHeight = is_numeric($item->get_product()->get_height() ) ? $item->get_product()->get_height() : 0;
                if ($itemHeight) {
                    $height = wc_get_dimension($itemHeight, 'cm');
                }

                $itemLength = is_numeric($item->get_product()->get_length() ) ? $item->get_product()->get_length() : 0;
                if ($itemLength) {
                    $length = wc_get_dimension($itemLength, 'cm');
                }
                
                $body['product'][$i]     = trim($item->get_name());
                $body['qty'][$i]         = trim($item->get_quantity());
                $body['price'][$i]       = trim($product->get_price());
                $body['weight'][$i]      = trim(ceil($weight));
                $body['length'][$i]      = trim(ceil($length));
                $body['width'][$i]       = trim(ceil($width));
                $body['height'][$i]      = trim(ceil($height));
                $body['dimension'][$i]   = trim(ceil($length)) . ':' . trim(ceil($width)) . ':' . trim(ceil($height));

                $totalPrice += floatval($product->get_price()) * intval($item->get_quantity());
                $i++;
            }

            if ($totalPrice != $order->get_total()) {
                echo 'Invalid Total Product Price';
                exit;
                // return new WP_Error( 'ipaymu_request', 'Invalid Total Product Price');
            }
    
            $body['buyerName']           = trim($buyerName ?? null);
            $body['buyerPhone']          = trim($buyerPhone ?? null);
            $body['buyerEmail']          = trim($buyerEmail ?? null);
            $body['referenceId']         = trim($order_id);
            $body['returnUrl']           = trim($this->return_url);
            $body['notifyUrl']           = trim($this->redirect_url.'&id_order='.$order_id.'&param=notify');
            $body['cancelUrl']           = trim($this->redirect_url.'&id_order='.$order_id.'&param=cancel');
            $body['expired']             = trim($this->expied_time ?? 24);
            $body['expiredType']         = 'hours';


            $bodyJson     = json_encode($body, JSON_UNESCAPED_SLASHES);
            $requestBody  = strtolower(hash('sha256', $bodyJson));
            $secret       = $this->apikey;
            $va           = $this->ipaymu_va;
            $stringToSign = 'POST:' . $va . ':' . $requestBody . ':' . $secret;
            $signature    = hash_hmac('sha256', $stringToSign, $secret);

            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
                'va: ' . $va,
                'signature: ' . $signature,
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
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
                echo 'Invalid request: ' . $err;
                exit;
                // return new WP_Error( 'ipaymu_request', 'Invalid request: ' . $err);
            }
            if (empty($res)) {
                // return new WP_Error( 'ipaymu_request', 'Invalid request');
                echo 'Request Failed: Invalid response';
                exit;
            }

            $response = json_decode($res);
            if (empty($response->Data->Url)) {
                echo 'Invalid request: ' . $response->Message;
                exit;
                
            }
            wp_redirect($response->Data->Url);
        }

        
        function process_payment($order_id) {
            global $woocommerce;
            $order = new WC_Order($order_id);

			$order->reduce_order_stock();

			WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url( true ));
        }

  
        function check_ipaymu_response() {
            global $woocommerce;
            $order = new WC_Order($_REQUEST['id_order']);
            
            $order_received_url = wc_get_endpoint_url( 'order-received', $_REQUEST['id_order'], wc_get_page_permalink( 'checkout' ) );

            if ( 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) || is_ssl() ) {
                $order_received_url = str_replace( 'http:', 'https:', $order_received_url );
            }
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                if ($_REQUEST['status'] == 'berhasil') {
                    $order->add_order_note( __( 'Payment Success iPaymu ID '.$_REQUEST['trx_id'], 'woocommerce' ) );
                    $order->update_status( 'completed' );
                    $order->update_status( 'processing' );
                    $order->payment_complete();
                echo 'completed';
                exit;
                } else if($_REQUEST['status'] == 'pending') {
                    $order->add_order_note( __( 'Waiting Payment iPaymu ID '.$_REQUEST['trx_id'], 'woocommerce' ) );
                    $order->update_status( 'on-hold' );
                    echo 'on-hold';
                        exit;
                } else if($_REQUEST['status'] == 'expired') {
                    $order->add_order_note( __( 'Payment Expired iPaymu ID '.$_REQUEST['trx_id'] . ' expired', 'woocommerce' ) );
                    $order->update_status( 'cancelled' );
                    echo 'cancelled';
                    exit;
                } else {
                    echo 'invalid status';
                    exit;
                }
            }
           

            // $order_received_url = add_query_arg('key', $order->order_key, add_query_arg('order', $_REQUEST['id_order'], $order_received_url));
            // $order_received_url = add_query_arg( 'key', $order->order_key, $order_received_url );
            $order_received_url = add_query_arg( 'key', $order->get_order_key(), $order_received_url );
            $redirect =  apply_filters( 'woocommerce_get_checkout_order_received_url', $order_received_url, $this );
            
            wp_redirect($redirect);
            
        }

    }

    function add_ipaymu_gateway($methods) {
        $methods[] = 'WC_Gateway_iPaymu';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_ipaymu_gateway');
}
