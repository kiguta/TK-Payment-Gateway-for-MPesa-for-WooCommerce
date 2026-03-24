<?php
/**
 * Plugin Name: TK MPesa Payment Gateway for WooCommerce
 * Plugin URI: https://ziprof.co.ke/
 * Description: MPesa payment gateway for WooCommerce via Safaricom Daraja STK Push.
 * Version: 2.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Tonie Kiguta
 * Author URI: mailto:tonkigs@gmail.com
 * Text Domain: tk-mpesa-payments-for-woocommerce
 * Domain Path: /languages
 * WC requires at least: 6.0
 * WC tested up to: 9.8
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// HPOS compatibility declaration.
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

// Checkout Blocks integration.
add_action( 'woocommerce_blocks_loaded', function() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-mpesa-blocks-integration.php';
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function( $registry ) {
			$registry->register( new WC_MPesa_Blocks_Integration() );
		}
	);
} );

// Register gateway class with WooCommerce.
add_filter( 'woocommerce_payment_gateways', 'tk_add_mpesa_gateway_class' );
function tk_add_mpesa_gateway_class( $gateways ) {
	$gateways[] = 'WC_MPesa_Gateway';
	return $gateways;
}

// Load text domain.
add_action( 'init', 'tk_mpesa_load_textdomain' );
function tk_mpesa_load_textdomain() {
	load_plugin_textdomain(
		'tk-mpesa-payments-for-woocommerce',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}

// Hooks.
add_action( 'plugins_loaded', 'mpesa_init_gateway_class' );
add_action( 'woocommerce_checkout_update_order_meta', 'tk_woo_checkout_update_order_meta', 10, 1 );
add_action( 'woocommerce_admin_order_data_after_billing_address', 'tk_woo_order_data_after_billing_address', 10, 1 );
add_action( 'rest_api_init', 'tk_woo_add_callback_url_endpoint' );
add_action( 'init', 'add_tk_woo_post_type' );

function mpesa_init_gateway_class() {

	class WC_MPesa_Gateway extends WC_Payment_Gateway {

		/** @var bool */
		public $testmode;

		public function __construct() {
			$this->id                 = 'tk_mpesa';
			$this->icon               = apply_filters( 'woocommerce_mpesa_icon', plugins_url( '/assets/mpesa_icon.png', __FILE__ ) );
			$this->has_fields         = true;
			$this->method_title       = __( 'MPesa Gateway', 'tk-mpesa-payments-for-woocommerce' );
			$this->method_description = __( 'Receive payments from your customers via MPesa.', 'tk-mpesa-payments-for-woocommerce' );
			$this->supports           = array( 'products' );

			$this->init_form_fields();
			$this->init_settings();

			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->enabled      = $this->get_option( 'enabled' );
			$this->testmode     = 'yes' === $this->get_option( 'testmode' );

			// MPesa parameters.
			$this->customer_key  = $this->get_option( 'customer_key' );
			$this->customer_pass = $this->get_option( 'customer_pass' );
			$this->short_code    = $this->get_option( 'short_code' );
			$this->pay_to        = $this->get_option( 'pay_to' );
			$this->payment_type  = $this->get_option( 'payment_type' );
			$this->pass_key      = $this->get_option( 'pass_key' );
			$this->callback_url  = $this->get_option( 'callback_url' );

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'      => array(
					'title'   => __( 'Enable/Disable', 'tk-mpesa-payments-for-woocommerce' ),
					'label'   => __( 'Enable MPesa Gateway', 'tk-mpesa-payments-for-woocommerce' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				'testmode'     => array(
					'title'       => __( 'Test mode', 'tk-mpesa-payments-for-woocommerce' ),
					'label'       => __( 'Enable Daraja Sandbox', 'tk-mpesa-payments-for-woocommerce' ),
					'type'        => 'checkbox',
					'default'     => 'no',
					'description' => __( 'Use the Safaricom sandbox API for testing. Disable for live transactions.', 'tk-mpesa-payments-for-woocommerce' ),
				),
				'title'        => array(
					'title'       => __( 'Title', 'tk-mpesa-payments-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'tk-mpesa-payments-for-woocommerce' ),
					'default'     => __( 'MPesa Payments', 'tk-mpesa-payments-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'description'  => array(
					'title'       => __( 'Description', 'tk-mpesa-payments-for-woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'tk-mpesa-payments-for-woocommerce' ),
					'default'     => __( 'A payment request will be sent to the payment MPesa number you will provide below. Please ensure your phone is ON and Unlocked', 'tk-mpesa-payments-for-woocommerce' ),
				),
				'customer_key' => array(
					'title' => __( 'App Consumer Key', 'tk-mpesa-payments-for-woocommerce' ),
					'type'  => 'text',
				),
				'customer_pass' => array(
					'title' => __( 'App Consumer Secret', 'tk-mpesa-payments-for-woocommerce' ),
					'type'  => 'password',
				),
				'pass_key'     => array(
					'title' => __( 'Pass Key', 'tk-mpesa-payments-for-woocommerce' ),
					'type'  => 'text',
				),
				'payment_type' => array(
					'title'       => __( 'Payment Type', 'tk-mpesa-payments-for-woocommerce' ),
					'type'        => 'select',
					'options'     => array(
						'none'                    => __( 'Select Payment Type', 'tk-mpesa-payments-for-woocommerce' ),
						'CustomerPayBillOnline'   => 'CustomerPayBillOnline',
						'CustomerBuyGoodsOnline'  => 'CustomerBuyGoodsOnline',
					),
					'description' => __( 'CustomerPayBillOnline if using Paybill or CustomerBuyGoodsOnline if using a Till Number', 'tk-mpesa-payments-for-woocommerce' ),
				),
				'short_code'   => array(
					'title'       => __( 'Short Code', 'tk-mpesa-payments-for-woocommerce' ),
					'description' => __( 'This is Paybill Number for Paybill or Head Office Number for Till', 'tk-mpesa-payments-for-woocommerce' ),
					'type'        => 'text',
				),
				'pay_to'       => array(
					'title'       => __( 'Payment to', 'tk-mpesa-payments-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This is the Paybill Number / Till Number', 'tk-mpesa-payments-for-woocommerce' ),
				),
				'callback_url' => array(
					'title'       => __( 'Callback URL', 'tk-mpesa-payments-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'The endpoint where MPesa will send payment notifications. Enter a unique name without spaces. Must not contain the word "mpesa".', 'tk-mpesa-payments-for-woocommerce' ),
				),
			);
		}

		public function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wp_kses_post( $this->description ) );
			}

			wp_nonce_field( 'tk_mpesa_payment', 'tk_mpesa_nonce' );

			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form">';

			do_action( 'woocommerce_credit_card_form_start', $this->id );

			echo '<div class="form-row form-row-wide">
				<label>' . esc_html__( 'MPesa Phone Number', 'tk-mpesa-payments-for-woocommerce' ) . ' <span class="required">*</span></label>
				<input id="tk-mpesa-phone" name="phonenumber" type="text" autocomplete="off" required="required" style="width:100%;height:30px;" placeholder="0XXX XXX XXX">
				<div class="clear"></div>
			</div>';

			do_action( 'woocommerce_credit_card_form_end', $this->id );

			echo '<div class="clear"></div></fieldset>';
		}

		public function payment_scripts() {
			// Intentionally empty — classic checkout requires no additional scripts.
		}

		public function validate_fields() {
			if ( ! isset( $_POST['tk_mpesa_nonce'] )
				|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tk_mpesa_nonce'] ) ), 'tk_mpesa_payment' )
			) {
				wc_add_notice( __( 'Security check failed. Please refresh and try again.', 'tk-mpesa-payments-for-woocommerce' ), 'error' );
				return false;
			}

			if ( empty( $_POST['phonenumber'] ) ) {
				wc_add_notice( __( 'MPesa Phone Number is required!', 'tk-mpesa-payments-for-woocommerce' ), 'error' );
				return false;
			}

			return true;
		}

		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			// Support both classic checkout ($_POST) and Blocks checkout (payment_method_data).
			$raw_phone = '';
			if ( ! empty( $_POST['phonenumber'] ) ) {
				$raw_phone = sanitize_text_field( wp_unslash( $_POST['phonenumber'] ) );
			} elseif ( isset( $this->payment_method_data['phonenumber'] ) ) {
				$raw_phone = sanitize_text_field( $this->payment_method_data['phonenumber'] );
			}

			$payment_args = array(
				'customer_key'   => $this->customer_key,
				'customer_pass'  => $this->customer_pass,
				'short_code'     => $this->short_code,
				'payment_type'   => $this->payment_type,
				'pass_key'       => $this->pass_key,
				'callbackurl'    => get_site_url( null, '/wp-json/tk_woopesa/v1/' . $this->callback_url, 'https' ),
				'payment_amount' => (int) ceil( $order->get_total() ),
				'tel'            => $this->parse_tel( $raw_phone ),
				'ref'            => $order->get_id(),
				'pay_to'         => $this->pay_to,
			);

			$response = $this->stk_push( $payment_args );

			if ( is_wp_error( $response ) || isset( $response->errorMessage ) ) {
				$error_msg = is_wp_error( $response ) ? $response->get_error_message() : $response->errorMessage;
				wc_add_notice( __( 'MPesa payment request failed. Please try again.', 'tk-mpesa-payments-for-woocommerce' ), 'error' );
				$this->log( 'STK Push error: ' . $error_msg );
				return;
			}

			$order->update_status( 'pending', __( 'Awaiting MPesa Payment Confirmation', 'tk-mpesa-payments-for-woocommerce' ) );
			wc_reduce_stock_levels( $order->get_id() );
			WC()->cart->empty_cart();

			$post_id = wp_insert_post( array(
				'post_title'   => $order->get_id(),
				'post_content' => wp_json_encode( $response ),
				'post_status'  => 'draft',
				'post_type'    => 'paymentrequests',
			) );

			update_post_meta( $post_id, 'MerchantRequestID',  $response->MerchantRequestID );
			update_post_meta( $post_id, 'CheckoutRequestID',  $response->CheckoutRequestID );
			update_post_meta( $post_id, 'ResponseCode',       $response->ResponseCode );
			update_post_meta( $post_id, 'ResponseDescription', $response->ResponseDescription );
			update_post_meta( $post_id, 'CustomerMessage',    $response->CustomerMessage );
			update_post_meta( $post_id, 'DepositRef',         $order->get_id() );
			update_post_meta( $post_id, 'RequestAmount',      (int) ceil( $order->get_total() ) );
			update_post_meta( $post_id, 'MpesaRequestNumber', $payment_args['tel'] );
			update_post_meta( $post_id, 'PaymentStatus',      'pending' );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		public function webhook() {}

		// -------------------------------------------------------------------------
		// Private helpers
		// -------------------------------------------------------------------------

		private function generate_token( array $payment_args ): string {
			$credentials = base64_encode( $payment_args['customer_key'] . ':' . $payment_args['customer_pass'] );
			$url = $this->testmode
				? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
				: 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

			$response = wp_remote_get( $url, array(
				'headers' => array( 'Authorization' => 'Basic ' . $credentials ),
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				$this->log( 'Token error: ' . $response->get_error_message() );
				return '';
			}

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			return isset( $body->access_token ) ? $body->access_token : '';
		}

		private function stk_push( array $payment_args ) {
			$token = $this->generate_token( $payment_args );

			if ( empty( $token ) ) {
				return new WP_Error( 'token_error', __( 'Could not authenticate with MPesa API.', 'tk-mpesa-payments-for-woocommerce' ) );
			}

			$url = $this->testmode
				? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
				: 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

			$timestamp = gmdate( 'YmdHis' );
			$password  = base64_encode( $payment_args['short_code'] . $payment_args['pass_key'] . $timestamp );

			$body = array(
				'BusinessShortCode' => $payment_args['short_code'],
				'Password'          => $password,
				'Timestamp'         => $timestamp,
				'TransactionType'   => $payment_args['payment_type'],
				'Amount'            => $payment_args['payment_amount'],
				'PartyA'            => $payment_args['tel'],
				'PartyB'            => $payment_args['pay_to'],
				'PhoneNumber'       => $payment_args['tel'],
				'CallBackURL'       => $payment_args['callbackurl'],
				'AccountReference'  => $payment_args['ref'],
				'TransactionDesc'   => 'stk',
			);

			$response = wp_remote_post( $url, array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			return json_decode( wp_remote_retrieve_body( $response ) );
		}

		private function parse_tel( string $tel ): string {
			$tel = preg_replace( '/\s+/', '', $tel );
			$tel = preg_replace( '/^\+?254/', '', $tel );
			$tel = ltrim( $tel, '0' );
			return '254' . $tel;
		}

		private function log( string $message ): void {
			wc_get_logger()->info( $message, array( 'source' => 'tk-mpesa' ) );
		}
	}
}

// Save MPesa phone number to order meta.
function tk_woo_checkout_update_order_meta( $order_id ) {
	if ( isset( $_POST['phonenumber'] ) && ! empty( $_POST['phonenumber'] ) ) {
		update_post_meta( $order_id, 'phonenumber', sanitize_text_field( wp_unslash( $_POST['phonenumber'] ) ) );
	}
}

// Display MPesa phone number in admin order view.
function tk_woo_order_data_after_billing_address( $order ) {
	echo '<p><strong>' . esc_html__( 'Billed MPesa Number:', 'tk-mpesa-payments-for-woocommerce' ) . '</strong><br>'
		. esc_html( get_post_meta( $order->get_id(), 'phonenumber', true ) ) . '</p>';
}

// Register the MPesa callback REST endpoint.
function tk_woo_add_callback_url_endpoint() {
	$payment_gateway = WC()->payment_gateways->payment_gateways()['tk_mpesa'] ?? null;
	if ( $payment_gateway && ! empty( $payment_gateway->callback_url ) ) {
		register_rest_route(
			'tk_woopesa/v1',
			$payment_gateway->callback_url,
			array(
				'methods'             => 'POST',
				'callback'            => 'tk_woo_receive_callback',
				'permission_callback' => '__return_true',
			)
		);
	}
}

// Handle MPesa STK callback.
function tk_woo_receive_callback( $request_data ) {
	$parameters = $request_data->get_params();

	$logger = wc_get_logger();
	$logger->info( wp_json_encode( $parameters ), array( 'source' => 'tk-mpesa' ) );

	$MerchantRequestID = isset( $parameters['Body']['stkCallback']['MerchantRequestID'] ) ? $parameters['Body']['stkCallback']['MerchantRequestID'] : null;
	$CheckoutRequestID = isset( $parameters['Body']['stkCallback']['CheckoutRequestID'] ) ? $parameters['Body']['stkCallback']['CheckoutRequestID'] : null;
	$ResultCode        = isset( $parameters['Body']['stkCallback']['ResultCode'] )        ? $parameters['Body']['stkCallback']['ResultCode']        : null;
	$ResultDesc        = isset( $parameters['Body']['stkCallback']['ResultDesc'] )        ? $parameters['Body']['stkCallback']['ResultDesc']        : null;

	$CallbackMetadata   = null;
	$Amount             = null;
	$MpesaReceiptNumber = null;
	$Balance            = null;
	$TransactionDate    = null;
	$PhoneNumber        = null;

	if ( isset( $parameters['Body']['stkCallback']['CallbackMetadata'] ) ) {
		$CallbackMetadata   = $parameters['Body']['stkCallback']['CallbackMetadata'];
		$Amount             = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['0']['Value'];
		$MpesaReceiptNumber = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['1']['Value'];
		$Balance            = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['2']['Value'];
		$TransactionDate    = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['3']['Value'];
		$PhoneNumber        = $parameters['Body']['stkCallback']['CallbackMetadata']['Item']['4']['Value'];
	}

	if ( ! isset( $CheckoutRequestID ) ) {
		return 'Invalid';
	}

	$requestpost = get_posts( array(
		'post_type'   => 'paymentrequests',
		'post_status' => 'draft',
		'numberposts' => 1,
		'meta_query'  => array(
			array( 'key' => 'CheckoutRequestID', 'value' => $CheckoutRequestID ),
			array( 'key' => 'PaymentStatus',     'value' => 'pending' ),
		),
	) );

	if ( empty( $requestpost ) ) {
		return 'Invalid';
	}

	$post_id       = $requestpost[0]->ID;
	$RequestAmount = get_post_meta( $post_id, 'RequestAmount', true );
	$orderID       = get_post_meta( $post_id, 'DepositRef', true );

	if ( ! isset( $CallbackMetadata ) ) {
		update_post_meta( $post_id, 'ResultCode',    $ResultCode );
		update_post_meta( $post_id, 'ResultDesc',    $ResultDesc );
		update_post_meta( $post_id, 'PaymentStatus', 'cancelled' );
		wp_trash_post( $post_id );

		$order = wc_get_order( $orderID );
		if ( $order ) {
			$order->update_status( 'cancelled', __( 'Payment process not completed.', 'tk-mpesa-payments-for-woocommerce' ) );
		}
	} else {
		update_post_meta( $post_id, 'ResultCode',         $ResultCode );
		update_post_meta( $post_id, 'ResultDesc',         $ResultDesc );
		update_post_meta( $post_id, 'Amount',             $Amount );
		update_post_meta( $post_id, 'MpesaReceiptNumber', $MpesaReceiptNumber );
		update_post_meta( $post_id, 'TransactionDate',    $TransactionDate );
		update_post_meta( $post_id, 'PhoneNumber',        $PhoneNumber );
		update_post_meta( $post_id, 'Balance',            $Balance );

		if ( $RequestAmount == $Amount ) {
			update_post_meta( $post_id, 'PaymentStatus', 'Paid' );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );

			$order = wc_get_order( $orderID );
			if ( $order ) {
				/* translators: %s: MPesa receipt number */
				$order->update_status( 'completed', sprintf( __( 'Paid via MPesa: %s.', 'tk-mpesa-payments-for-woocommerce' ), $MpesaReceiptNumber ) );
			}
		}
	}

	return 'Updated';
}

