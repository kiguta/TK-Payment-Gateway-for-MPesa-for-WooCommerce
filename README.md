# TK Payment Gateway for MPesa for WooCommerce

MPesa WooCommerce payment gateway that allows customers to pay directly from their mobile phone via MPesa STK Push (Lipa Na MPesa Online). Supports both Paybill and Buy Goods Till, the classic shortcode checkout, and the WooCommerce Checkout Block.

##### Requires at least: Wordpress 6.4

##### Tested up to: Wordpress 6.8

##### Requires PHP: 7.4

##### WooCommerce requires at least: 6.0

##### WooCommerce tested up to: 9.8

---

### What's New in v2.0.0

- **WooCommerce Checkout Blocks support** — fully compatible with the modern block-based checkout
- **HPOS (High-Performance Order Storage) compatible** — declared compatible with WooCommerce's high-performance order tables (enabled by default in WooCommerce 8.2+)
- **Sandbox / test mode** — toggle between Safaricom Daraja sandbox and live APIs without changing credentials
- **Security improvements** — nonce verification on payment form, input sanitisation, output escaping throughout
- **Multilingual support** — ships with English (UK) language files; ready for translation via translate.wordpress.org
- **Better error handling** — customer-facing notices on payment failure; errors logged to WooCommerce logs
- **WooCommerce logger integration** — replaces file-based logging; view logs at WooCommerce → Status → Logs → tk-mpesa
- **WordPress HTTP API** — replaced raw cURL calls with `wp_remote_get` / `wp_remote_post` for better compatibility and testability
- **PHP 7.4+ required** — dropped support for end-of-life PHP versions

---

### Features

- MPesa STK Push integration — customers receive a payment prompt directly on their phone
- Supports both **Paybill** (CustomerPayBillOnline) and **Buy Goods Till** (CustomerBuyGoodsOnline)
- Compatible with the classic shortcode checkout and the WooCommerce Checkout Block
- Sandbox / test mode for development using the Safaricom Daraja sandbox
- Payment request tracking via a dedicated admin panel (WP Admin → MPesa Payments)

---

### Getting MPesa Credentials

1. Register a Paybill or Buy Goods Till with Safaricom MPesa
2. If using a Buy Goods Till, it must be settling funds to a bank account
3. Once ready, register an Administrator for your Paybill/Till through Safaricom
4. Use the registered Administrator credentials to create an App on the [MPesa Daraja Portal](https://developer.safaricom.co.ke)
5. The following credentials will be provided: **PassKey**, **Consumer Key**, and **Consumer Secret**

---

### Setting Up

1. Install and activate the plugin
2. Navigate to **WooCommerce → Settings → Payments**
3. Click **Manage** on the MPesa Gateway row
4. Check **Enable MPesa Gateway**
5. Add your **PassKey**, **Consumer Key**, and **Consumer Secret**
6. Select the payment type (Paybill or Buy Goods Till)
7. Enter your **Short Code** and **Payment To** number
8. Set a unique **Callback URL** name — no spaces, must not contain the word "mpesa"
9. Save changes

> **Test mode:** Enable the **Test mode** checkbox to use the Safaricom Daraja sandbox API. Disable for live transactions.

---

### Requirements

- WordPress 6.4 or higher
- WooCommerce 6.0 or higher
- PHP 7.4 or higher
- HTTPS-enabled site (required by the MPesa callback)
- Active Safaricom MPesa Daraja API application

---

### Changelog

#### 2.0.0

- Added WooCommerce Checkout Blocks support (`AbstractPaymentMethodType` integration)
- Added HPOS compatibility declaration
- Added sandbox/test mode with Daraja sandbox API URLs
- Added multilingual support with `en_GB` language files
- Fixed: nonce verification on payment form submission
- Fixed: input sanitisation on phone number field
- Fixed: output escaping on all admin column and order meta displays
- Fixed: broken `permission_callback` on REST callback endpoint
- Fixed: nested function declarations inside `process_payment()` — promoted to class methods
- Fixed: `parse_tel()` phone normalisation (replaced broken `ltrim()` with regex)
- Fixed: `date()` replaced with `gmdate()` for timezone correctness
- Replaced raw cURL with WordPress HTTP API (`wp_remote_get` / `wp_remote_post`)
- Replaced file-based logging (`StkRequests.txt`) with WooCommerce logger
- Standardised text domain to `tk-mpesa-payments-for-woocommerce` throughout
- Updated: WC tested up to 9.8, WordPress tested up to 6.8, PHP minimum 7.4

#### 1.0.0

- Initial release

---

#### Donate

[PayPal](https://www.paypal.com/donate/?hosted_button_id=CSQFKDWQZVE4W)

##### MPesa Donation: 254725682556
