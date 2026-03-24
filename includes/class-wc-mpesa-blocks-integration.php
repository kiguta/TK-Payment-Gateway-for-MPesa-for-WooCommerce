<?php
/**
 * WooCommerce Checkout Blocks integration for MPesa payment gateway.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_MPesa_Blocks_Integration extends AbstractPaymentMethodType {

	/** @var string */
	protected $name = 'tk_mpesa';

	/** @var WC_MPesa_Gateway|null */
	private $gateway;

	public function initialize(): void {
		$this->settings = get_option( 'woocommerce_tk_mpesa_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways['tk_mpesa'] ?? null;
	}

	public function is_active(): bool {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	public function get_payment_method_script_handles(): array {
		wp_register_script(
			'tk-mpesa-blocks',
			plugins_url( 'assets/js/mpesa-blocks.js', dirname( __FILE__ ) ),
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n' ),
			'2.0.0',
			true
		);
		return array( 'tk-mpesa-blocks' );
	}

	public function get_payment_method_data(): array {
		return array(
			'title'       => $this->gateway ? $this->gateway->title : '',
			'description' => $this->gateway ? $this->gateway->description : '',
			'supports'    => array( 'products' ),
		);
	}
}