// Register the payment requests custom post type.
function add_tk_woo_post_type() {
	$labels = array(
		'name'          => __( 'MPesa Payments', 'tk-mpesa-payments-for-woocommerce' ),
		'singular_name' => __( 'MPesa Payment', 'tk-mpesa-payments-for-woocommerce' ),
	);

	$args = array(
		'label'                 => __( 'MPesa Payments', 'tk-mpesa-payments-for-woocommerce' ),
		'labels'                => $labels,
		'public'                => true,
		'publicly_queryable'    => false,
		'show_ui'               => true,
		'show_in_rest'          => true,
		'has_archive'           => false,
		'show_in_menu'          => true,
		'capabilities'          => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'          => true,
		'show_in_nav_menus'     => false,
		'delete_with_user'      => false,
		'exclude_from_search'   => true,
		'capability_type'       => 'post',
		'hierarchical'          => false,
		'can_export'            => true,
		'query_var'             => true,
		'menu_icon'             => 'dashicons-menu',
		'supports'              => array( 'custom-fields' ),
		'show_in_graphql'       => false,
	);

	register_post_type( 'paymentrequests', $args );
}

// Admin columns for the payment requests CPT.
add_filter( 'manage_paymentrequests_posts_columns', 'tk_mpesa_woo_filter_posts_columns' );
function tk_mpesa_woo_filter_posts_columns( $columns ) {
	unset( $columns['title'] );
	$columns['CheckoutRequestID']  = __( 'Request ID',       'tk-mpesa-payments-for-woocommerce' );
	$columns['DepositRef']         = __( 'Order ID',         'tk-mpesa-payments-for-woocommerce' );
	$columns['MpesaRequestNumber'] = __( 'Requesting Number','tk-mpesa-payments-for-woocommerce' );
	$columns['RequestAmount']      = __( 'Request Amount',   'tk-mpesa-payments-for-woocommerce' );
	$columns['PaymentStatus']      = __( 'Payment Status',   'tk-mpesa-payments-for-woocommerce' );
	return $columns;
}

