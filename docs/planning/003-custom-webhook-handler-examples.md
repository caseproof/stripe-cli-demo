# Planning: Custom Webhook Handler Examples

> **Status:** Planned
> **Priority:** Medium
> **Complexity:** Low-Medium

## Overview

Provide documented code examples showing how to build custom webhook handlers. This turns the demo plugin into an educational resource for developers learning Stripe webhook integration.

## Goals

1. Teach webhook handling patterns (not just demonstrate them)
2. Show real-world scenarios developers encounter
3. Provide copy-paste examples for common use cases
4. Document best practices and security considerations

## Example Categories

### Category 1: Basic Patterns
- Event type routing
- Signature verification
- Idempotency handling
- Error responses

### Category 2: Payment Scenarios
- Fulfillment on successful payment
- Handling failed payments
- Refund processing
- Dispute handling

### Category 3: Subscription Scenarios
- Subscription activation
- Renewal processing
- Cancellation handling
- Trial management
- Dunning (failed payment retry)

### Category 4: Advanced Patterns
- Webhook retries and timeouts
- Async processing with queues
- Multi-tenant webhook routing
- Event logging and debugging

## Implementation Plan

### Step 1: Create Examples Directory Structure

```
docs/
└── examples/
    ├── README.md
    ├── basic/
    │   ├── 01-signature-verification.php
    │   ├── 02-event-routing.php
    │   └── 03-idempotency.php
    ├── payments/
    │   ├── 01-fulfill-order.php
    │   ├── 02-handle-failure.php
    │   └── 03-process-refund.php
    ├── subscriptions/
    │   ├── 01-activate-subscription.php
    │   ├── 02-handle-renewal.php
    │   ├── 03-handle-cancellation.php
    │   └── 04-trial-management.php
    └── advanced/
        ├── 01-retry-handling.php
        ├── 02-async-processing.php
        └── 03-debugging-tips.md
```

### Step 2: Example File Format

Each example follows a consistent format:

```php
<?php
/**
 * Example: [Title]
 *
 * Description of what this example demonstrates.
 *
 * Webhook events handled:
 * - event.type.one
 * - event.type.two
 *
 * @see https://stripe.com/docs/webhooks
 */

// ============================================
// EXAMPLE CODE - Adapt for your application
// ============================================

/**
 * [Detailed explanation of the pattern]
 */
function example_webhook_handler($event) {
    // Implementation with detailed comments
}

// ============================================
// USAGE NOTES
// ============================================

/*
 * How to use this example:
 * 1. Copy this function to your webhook handler
 * 2. Modify [x] to match your application
 * 3. Test with: stripe trigger [event_type]
 *
 * Common gotchas:
 * - [Gotcha 1]
 * - [Gotcha 2]
 */
```

### Step 3: Core Examples

#### Example: Signature Verification (basic/01-signature-verification.php)

```php
<?php
/**
 * Example: Webhook Signature Verification
 *
 * ALWAYS verify webhook signatures to ensure events are from Stripe.
 * Never process unverified webhooks in production.
 */

function verify_stripe_webhook($payload, $sig_header, $webhook_secret) {
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sig_header,
            $webhook_secret
        );
        return $event;
    } catch (\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        error_log('Stripe webhook error: Invalid payload');
        exit('Invalid payload');
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(400);
        error_log('Stripe webhook error: Invalid signature');
        exit('Invalid signature');
    }
}

// Usage in WordPress REST API
add_action('rest_api_init', function() {
    register_rest_route('my-plugin/v1', '/webhook', [
        'methods' => 'POST',
        'callback' => function($request) {
            $event = verify_stripe_webhook(
                $request->get_body(),
                $request->get_header('stripe-signature'),
                get_option('my_webhook_secret')
            );

            // Process verified event...
            return new WP_REST_Response(['received' => true], 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
```

#### Example: Order Fulfillment (payments/01-fulfill-order.php)

