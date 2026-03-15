# Flexi Revive Cart – Hooks Reference

This document lists all available **action hooks** and **filter hooks** in the Flexi Revive Cart (Free) plugin. These hooks form the **extensibility API** that Pro add-ons and third-party plugins can use to inject features.

> **Architecture:** The Free version is the **Core Engine**. The Pro add-on is a separate plugin that hooks into these extension points to add premium features (SMS, WhatsApp, push notifications, discounts, A/B testing, analytics, etc.).

---

## Table of Contents

- [Plugin Lifecycle](#plugin-lifecycle)
- [Admin & Settings](#admin--settings)
- [Email Customization](#email-customization)
- [Cart Tracking](#cart-tracking)
- [Cart Recovery](#cart-recovery)
- [Cron & Scheduling](#cron--scheduling)
- [Public / Frontend](#public--frontend)
- [Language & i18n](#language--i18n)
- [Scalability Notes](#scalability-notes)

---

## Plugin Lifecycle

### `frc_loaded` (Action)

**When It Fires:** After the Free plugin is fully loaded and all core components are initialised.

**Use Cases:** Pro add-ons should hook here to load their own files and register hooks.

```php
add_action( 'frc_loaded', function() {
    require_once MY_PRO_DIR . 'includes/class-pro-features.php';
    new My_Pro_Features();
});
```

### `frc_pro_license_valid` (Filter)

**Parameters:** `$valid` (bool) – Defaults to `false`.

**When It Fires:** When the free plugin needs to check if the Pro add-on has a valid license. Used by `frc_is_pro_licensed()` to gate Pro UI features and prevent license circumvention.

**Security:** Even if `FRC_PRO_VERSION` is manually defined in `wp-config.php`, Pro features won't activate unless this filter returns `true` (which only happens when the Pro plugin has validated its license key).

```php
// The Pro add-on hooks in after license validation:
add_filter( 'frc_pro_license_valid', '__return_true' );
```

---

## Admin & Settings

### `frc_admin_tabs` (Filter)

**Parameters:** `$tabs` (array) – Associative array of `tab_id => tab_label`.

**When It Fires:** When rendering the settings page tab navigation.

```php
add_filter( 'frc_admin_tabs', function( $tabs ) {
    $tabs['discount'] = __( 'Discounts', 'flexi-revive-cart-pro' );
    $tabs['sms']      = __( 'SMS', 'flexi-revive-cart-pro' );
    $tabs['whatsapp'] = __( 'WhatsApp', 'flexi-revive-cart-pro' );
    $tabs['push']     = __( 'Push', 'flexi-revive-cart-pro' );
    $tabs['popup']    = __( 'Popups', 'flexi-revive-cart-pro' );
    return $tabs;
});
```

### `frc_export_csv_columns` (Filter)

**Parameters:** `$columns` (array) – Associative array of `column_key => column_label`.

**When It Fires:** When building the CSV export for the Abandoned Carts list (Pro users only).

**Use Cases:** Pro add-ons can add or reorder columns (e.g. `recovery_channel`, `discount_code`).

```php
add_filter( 'frc_export_csv_columns', function( $columns ) {
    $columns['recovery_channel'] = __( 'Recovery Channel', 'flexi-revive-cart-pro' );
    $columns['discount_code']    = __( 'Discount Code', 'flexi-revive-cart-pro' );
    return $columns;
});
```

### `frc_export_csv_row` (Filter)

**Parameters:** `$values` (array) – Associative `column_key => value` for the current row. `$row` (object) – Full database row.

**When It Fires:** For each cart row written to the CSV download.

**Use Cases:** Pro add-ons should hook here to populate any extra columns registered via `frc_export_csv_columns`.

```php
add_filter( 'frc_export_csv_row', function( $values, $row ) {
    $values['recovery_channel'] = $row->recovery_channel ?? '';
    $values['discount_code']    = $row->discount_code ?? '';
    return $values;
}, 10, 2 );
```

### `frc_register_settings` (Action)

**When It Fires:** After core settings are registered via `admin_init`. Pro add-ons should call `register_setting()` here.

```php
add_action( 'frc_register_settings', function() {
    register_setting( 'frc_discount', 'frc_enable_auto_discounts', array( 'sanitize_callback' => 'absint', 'default' => 0 ) );
    // ... more settings
});
```

### `frc_render_settings_tab_{$tab_id}` (Action)

**When It Fires:** When a custom tab (added via `frc_admin_tabs`) is active and needs rendering.

```php
add_action( 'frc_render_settings_tab_discount', function() {
    settings_fields( 'frc_discount' );
    // Render discount settings HTML...
});
```

### `frc_admin_menu` (Action)

**Parameters:** `$parent_slug` (string) – The parent menu slug (`'flexi-revive-cart'`).

**When It Fires:** After core admin menu items are registered.

```php
add_action( 'frc_admin_menu', function( $parent_slug ) {
    add_submenu_page(
        $parent_slug,
        __( 'Analytics', 'flexi-revive-cart-pro' ),
        __( 'Analytics', 'flexi-revive-cart-pro' ),
        'manage_woocommerce',
        'frc-analytics',
        'render_analytics_page'
    );
});
```

### `frc_admin_page_hooks` (Filter)

**Parameters:** `$frc_pages` (array) – Array of admin page hook suffixes where FRC assets are loaded.

**When It Fires:** Before deciding whether to enqueue FRC admin styles/scripts.

```php
add_filter( 'frc_admin_page_hooks', function( $pages ) {
    $pages[] = 'flexi-revive_page_frc-analytics';
    return $pages;
});
```

### `frc_admin_enqueue_scripts` (Action)

**Parameters:** `$hook` (string) – Current admin page hook.

**When It Fires:** After core admin scripts are enqueued on FRC pages.

```php
add_action( 'frc_admin_enqueue_scripts', function( $hook ) {
    if ( 'flexi-revive_page_frc-analytics' === $hook ) {
        wp_enqueue_script( 'frc-pro-analytics', MY_PRO_URL . 'js/analytics.js' );
    }
});
```

### `frc_dashboard_after_charts` (Action)

**When It Fires:** After the dashboard charts grid is rendered.

```php
add_action( 'frc_dashboard_after_charts', function() {
    echo '<div class="frc-pro-section">';
    echo '<h2>' . esc_html__( 'A/B Test Summary', 'flexi-revive-cart-pro' ) . '</h2>';
    // Render A/B test results table...
    echo '</div>';
});
```

### `frc_test_email_stages` (Action)

**When It Fires:** When rendering the test email stage dropdown options.

```php
add_action( 'frc_test_email_stages', function() {
    echo '<option value="2">' . esc_html__( 'Stage 2 – Urgency', 'flexi-revive-cart-pro' ) . '</option>';
    echo '<option value="3">' . esc_html__( 'Stage 3 – Incentive', 'flexi-revive-cart-pro' ) . '</option>';
});
```

### `frc_test_email_allowed_stages` (Filter)

**Parameters:** `$allowed_stages` (array) – Array of allowed stage numbers (default: `[1]`).

```php
add_filter( 'frc_test_email_allowed_stages', function( $stages ) {
    return array( 1, 2, 3 ); // Allow all stages.
});
```

---

## Email Customization

### `frc_reminder_types` (Filter)

**Parameters:** `$type_labels` (array) – Associative array of `type_key => label`.

**When It Fires:** When building the reminder type dropdown in settings.

```php
add_filter( 'frc_reminder_types', function( $types ) {
    $types['urgency']   = __( 'Urgency Reminder', 'flexi-revive-cart-pro' );
    $types['incentive'] = __( 'Incentive/Discount Reminder', 'flexi-revive-cart-pro' );
    return $types;
});
```

### `frc_max_reminders` (Filter)

**Parameters:** `$max` (int) – Maximum number of reminders (default: `3`).

```php
add_filter( 'frc_max_reminders', function( $max ) {
    return 10; // Allow up to 10 reminders.
});
```

### `frc_reminder_type_for_cart` (Filter)

**Parameters:** `$reminder_type` (string), `$stage` (int), `$cart` (object)

**When It Fires:** Before determining which email template to use for a specific cart/stage.

```php
add_filter( 'frc_reminder_type_for_cart', function( $type, $stage, $cart ) {
    if ( $stage === 2 ) return 'urgency';
    if ( $stage >= 3 ) return 'incentive';
    return $type;
}, 10, 3 );
```

### `frc_email_discount` (Filter)

**Parameters:** `$discount` (array with `code` and `amount` keys), `$cart` (object), `$reminder_type` (string), `$stage` (int)

**When It Fires:** Before building email template variables, allowing Pro to inject discount data.

```php
add_filter( 'frc_email_discount', function( $discount, $cart, $type, $stage ) {
    if ( 'incentive' === $type ) {
        $discount['code']   = 'RECOVER-XYZ';
        $discount['amount'] = '10%';
    }
    return $discount;
}, 10, 4 );
```

### `frc_before_reminder_sent` (Action)

**Parameters:** `$cart` (object), `$stage` (int)

**When It Fires:** Immediately before a reminder email is assembled and sent.

### `frc_email_content` (Filter)

**Parameters:** `$body` (string), `$cart` (object), `$stage` (int), `$template_id` (string)

**When It Fires:** After the email body HTML is rendered, before sending.

```php
add_filter( 'frc_email_content', function( $body, $cart, $stage, $template_id ) {
    // Append a tracking pixel.
    $body .= '<img src="https://example.com/track?id=' . $cart->id . '" width="1" height="1" />';
    return $body;
}, 10, 4 );
```

### `frc_email_subject` (Filter)

**Parameters:** `$subject` (string), `$cart` (object), `$stage` (int), `$template_id` (string)

**When It Fires:** After the email subject is built, before sending.

### `frc_after_reminder_sent` (Action)

**Parameters:** `$cart_id` (int), `$stage` (int), `$lang` (string)

**When It Fires:** After a reminder email is successfully sent.

```php
add_action( 'frc_after_reminder_sent', function( $cart_id, $stage, $lang ) {
    error_log( "Reminder stage {$stage} sent for cart {$cart_id} in {$lang}." );
}, 10, 3 );
```

### `frc_email_template_tabs` (Filter)

**Parameters:** `$available_templates` (array) – Array of template IDs (default: `['reminder-1']`).

**When It Fires:** When determining which templates can be edited.

```php
add_filter( 'frc_email_template_tabs', function( $tabs ) {
    $tabs[] = 'reminder-2';
    $tabs[] = 'reminder-3';
    return $tabs;
});
```

### `frc_email_template_variables` (Filter)

**Parameters:** `$vars` (array) – Array of variable names shown in the editor.

```php
add_filter( 'frc_email_template_variables', function( $vars ) {
    $vars[] = 'discount_code';
    $vars[] = 'discount_amount';
    $vars[] = 'discount_expiry';
    return $vars;
});
```

### `frc_pro_only_placeholders` (Filter)

**Parameters:** `$placeholders` (array) – List of placeholder names that require Pro.

**When It Fires:** When validating or stripping Pro-only placeholders in templates.

```php
add_filter( 'frc_pro_only_placeholders', function( $placeholders ) {
    return array(); // Pro is active – allow all placeholders.
});
```

### `frc_email_editor_variable_reference` (Action)

**When It Fires:** Inside the email editor sidebar variable reference table.

```php
add_action( 'frc_email_editor_variable_reference', function() {
    echo '<tr><td><code>{discount_code}</code></td><td>Generated coupon code (Pro)</td></tr>';
});
```

### `frc_email_editor_sidebar` (Action)

**When It Fires:** In the email editor sidebar area, for adding extra info boxes.

---

## Cart Tracking

### `frc_after_cart_tracked` (Action)

**Parameters:** `$cart_id` (int), `$user_id` (int)

**When It Fires:** After a cart is identified as abandoned and is about to receive a reminder.

```php
add_action( 'frc_after_cart_tracked', function( $cart_id, $user_id ) {
    error_log( "Cart {$cart_id} abandoned by user {$user_id}." );
}, 10, 2 );
```

---

## Cart Recovery

### `frc_cart_restored` (Action)

**Parameters:** `$cart` (object)

**When It Fires:** After cart items are restored into the WooCommerce session, before marking as recovered.

```php
add_action( 'frc_cart_restored', function( $cart ) {
    // Auto-apply discount coupon.
    if ( ! empty( $cart->discount_code ) ) {
        WC()->cart->apply_coupon( sanitize_text_field( $cart->discount_code ) );
    }
});
```

### `frc_cart_recovered` (Action)

**Parameters:** `$cart` (object), `$channel` (string)

**When It Fires:** After a cart has been fully recovered and marked in the database.

```php
add_action( 'frc_cart_recovered', function( $cart, $channel ) {
    error_log( "Cart {$cart->id} recovered via {$channel}." );
}, 10, 2 );
```

---

## Cron & Scheduling

### `frc_dispatch_reminder` (Action)

**Parameters:** `$cart` (object), `$stage` (int)

**When It Fires:** After the core email reminder is dispatched. Pro should hook here to send SMS, WhatsApp, or push notifications.

```php
add_action( 'frc_dispatch_reminder', function( $cart, $stage ) {
    if ( get_option( 'frc_enable_sms', '0' ) ) {
        $sms = new FRC_SMS_Manager();
        $sms->send_sms( $cart, $stage );
    }
}, 10, 2 );
```

### `frc_check_abandoned_carts` (Cron Action)

**When It Fires:** Every 15 minutes via WordPress cron. Processes abandoned carts in batches of 20.

### `frc_cleanup_old_carts` (Cron Action)

**When It Fires:** Periodically to delete carts and email logs older than the configured data retention period.

### `frc_send_reminder` (Action Scheduler)

**Parameters:** `$cart_id` (int), `$stage` (int)

**When It Fires:** Callback for Action Scheduler to send a scheduled reminder for a specific cart.

---

## Public / Frontend

### `frc_public_enqueue_scripts` (Action)

**When It Fires:** After core public scripts are enqueued on WooCommerce pages.

```php
add_action( 'frc_public_enqueue_scripts', function() {
    if ( get_option( 'frc_enable_exit_intent', '0' ) ) {
        wp_enqueue_script( 'frc-exit-intent', MY_PRO_URL . 'js/exit-intent.js', array( 'jquery' ) );
    }
});
```

### `frc_time_units` (Filter)

**Parameters:** `$units` (array) – Associative array of `unit_key => label`.

**When It Fires:** When rendering time unit dropdowns in settings.

---

## Language & i18n

### `wp_ajax_frc_set_language` / `wp_ajax_nopriv_frc_set_language` (AJAX Action)

**When It Fires:** When a user selects a language from the frontend language switcher.

---

## Scalability Notes

### Performance

Hooks fire synchronously. For expensive operations (e.g., CRM syncs, API calls), use `wp_schedule_single_event()`:

```php
add_action( 'frc_after_cart_tracked', function( $cart_id, $user_id ) {
    wp_schedule_single_event( time(), 'my_crm_sync_cart', array( $cart_id, $user_id ) );
}, 10, 2 );
```

### Error Handling

Critical hooks (`frc_after_cart_tracked`, `frc_after_reminder_sent`) are wrapped in `try-catch` blocks. Exceptions from callbacks are logged without crashing the plugin.

---

## Supported Languages

| Code    | Language              |
|---------|-----------------------|
| `en`    | English               |
| `es`    | Spanish               |
| `fr`    | French                |
| `de`    | German                |
| `it`    | Italian               |
| `hi`    | Hindi                 |
| `pt_BR` | Portuguese (Brazil)  |
| `zh_CN` | Chinese (Simplified) |
| `ar`    | Arabic                |
| `ja`    | Japanese              |

---

## Placeholder Reference

### Allowed Placeholders by Template Type

| Template Type        | Allowed Placeholders                                                                                  |
|----------------------|-------------------------------------------------------------------------------------------------------|
| Friendly Reminder    | `{user_name}`, `{cart_items}`, `{cart_total}`, `{recovery_link}`, `{cart_link}`, `{store_name}`, `{abandoned_time}`, `{unsubscribe_link}`, `{tracking_pixel}` |
| Urgency Reminder     | Same as Friendly Reminder (Pro add-on)                                                                |
| Incentive / Discount | All above **+** `{discount_code}`, `{discount_amount}`, `{discount_expiry}` (Pro add-on)            |

> **Note:** Discount placeholders are injected by the Pro add-on. In the Free version, they are automatically stripped from templates.
