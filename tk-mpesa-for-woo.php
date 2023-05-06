<?php
/**
 * Plugin Name: TK MPesa Payment Gateway for WooCommerce
 * Plugin URI: https://ziprof.co.ke/
 * Description: MPesa payment gateway for WooCommerce
 * Version: 1.0.0
 * Author: Tonie Kiguta
 * Author URI: mailto:tonkigs@gmail.com
 * Text Domain: tk-mpesa-payments-for-woocommerce
 * Domain Path: /langs
 * Copyright: © 2023 Ziprof Technologies
 * WC tested up to: 6.1.1
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
 add_filter( 'woocommerce_payment_gateways', 'tk_add_mpesa_gateway_class' );
 function tk_add_mpesa_gateway_class( $gateways ) {
	$gateways[] = __('WC_MPesa_Gateway','tk-mpesa-woo'); // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'mpesa_init_gateway_class' );
add_action( 'woocommerce_checkout_update_order_meta', 'tk_woo_checkout_update_order_meta', 10, 1 );
add_action( 'woocommerce_admin_order_data_after_billing_address', 'tk_woo_order_data_after_billing_address', 10, 1 );
add_action( 'rest_api_init', 'tk_woo_add_callback_url_endpoint' );
add_action('init','add_tk_woo_post_type');

function mpesa_init_gateway_class() {

	class WC_MPesa_Gateway extends WC_Payment_Gateway {
		public function __construct() {
        $this->id = 'tk_mpesa'; // payment gateway plugin ID
        $this->icon = apply_filters( 'woocommerce_mpesa_icon', plugins_url('/assets/mpesa_icon.png', __FILE__ ) );
				$this->has_fields = true; // in case you need a custom form
				$this->method_title = __('MPesa Gateway','tk-mpesa-woo');
				$this->method_description = __('Receive payments from your customers via MPesa.','tk-mpesa-woo'); 
				$this->supports = array(
					'products'
				);

	// Method with all the options fields
				$this->init_form_fields();

	// Load the settings.
				$this->init_settings();
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->enabled = $this->get_option( 'enabled' );

	// This action hook saves the settings
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	// We need custom JavaScript to obtain a token
				add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	//MPesa Parameters
				$this->customer_key = $this->get_option( 'customer_key' );
				$this->customer_pass = $this->get_option( 'customer_pass' );
				$this->short_code = $this->get_option( 'short_code' );
				$this->pay_to= $this->get_option( 'pay_to' );
				$this->payment_type = $this->get_option( 'payment_type' );
				$this->pass_key = $this->get_option( 'pass_key' );
				$this->callback_url = $this->get_option('callback_url');
			}

			public function init_form_fields(){

				$this->form_fields = array(
					'enabled' => array(
						'title'       => __('Enable/Disable','tk-mpesa-woo'),
						'label'       => __('Enable MPesa Gateway','tk-mpesa-woo'),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no'
					),
					'title' => array(
						'title'       => __('Title','tk-mpesa-woo'),
						'type'        => 'text',
						'description' => __('This controls the title which the user sees during checkout.','tk-mpesa-woo'),
						'default'     => __('MPesa Payments','tk-mpesa-woo'),
						'desc_tip'    => true,
					),
					'description' => array(
						'title'       => __('Description','tk-mpesa-woo'),
						'type'        => 'textarea',
						'description' => __('This controls the description which the user sees during checkout.','tk-mpesa-woo'),
						'default'     => __('A payment request will be sent to the payment MPesa number you will provide below. Please ensure your phone is ON and Unlocked','tk-mpesa-woo'),
					),
					'customer_key' => array(
						'title'       => __('App Consumer Key','tk-mpesa-woo'),
						'type'        => 'text'
					),
					'customer_pass' => array(
						'title'       => __('App Consumer Secret','tk-mpesa-woo'),
						'type'        => 'password',
					),
					'pass_key' => array(
						'title'       => __('Pass Key','tk-mpesa-woo'),
						'type'        => 'text'
					),
					'payment_type' => array(
						'title'       => __('Payment Type','tk-mpesa-woo'),
						'type'        => 'select',
						'options' => array(
							'none' => __('Select Payment Type','tk-mpesa-woo'),
							'CustomerPayBillOnline' => 'CustomerPayBillOnline',
							'CustomerBuyGoodsOnline' => 'CustomerBuyGoodsOnline'),
						'description' => __('CustomerPayBillOnline if using Paybill or <br> CustomerBuyGoodsOnline if using a Till Number','tk-mpesa-woo')
					),
					'short_code' => array(
						'title'       => __('Short Code','tk-mpesa-woo'),
						'description' => __('This is Paybill Number for Paybill or Head Office Number for Till','tk-mpesa-woo'),
						'type'        => 'text'
					),
					'pay_to' => array(
						'title'       => __('Payment to','tk-mpesa-woo'),
						'type'        => 'text',
						'description' => __('This is the Paybill Number / Till Number','tk-mpesa-woo'),
					),
					'callback_url' => array(
						'title'       => __('Callback url to','tk-mpesa-woo'),
						'type'        => 'text',
						'description' => __('This is the link where MPesa Data will be sent. <br>Type a unique name without spaces. <br> Should not include <strong>mpesa</strong>','tk-mpesa-woo'),
					)
				);
			}

			public function payment_fields() {

	//display some description before the payment form
				if ( $this->description ) {
		// display the description with <p> tags etc.
					echo wpautop( wp_kses_post( $this->description ) );
				}

	// I will echo() the form, but you can close PHP tags and print it directly in HTML
				echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:gery;">';

	// Add this action hook if you want your custom payment gateway to support it
				do_action( 'woocommerce_credit_card_form_start', $this->id );

	// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
				echo '<div class="form-row form-row-wide"><label>'.__('Mpesa Phone Number ','tk-mpesa-woo').'<span class="required">*</span></label>
				<input id="phone" name="phonenumber" type="text" class="form-control" autocomplete="off" required="required" style="width:100%;height:30px;" placeholder="0XXX XXX XXX">
				<div class="clear"></div>';

				do_action( 'woocommerce_credit_card_form_end', $this->id );

				echo '<div class="clear"></div></fieldset>';

			}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
		public function payment_scripts() {

		}

		/*
 		 * Fields validation
		 */
		public function validate_fields() {

			if( empty( $_POST[ 'phonenumber' ]) ) {
				wc_add_notice(__('MPesa Phone Number is required!','tk-mpesa-woo'), 'error' );
				return false;
			}
			return true;

		}

		/*
		 * We're processing the payments here
		 */
		public function process_payment( $order_id ) {

			global $woocommerce;
			
			function generateToken($payment_args){

				$curl = curl_init();

				$account_id = $payment_args['customer_key'];

				$account_pass = $payment_args['customer_pass'];

				$credentials = base64_encode($account_id.':'.$account_pass);

				curl_setopt_array($curl, array(

					CURLOPT_URL => "https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials",

					CURLOPT_RETURNTRANSFER => true,

					CURLOPT_ENCODING => "",

					CURLOPT_MAXREDIRS => 10,

					CURLOPT_TIMEOUT => 30,

					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

					CURLOPT_CUSTOMREQUEST => "GET",

					CURLOPT_HTTPHEADER => array(
						"Authorization: Basic ".$credentials,  
					),

				));

				$response = curl_exec($curl);
				$err = curl_error($curl);
				curl_close($curl); 
				$json = json_decode($response);

				return $json->access_token;

			}

			function stkpush($payment_args){

				$url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
				$BusinessShortCode = $payment_args['short_code'];
				$Passkey = $payment_args['pass_key'];
				$Timestamp = date('YmdHis');
				$Password = base64_encode($BusinessShortCode.$Passkey.$Timestamp);
				$TransactionType = $payment_args['payment_type'];
				$Amount = $payment_args['payment_amount'];
				$PartyA = $payment_args['tel'];
				$PartyB = $payment_args['pay_to'];
				$PhoneNumber = $PartyA;
				$CallBackURL = $payment_args['callbackurl'];
				$AccountReference = $payment_args['ref'];
				$TransactionDesc = "stk";

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
  			    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.generateToken($payment_args))); //setting custom header
  			    $curl_post_data = array(
			    //Fill in the request parameters with valid values
  			    	'BusinessShortCode' =>$BusinessShortCode,
  			    	'Password' => $Password,
  			    	'Timestamp' => $Timestamp,
  			    	'TransactionType' => $TransactionType,
  			    	'Amount' =>$Amount,
  			    	'PartyA' => $PartyA,
  			    	'PartyB' => $PartyB,
  			    	'PhoneNumber' => $PhoneNumber,
  			    	'CallBackURL' => $CallBackURL,
  			    	'AccountReference' => $AccountReference,
  			    	'TransactionDesc' => $TransactionDesc
  			    );
  			    $data_string = json_encode($curl_post_data);
  			    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  			    curl_setopt($curl, CURLOPT_POST, true);
  			    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
  			    $curl_response = curl_exec($curl);

  			    $paymentresonse = json_decode($curl_response);
  			    
  			    return $paymentresonse;
  			}
  			function parse_tel($tel){
  				$str = ltrim($tel, '0');
  				$str = ltrim($str, '254');
  				$str = ltrim($str, '+254');
  				$str = ltrim($str, '2540');
  				$str = ltrim($str, '254254');
  				$str = ltrim($str, '254+254');
  				$tel = '254' . $str;
  				return $tel;
  			}

	// we need it to get order detailes
  			$order = wc_get_order($order_id);

  			$payment_args = array(
  				'customer_key' => $this->customer_key,
  				'customer_pass' => $this->customer_pass,
  				'short_code' => $this->short_code,
  				'payment_type' => $this->payment_type,
  				'pass_key' => $this->pass_key,
  				'callbackurl' => get_site_url(null, '/wp-json/tk_woopesa/v1/'.$this->callback_url, 'https'),  				
  				'payment_amount' => ceil($order->get_total()),
  				'tel' => parse_tel($_POST['phonenumber']),
  				'ref' => $order->get_id(),
  				'pay_to' => $this->pay_to
  			);

  			$response = stkpush($payment_args);

  			if(!isset($response->errorMessage)){

  				$order->update_status( 'pending',  'Awaiting MPesa Payment Confirmation');

  				// $order->reduce_order_stock(); Depreciated
  				wc_reduce_stock_levels( $order->get_id() );   				

  				WC()->cart->empty_cart();

  				//Add Payment request post
  				$postargs = [
  					'post_title' => $order->get_id(),
  					'post_content' => json_encode($response),
  					'post_status' => 'draft',
  					'post_type' => 'paymentrequests'
  				];

  				$post_id = wp_insert_post($postargs);

  				update_post_meta($post_id,'MerchantRequestID', $response->MerchantRequestID);
  				update_post_meta($post_id,'CheckoutRequestID', $response->CheckoutRequestID);
  				update_post_meta($post_id,'ResponseCode', $response->ResponseCode);
  				update_post_meta($post_id,'ResponseDescription', $response->ResponseDescription);
  				update_post_meta($post_id,'CustomerMessage', $response->CustomerMessage);
  				update_post_meta($post_id,'DepositRef',$order->get_id());
  				update_post_meta($post_id,'RequestAmount', ceil($order->get_total()));
  				update_post_meta($post_id,'MpesaRequestNumber', parse_tel($_POST['phonenumber']));
  				update_post_meta($post_id,'PaymentStatus', 'pending');
  				return array(
  					'result'   => 'success',
  					'redirect' => $this->get_return_url( $order ),
  				);

  			}

  		}

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {



		}
	}
}
function tk_woo_checkout_update_order_meta( $order_id ) {
	if( isset( $_POST['phonenumber'] ) || ! empty( $_POST['phonenumber'] ) ) {
		update_post_meta( $order_id, 'phonenumber', $_POST['phonenumber'] );
	}
}
function tk_woo_order_data_after_billing_address( $order) {
	echo '<p><strong>' . __( 'Billed MPesa Number:', 'tk-mpesa-woo' ) . '</strong><br>' . get_post_meta( $order->get_id(), 'phonenumber', true ) . '</p>';
}

