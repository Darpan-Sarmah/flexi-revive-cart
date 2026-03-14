# Flexi Revive Cart – Hooks Reference

This document lists all available **action hooks** and **filter hooks** in the Flexi Revive Cart plugin. Use these hooks to extend functionality, integrate with third-party plugins, or customize behavior.

> **Note:** All hooks are wrapped in `try-catch` blocks for safety. If a third-party callback throws an exception, it will be caught and logged (via `error_log`) without crashing the plugin.

---

## Table of Contents

- [Cart Tracking](#cart-tracking)
- [Email Customization](#email-customization)
- [Cron & Scheduling](#cron--scheduling)
- [Cart Recovery](#cart-recovery)
- [Admin & Settings](#admin--settings)
- [Language & i18n](#language--i18n)

---

## Cart Tracking

### `frc_after_cart_tracked`

**Parameters:** `$cart_id` (int), `$user_id` (int)

**When It Fires:** After a cart is identified as abandoned and is about to receive a reminder during the cron check.

**Example:**

```php
add_action( 'frc_after_cart_tracked', function( $cart_id, $user_id ) {
    error_log( "Cart {$cart_id} abandoned by user {$user_id}." );
}, 10, 2 );
```

**Use Cases:**
- Sync abandoned cart data to a CRM (e.g., HubSpot, Salesforce).
- Trigger a custom notification (e.g., Slack alert).
- Log events for analytics dashboards.

---

## Email Customization

### `frc_after_reminder_sent`

**Parameters:** `$cart_id` (int), `$stage` (int), `$lang` (string)

**When It Fires:** After a reminder email is successfully sent for an abandoned cart.

**Example:**

```php
add_action( 'frc_after_reminder_sent', function( $cart_id, $stage, $lang ) {
    error_log( "Reminder stage {$stage} sent for cart {$cart_id} in language {$lang}." );

    // Example: sync to external analytics.
    if ( function_exists( 'my_analytics_track' ) ) {
        my_analytics_track( 'cart_reminder_sent', array(
            'cart_id' => $cart_id,
            'stage'   => $stage,
            'lang'    => $lang,
        ) );
    }
}, 10, 3 );
```

**Use Cases:**
- Track email send events in external analytics.
- Trigger follow-up actions (e.g., schedule a phone call for high-value carts).
- Update CRM contact records.

---

## Cron & Scheduling

### `frc_check_abandoned_carts`

**Type:** WordPress Cron Action

**When It Fires:** Every 15 minutes via WordPress cron. Processes abandoned carts in batches of 20.

**Example:**

```php
add_action( 'frc_check_abandoned_carts', function() {
    error_log( 'FRC cron: checking abandoned carts.' );
}, 5 ); // Run before the default handler.
```

### `frc_cleanup_old_carts`

**Type:** WordPress Cron Action

**When It Fires:** Periodically to delete carts and email logs older than the configured data retention period.

### `frc_send_reminder`

**Parameters:** `$cart_id` (int), `$stage` (int)

**When It Fires:** Callback for Action Scheduler (if available) to send a scheduled reminder for a specific cart.

**Example:**

```php
add_action( 'frc_send_reminder', function( $cart_id, $stage ) {
    error_log( "Sending reminder stage {$stage} for cart {$cart_id}." );
}, 5, 2 );
```

---

## Cart Recovery

### `template_redirect` (WordPress Core)

**When It Fires:** The plugin intercepts `template_redirect` to handle recovery links (`?frc_recover=TOKEN`). Cart recovery restores the customer's cart and redirects to checkout.

### `woocommerce_cart_item_removed` (WooCommerce)

**When It Fires:** Used by the cart tracker to detect cart modifications and update the abandoned cart record.

### `woocommerce_checkout_order_created` (WooCommerce)

**When It Fires:** Used to mark a cart as "converted" (recovered) when an order is placed.

---

## Admin & Settings

### `wp_ajax_frc_send_test_email`

**Type:** AJAX Action

**When It Fires:** When an admin sends a test email from the settings page. Supports language selection for preview.

### `wp_ajax_frc_get_chart_data`

**Type:** AJAX Action

**When It Fires:** When the admin dashboard loads chart data.

### `wp_ajax_frc_delete_cart`

**Type:** AJAX Action

**When It Fires:** When an admin deletes an abandoned cart from the cart list.

### `wp_ajax_frc_resend_reminder`

**Type:** AJAX Action

**When It Fires:** When an admin manually resends a reminder for a specific cart.

### `wp_ajax_frc_send_whatsapp_bulk` (Pro)

**Type:** AJAX Action

**When It Fires:** When an admin sends a bulk WhatsApp campaign.

---

## Language & i18n

### `wp_ajax_frc_set_language` / `wp_ajax_nopriv_frc_set_language`

**Type:** AJAX Action

**When It Fires:** When a user selects a language from the frontend language switcher. Updates user meta for logged-in users.

---

## Scalability Notes

### Performance

- Hooks fire synchronously by default. For non-critical actions (e.g., CRM syncs), use `wp_schedule_single_event()` inside your hook callback:

```php
add_action( 'frc_after_cart_tracked', function( $cart_id, $user_id ) {
    // Schedule async processing instead of blocking.
    wp_schedule_single_event( time(), 'my_crm_sync_cart', array( $cart_id, $user_id ) );
}, 10, 2 );

add_action( 'my_crm_sync_cart', function( $cart_id, $user_id ) {
    // Expensive CRM API call runs asynchronously.
    my_crm_api_sync( $cart_id, $user_id );
}, 10, 2 );
```

### Error Handling

All plugin hooks are wrapped in `try-catch` blocks to prevent third-party code from breaking the plugin:

```php
try {
    do_action( 'frc_after_cart_tracked', $cart_id, $user_id );
} catch ( Exception $e ) {
    error_log( 'FRC Hook Error: ' . $e->getMessage() );
}
```

If your hook callback throws an exception, it will be caught, logged, and the plugin will continue functioning normally.

---

## Supported Languages

The following languages are supported for email templates and the frontend language switcher:

| Code    | Language               |
|---------|------------------------|
| `en`    | English                |
| `es`    | Spanish                |
| `fr`    | French                 |
| `de`    | German                 |
| `it`    | Italian                |
| `hi`    | Hindi                  |
| `pt_BR` | Portuguese (Brazil)   |
| `zh_CN` | Chinese (Simplified)  |
| `ar`    | Arabic                 |
| `ja`    | Japanese               |

---

## Placeholder Reference

### Allowed Placeholders by Template Type

| Template Type        | Allowed Placeholders                                                                                  |
|----------------------|-------------------------------------------------------------------------------------------------------|
| Friendly Reminder    | `{user_name}`, `{cart_items}`, `{cart_total}`, `{recovery_link}`, `{cart_link}`, `{store_name}`, `{abandoned_time}`, `{unsubscribe_link}`, `{tracking_pixel}` |
| Urgency Reminder     | Same as Friendly Reminder                                                                             |
| Incentive / Discount | All above **+** `{discount_code}`, `{discount_amount}`, `{discount_expiry}`                         |

> **Note:** Discount placeholders (`{discount_code}`, `{discount_amount}`, `{discount_expiry}`) are **Pro-only**. In the Free version, they are automatically replaced with empty strings.