```php
<?php
/**
 * Example: Fulfill Order on Successful Payment
 *
 * Webhook events handled:
 * - checkout.session.completed
 * - payment_intent.succeeded
 *
 * Important: Use checkout.session.completed for Checkout,
 * or payment_intent.succeeded for PaymentIntents API.
 */

function fulfill_order_on_payment($event) {
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;

            // Get order ID from metadata (set during checkout creation)
            $order_id = $session->metadata->order_id ?? null;

            if (!$order_id) {
                error_log('No order_id in session metadata');
                return;
            }

            // Check payment status
            if ($session->payment_status === 'paid') {
                // Payment complete - fulfill immediately
                fulfill_order($order_id);
            } elseif ($session->payment_status === 'unpaid') {
                // Async payment (bank transfer, etc.)
                // Wait for checkout.session.async_payment_succeeded
                mark_order_pending($order_id);
            }
            break;

        case 'checkout.session.async_payment_succeeded':
            $session = $event->data->object;
            $order_id = $session->metadata->order_id ?? null;
            if ($order_id) {
                fulfill_order($order_id);
            }
            break;

        case 'checkout.session.async_payment_failed':
            $session = $event->data->object;
            $order_id = $session->metadata->order_id ?? null;
            if ($order_id) {
                cancel_order($order_id, 'Payment failed');
            }
            break;
    }
}

function fulfill_order($order_id) {
    // Your fulfillment logic:
    // - Update order status
    // - Send confirmation email
    // - Grant access to digital goods
    // - Trigger shipping
    error_log("Fulfilling order: $order_id");
}

function mark_order_pending($order_id) {
    error_log("Order pending payment: $order_id");
}

function cancel_order($order_id, $reason) {
    error_log("Cancelling order $order_id: $reason");
}
```

#### Example: Subscription Activation (subscriptions/01-activate-subscription.php)

```php
<?php
/**
 * Example: Activate User Subscription
 *
 * Webhook events handled:
 * - customer.subscription.created
 * - customer.subscription.updated
 * - customer.subscription.deleted
 *
 * Pattern: Map Stripe subscription to user account via metadata.
 */

function handle_subscription_event($event) {
    $subscription = $event->data->object;

    // Get user ID from subscription metadata
    // (Set this when creating the subscription)
    $user_id = $subscription->metadata->user_id ?? null;

    if (!$user_id) {
        // Try to find user by Stripe customer ID
        $user_id = get_user_by_stripe_customer($subscription->customer);
    }

    if (!$user_id) {
        error_log('Cannot find user for subscription: ' . $subscription->id);
        return;
    }

    switch ($event->type) {
        case 'customer.subscription.created':
        case 'customer.subscription.updated':
            $status = $subscription->status;

            if ($status === 'active' || $status === 'trialing') {
                // Grant access
                activate_user_subscription($user_id, [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->items->data[0]->price->id,
                    'current_period_end' => $subscription->current_period_end,
                    'status' => $status,
                ]);
            } elseif ($status === 'past_due') {
                // Payment failed but still in grace period
                flag_subscription_past_due($user_id, $subscription->id);
            } elseif ($status === 'canceled' || $status === 'unpaid') {
                // Revoke access
                deactivate_user_subscription($user_id, $subscription->id);
            }
            break;

        case 'customer.subscription.deleted':
            // Subscription fully cancelled
            deactivate_user_subscription($user_id, $subscription->id);
            break;
    }
}

function activate_user_subscription($user_id, $data) {
    // Store subscription data in user meta
    update_user_meta($user_id, 'stripe_subscription', $data);
    update_user_meta($user_id, 'subscription_status', 'active');

    // Grant role/capability
    $user = get_user_by('ID', $user_id);
    $user->add_role('subscriber');

    error_log("Activated subscription for user $user_id");
}

function deactivate_user_subscription($user_id, $subscription_id) {
    delete_user_meta($user_id, 'stripe_subscription');
    update_user_meta($user_id, 'subscription_status', 'inactive');

    $user = get_user_by('ID', $user_id);
    $user->remove_role('subscriber');

    error_log("Deactivated subscription for user $user_id");
}
```

#### Example: Idempotency (basic/03-idempotency.php)

```php
<?php
/**
 * Example: Idempotent Webhook Handling
 *
 * Stripe may send the same event multiple times (retries).
 * Your handler must be idempotent - processing the same event
 * twice should have the same result as processing it once.
 */

function idempotent_webhook_handler($event) {
    $event_id = $event->id;

    // Check if we've already processed this event
    $processed = get_option('stripe_processed_events', []);

    if (in_array($event_id, $processed)) {
        // Already processed - return success without re-processing
        error_log("Skipping duplicate event: $event_id");
        return ['status' => 'already_processed'];
    }

    // Process the event
    $result = process_event($event);

    // Mark as processed
    $processed[] = $event_id;

    // Keep only last 1000 events to prevent unbounded growth
    if (count($processed) > 1000) {
        $processed = array_slice($processed, -1000);
    }

    update_option('stripe_processed_events', $processed);

    return $result;
}

// Alternative: Use database with unique constraint
function idempotent_with_database($event) {
    global $wpdb;

    $event_id = $event->id;
    $table = $wpdb->prefix . 'stripe_webhook_events';

    // Try to insert - will fail if duplicate
    $inserted = $wpdb->insert($table, [
        'event_id' => $event_id,
        'event_type' => $event->type,
        'processed_at' => current_time('mysql'),
    ]);

    if ($inserted === false) {
        // Duplicate - already processed
        return ['status' => 'already_processed'];
    }

    // Process the event
    return process_event($event);
}
```

