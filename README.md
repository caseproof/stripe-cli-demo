# Stripe CLI Demo

A WordPress plugin to teach developers how to use the Stripe CLI for local webhook testing.

## Description

This plugin provides a simple demo store with a $1 test product to demonstrate how Stripe webhooks work in local development. It includes a setup wizard that guides developers through configuring the Stripe CLI and testing webhook delivery.

**Perfect for:**
- Teaching teams how Stripe webhooks work
- Onboarding new developers to Stripe integrations
- Testing webhook handling in local development
- Understanding the Stripe CLI workflow

## Features

- **Setup Wizard** - Step-by-step guide to configure Stripe CLI and API keys
- **Demo Store** - Simple $1 product to trigger real Stripe Checkout sessions
- **Webhook Viewer** - Real-time display of received webhook events
- **Copy Commands** - One-click copy buttons for CLI commands
- **Test Connection** - Verify your API credentials are working
- **Debug Mode** - Instructions for JSON-formatted webhook output

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Stripe account (test mode)
- [Stripe CLI](https://stripe.com/docs/stripe-cli) installed locally

## Installation

### Option 1: Download Release (Recommended)

1. Go to the [Releases page](../../releases)
2. Download the latest `stripe-cli-demo.zip`
3. In WordPress admin, go to **Plugins → Add New → Upload Plugin**
4. Upload the ZIP file and activate
5. Follow the setup wizard

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
- Watch webhook events appear in the **Webhook Events** page

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

## Plugin Structure

```
stripe-cli-demo/
├── stripe-cli-demo.php      # Main plugin file
├── composer.json            # Dependencies
├── includes/
│   ├── class-settings.php   # Settings page
│   ├── class-admin-pages.php # Demo store & events viewer
│   ├── class-checkout.php   # Checkout handler
│   ├── class-webhook.php    # Webhook endpoint
│   └── class-wizard.php     # Setup wizard
└── assets/
    ├── css/admin.css        # Admin styles
    └── js/admin.js          # Admin JavaScript
```

## Troubleshooting

### "Invalid signature" error
- Make sure the webhook secret in WordPress matches the `whsec_...` from `stripe listen`
- The secret changes each time you restart `stripe listen`

### No events appearing
- Is `stripe listen` running in your terminal?
- Check that the forward URL matches your WordPress site
- Verify your API keys are for the same Stripe account as the CLI

### API connection test fails
- Verify your secret key starts with `sk_test_`
- Check that you're using keys from the correct Stripe account
- Run `stripe config --list` to see which account the CLI is using

## Important Notes

- **Only use TEST mode keys** - Never use live keys for testing
- **Webhook secret changes** - Update it each time you restart `stripe listen`
- **Same account required** - Your API keys and CLI must use the same Stripe account

## License

GPL v2 or later

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
