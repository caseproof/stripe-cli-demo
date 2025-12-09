# Stripe CLI Demo - Claude Code Instructions

## Project Overview
WordPress plugin that teaches developers how to use the Stripe CLI for local webhook testing. Provides a setup wizard, demo store with $1 product, and webhook event viewer.

## Tech Stack
- PHP 7.4+ (WordPress plugin)
- Stripe PHP SDK v13
- WordPress REST API for webhook endpoint
- jQuery (WordPress bundled)

## Architecture
```
stripe-cli-demo/
├── stripe-cli-demo.php      # Main plugin file, singleton loader
├── includes/
│   ├── class-settings.php   # WP Settings API integration
│   ├── class-admin-pages.php # Admin UI rendering
│   ├── class-checkout.php   # AJAX checkout handler
│   ├── class-webhook.php    # REST API webhook endpoint
│   └── class-wizard.php     # Setup wizard flow
└── assets/                  # CSS/JS assets
```

## Key Endpoints
- **Webhook:** `/wp-json/stripe-cli-demo/v1/webhook`
- **Checkout AJAX:** `wp_ajax_stripe_cli_demo_create_checkout`

## Coding Principles
- **KISS** - Simple, focused classes with single responsibilities
- **YAGNI** - No speculative features; only what's needed for the demo
- **DRY** - Reuse WordPress APIs (Settings API, REST API, AJAX handlers)
- **PSR-4** - Autoloading via Composer for Stripe SDK
- **SOLID** - Each class handles one concern (settings, checkout, webhooks, etc.)

## WordPress Conventions
- Prefix all options with `stripe_cli_demo_`
- Use `wp_` functions for security (nonces, sanitization, escaping)
- Singleton pattern for main classes
- Hooks: `admin_menu`, `admin_init`, `rest_api_init`, `wp_ajax_*`

## Testing Locally
```bash
# Start webhook listener
stripe listen --forward-to localhost/wp-json/stripe-cli-demo/v1/webhook

# Trigger test event
stripe trigger payment_intent.succeeded
```

## Don't
- Add features beyond webhook demo scope
- Store sensitive data outside wp_options
- Skip nonce verification on AJAX/form handlers
- Use live Stripe keys (test mode only)
