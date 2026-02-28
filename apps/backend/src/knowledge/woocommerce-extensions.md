## WooCommerce Payment & Extension Plugins

### WooPayments (woocommerce-payments)
- **Plugin slug**: `woocommerce-payments/woocommerce-payments.php`. Gateway ID: `woocommerce_payments`.
- **Settings**: wp_options key `woocommerce_woocommerce_payments_settings` (serialized array). Key sub-keys: `enabled`, `title`, `description`, `payment_request`, `payment_request_button_type`, `payment_request_button_theme`, `payment_request_button_size`, `payment_request_button_locations`, `upe_enabled_payment_method_ids`, `is_multi_currency_enabled`.
- **Jetpack Connection**: Requires active Jetpack connection to WordPress.com. Connection state stored in options: `jetpack_connection_active_plugins`, `jetpack_options`, `woocommerce_payments_account_id`. The plugin communicates with the WooCommerce Payments server (hosted by Automattic/Stripe) via the Jetpack connection tunnel.
- **Database tables**:
  - `{prefix}wc_payments_order_intent` — maps WC orders to Stripe PaymentIntent IDs
  - `{prefix}woocommerce_payment_tokens` — stored payment methods (tokenized cards)
  - `{prefix}wc_payments_fraud_outcomes` — fraud screening results
- **Order meta keys**:
  - `_wcpay_intent_id` — Stripe PaymentIntent ID (pi_xxx)
  - `_wcpay_charge_id` — Stripe Charge ID (ch_xxx)
  - `_wcpay_payment_method_id` — Stripe PaymentMethod ID (pm_xxx)
  - `_wcpay_mode` — `live` or `test`
  - `_wcpay_transaction_fee` — Stripe processing fee amount
  - `_wcpay_multi_currency_order_exchange_rate` — exchange rate for multi-currency orders
  - `_wcpay_fraud_outcome` — fraud detection result (`allow`, `review`, `block`)
- **Fraud protection**: Settings stored in `wcpay_fraud_protection_settings` option. Configurable rules: international IP address check, order velocity check, purchase price threshold, address mismatch, IP country check. Levels: `standard`, `high`, `advanced`.
- **Multi-currency**: Option `wcpay_multi_currency_enabled_currencies` stores enabled currencies. Exchange rates cached in transients prefixed `wcpay_multi_currency_exchange_rate_`.
- **Subscriptions support**: When WooCommerce Subscriptions is active, WooPayments supports recurring payments, subscription renewal, and payment method updates. Subscription meta: `_wcpay_subscription_id`.
- **Key hooks**:
  ```php
  // After successful payment processing
  do_action( 'woocommerce_payments_payment_complete', $order, $intent_id );

  // Before payment intent creation
  apply_filters( 'wcpay_payment_request_data', $data, $order );

  // Fraud outcome filter
  apply_filters( 'wcpay_fraud_protection_settings', $settings );

  // Multi-currency rate filter
  apply_filters( 'wcpay_multi_currency_exchange_rate', $rate, $from_currency, $to_currency );

  // Payment gateway availability
  apply_filters( 'woocommerce_available_payment_gateways', $gateways );
  ```
- **Key classes**: `WC_Payment_Gateway_WCPay` (main gateway class), `WC_Payments_Account` (Stripe account management), `WC_Payments_API_Client` (API communication), `WC_Payments_Order_Service` (order handling).
- **In-person payments**: Terminal settings in `woocommerce_woocommerce_payments_settings` under `is_in_person_payments_enabled`. Location ID stored in `wcpay_terminal_location_id`.
- **Test mode**: Controlled by `test_mode` key in settings. When enabled, uses Stripe test keys and all transactions are simulated. Test mode indicator stored in `wcpay_test_mode`.
- **Detection**:
  ```php
  class_exists( 'WC_Payments' ) // true if WooPayments is active
  defined( 'WCPAY_VERSION_NUMBER' ) // version check
  ```

### WooCommerce Stripe Gateway (woocommerce-gateway-stripe)
- **Plugin slug**: `woocommerce-gateway-stripe/woocommerce-gateway-stripe.php`. Gateway ID: `stripe`.
- **Settings**: wp_options key `woocommerce_stripe_settings` (serialized array). Key sub-keys: `enabled`, `title`, `description`, `testmode`, `test_publishable_key`, `test_secret_key`, `publishable_key`, `secret_key`, `webhook_secret`, `test_webhook_secret`, `capture`, `payment_request`, `saved_cards`, `logging`.
- **Payment methods**: Supports multiple gateway IDs for different payment methods:
  - `stripe` — Credit/debit cards (main gateway)
  - `stripe_sepa` — SEPA Direct Debit
  - `stripe_ideal` — iDEAL (Netherlands)
  - `stripe_bancontact` — Bancontact (Belgium)
  - `stripe_giropay` — Giropay (Germany)
  - `stripe_sofort` — SOFORT/Klarna
  - `stripe_p24` — Przelewy24 (Poland)
  - `stripe_eps` — EPS (Austria)
  - `stripe_boleto` — Boleto (Brazil)
  - `stripe_oxxo` — OXXO (Mexico)
  - `stripe_alipay` — Alipay
  - `stripe_multibanco` — Multibanco (Portugal)
  - Each has its own settings key: `woocommerce_stripe_{method}_settings`
