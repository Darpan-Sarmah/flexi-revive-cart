# Flexi Revive Cart Pro – Setup Guide

The `flexi-revive-cart-pro/` directory in this repository contains the **complete Pro add-on plugin** ready to be pushed to its own repository.

## How to Push Pro Add-on to Its Own Repository

### Option 1: Quick Push (Recommended)

```bash
# 1. Clone this repository (or use your existing clone)
git clone https://github.com/Darpan-Sarmah/flexi-revive-cart.git
cd flexi-revive-cart
git checkout copilot/add-pro-addon-features

# 2. Copy the pro plugin files to a temporary directory
cp -r flexi-revive-cart-pro /tmp/flexi-revive-cart-pro

# 3. Initialize the pro repository
cd /tmp/flexi-revive-cart-pro
git init
git remote add origin https://github.com/Darpan-Sarmah/flexi-revive-cart-pro.git

# 4. Create .gitignore
cat > .gitignore << 'EOF'
.DS_Store
Thumbs.db
*.log
node_modules/
vendor/
EOF

# 5. Commit and push
git add .
git commit -m "Initial commit: Flexi Revive Cart Pro add-on plugin"
git branch -M main
git push -u origin main
```

### Option 2: Using GitHub CLI

```bash
# 1. Navigate to your clone of this repo
cd flexi-revive-cart
git checkout copilot/add-pro-addon-features

# 2. Push the subdirectory to the pro repo
cd flexi-revive-cart-pro
git init
git remote add origin https://github.com/Darpan-Sarmah/flexi-revive-cart-pro.git
git add .
git commit -m "Initial commit: Flexi Revive Cart Pro add-on plugin"
git branch -M main
git push -u origin main
```

## Pro Plugin Structure

```
flexi-revive-cart-pro/
├── flexi-revive-cart-pro.php      # Main plugin file
├── readme.txt                      # WordPress readme
├── admin/
│   ├── class-frc-pro-admin.php            # Admin menu & pages
│   ├── class-frc-pro-admin-settings.php   # Pro settings tabs
│   ├── class-frc-pro-admin-analytics.php  # Analytics dashboard
│   ├── class-frc-pro-admin-ab-results.php # A/B test results
│   ├── class-frc-pro-admin-whatsapp.php   # WhatsApp log viewer
│   └── css/frc-pro-admin.css              # Admin styles
├── includes/
│   ├── class-frc-pro-loader.php           # Hook loader (29 hooks)
│   ├── class-frc-pro-license.php          # HMAC license system
│   ├── class-frc-pro-activator.php        # Activation/deactivation
│   ├── class-frc-pro-discount.php         # Dynamic discounts
│   ├── class-frc-pro-sms.php              # SMS (Twilio/Plivo)
│   ├── class-frc-pro-whatsapp.php         # WhatsApp messaging
│   ├── class-frc-pro-push.php             # Push notifications
│   ├── class-frc-pro-browse.php           # Browse abandonment
│   ├── class-frc-pro-guest-capture.php    # Guest email capture
│   ├── class-frc-pro-ab-testing.php       # A/B testing engine
│   ├── class-frc-pro-export.php           # CSV/JSON export
│   └── class-frc-pro-rest-api.php         # REST API endpoints
├── public/
│   ├── css/frc-pro-popups.css             # Popup styles
│   └── js/frc-pro-popups.js               # Popup scripts
└── templates/
    ├── emails/
    │   ├── reminder-2.php                 # Urgency template
    │   └── reminder-3.php                 # Incentive template
    └── popups/
        ├── exit-intent.php                # Exit-intent popup
        └── guest-capture.php              # Guest capture popup
```

## How the Free + Pro Architecture Works

- **Free plugin** (`flexi-revive-cart`): Contains all core functionality + 29 extension hooks
- **Pro plugin** (`flexi-revive-cart-pro`): Hooks into those 29 extension points after license validation
- The Pro plugin requires the Free plugin to be installed and active
- Pro features only activate after HMAC-SHA256 license key verification

## After Pushing to Pro Repo

Once the Pro code is in its own repository, you can safely **remove the `flexi-revive-cart-pro/` directory and this guide** from the free version repo before merging the PR.
