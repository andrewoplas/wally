# WooCommerce Extensions

## When to Use
- User asks about WooCommerce payment gateways (WooPayments, Stripe, PayPal)
- User wants to check if a payment gateway is active or configured
- User asks about payment gateway settings or test/live mode
- User wants to install a WooCommerce payment extension

## Available Tools
- `list_plugins` — check which payment gateways are installed and active
- `get_option` — read payment gateway settings
- `install_plugin` — install a gateway from WordPress.org (requires confirmation)
- `activate_plugin` — activate an installed gateway (requires confirmation)

## Workflows

### Check Which Payment Gateways Are Active
1. Call `list_plugins`
2. Look for these slugs:
   - WooPayments: `woocommerce-payments`
   - Stripe Gateway: `woocommerce-gateway-stripe`
   - PayPal Payments: `woocommerce-paypal-payments`

### Check WooPayments Settings
1. Call `get_option` with key `woocommerce_woocommerce_payments_settings`
2. Key fields: `enabled` (`yes`/`no`), `test_mode` (enabled/disabled)

### Check Stripe Gateway Settings
1. Call `get_option` with key `woocommerce_stripe_settings`
2. Key fields: `enabled` (`yes`/`no`), `testmode` (`yes`/`no`), `publishable_key`, `test_publishable_key`

### Check PayPal Payments Settings
1. Call `get_option` with key `woocommerce_ppcp-gateway_settings`
2. Check `ppcp-sandbox_on` option for sandbox vs live mode

### Install a Payment Gateway
1. Call `install_plugin` with the plugin slug (requires confirmation)
2. Call `activate_plugin` with the slug (requires confirmation)
3. Tell the user: "The gateway is installed and active. Go to **WooCommerce > Settings > Payments** to configure API keys and enable it."

## Important Notes
- Wally can check gateway status and read settings, but CANNOT configure API keys, enable/disable gateways in WooCommerce settings, or manage transactions — guide user to **WooCommerce > Settings > Payments**
- Never display or expose API keys, secret keys, or webhook secrets from option values — treat these as sensitive data
- Payment gateway configuration (adding API keys, test/live toggle) must be done through the WordPress admin UI
- Refunds, disputes, and transaction management must be handled in the payment provider's dashboard or WooCommerce > Orders
- Premium WooCommerce extensions (subscriptions, memberships, bookings) must be purchased and uploaded manually — `install_plugin` only works for free WordPress.org plugins