add_action( 'manage_paymentrequests_posts_custom_column', 'tk_mpesa_woo_paymentrequests_column', 10, 2 );
function tk_mpesa_woo_paymentrequests_column( $column, $post_id ) {
	switch ( $column ) {
		case 'CheckoutRequestID':
			echo esc_html( get_post_meta( $post_id, 'CheckoutRequestID', true ) );
			break;
		case 'DepositRef':
			echo esc_html( get_post_meta( $post_id, 'DepositRef', true ) );
			break;
		case 'MpesaRequestNumber':
			echo esc_html( get_post_meta( $post_id, 'MpesaRequestNumber', true ) );
			break;
		case 'RequestAmount':
			echo esc_html( number_format( (float) get_post_meta( $post_id, 'RequestAmount', true ), 2 ) );
			break;
		case 'PaymentStatus':
			echo esc_html( ucfirst( get_post_meta( $post_id, 'PaymentStatus', true ) ) );
			break;
		default:
			echo '';
			break;
	}
}

add_filter( 'manage_edit-paymentrequests_sortable_columns', 'tk_mpesa_woo_sortable_columns' );
function tk_mpesa_woo_sortable_columns( $columns ) {
	$columns['DepositRef']         = 'DepositRef';
	$columns['MpesaRequestNumber'] = 'MpesaRequestNumber';
	return $columns;
}
