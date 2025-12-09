# Code Review - 2025-12-09

## Summary
- Scope: core admin flow (settings, checkout AJAX, webhook endpoint, setup wizard JS/PHP).
- General quality: clear structure and helpful UI copy; lacks guardrails in a few request handlers.

## Findings
- High — `includes/class-checkout.php:30`: uses `$_POST['nonce']` without `isset`/sanitization before `wp_verify_nonce`, which throws PHP notices when missing and skips the intended early-return path. Add existence check and sanitize before verifying; return a JSON error if absent.
- Medium — `includes/class-wizard.php:842-876`: accesses `$data['publishable_key']`, `secret_key`, and `webhook_secret` without guarding their presence or type. Missing keys trigger notices and may pass unexpected types into `strpos`. Validate keys exist, sanitize, and fail fast with a clear error message.
- Medium — `includes/class-webhook.php:47-75`: if the webhook secret is empty, the handler still processes payloads without verification, so any caller can write arbitrary events to `stripe_cli_demo_webhook_events`. Consider rejecting unsigned calls (400) unless an explicit “unsafe demo” flag is set, or always require the secret once setup is complete.
- Low — `includes/class-admin-pages.php:226-230`: full-page `location.reload()` every 5s to view events can produce unnecessary DB load in admin. Swap for AJAX polling of the events list or lengthen the interval.
- Low — `includes/class-webhook.php:44,117,134,139,148,153,158`: unconditional `error_log` calls for every webhook can spam logs on busy sites; gate with `WP_DEBUG`, a plugin setting, or throttle logging.

## Questions / Assumptions
- Should the checkout AJAX (`wp_ajax_stripe_cli_demo_create_checkout`) remain admin-only? Current `manage_options` check blocks non-admin testers.
- Is skipping signature verification acceptable for the intended threat model, or should unsigned webhook payloads be rejected?

## Suggested Tests
- Admin checkout: from `Stripe CLI Demo` page, click “Buy Now,” confirm redirect to Stripe Checkout, and verify success message plus a new entry in Webhook Events.
- Webhook verification: set the webhook secret, run `stripe listen --forward-to <site>/wp-json/stripe-cli-demo/v1/webhook`, then `stripe trigger checkout.session.completed`; confirm the event is stored with status `processed`.
- Failure handling: call the checkout AJAX without a nonce (e.g., tampered request) to ensure it returns a JSON error without PHP notices once guards are added.
