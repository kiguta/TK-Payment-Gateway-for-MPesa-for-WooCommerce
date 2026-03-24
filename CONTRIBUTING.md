# Contributing to TK MPesa Payment Gateway for WooCommerce

Thank you for your interest in contributing. All contributions are welcome — bug reports, feature suggestions, documentation improvements, and code.

---

## Reporting Bugs

1. Check the [existing issues](../../issues) to see if the bug has already been reported.
2. If not, open a new issue and include:
   - WordPress version, WooCommerce version, PHP version
   - Plugin version
   - Steps to reproduce the problem
   - Expected behaviour vs actual behaviour
   - Any relevant error messages or log entries (WooCommerce → Status → Logs → tk-mpesa)

---

## Suggesting Features

Open an issue with the label `enhancement` and describe:
- The problem you are trying to solve
- Your proposed solution or behaviour
- Any alternatives you have considered

---

## Submitting a Pull Request

1. Fork the repository and create a branch from `main`.
2. Make your changes following the coding standards below.
3. Test your changes against the latest WordPress and WooCommerce releases.
4. Open a pull request with a clear description of what was changed and why.

Keep pull requests focused — one feature or fix per PR makes review faster.

---

## Coding Standards

This plugin follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

- Indentation: tabs, not spaces
- All user-facing strings wrapped in `__()` / `esc_html__()` with text domain `tk-mpesa-payments-for-woocommerce`
- All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()` as appropriate
- All `$_POST` / `$_GET` input sanitised with `sanitize_text_field()` / `wp_unslash()`
- No raw `curl_*` calls — use `wp_remote_get()` / `wp_remote_post()`
- No direct file writes — use `wc_get_logger()` for logging

---

## Contact

For questions not suited to a public issue, email **tonkigs@gmail.com**.