### Step 4: Add Examples Tab to Admin UI

Add a tab in the plugin to view examples:

```php
// In class-admin-pages.php
public static function render_examples_page() {
    $examples_dir = STRIPE_CLI_DEMO_PATH . 'docs/examples/';
    $categories = [
        'basic' => __('Basic Patterns', 'stripe-cli-demo'),
        'payments' => __('Payment Handling', 'stripe-cli-demo'),
        'subscriptions' => __('Subscriptions', 'stripe-cli-demo'),
        'advanced' => __('Advanced', 'stripe-cli-demo'),
    ];
    ?>
    <div class="wrap">
        <h1><?php _e('Webhook Handler Examples', 'stripe-cli-demo'); ?></h1>
        <p class="description">
            <?php _e('Copy-paste examples for common webhook handling patterns.', 'stripe-cli-demo'); ?>
        </p>

        <?php foreach ($categories as $folder => $title): ?>
            <h2><?php echo esc_html($title); ?></h2>
            <div class="examples-grid">
                <?php
                $files = glob($examples_dir . $folder . '/*.php');
                foreach ($files as $file):
                    $content = file_get_contents($file);
                    $filename = basename($file);
                ?>
                    <div class="example-card">
                        <h3><?php echo esc_html($filename); ?></h3>
                        <pre><code><?php echo esc_html($content); ?></code></pre>
                        <button class="button copy-btn" data-copy="<?php echo esc_attr($content); ?>">
                            <?php _e('Copy Code', 'stripe-cli-demo'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
```

### Step 5: Create Examples README

```markdown
# Webhook Handler Examples

This directory contains copy-paste examples for common Stripe webhook patterns.

## How to Use

1. Find the example matching your use case
2. Copy the code to your plugin/theme
3. Modify the placeholder functions for your application
4. Test with `stripe trigger [event_type]`

## Categories

### Basic (`basic/`)
Foundation patterns every webhook handler needs.

### Payments (`payments/`)
Handle one-time payment scenarios.

### Subscriptions (`subscriptions/`)
Handle recurring billing events.

### Advanced (`advanced/`)
Production-ready patterns for scale.

## Testing Examples

Use the Stripe CLI to trigger events:

```bash
# Basic payment
stripe trigger payment_intent.succeeded

# Checkout session
stripe trigger checkout.session.completed

# Subscription lifecycle
stripe trigger customer.subscription.created
stripe trigger invoice.paid
stripe trigger customer.subscription.deleted
```

## Best Practices

1. **Always verify signatures** - Never trust unverified webhooks
2. **Be idempotent** - Handle duplicate events gracefully
3. **Return 200 quickly** - Process async if needed
4. **Log everything** - Debug webhooks are notoriously tricky
5. **Handle all statuses** - Don't assume success
```

## Files to Create/Modify

| File | Purpose |
|------|---------|
| `docs/examples/README.md` | Overview and usage guide |
| `docs/examples/basic/*.php` | Basic pattern examples |
| `docs/examples/payments/*.php` | Payment handling examples |
| `docs/examples/subscriptions/*.php` | Subscription examples |
| `docs/examples/advanced/*.php` | Advanced pattern examples |
| `includes/class-admin-pages.php` | Examples viewer tab (optional) |

## Testing Checklist

- [ ] All example files have consistent format
- [ ] Code examples are syntactically valid PHP
- [ ] Each example includes test commands
- [ ] README provides clear guidance
- [ ] Copy button works in examples viewer

## Dependencies

None - documentation only.

## Estimated Effort

Low-Medium - primarily documentation writing with optional UI.

## Notes

- Examples should work standalone (no plugin dependencies)
- Include WordPress-specific and framework-agnostic versions
- Keep examples focused - one concept per file
- Update examples when Stripe API changes