function tk_woo_add_callback_url_endpoint(){	
	$payment_gateway = WC()->payment_gateways->payment_gateways()['tk_mpesa'];
	if(isset($payment_gateway->callback_url) && !empty($payment_gateway->callback_url)){
		register_rest_route(
        'tk_woopesa/v1', // Namespace
        $payment_gateway->callback_url, // Endpoint
        array(
        	'methods'  => 'POST',
        	'callback' => 'tk_woo_receive_callback',
        	'permission_callback' => __return_false()
        )
    );					
	}	
}

function tk_woo_receive_callback( $request_data ) {

	$parameters = $request_data->get_params();

	//get sent data
	$datareceived=file_get_contents('php://input');

        //Create a logs file
	$handle = fopen('StkRequests.txt', 'a');

	fwrite($handle, $datareceived); 

	//Get variables
	$MerchantRequestID = isset($parameters['Body']['stkCallback']['MerchantRequestID']) ? $parameters['Body']['stkCallback']['MerchantRequestID']:NULL;
	$CheckoutRequestID = isset($parameters['Body']['stkCallback']['CheckoutRequestID']) ? $parameters['Body']['stkCallback']['CheckoutRequestID']:NULL;
	$ResultCode = isset($parameters['Body']['stkCallback']['ResultCode']) ? $parameters['Body']['stkCallback']['ResultCode']:NULL;
	$ResultDesc = isset($parameters['Body']['stkCallback']['ResultDesc']) ? $parameters['Body']['stkCallback']['ResultDesc']:NULL;

	if(isset($parameters['Body']['stkCallback']['CallbackMetadata'])){
		$CallbackMetadata = $parameters['Body']['stkCallback']['CallbackMetadata'];
		$Amount = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['0']['Value'];
		$MpesaReceiptNumber = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['1']['Value'];
		$Balance = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['2']['Value'];
		$TransactionDate = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['3']['Value'];
		$PhoneNumber = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['4']['Value'];
	}
	
	if(isset($CheckoutRequestID)){
		//Get the post
		$requestpost = get_posts([
			'post_type' => 'paymentrequests',
			'post_status' => 'draft',
			'numberposts' => 1,
			'meta_query' => [
				[
					'key'   => 'CheckoutRequestID',
					'value' => $CheckoutRequestID
				],
				[
					'key'   => 'PaymentStatus',
					'value' => 'pending'
				]
			]]);
		$post_id = $requestpost['0']->ID;
		$RequestAmount = get_post_meta( $post_id, 'RequestAmount', true );
		$orderID = get_post_meta( $post_id, 'DepositRef', true );

		if(!isset($CallbackMetadata)){
			//Set results
			update_post_meta($post_id, 'ResultCode', $ResultCode);
			update_post_meta($post_id, 'ResultDesc', $ResultDesc);
			update_post_meta($post_id, 'PaymentStatus', 'canceled');

			//Trash Post
			wp_trash_post($post_id);

			//Cancel Order
			$order = wc_get_order($orderID);
			$order->update_status( 'cancelled',  'Payment Process not completed.');	
		}
		else{		

			//Update Meta
			update_post_meta($post_id, 'ResultCode', $ResultCode);
			update_post_meta($post_id, 'ResultDesc', $ResultDesc);
			update_post_meta($post_id, 'Amount', $Amount);
			update_post_meta($post_id, 'MpesaReceiptNumber', $MpesaReceiptNumber);
			update_post_meta($post_id, 'TransactionDate', $TransactionDate);
			update_post_meta($post_id, 'PhoneNumber', $PhoneNumber);
			update_post_meta($post_id, 'TransactionDate', $TransactionDate);

			if($RequestAmount == $Amount){
				//update post meta
				update_post_meta($post_id, 'PaymentStatus', 'Paid');

				//Update post status
				wp_update_post([
					'ID' => $post_id,
					'post_status'   => 'publish'
				]);

				//update order status				
				$order = wc_get_order($orderID);
				$order->update_status( 'completed',  'Paid via MPesa : '.$MpesaReceiptNumber.'.');					
			}
		}

		return 'Updated';

	}
	else{
		return "Invalid";
	}

}

