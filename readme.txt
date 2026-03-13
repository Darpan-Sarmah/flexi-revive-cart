=== Flexi Revive Cart - Abandoned Cart Recovery for WooCommerce ===
Contributors: darpansarmah
Tags: woocommerce, abandoned cart, cart recovery, email reminder, email marketing, sms, push notifications, exit intent, discount coupon
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Recover abandoned WooCommerce carts with automated email sequences, SMS, push notifications, exit-intent popups, and dynamic discounts.

== Description ==

**Flexi Revive Cart** is a powerful WooCommerce abandoned cart recovery plugin that helps you automatically follow up with customers who leave items in their shopping cart without completing their purchase.

Studies show that up to **70% of shopping carts are abandoned**. Flexi Revive Cart helps you recover a significant portion of that lost revenue with minimal effort.

= Free Features =

* **Automatic Cart Tracking** – Detects abandoned carts for logged-in users
* **Email Reminders** – Send 1 automated email reminder per cart
* **Three Beautiful Email Templates** – Professionally designed, mobile-responsive HTML templates
* **One-Click Cart Recovery** – Recovery link instantly restores the cart and redirects to checkout
* **Dashboard Analytics** – Overview of abandoned carts, recovery rate, and revenue recovered
* **Abandoned Carts List** – View all abandoned carts with filtering and bulk actions
* **Settings Page** – Configurable abandonment timeout, from name/email, and reminder intervals
* **GDPR Compliant** – Opt-out links in every email, WordPress Privacy API integration
* **WooCommerce HPOS Compatible** – Works with High-Performance Order Storage
* **Action Scheduler Support** – Uses WooCommerce's Action Scheduler for reliable email delivery
* **Translations** – Spanish, French, and German included

= Pro Features =

* **Guest Email Capture** – Capture emails from guest shoppers via a popup before they leave
* **Exit-Intent Popups** – Show a targeted popup when users are about to leave the site
* **SMS & WhatsApp Reminders** – Send SMS via Twilio or Plivo integration
* **Push Notifications** – Send push notifications via OneSignal
* **Dynamic Discount Coupons** – Auto-generate unique WooCommerce coupons for each abandoned cart
* **A/B Testing Engine** – Test subject lines, content, timing, and channels
* **Browse Abandonment** – Follow up with users who viewed products but didn't add to cart
* **Unlimited Email Reminders** – Configure any number of reminder stages
* **REST API** – Full REST API for headless/custom integrations
* **Advanced Analytics** – Channel breakdown, A/B test results, CSV export

= How It Works =

1. A customer adds items to their cart but doesn't complete checkout
2. After a configurable timeout (default: 60 minutes), the cart is marked as "abandoned"
3. The plugin automatically sends timed email reminders
4. Each email contains a one-click recovery link that restores the exact cart
5. When the customer clicks the link, their cart is restored and they're redirected to checkout
6. The cart is marked as "recovered" and your revenue is saved!

= Security =

* All AJAX handlers verify nonces
* All database queries use `$wpdb->prepare()`
* Output is properly escaped with WordPress functions
* Recovery tokens are cryptographically random

== Installation ==

= Automatic Installation =

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for "Flexi Revive Cart"
3. Click **Install Now** and then **Activate**
4. Navigate to **Flexi Revive → Settings** to configure the plugin

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Activate the plugin
5. Navigate to **Flexi Revive → Settings** to configure

= Requirements =

* WordPress 6.0+
* WooCommerce 7.0+
* PHP 7.4+

== Frequently Asked Questions ==

= Does this work with guest customers? =

In the **Free version**, cart tracking requires the customer to be logged in. The **Pro version** adds a guest email capture popup that collects emails from guest shoppers.

= Will this slow down my site? =

No. The cart tracker uses a lightweight AJAX heartbeat every 30 seconds only on cart/checkout pages. Analytics data is cached with transients. Email sending uses background processing.

= Is it GDPR compliant? =

Yes. Every reminder email includes an unsubscribe/opt-out link. Opt-out requests are stored in the database and honored permanently. The plugin integrates with WordPress's Privacy Export/Erasure API.

= Does it work with caching plugins? =

Yes. The frontend cart tracking uses AJAX which bypasses page caching. We recommend configuring your caching plugin to not cache admin-ajax.php requests.

= Can I customize the email templates? =

Yes. The **Pro version** includes a WYSIWYG email template editor with variable insertion buttons and live preview.

= What email variables are available? =

`{user_name}`, `{cart_items}`, `{cart_total}`, `{recovery_link}`, `{discount_code}`, `{discount_amount}`, `{store_name}`, `{abandoned_time}`, `{unsubscribe_link}`

= Does it support WooCommerce HPOS? =

Yes, Flexi Revive Cart declares compatibility with WooCommerce High-Performance Order Storage (HPOS).

= Which SMS providers are supported? =

The Pro version supports **Twilio** (primary) and **Plivo** (secondary).

= Which push notification service is supported? =

The Pro version integrates with **OneSignal** via their REST API.

== Screenshots ==

1. **Dashboard** – Overview of abandoned carts, recovery rate, and revenue statistics with charts
2. **Abandoned Carts List** – Detailed list of all abandoned carts with status badges and bulk actions
3. **Settings – General** – Configure abandonment timeout and tracking options
4. **Settings – Email** – Configure email reminders, subjects, and intervals
5. **Email Template 1** – Friendly reminder email template
6. **Email Template 3** – Incentive email with discount code
7. **Guest Capture Popup** – Pro email capture popup (Pro)
8. **Exit-Intent Popup** – Pro exit-intent popup with discount offer (Pro)
9. **A/B Test Results** – Pro A/B testing results page (Pro)

== Changelog ==

= 1.0.0 =
* Initial release
* Cart tracking for logged-in users
* 3-stage email reminder sequence
* One-click cart recovery
* Dashboard with analytics and charts
* Abandoned carts list table with bulk actions
* Settings page with tabbed interface
* GDPR/CCPA compliance features
* WooCommerce HPOS compatibility
* Spanish, French, and German translations
* Pro: Guest email capture popup
* Pro: Exit-intent popup
* Pro: SMS via Twilio/Plivo
* Pro: Push notifications via OneSignal
* Pro: Dynamic discount coupon generation
* Pro: A/B testing engine
* Pro: Browse abandonment tracking
* Pro: REST API

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade required.