- **Payment Element / UPE**: Unified Payment Element enabled via `payment_request` and `upe_checkout_experience_enabled` in settings. When enabled, renders Stripe's Payment Element instead of individual card fields.
- **Order meta keys**:
  - `_stripe_intent_id` — Stripe PaymentIntent ID
  - `_stripe_charge_id` — Stripe Charge ID
  - `_stripe_customer_id` — Stripe Customer ID (cus_xxx)
  - `_stripe_source_id` — Stripe Source/PaymentMethod ID
  - `_stripe_currency` — payment currency
  - `_stripe_fee` — Stripe fee amount
  - `_stripe_net` — net amount after fees
- **Saved cards**: Stored in `{prefix}woocommerce_payment_tokens` table. Token type `CC` with gateway `stripe`. Customer ID in usermeta key `wp_stripe_customer_id` (live) and `wp_stripe_customer_id_test` (test).
- **Webhooks**: Endpoint registered at `/?wc-api=wc_stripe`. Required webhook events: `charge.succeeded`, `charge.failed`, `charge.captured`, `charge.dispute.created`, `charge.refunded`, `payment_intent.succeeded`, `payment_intent.payment_failed`, `review.opened`, `review.closed`, `source.chargeable`, `checkout.session.completed`. Webhook secret stored in `webhook_secret` or `test_webhook_secret` settings.
- **SCA/3DS**: Strong Customer Authentication handled automatically. PaymentIntents API used for 3D Secure confirmation. Client-side confirmation via `stripe.confirmCardPayment()` or `stripe.handleCardAction()`.
- **Test mode**: Controlled by `testmode` key in settings (`yes`/`no`). When enabled, uses `test_publishable_key` and `test_secret_key`.
- **Key hooks**:
  ```php
  // Filter payment intent arguments before creation
  apply_filters( 'wc_stripe_payment_intent_args', $args, $order );

  // After payment is completed
  do_action( 'wc_gateway_stripe_process_payment', $order, $intent );

  // Filter Stripe customer data
  apply_filters( 'wc_stripe_customer_data', $customer_data, $order );

  // Filter payment metadata sent to Stripe
  apply_filters( 'wc_stripe_payment_metadata', $metadata, $order );

  // Modify Stripe API request
  apply_filters( 'wc_stripe_request_body', $request, $api );

  // After webhook event processed
  do_action( 'wc_gateway_stripe_process_webhook_payment', $notification );

  // Control payment method availability
  apply_filters( 'wc_stripe_allowed_payment_processing_statuses', $statuses );
  ```
- **Key classes**: `WC_Gateway_Stripe` (main gateway), `WC_Stripe_API` (Stripe API wrapper), `WC_Stripe_Customer` (customer management), `WC_Stripe_Webhook_Handler` (webhook processing), `WC_Stripe_Intent_Controller` (PaymentIntent handling).
- **Logging**: When `logging` is enabled in settings, logs written to WooCommerce log files at `wp-content/uploads/wc-logs/stripe-*.log`. Viewable at WooCommerce > Status > Logs.
- **Detection**:
  ```php
  class_exists( 'WC_Stripe' ) // true if Stripe Gateway is active
  defined( 'WC_STRIPE_VERSION' ) // version check
  ```

### WooCommerce PayPal Payments (woocommerce-paypal-payments)
- **Plugin slug**: `woocommerce-paypal-payments/woocommerce-paypal-payments.php`. Gateway ID: `ppcp-gateway` (PayPal), `ppcp-credit-card-gateway` (card fields).
- **Settings**: wp_options keys:
  - `woocommerce_ppcp-gateway_settings` — main PayPal gateway settings (serialized)
  - `woocommerce_ppcp-credit-card-gateway_settings` — card fields gateway settings
  - Key sub-keys: `enabled`, `title`, `description`, `intent` (`capture` or `authorize`), `button_layout`, `button_color`, `button_shape`, `button_label`, `pay_later_messaging_enabled`, `vaulting_enabled`, `3d_secure_contingency`
