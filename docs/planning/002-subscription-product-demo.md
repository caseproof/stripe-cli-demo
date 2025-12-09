# Planning: Subscription Product Demo

> **Status:** Planned
> **Priority:** Medium
> **Complexity:** Medium

## Overview

Add subscription product support to demonstrate recurring payment webhooks. This is particularly valuable for teams working with MemberPress or other membership plugins.

## Goals

1. Demonstrate subscription-specific webhook events
2. Show the full subscription lifecycle (create, invoice, renew, cancel)
3. Provide test scenarios for recurring billing edge cases

## Subscription Webhook Events

Key events that subscriptions generate:

| Event | When It Fires |
|-------|---------------|
| `customer.subscription.created` | New subscription starts |
| `customer.subscription.updated` | Subscription modified |
| `customer.subscription.deleted` | Subscription cancelled |
| `invoice.created` | Invoice generated for billing cycle |
| `invoice.paid` | Invoice payment succeeded |
| `invoice.payment_failed` | Invoice payment failed |
| `invoice.upcoming` | Invoice will be created soon |
| `payment_intent.succeeded` | Each payment succeeds |
| `charge.succeeded` | Each charge succeeds |

## Proposed Subscription Products

| Product | Price | Interval | Purpose |
|---------|-------|----------|---------|
| Monthly Widget | $5.00/mo | Monthly | Standard recurring |
| Annual Widget | $50.00/yr | Yearly | Annual billing test |
| Weekly Test | $1.00/wk | Weekly | Rapid webhook testing |

## Implementation Plan

### Step 1: Add Subscription Products to Registry

Extend `class-products.php`:

```php
public static function get_products() {
    return array(
        // ... existing one-time products ...

        'monthly_widget' => array(
            'name' => 'Monthly Widget',
            'description' => 'A $5/month subscription for testing recurring webhooks',
            'price' => 500,
            'currency' => 'usd',
            'type' => 'subscription',
            'interval' => 'month',
            'interval_count' => 1,
        ),
        'annual_widget' => array(
            'name' => 'Annual Widget',
            'description' => 'A $50/year subscription',
            'price' => 5000,
            'currency' => 'usd',
            'type' => 'subscription',
            'interval' => 'year',
            'interval_count' => 1,
        ),
        'weekly_test' => array(
            'name' => 'Weekly Test',
            'description' => 'A $1/week subscription for rapid testing',
            'price' => 100,
            'currency' => 'usd',
            'type' => 'subscription',
            'interval' => 'week',
            'interval_count' => 1,
        ),
    );
}

public static function is_subscription($product_id) {
    $product = self::get_product($product_id);
    return $product && isset($product['type']) && $product['type'] === 'subscription';
}
```

### Step 2: Update Checkout Handler for Subscriptions

Modify `class-checkout.php`:

```php
public function create_checkout_session() {
    // ... validation ...

    $product = Stripe_CLI_Demo_Products::get_product($product_id);
    $is_subscription = Stripe_CLI_Demo_Products::is_subscription($product_id);

    $line_item = [
        'price_data' => [
            'currency' => $product['currency'],
            'product_data' => [
                'name' => $product['name'],
                'description' => $product['description'],
            ],
            'unit_amount' => $product['price'],
        ],
        'quantity' => 1,
    ];

    // Add recurring for subscriptions
    if ($is_subscription) {
        $line_item['price_data']['recurring'] = [
            'interval' => $product['interval'],
            'interval_count' => $product['interval_count'],
        ];
    }

    $session_params = [
        'payment_method_types' => ['card'],
        'line_items' => [$line_item],
        'mode' => $is_subscription ? 'subscription' : 'payment',
        'success_url' => admin_url('admin.php?page=stripe-cli-demo&status=success'),
        'cancel_url' => admin_url('admin.php?page=stripe-cli-demo&status=cancelled'),
        'metadata' => [
            'product_id' => $product_id,
            'product_type' => $is_subscription ? 'subscription' : 'one_time',
        ],
    ];

    $session = \Stripe\Checkout\Session::create($session_params);
}
```

### Step 3: Update Demo Store UI

Add subscription section to `class-admin-pages.php`:

```php
public static function render_demo_page() {
    $products = Stripe_CLI_Demo_Products::get_products();
    $one_time = array_filter($products, function($p) {
        return !isset($p['type']) || $p['type'] !== 'subscription';
    });
    $subscriptions = array_filter($products, function($p) {
        return isset($p['type']) && $p['type'] === 'subscription';
    });
    ?>
    <div class="stripe-cli-demo-container">
        <h2><?php _e('One-Time Products', 'stripe-cli-demo'); ?></h2>
        <div class="products-grid">
            <?php foreach ($one_time as $id => $product): ?>
                <!-- product card -->
            <?php endforeach; ?>
        </div>

        <h2 style="margin-top: 30px;"><?php _e('Subscription Products', 'stripe-cli-demo'); ?></h2>
        <p class="description"><?php _e('Test recurring payment webhooks', 'stripe-cli-demo'); ?></p>
        <div class="products-grid">
            <?php foreach ($subscriptions as $id => $product): ?>
                <div class="product-card subscription-card" data-product-id="<?php echo esc_attr($id); ?>">
                    <span class="product-badge"><?php _e('Subscription', 'stripe-cli-demo'); ?></span>
                    <h2 class="product-name"><?php echo esc_html($product['name']); ?></h2>
                    <p class="product-description"><?php echo esc_html($product['description']); ?></p>
                    <div class="product-price">
                        $<?php echo number_format($product['price'] / 100, 2); ?>/<?php echo esc_html($product['interval']); ?>
                    </div>
                    <button type="button" class="button button-primary stripe-cli-demo-buy-btn">
                        <?php _e('Subscribe', 'stripe-cli-demo'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
```

