=== TK MPesa Payment Gateway for WooCommerce ===
Contributors: tonkigs
Tags: mpesa, woocommerce, payment, kenya, safaricom, daraja, stk push
Requires at least: 6.4
Tested up to: 6.8
Stable tag: 2.0.0
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 9.8
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

MPesa payment gateway for WooCommerce. Accepts payments via Safaricom Daraja STK Push for Paybill and Buy Goods Till.

== Description ==

TK MPesa Payment Gateway for WooCommerce allows your customers to pay directly from their mobile phone via MPesa STK Push (Lipa Na MPesa Online).

**Features:**

* MPesa STK Push integration — customers receive a payment prompt on their phone
* Supports both Paybill (CustomerPayBillOnline) and Buy Goods Till (CustomerBuyGoodsOnline)
* Compatible with the classic shortcode checkout and the WooCommerce Checkout Block
* Sandbox / test mode using the Safaricom Daraja sandbox API
* Payment request tracking via a dedicated admin panel
* HPOS (High-Performance Order Storage) compatible
* Multilingual — ships with English (UK) language files

**Requirements:**

* A Safaricom MPesa Paybill or Buy Goods Till number
* A registered Safaricom Daraja application (Consumer Key, Consumer Secret, PassKey)
* An HTTPS-enabled WordPress site (required by the MPesa callback)

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen.
3. Go to **WooCommerce → Settings → Payments**.
4. Click **Manage** on the MPesa Gateway row.
5. Check **Enable MPesa Gateway**.
6. Enter your **Pass Key**, **Consumer Key**, and **Consumer Secret** from the Daraja portal.
7. Select your **Payment Type** (Paybill or Buy Goods Till).
8. Enter your **Short Code** and **Payment To** number.
9. Enter a unique **Callback URL** name — no spaces, must not contain the word "mpesa".
10. Save changes.

**Getting MPesa Credentials:**

1. Register a Paybill or Buy Goods Till with Safaricom MPesa.
2. If using a Buy Goods Till, it must be settling funds to a bank account.
3. Register an Administrator for your Paybill/Till through Safaricom.
4. Use those credentials to create an App on the MPesa Daraja Portal.
5. Your Pass Key, Consumer Key, and Consumer Secret will be provided.

== Frequently Asked Questions ==

= Does this work with the WooCommerce Checkout Block? =

Yes. Version 2.0.0 and above fully supports the WooCommerce Checkout Block via the `AbstractPaymentMethodType` integration.

= Can I test without going live? =

Yes. Enable **Test mode** in the gateway settings to use the Safaricom Daraja sandbox API. Use sandbox credentials from the Daraja portal.

= Why must my callback URL not contain "mpesa"? =

Safaricom's systems block callback URLs containing the word "mpesa" as a security measure.

= Is this plugin PCI DSS compliant? =

Yes. No card data is handled. All payment data flows directly between the customer's phone and Safaricom's servers. The plugin only initiates an STK Push request and receives a callback confirmation.

== Changelog ==

= 2.0.0 =
* Added WooCommerce Checkout Blocks support
* Added HPOS (High-Performance Order Storage) compatibility
* Added sandbox/test mode with Daraja sandbox API URLs
* Added multilingual support with en_GB language files
* Fixed security: nonce verification on payment form
* Fixed security: input sanitisation on phone number field
* Fixed security: output escaping on all admin displays
* Fixed: broken `permission_callback` on REST callback endpoint
* Fixed: nested function declarations promoted to class methods
* Fixed: `parse_tel()` phone normalisation using regex (replaced broken `ltrim()`)
* Fixed: `date()` replaced with `gmdate()` for timezone correctness
* Replaced raw cURL with WordPress HTTP API (`wp_remote_get`/`wp_remote_post`)
* Replaced file-based logging with WooCommerce logger (WooCommerce → Status → Logs)
* Standardised text domain to `tk-mpesa-payments-for-woocommerce`
* Updated: WC tested up to 9.8, WordPress tested up to 6.8, PHP minimum 7.4

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Security and compatibility update. Adds WooCommerce Blocks support and HPOS compatibility. Fully backward compatible — existing settings, orders, and webhook URLs are unchanged.
