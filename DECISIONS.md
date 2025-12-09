# Stripe CLI Demo - Architecture Decisions

> This file records key architectural and design decisions made during development.

---

## ADR-001: WordPress Plugin vs Standalone PHP

**Date:** 2025-12-09
**Status:** Accepted

### Context
Initially built as standalone PHP with built-in server. User requested WordPress plugin format for team use.

### Decision
Convert to WordPress plugin using WordPress APIs (Settings API, REST API, AJAX).

### Rationale
- Team uses Local by Flywheel (WordPress-focused)
- WordPress provides security primitives (nonces, capabilities)
- Settings API handles options storage automatically
- Plugin can be distributed via GitHub

### Consequences
- Requires WordPress installation
- Limited to admin users (manage_options capability)
- Uses wp_options table instead of file-based storage

---

## ADR-002: Admin-Only Access

**Date:** 2025-12-09
**Status:** Accepted

### Context
Could expose demo store publicly or restrict to admin.

### Decision
Restrict all functionality to users with `manage_options` capability.

### Rationale
- This is a developer tool, not customer-facing
- Prevents accidental exposure of test credentials
- Simpler permission model
- No need for frontend shortcodes or blocks

### Consequences
- Only admins can use the demo store
- No frontend integration required
- Checkout sessions created via wp_ajax (authenticated only)

---

## ADR-003: Inline price_data vs Stripe Products

**Date:** 2025-12-09
**Status:** Accepted

### Context
Stripe Checkout can use:
1. Pre-created Products/Prices in Stripe Dashboard
2. Inline `price_data` at checkout time

### Decision
Use inline `price_data` for the demo product.

### Rationale
- No setup required in Stripe Dashboard
- Works immediately with any Stripe account
- Simpler for teaching purposes
- Demo product is ephemeral, not a real catalog item

### Consequences
- Product doesn't appear in Stripe Dashboard products list
- Each checkout creates transient line item
- Cannot use Stripe's product management features

---

## ADR-004: Setup Wizard on Activation

**Date:** 2025-12-09
**Status:** Accepted

### Context
Users need to configure API keys, start CLI, and enter webhook secret.

### Decision
Show setup wizard automatically on first activation, guide through 4 steps.

### Rationale
- Reduces friction for new users
- Ensures proper configuration before use
- Provides copy-paste CLI commands
- Validates API keys before proceeding

### Consequences
- Wizard transient set on activation
- Redirect to wizard on first admin load
- Users can skip and configure later via Settings

---

## ADR-005: AJAX Polling vs WebSockets

**Date:** 2025-12-09
**Status:** Accepted

### Context
Event viewer needs real-time updates. Options:
1. Full page reload (original implementation)
2. AJAX polling
3. WebSockets/Server-Sent Events

### Decision
Use AJAX polling every 10 seconds.

### Rationale
- WebSockets require additional server configuration
- Page reload wastes bandwidth and resets scroll
- AJAX polling is simple, works everywhere
- 10-second interval balances freshness vs load

### Consequences
- Events appear within 10 seconds of webhook receipt
- Lower server load than page reloads
- No additional infrastructure required

---

## ADR-006: Reject Unsigned Webhooks

**Date:** 2025-12-09
**Status:** Accepted

### Context
Code review identified that empty webhook_secret allowed unsigned payloads through.

### Decision
Reject all webhook requests when secret is not configured.

### Rationale
- Unsigned payloads could be forged
- Events written to wp_options should be verified
- Forces users to complete setup properly
- Better security posture

### Consequences
- Webhooks fail until wizard step 3 completed
- Clear error message directs to setup
- No "silent failure" mode

---

## ADR-007: WP_DEBUG Gated Logging

**Date:** 2025-12-09
**Status:** Accepted

### Context
Code review noted unconditional error_log() calls.

### Decision
Gate all logging behind WP_DEBUG constant.

### Rationale
- Production sites shouldn't have debug logs
- WP_DEBUG is standard WordPress debugging flag
- Reduces log noise in production
- Consistent with WordPress conventions

### Consequences
- Must enable WP_DEBUG to see webhook logs
- Debug output only in development
- Cleaner production logs
