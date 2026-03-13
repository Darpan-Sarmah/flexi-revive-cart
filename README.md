# Flexi Revive Cart – Abandoned Cart Recovery for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892BF)](https://www.php.net/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

> Recover abandoned WooCommerce carts with automated email sequences, SMS, push notifications, exit-intent popups, and dynamic discounts. **Free and Pro versions available.**

---

## 🛒 Overview

Studies show that up to **70% of shopping carts are abandoned**. Flexi Revive Cart automatically follows up with customers who leave items in their cart, helping you recover lost revenue with zero manual effort.

---

## ✨ Features

### Free

| Feature | Description |
|---------|-------------|
| **Cart Tracking** | Automatically tracks abandoned carts for logged-in users |
| **Email Reminders** | 1 automated reminder email per abandoned cart |
| **Beautiful Templates** | 3 mobile-responsive HTML email templates |
| **One-Click Recovery** | Recovery link restores cart and redirects to checkout |
| **Dashboard Analytics** | Recovery rate, revenue recovered/lost, charts |
| **Cart Management** | List, filter, bulk-delete abandoned carts |
| **Settings Page** | Tabbed settings for all plugin options |
| **GDPR Compliant** | Opt-out links, WordPress Privacy API integration |
| **HPOS Compatible** | WooCommerce High-Performance Order Storage support |
| **Translations** | Spanish 🇪🇸, French 🇫🇷, German 🇩🇪 included |

### Pro

| Feature | Description |
|---------|-------------|
| **Guest Email Capture** | Popup to capture emails from guest shoppers |
| **Exit-Intent Popups** | Detect and capture users about to leave |
| **SMS Reminders** | Twilio & Plivo integration |
| **Push Notifications** | OneSignal integration |
| **Dynamic Discounts** | Auto-generate unique WooCommerce coupons |
| **A/B Testing** | Test subject lines, timing, content, channels |
| **Browse Abandonment** | Follow up on product views without add-to-cart |
| **Unlimited Reminders** | Configure unlimited email stages |
| **REST API** | Full REST API for custom integrations |
| **Advanced Analytics** | Channel breakdown, CSV export, A/B results |
| **Email Editor** | WYSIWYG template editor with variable insertion |

---

## 📦 Installation

### WordPress Admin (Recommended)

1. Download the plugin ZIP
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Install Now**
4. **Activate** the plugin
5. Go to **Flexi Revive → Settings** to configure

### Manual (FTP/SSH)

```bash
cd /path/to/wp-content/plugins/
git clone https://github.com/Darpan-Sarmah/flexi-revive-cart.git
```

Then activate the plugin from **Plugins** in your WordPress admin.

### Requirements

- **WordPress** 6.0+
- **WooCommerce** 7.0+
- **PHP** 7.4+

---

## ⚙️ Configuration

### General Settings

Navigate to **Flexi Revive → Settings → General**:

- **Enable Tracking** – Toggle cart tracking on/off
- **Abandonment Timeout** – How long after inactivity to mark a cart as abandoned (default: 60 minutes)
- **Auto-delete** – Automatically delete old cart records after N days (default: 90)

### Email Settings

Navigate to **Flexi Revive → Settings → Email**:

- Configure **From Name** and **From Email**
- Set **reminder intervals** (e.g., Stage 1: 1h, Stage 2: 6h, Stage 3: 24h)
- Customize **subject lines** for each reminder stage
- Send **test emails** directly from the settings page

### Email Variables

Use these placeholders in subject lines and email content:

| Variable | Description |
|----------|-------------|
| `{user_name}` | Customer's first name |
| `{cart_items}` | HTML table of cart items |
| `{cart_total}` | Formatted cart total |
| `{recovery_link}` | One-click recovery URL |
| `{discount_code}` | Auto-generated coupon code (Pro) |
| `{discount_amount}` | Discount percentage/amount (Pro) |
| `{store_name}` | Your store name |
| `{abandoned_time}` | Time since abandonment |
| `{unsubscribe_link}` | GDPR opt-out URL |

---

## 🏗️ Plugin Structure

```
flexi-revive-cart/
├── flexi-revive-cart.php          # Main plugin file
├── uninstall.php                   # Clean uninstall handler
├── readme.txt                      # WordPress.org readme
├── README.md                       # This file
├── LICENSE                         # GPL v2+ license
├── includes/                       # Core PHP classes
│   ├── class-frc-loader.php
│   ├── class-frc-activator.php
│   ├── class-frc-deactivator.php
│   ├── class-frc-cart-tracker.php
│   ├── class-frc-cart-recovery.php
│   ├── class-frc-email-manager.php
│   ├── class-frc-email-templates.php
│   ├── class-frc-cron-manager.php
│   ├── class-frc-discount-manager.php  # Pro
│   ├── class-frc-guest-capture.php     # Pro
│   ├── class-frc-sms-manager.php       # Pro
│   ├── class-frc-push-manager.php      # Pro
│   ├── class-frc-ab-testing.php        # Pro
│   ├── class-frc-browse-abandonment.php # Pro
│   ├── class-frc-rest-api.php          # Pro
│   ├── class-frc-compliance.php
│   └── class-frc-helpers.php
├── admin/                          # Admin PHP classes, CSS, JS
├── public/                         # Frontend PHP class, CSS, JS
├── templates/                      # Email & popup HTML templates
└── languages/                      # Translation files (.pot, .po)
```

---

## 🔗 REST API (Pro)

Base URL: `{site_url}/wp-json/flexi-revive-cart/v1/`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/carts` | List abandoned carts |
| GET | `/carts/{id}` | Get a single cart |
| POST | `/carts/{id}/recover` | Manually trigger recovery |
| GET | `/analytics` | Recovery statistics |
| GET | `/ab-tests` | A/B test results |

Authentication: WordPress REST API nonces or Application Passwords.
Permission required: `manage_woocommerce`.

---

## 🛡️ Security

- All AJAX handlers verify **nonces** (`wp_verify_nonce`)
- All database queries use **`$wpdb->prepare()`**
- All output is **escaped** (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)
- **Capability checks** on all admin actions
- Recovery tokens are **cryptographically random** (64 characters)

---

## 🌍 Translations

Included translations:
- 🇪🇸 Spanish (`es_ES`)
- 🇫🇷 French (`fr_FR`)
- 🇩🇪 German (`de_DE`)

To add a translation, use the `.pot` file in `languages/flexi-revive-cart.pot` with a tool like [Poedit](https://poedit.net/).

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request

Please follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

---

## 📜 License

This plugin is licensed under the **GPL v2 or later**.

> This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

See [LICENSE](LICENSE) for the full license text.

---

## 👤 Author

**Darpan Sarmah**
- GitHub: [@Darpan-Sarmah](https://github.com/Darpan-Sarmah)
- Plugin URI: https://github.com/Darpan-Sarmah/flexi-revive-cart