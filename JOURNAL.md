# Stripe CLI Demo - Development Journal

> Learnings, debugging notes, and patterns discovered during development.

---

## 2025-12-09: Initial Development

### Stripe Account Mismatch Debugging

**Problem:** Test purchases succeeded in Stripe Checkout but no webhook events appeared in the event viewer.

**Investigation:**
1. Verified webhook endpoint was registered (`/wp-json/stripe-cli-demo/v1/webhook`)
2. Checked server logs - no POST requests to webhook URL
3. Ran `stripe config --list` to check CLI configuration
4. Discovered CLI was using account `acct_1STo83CflBsTRln5`
5. API keys in settings were from different account `51S7RBP...`

**Root Cause:** Stripe CLI and API keys must be from the same Stripe account. The CLI forwards events from its configured account, but checkouts go to the account whose secret key is used.

**Solution:** Updated API keys to match CLI account.

**Lesson:** When webhooks aren't arriving, always verify:
```bash
stripe config --list  # Check which account CLI uses
```
Then ensure API keys match that account.

---

### Clipboard API Fallback Pattern

**Problem:** Copy buttons didn't work in Local by Flywheel.

**Investigation:**
1. Modern `navigator.clipboard.writeText()` requires secure context (HTTPS)
2. Local development runs on HTTP
3. Promise callback lost button reference (`this` binding issue)

**Solution:** Implement progressive enhancement:
```javascript
function copyToClipboard(text, btn, originalText) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            btn.text("Copied!");
            setTimeout(function() { btn.text(originalText); }, 2000);
        }).catch(function() {
            fallbackCopy(text, btn, originalText);
        });
    } else {
        fallbackCopy(text, btn, originalText);
    }
}

function fallbackCopy(text, btn, originalText) {
    var textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand("copy");
        btn.text("Copied!");
    } catch (err) {
        btn.text("Copy failed");
    }
    document.body.removeChild(textarea);
    setTimeout(function() { btn.text(originalText); }, 2000);
}
```

**Lesson:** Always have a fallback for clipboard operations. Capture button references before async callbacks.

---

### WordPress Security Patterns

**Pattern 1: Nonce Validation with isset**
```php
// Bad - causes PHP notice if nonce missing
if (!wp_verify_nonce($_POST['nonce'], 'action_name')) { ... }

// Good - check existence first
$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
if (empty($nonce) || !wp_verify_nonce($nonce, 'action_name')) {
    wp_send_json_error(array('message' => 'Invalid security token'));
}
```

**Pattern 2: Capability Checks**
```php
if (!current_user_can('manage_options')) {
    wp_send_json_error(array('message' => 'Unauthorized'));
}
```

**Pattern 3: Input Sanitization**
```php
$key = sanitize_text_field($_POST['data']['publishable_key']);
```

**Pattern 4: Output Escaping**
```php
echo esc_html($event['event_type']);
echo esc_attr($event['status']);
echo esc_url($webhook_url);
```

---

### AJAX Polling Implementation

**Before (problematic):**
```javascript
setInterval(function() {
    location.reload();
}, 5000);
```
Issues: Wastes bandwidth, resets scroll, 5s too frequent.

**After (better):**
```javascript
function refreshEvents() {
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'stripe_cli_demo_get_events',
            nonce: '<?php echo wp_create_nonce("stripe_cli_demo_events"); ?>'
        },
        success: function(response) {
            if (response.success && response.data.html) {
                $('#webhook-events-container').html(response.data.html);
            }
        },
        complete: function() {
            setTimeout(refreshEvents, 10000);
        }
    });
}
setTimeout(refreshEvents, 10000);
```

**Key points:**
- Use `setTimeout` in `complete` callback for sequential polling
- Don't use `setInterval` (can stack requests if server slow)
- 10 seconds is reasonable for non-critical updates
- Return rendered HTML from server for simplicity

---

### WordPress REST API for Webhooks

**Registration:**
```php
add_action('rest_api_init', function() {
    register_rest_route('stripe-cli-demo/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => array($this, 'handle_webhook'),
        'permission_callback' => '__return_true', // Public endpoint
    ));
});
```

**Stripe Signature Verification:**
```php
try {
    $event = \Stripe\Webhook::constructEvent(
        $request->get_body(),
        $request->get_header('stripe-signature'),
        $webhook_secret
    );
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    return new WP_REST_Response(array('error' => 'Invalid signature'), 400);
}
```

**Lesson:** Webhook endpoints must be public (`permission_callback => '__return_true'`), but verify authenticity via Stripe signature.

---

## Useful Commands Reference

```bash
# Start webhook listener
stripe listen --forward-to yoursite.local/wp-json/stripe-cli-demo/v1/webhook

# With debug output
stripe listen --forward-to yoursite.local/wp-json/stripe-cli-demo/v1/webhook --format JSON

# Trigger specific events
stripe trigger payment_intent.succeeded
stripe trigger checkout.session.completed
stripe trigger customer.subscription.created

# Check CLI config
stripe config --list

# Test card numbers
4242 4242 4242 4242  # Succeeds
4000 0000 0000 3220  # 3D Secure required
4000 0000 0000 9995  # Declined
```
