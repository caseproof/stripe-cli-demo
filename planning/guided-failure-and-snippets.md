# Plans: Guided Failure Drills & Snippet Generator

## Guided Failure Drills
- Objective: let users simulate common failure events (dispute, card error, 3DS required) from WP admin, observe handling, and see remediation guidance.
- UX: add a “Failure Drills” card on the Demo page with buttons for preset triggers; each shows preconditions, command copy button, and expected results.
- Events to cover: `payment_intent.payment_failed` (insufficient funds), `payment_intent.payment_failed` (card_declined), `charge.dispute.created`, `checkout.session.async_payment_failed`.
- Behavior: when a drill runs, log the event with a `drill` flag; display a checklist beside the event describing what to verify (signature, status, user messaging).
- Tech notes: reuse webhook handler; add metadata `drill=true` when triggering via CLI command copy; optionally provide a local POST-to-webhook button guarded by nonce/role to replay stored payloads.
- Instrumentation: add a “last drill run” timestamp and store payload samples under `uploads/stripe-cli-demo/drills/` (ensure capability checks and directory creation).
- Risks: do not expose replay endpoints publicly; keep commands/test cards clearly labeled as test-mode only.

## Snippet Generator
- Objective: produce ready-to-use code snippets for common tasks tied to the user’s saved settings (keys, webhook URL) in PHP and JS.
- UX: new “Snippet Generator” tab or metabox on Settings page with dropdowns for language (PHP/JS) and scenario (signature verification, basic charge retrieval, webhook event logging).
- Output: read-only code block with copy button; inject site-specific values like webhook URL, text domain, and option names; avoid embedding secrets directly—use placeholders unless user explicitly opts in.
- Snippet presets:
  - PHP: verify webhook signature and route events; example WP REST callback.
  - PHP: create Checkout Session with product/price placeholders mirroring the demo.
  - JS: fetch events via REST for a custom admin widget.
- Tech notes: generate strings server-side in PHP; escape output; include `@since` and minimal comments in snippets; ensure l10n placeholders remain intact.
- Future: allow exporting snippets as downloadable files, and version them to plugin releases for consistency.***
