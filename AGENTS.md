# Repository Guidelines

## Project Structure & Module Organization
- WordPress plugin root: `stripe-cli-demo.php` wires activation hooks and loads components.
- Core classes live in `includes/`: settings (`class-settings.php`), admin UI (`class-admin-pages.php`), checkout AJAX (`class-checkout.php`), webhook endpoint (`class-webhook.php`), and setup wizard (`class-wizard.php`).
- Assets for admin screens sit in `assets/css` and `assets/js`; vendor libraries are under `vendor/` (managed by Composer).
- Keep new feature code scoped to its module; introduce new files in `includes/` and load them via `load_dependencies()` in `stripe-cli-demo.php`.

## Build, Test, and Development Commands
- `composer install` — install PHP dependencies (Stripe SDK) into `vendor/`.
- `composer update stripe/stripe-php` — refresh the Stripe SDK when needed; verify compatibility before committing.
- `php -l <file>` — quick syntax check for edited PHP files.
- Local run: place the folder in `wp-content/plugins/`, run `composer install`, activate via WP Admin or `wp plugin activate stripe-cli-demo`.
- Webhook testing: `stripe listen --forward-to your-site.local/wp-json/stripe-cli-demo/v1/webhook --format JSON` to stream events into the plugin.

## Coding Style & Naming Conventions
- PHP 7.4+; follow WordPress PHP coding standards: 4-space indents, snake_case function names, class names prefixed with `Stripe_CLI_Demo_`, and plugin-specific functions prefixed `stripe_cli_demo_`.
- Prefer single-responsibility classes; mirror existing singleton pattern (`get_instance()`).
- Escape output with WordPress helpers (`esc_html`, `esc_url`) and check permissions/nonces on admin actions.
- Keep strings translatable using the `stripe-cli-demo` text domain.

## Testing Guidelines
- No automated suite yet; use manual flows:
  - Checkout flow: trigger via the demo widget and confirm success webhooks appear.
  - Webhook handler: run `stripe trigger checkout.session.completed` while `stripe listen` streams, verify admin events log updates.
- When adding code, include reproducible manual steps in PRs; prefer arranging code to be testable with future unit tests (pure functions, injectable dependencies).

## Commit & Pull Request Guidelines
- Commit messages: imperative, short summary (<72 chars) with scope when helpful (e.g., `checkout: handle failed intents`).
- PRs should describe the change, why it’s needed, and how to verify (commands, URLs, test card used). Include screenshots/gifs for admin UI tweaks.
- Link related issues or Stripe docs where applicable; call out any configuration changes (options created, new hooks, added dependencies).

## Security & Configuration Tips
- Use only test mode keys; never commit secrets. Rely on WordPress options for stored keys and webhook secrets.
- Validate and sanitize all inputs from admin forms and AJAX; verify nonces and capabilities (`manage_options` for settings).
- If touching webhooks, keep signature verification intact and note any new events handled in docs/admin copy.
