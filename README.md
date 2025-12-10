# Stripe CLI Demo

A WordPress plugin to teach developers how to use the Stripe CLI for local webhook testing.

## Description

This plugin provides a simple demo store with a $1 test product to demonstrate how Stripe webhooks work in local development. It includes a setup wizard that guides developers through configuring the Stripe CLI and testing webhook delivery.

**Perfect for:**
- Teaching teams how Stripe webhooks work
- Onboarding new developers to Stripe integrations
- Testing webhook handling in local development
- Understanding the Stripe CLI workflow
- Monitoring MemberPress Stripe transactions

## Features

- **Setup Wizard** - Step-by-step guide to configure Stripe CLI and API keys
- **Demo Store** - Simple $1 product to trigger real Stripe Checkout sessions
- **Webhook Events** - Real-time display of received webhook events (auto-refreshes every 10s)
- **MemberPress Integration** - Monitor MemberPress transactions when using Stripe Gateway
- **Copy Commands** - One-click copy buttons for all CLI commands
- **Test Connection** - Verify your API credentials are working
- **Debug Mode** - Instructions for JSON-formatted webhook output

## What's New in v1.2.0

- **Simplified Interface** - Webhook Events now on the Demo Store page (all-in-one view)
- **MemberPress Integration** - Separate page to monitor MemberPress Stripe transactions
- **Streamlined Menu** - Demo Store → MemberPress Events → Settings
- **Easy Installation** - Download ZIP from Releases, no build step required
- **Copy Buttons Everywhere** - All CLI commands have one-click copy

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Stripe account (test mode)
- [Stripe CLI](https://stripe.com/docs/stripe-cli) installed locally
- (Optional) MemberPress with Stripe Gateway for MemberPress integration

## Installation

### Option 1: Download Release (Recommended)

1. Go to the [Releases page](../../releases)
2. Download the latest `stripe-cli-demo.zip`
3. In WordPress admin, go to **Plugins → Add New → Upload Plugin**
4. Upload the ZIP file and activate
5. Follow the setup wizard

**No command line or composer needed!**

### Option 2: Clone and Build

```bash
cd wp-content/plugins
git clone https://github.com/your-repo/stripe-cli-demo.git
cd stripe-cli-demo
composer install --no-dev
```

Then activate the plugin in WordPress admin.

## Quick Start

### 1. Install the Stripe CLI

**macOS:**
```bash
brew install stripe/stripe-cli/stripe
```

**Windows:**
```bash
scoop install stripe
```

**Linux:**
Download from [Stripe CLI Releases](https://github.com/stripe/stripe-cli/releases)

### 2. Login to Stripe

```bash
stripe login
```

### 3. Start the Webhook Listener

```bash
stripe listen --forward-to your-site.local/wp-json/stripe-cli-demo/v1/webhook
```

**For debugging with JSON output:**
```bash
stripe listen --forward-to your-site.local/wp-json/stripe-cli-demo/v1/webhook --format JSON
```

### 4. Copy the Webhook Secret

When the CLI starts, it displays a webhook signing secret:
```
> Ready! Your webhook signing secret is whsec_xxxxxxxxxxxxx
```

Enter this secret in the plugin settings.

### 5. Test It!

- Go to **Stripe CLI Demo** in your WordPress admin
- Click "Buy Now" on the Demo Widget
- Use test card: `4242 4242 4242 4242`
- Watch webhook events appear in the **Webhook Events** section below

## Plugin Pages

### Demo Store (Main Page)
Everything you need in one place:
- **Demo Widget** - $1 test product with "Buy Now" button
- **Test Card Numbers** - Quick reference table
- **CLI Commands** - Copy buttons for both standard and debug modes
- **Webhook Events** - Auto-refreshing event log

### MemberPress Events
If MemberPress with Stripe Gateway is detected:
- Monitor real membership transactions
- See subscription events (new, renewed, cancelled)
- Separate event log from demo purchases

### Settings
- API key configuration
- Webhook secret storage
- Connection testing

## Test Card Numbers

| Card Number | Description |
|-------------|-------------|
| `4242 4242 4242 4242` | Succeeds |
| `4000 0000 0000 3220` | Requires 3D Secure |
| `4000 0000 0000 9995` | Declined (insufficient funds) |
| `4000 0000 0000 0002` | Declined (generic) |

Use any future expiry date and any 3-digit CVC.

## Stripe CLI Commands

### Basic Listening
```bash
stripe listen --forward-to your-site.local/wp-json/stripe-cli-demo/v1/webhook
```

### Debugging with JSON Output (Recommended)
```bash
stripe listen --forward-to your-site.local/wp-json/stripe-cli-demo/v1/webhook --format JSON
```

### Verbose Debug Mode
```bash
stripe listen --forward-to your-site.local/wp-json/stripe-cli-demo/v1/webhook --log-level debug
```

### Trigger Test Events
```bash
# Trigger a payment success event
stripe trigger payment_intent.succeeded

# Trigger checkout completed
stripe trigger checkout.session.completed

# Trigger subscription events
stripe trigger customer.subscription.created

# See all available triggers
stripe trigger --help
```

## Webhook Events

When a purchase is made, you'll typically see these events:

1. `payment_intent.created` - Payment intent initialized
2. `customer.created` - Customer record created (if new)
3. `charge.succeeded` - The charge went through
4. `payment_intent.succeeded` - Payment completed
5. `checkout.session.completed` - Checkout session finished

## MemberPress Integration

When MemberPress with Stripe Gateway is active, the plugin automatically:

- Detects MemberPress installation
- Hooks into MemberPress transaction events
- Displays membership purchases, renewals, and cancellations
- Maintains a separate event log from demo store events

**Monitored Events:**
- New membership signups
- Subscription renewals
- Subscription cancellations
- Failed payments
- Refunds

## Plugin Structure

```
stripe-cli-demo/
├── stripe-cli-demo.php              # Main plugin file
├── composer.json                    # Dependencies
├── phpunit.xml                      # Test configuration
├── includes/
│   ├── class-settings.php           # Settings page & menu
│   ├── class-admin-pages.php        # Demo store & webhook events
│   ├── class-checkout.php           # Checkout handler
│   ├── class-webhook.php            # Webhook endpoint
│   ├── class-wizard.php             # Setup wizard
│   └── class-memberpress-integration.php  # MemberPress monitoring
├── assets/
│   ├── css/admin.css                # Admin styles
│   └── js/admin.js                  # Admin JavaScript
├── tests/                           # PHPUnit tests
└── .github/workflows/
    └── release.yml                  # GitHub Action for ZIP builds
```

## Troubleshooting

### "Invalid signature" error
- Make sure the webhook secret in WordPress matches the `whsec_...` from `stripe listen`
- The secret changes each time you restart `stripe listen`

### No events appearing
- Is `stripe listen` running in your terminal?
- Check that the forward URL matches your WordPress site
- Verify your API keys are for the same Stripe account as the CLI
- Check the "Auto-refreshing every 10s" indicator is visible

### API connection test fails
- Verify your secret key starts with `sk_test_`
- Check that you're using keys from the correct Stripe account
- Run `stripe config --list` to see which account the CLI is using

### MemberPress events not showing
- Ensure MemberPress is active with Stripe Gateway enabled
- MemberPress Events page only appears when MemberPress is detected
- MemberPress transactions are separate from Demo Store events

## Important Notes

- **Only use TEST mode keys** - Never use live keys for testing
- **Webhook secret changes** - Update it each time you restart `stripe listen`
- **Same account required** - Your API keys and CLI must use the same Stripe account
- **MemberPress is optional** - Demo Store works without MemberPress

## Running Tests

```bash
composer install
composer test
```

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.2.0 | 2025-12-09 | UI unification, consolidated pages, easy ZIP install |
| 1.1.0 | 2025-12-09 | MemberPress integration |
| 1.0.0 | 2025-12-09 | Initial release |

## License

GPL v2 or later

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
