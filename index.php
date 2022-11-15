<?php

/*
  Plugin Name: iPaymu Payment Gateway
  Plugin URI: http://ipaymu.com
  Description: iPaymu - Indonesia Online Payment
  Version: 2.0
  Author: iPaymu Development Team
  Author URI: http://ipaymu.com
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
            // var_dump($this->settings);
            // exit;
            // Define user set variables
            $this->enabled      = $this->settings['enabled'] ?? '';
            $this->sandbox_mode      = $this->settings['sandbox_mode'] ?? 'no';
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

        
        public function generate_ipaymu_form($order_id) {

            global $woocommerce;
            
            $order = new WC_Order($order_id);
            // var_dump($order);
            
            
            $url = 'https://my.ipaymu.com/payment';
            if($this->sandbox_mode == 'yes') {
                $url = 'https://sandbox.ipaymu.com/payment';    
            }
            
            $auto_redirect = $this->auto_redirect ?? 60;

            //for cod
            $width  = 1;
            $height = 1;
            $length = 1;
            $weight = 1;

            foreach ($order->get_items() as $item_key => $item_value) {

                // if($item_value->get_product()->get_width() != false) {
                //     $width = $item_value->get_product()->get_width() ?? 1;
                // }
                $width = $item_value->get_product()->get_width() ?? 1;
                
                // if($item_value->get_product()->get_height() != false) {
                //     $height = $item_value->get_product()->get_height() ?? 1;
                // }
                $height = $item_value->get_product()->get_height() ?? 1;

                // if($item_value->get_product()->get_length() != false) {
                //     $length = $item_value->get_product()->get_length() ?? 1;
                // }
                $length = $item_value->get_product()->get_length() ?? 1;

                // if($item_value->get_product()->get_weight() != false) {
                //     $weight = $item_value->get_product()->get_weight() ?? 1;
                // }
                $weight = $item_value->get_product()->get_weight() ?? 1;
                // $width  = $item_value->get_product()->get_width();
            }

            $buyer_name = $order->get_billing_first_name() . $order->get_billing_last_name();
            $buyer_email = $order->get_billing_email();
            $buyer_phone = $order->get_billing_phone();

           
            if($weight > 0) {
                 // Prepare Parameters
                $params = array(
                    'key'      => $this->apikey, // API Key Merchant / Penjual
		    'account'  => $this->ipaymu_va,
                    'action'   => 'payment',
                    'auto_redirect' => $auto_redirect,
                    'product'  => 'Order : #' . $order_id,
//                     'price'    => $order->order_total ?? $order->total, // Total Harga
                    'price'    => $order->get_total(),
                    'quantity' => 1,
                    'weight'   => $weight,
                    'dimensi'  => $length . ":" . $width . ":" . $height,
                    'reference_id' => $order_id,
                    'comments' => '', // Optional           
//                     'ureturn'  => $this->redirect_url.'&id_order='.$order_id,
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
//                     'ureturn'  => $this->redirect_url.'&id_order='.$order_id,
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

                if( isset($result['url']) )
                    wp_redirect($result['url']);
                else {
                    //echo "Request Error ". $result['Status'] .": ". $result['Keterangan'];
			
                    echo "Request Error : " . json_encode($result);
                }
            }

            //close connection
            curl_close($ch);
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
	    	 if($_REQUEST['status'] == 'berhasil') {
			$order->add_order_note( __( 'Payment Success iPaymu ID '.$_REQUEST['trx_id'], 'woocommerce' ) );
// 			$order->update_status( 'completed' );
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