function add_tk_woo_post_type(){

	$labels = [
		"name" => __('MPesa Payments','tk-mpesa-woo'),
		"singular_name" => __('MPesa Payment','tk-mpesa-woo'),
	];

	$args = [
		"label" => __('MPesa Payments','tk-mpesa-woo'),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => false,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"capabilities"    => array('create_posts' => 'do_not_allow','read_post'),
		"map_meta_cap" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"hierarchical" => true,
		"can_export" => true,
		"query_var" => true,
		"menu_icon" => "dashicons-menu",
		'supports' => array('custom-fields' ),
		"show_in_graphql" => false,
	];

	register_post_type( "paymentrequests", $args );

}

add_filter( 'manage_paymentrequests_posts_columns', 'tk_mpesa_woo_filter_posts_columns' );
function tk_mpesa_woo_filter_posts_columns( $columns ) {
	unset( $columns['title'] );
	$columns['CheckoutRequestID'] = __( 'Request ID', 'tk-mpesa-woo' );
	$columns['DepositRef'] = __( 'Order ID', 'tk-mpesa-woo' );
	$columns['MpesaRequestNumber'] = __( 'Requesting Number', 'tk-mpesa-woo' );
	$columns['RequestAmount'] = __( 'Request Amount', 'tk-mpesa-woo' );
	$columns['PaymentStatus'] = __( 'Payment Status', 'tk-mpesa-woo' );
	return $columns;
}


function tk_mpesa_woo_paymentrequests_column( $column, $post_id ) {
	switch ($column) {

		case 'CheckoutRequestID':
		echo get_post_meta($post_id, 'CheckoutRequestID', true);
		break;

		case 'DepositRef':
		echo get_post_meta($post_id, 'DepositRef', true);
		break;

		case 'MpesaRequestNumber':
		echo get_post_meta($post_id, 'MpesaRequestNumber', true);
		break;
		case 'RequestAmount':
		echo number_format(get_post_meta($post_id, 'RequestAmount', true),2);
		break;
		case 'PaymentStatus':
		echo ucfirst(get_post_meta($post_id, 'PaymentStatus', true));
		break;
		
		default:
		echo '';
		break;
	}
}

add_action( 'manage_paymentrequests_posts_custom_column', 'tk_mpesa_woo_paymentrequests_column', 10, 2);


add_filter( 'manage_edit-paymentrequests_sortable_columns', 'smashing_realestate_sortable_columns');
function smashing_realestate_sortable_columns( $columns ) {
	$columns['DepositRef'] = 'DepositRef';
	$columns['MpesaRequestNumber'] = 'MpesaRequestNumber';
	return $columns;
}