### Step 4: Update Webhook Handler

Extend `class-webhook.php` to handle subscription events:

```php
private function process_event($event) {
    $type = $event->type ?? '';
    $events = get_option('stripe_cli_demo_webhook_events', array());

    if (empty($events)) return;

    switch ($type) {
        // Existing payment events...

        // Subscription lifecycle
        case 'customer.subscription.created':
            $events[0]['status'] = 'processed';
            $this->debug_log('Subscription created - ' . ($event->data->object->id ?? 'unknown'));
            break;

        case 'customer.subscription.updated':
            $events[0]['status'] = 'processed';
            $this->debug_log('Subscription updated');
            break;

        case 'customer.subscription.deleted':
            $events[0]['status'] = 'processed';
            $this->debug_log('Subscription cancelled');
            break;

        // Invoice events
        case 'invoice.created':
            $events[0]['status'] = 'processed';
            $this->debug_log('Invoice created');
            break;

        case 'invoice.paid':
            $events[0]['status'] = 'processed';
            $this->debug_log('Invoice paid');
            break;

        case 'invoice.payment_failed':
            $events[0]['status'] = 'failed';
            $this->debug_log('Invoice payment failed');
            break;

        default:
            $events[0]['status'] = 'unhandled';
    }

    update_option('stripe_cli_demo_webhook_events', $events);
}
```

### Step 5: Add Subscription Management Section

Add a section to view/cancel test subscriptions:

```php
// In class-admin-pages.php
public static function render_subscriptions_section() {
    ?>
    <div class="info-box" style="margin-top: 20px;">
        <h3><?php _e('Manage Test Subscriptions', 'stripe-cli-demo'); ?></h3>
        <p><?php _e('Cancel test subscriptions from your Stripe Dashboard:', 'stripe-cli-demo'); ?></p>
        <a href="https://dashboard.stripe.com/test/subscriptions" target="_blank" class="button">
            <?php _e('View Subscriptions in Stripe', 'stripe-cli-demo'); ?>
        </a>
        <p class="description" style="margin-top: 10px;">
            <?php _e('Or use the CLI to trigger subscription events:', 'stripe-cli-demo'); ?>
        </p>
        <pre>stripe trigger customer.subscription.created
stripe trigger invoice.paid
stripe trigger customer.subscription.deleted</pre>
    </div>
    <?php
}
```

### Step 6: Update Event Viewer

Enhance event cards to show subscription-specific info:

```php
// In event card rendering
<?php if (strpos($event['event_type'], 'subscription') !== false): ?>
    <span class="event-badge subscription-event"><?php _e('Subscription', 'stripe-cli-demo'); ?></span>
<?php endif; ?>
<?php if (strpos($event['event_type'], 'invoice') !== false): ?>
    <span class="event-badge invoice-event"><?php _e('Invoice', 'stripe-cli-demo'); ?></span>
<?php endif; ?>
```

## Files to Modify

| File | Changes |
|------|---------|
| `includes/class-products.php` | Add subscription products, is_subscription() helper |
| `includes/class-checkout.php` | Handle subscription mode, recurring price_data |
| `includes/class-admin-pages.php` | Subscription section, management links |
| `includes/class-webhook.php` | Process subscription/invoice events |
| `assets/css/admin.css` | Subscription card styling, badges |

## Testing Checklist

- [ ] Monthly subscription checkout works
- [ ] Annual subscription checkout works
- [ ] `customer.subscription.created` event received
- [ ] `invoice.paid` event received
- [ ] Event viewer shows subscription badge
- [ ] Link to Stripe Dashboard works

### CLI Testing Commands

```bash
# Trigger subscription events manually
stripe trigger customer.subscription.created
stripe trigger customer.subscription.updated
stripe trigger customer.subscription.deleted
stripe trigger invoice.created
stripe trigger invoice.paid
stripe trigger invoice.payment_failed

# Create a subscription with specific card
stripe trigger customer.subscription.created --stripe-account acct_xxx
```

### Test Cards for Subscriptions

| Card | Behavior |
|------|----------|
| `4242 4242 4242 4242` | Succeeds always |
| `4000 0000 0000 0341` | Attaches, but first charge fails |
| `4000 0000 0000 9995` | Always declines |

## Dependencies

- Requires "Multiple Test Products" (001) to be implemented first for shared product registry

## Estimated Effort

Medium - requires understanding of Stripe subscription model and additional webhook handling.

## Notes

- Test subscriptions will persist in Stripe account until cancelled
- Consider adding a "cleanup" button to cancel all test subscriptions
- Weekly interval is useful for rapid testing of renewal webhooks