- **Onboarding**: Uses PayPal's onboarding flow. Merchant credentials stored in:
  - `ppcp-onboarding-client_id` — PayPal client ID
  - `ppcp-onboarding-client_secret` — PayPal client secret (encrypted)
  - `ppcp-onboarding-merchant_id` — PayPal merchant ID
  - `ppcp-onboarding-merchant_email` — PayPal merchant email
  - `ppcp-sandbox_on` — sandbox mode toggle (1/0)
  - `ppcp-onboarding-sandbox-client_id` — sandbox client ID
  - `ppcp-onboarding-sandbox-client_secret` — sandbox client secret
- **Order meta keys**:
  - `_ppcp_paypal_order_id` — PayPal Order ID
  - `_ppcp_paypal_intent` — capture or authorize
  - `_ppcp_paypal_payment_mode` — `live` or `sandbox`
  - `_ppcp_paypal_fees` — PayPal fee amount
  - `_ppcp_paypal_transaction_id` — PayPal transaction/capture ID
  - `_ppcp_paypal_refund_id` — PayPal refund ID
- **Payment methods**: Supports multiple payment types:
  - PayPal Checkout (PayPal button)
  - Pay Later / Pay in 4 (installment messaging)
  - Venmo (US only)
  - Credit/Debit Card fields (Advanced Card Processing, requires PCI SAQ-A)
  - Alternative payment methods (BLIK, Bancontact, eps, iDEAL, MyBank, Przelewy24, SEPA — varies by region)
- **Webhooks**: Registered via PayPal REST API during onboarding. Webhook URL: `/?wc-api=ppcp-webhook`. Webhook ID stored in `ppcp-webhook-id` option. Key events: `CHECKOUT.ORDER.APPROVED`, `CHECKOUT.ORDER.COMPLETED`, `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.REFUNDED`, `PAYMENT.CAPTURE.DENIED`, `CUSTOMER.DISPUTE.CREATED`, `CUSTOMER.DISPUTE.RESOLVED`, `VAULT.PAYMENT-TOKEN.CREATED`.
- **Vaulting (saved payment methods)**: When `vaulting_enabled` is `yes`, PayPal tokens stored in `{prefix}woocommerce_payment_tokens` table with gateway `ppcp-gateway` or `ppcp-credit-card-gateway`. PayPal Vault token meta stored in usermeta.
- **Pay Later messaging**: Controlled by `pay_later_messaging_enabled`, `pay_later_messaging_product_enabled`, `pay_later_messaging_cart_enabled`, `pay_later_messaging_checkout_enabled` in settings. Displays financing options to customers.
- **Key hooks**:
  ```php
  // Filter PayPal order creation arguments
  apply_filters( 'woocommerce_paypal_payments_create_order_request', $data, $order );

  // After PayPal payment captured
  do_action( 'woocommerce_paypal_payments_order_captured', $order, $capture_id );

  // Filter button rendering
  apply_filters( 'woocommerce_paypal_payments_button_render_data', $data );

  // Control Pay Later messaging
  apply_filters( 'woocommerce_paypal_payments_pay_later_enabled', $enabled );

  // After webhook processed
  do_action( 'woocommerce_paypal_payments_webhook_handled', $event, $order );

  // Filter vaulting availability
  apply_filters( 'woocommerce_paypal_payments_vaulting_enabled', $enabled );
  ```
- **Key classes**: `PayPalGateway` (main gateway), `CreditCardGateway` (card fields gateway), `OrderEndpoint` (PayPal Orders API), `PaymentsEndpoint` (PayPal Payments API), `WebhookEndpoint` (webhook handling), `OnboardingRenderer` (onboarding UI).
- **Sandbox/Test mode**: Controlled by `ppcp-sandbox_on` option. When enabled, connects to PayPal sandbox environment with sandbox credentials. Test cards and PayPal sandbox accounts available at developer.paypal.com.
- **Detection**:
  ```php
  class_exists( 'WooCommerce\PayPalCommerce\Plugin' ) // true if PayPal Payments is active
  defined( 'PAYPAL_API_URL' ) // alternative check
  ```

### Common Patterns
- All three gateways extend `WC_Payment_Gateway` and register via the `woocommerce_payment_gateways` filter.
- Settings are accessed via `get_option('woocommerce_{gateway_id}_settings')` or through the gateway instance: `$gateway->get_option('key')`.
- Payment processing follows the WooCommerce flow: `process_payment($order_id)` returns `['result' => 'success', 'redirect' => $url]` on success or `['result' => 'failure']` on failure with `wc_add_notice()` for error messages.
- Order payment status should be updated via `$order->payment_complete($transaction_id)` for successful payments, which handles stock reduction and status transitions automatically.
- All gateways store transaction IDs in order meta for refund processing and audit trails.
- Webhook handlers verify signatures/authenticity before processing events to prevent fraud.
- Test/sandbox modes use separate API credentials — never expose live credentials programmatically.
- Gateway availability filtered via `woocommerce_available_payment_gateways` — useful for conditional gateway display by cart total, user role, or shipping destination.
