# Planning: MemberPress Integration

> **Status:** Planned
> **Priority:** High
> **Complexity:** Medium-High

## Overview

Integrate with MemberPress to demonstrate real-world webhook handling in a production membership plugin. This shows how Stripe webhooks flow through MemberPress and affect membership status.

## Goals

1. Show how MemberPress processes Stripe webhooks
2. Demonstrate membership lifecycle (signup, renewal, cancellation)
3. Help team debug MemberPress + Stripe webhook issues
4. Provide visibility into MemberPress's internal Stripe handling

## Prerequisites

- MemberPress plugin installed and activated
- MemberPress Stripe Gateway addon
- Stripe account connected to MemberPress
- Test memberships configured in MemberPress

## MemberPress Webhook Architecture

### How MemberPress Handles Stripe Webhooks

```
Stripe → MemberPress Webhook URL → MemberPress Gateway → Membership Updates
         /mepr/notify/stripe/
```

MemberPress registers its own webhook endpoint at `/mepr/notify/stripe/` and handles:

| Stripe Event | MemberPress Action |
|--------------|-------------------|
| `checkout.session.completed` | Create/activate subscription |
| `invoice.payment_succeeded` | Renew membership |
| `invoice.payment_failed` | Mark membership as failed |
| `customer.subscription.deleted` | Cancel membership |
| `charge.refunded` | Handle refund (varies by setting) |
| `charge.dispute.created` | Flag disputed transaction |

### MemberPress Database Tables

| Table | Purpose |
|-------|---------|
| `wp_mepr_transactions` | Payment records |
| `wp_mepr_subscriptions` | Recurring subscription data |
| `wp_mepr_members` | Member metadata |
| `wp_usermeta` | User capabilities/roles |

## Implementation Plan

### Step 1: Add MemberPress Detection

Check if MemberPress is active:

```php
// includes/class-memberpress-integration.php
class Stripe_CLI_Demo_MemberPress {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (!$this->is_memberpress_active()) {
            return;
        }

        add_action('admin_menu', array($this, 'add_memberpress_submenu'), 20);
        add_action('mepr_stripe_webhook_received', array($this, 'log_memberpress_webhook'), 10, 2);
    }

    public function is_memberpress_active() {
        return class_exists('MeprCtrlFactory') && defined('MEPR_VERSION');
    }

    public function is_stripe_gateway_active() {
        if (!$this->is_memberpress_active()) {
            return false;
        }
        $gateways = MeprOptions::fetch()->integrations;
        foreach ($gateways as $gateway) {
            if (isset($gateway['gateway']) && strpos($gateway['gateway'], 'Stripe') !== false) {
                return true;
            }
        }
        return false;
    }
}
```

### Step 2: Hook into MemberPress Webhook Processing

MemberPress fires actions when processing webhooks:

```php
// Log MemberPress webhook events
public function log_memberpress_webhook($event_type, $event_data) {
    $log_entry = array(
        'timestamp' => current_time('Y-m-d H:i:s'),
        'source' => 'memberpress',
        'event_type' => $event_type,
        'event_id' => $event_data->id ?? 'unknown',
        'status' => 'memberpress_processed',
        'data' => array(
            'customer' => $event_data->data->object->customer ?? null,
            'subscription' => $event_data->data->object->subscription ?? null,
            'amount' => $event_data->data->object->amount ?? null,
        ),
    );

    $events = get_option('stripe_cli_demo_webhook_events', array());
    array_unshift($events, $log_entry);
    $events = array_slice($events, 0, 50);
    update_option('stripe_cli_demo_webhook_events', $events);
}

// Hook into MemberPress subscription events
add_action('mepr-txn-store', function($txn) {
    if ($txn->gateway == 'MeprStripeGateway') {
        do_action('stripe_cli_demo_mepr_transaction', $txn);
    }
}, 10, 1);

add_action('mepr-event-subscription-created', function($event) {
    do_action('stripe_cli_demo_mepr_subscription_created', $event);
}, 10, 1);

add_action('mepr-event-subscription-stopped', function($event) {
    do_action('stripe_cli_demo_mepr_subscription_stopped', $event);
}, 10, 1);
```

### Step 3: Create MemberPress Dashboard Page

```php
public function add_memberpress_submenu() {
    add_submenu_page(
        'stripe-cli-demo',
        __('MemberPress Integration', 'stripe-cli-demo'),
        __('MemberPress', 'stripe-cli-demo'),
        'manage_options',
        'stripe-cli-demo-memberpress',
        array($this, 'render_memberpress_page')
    );
}

public function render_memberpress_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $mepr_active = $this->is_memberpress_active();
    $stripe_gateway = $this->is_stripe_gateway_active();
    ?>
    <div class="wrap stripe-cli-demo-wrap">
        <h1><?php _e('MemberPress + Stripe Webhooks', 'stripe-cli-demo'); ?></h1>

        <div class="status-cards">
            <div class="status-card <?php echo $mepr_active ? 'status-ok' : 'status-error'; ?>">
                <h3><?php _e('MemberPress', 'stripe-cli-demo'); ?></h3>
                <p><?php echo $mepr_active ? '✓ Active' : '✗ Not Active'; ?></p>
            </div>

            <div class="status-card <?php echo $stripe_gateway ? 'status-ok' : 'status-error'; ?>">
                <h3><?php _e('Stripe Gateway', 'stripe-cli-demo'); ?></h3>
                <p><?php echo $stripe_gateway ? '✓ Configured' : '✗ Not Configured'; ?></p>
            </div>
        </div>

        <?php if ($mepr_active && $stripe_gateway): ?>
            <?php $this->render_memberpress_webhook_info(); ?>
            <?php $this->render_recent_memberpress_transactions(); ?>
            <?php $this->render_test_membership_products(); ?>
        <?php else: ?>
            <div class="notice notice-error">
                <p><?php _e('MemberPress with Stripe Gateway required for this integration.', 'stripe-cli-demo'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
```

### Step 4: Display MemberPress Webhook Info

```php
private function render_memberpress_webhook_info() {
    $webhook_url = home_url('/mepr/notify/stripe/');
    ?>
    <div class="info-box">
        <h3><?php _e('MemberPress Webhook Endpoint', 'stripe-cli-demo'); ?></h3>
        <p><?php _e('MemberPress listens for Stripe webhooks at:', 'stripe-cli-demo'); ?></p>
        <pre><?php echo esc_url($webhook_url); ?></pre>

        <h4><?php _e('For Local Testing', 'stripe-cli-demo'); ?></h4>
        <p><?php _e('Forward webhooks to MemberPress:', 'stripe-cli-demo'); ?></p>
        <pre>stripe listen --forward-to <?php echo esc_url($webhook_url); ?></pre>
        <button type="button" class="button copy-btn" data-copy="stripe listen --forward-to <?php echo esc_url($webhook_url); ?>">
            <?php _e('Copy Command', 'stripe-cli-demo'); ?>
        </button>

        <h4 style="margin-top: 20px;"><?php _e('Webhook Events MemberPress Handles', 'stripe-cli-demo'); ?></h4>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Stripe Event', 'stripe-cli-demo'); ?></th>
                    <th><?php _e('MemberPress Action', 'stripe-cli-demo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>checkout.session.completed</code></td>
                    <td><?php _e('Create subscription, activate membership', 'stripe-cli-demo'); ?></td>
                </tr>
                <tr>
                    <td><code>invoice.payment_succeeded</code></td>
                    <td><?php _e('Renew membership, create transaction', 'stripe-cli-demo'); ?></td>
                </tr>
                <tr>
                    <td><code>invoice.payment_failed</code></td>
                    <td><?php _e('Mark subscription as failing', 'stripe-cli-demo'); ?></td>
                </tr>
                <tr>
                    <td><code>customer.subscription.deleted</code></td>
                    <td><?php _e('Cancel membership', 'stripe-cli-demo'); ?></td>
                </tr>
                <tr>
                    <td><code>charge.refunded</code></td>
                    <td><?php _e('Process refund (per settings)', 'stripe-cli-demo'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}
```

### Step 5: Show Recent MemberPress Transactions

```php
private function render_recent_memberpress_transactions() {
    global $wpdb;
    $table = $wpdb->prefix . 'mepr_transactions';

    $transactions = $wpdb->get_results(
        "SELECT t.*, u.user_email, p.post_title as product_name
         FROM {$table} t
         LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
         LEFT JOIN {$wpdb->posts} p ON t.product_id = p.ID
         WHERE t.gateway = 'MeprStripeGateway'
         ORDER BY t.created_at DESC
         LIMIT 10"
    );
    ?>
    <div class="info-box" style="margin-top: 20px;">
        <h3><?php _e('Recent MemberPress Stripe Transactions', 'stripe-cli-demo'); ?></h3>

        <?php if (empty($transactions)): ?>
            <p><?php _e('No Stripe transactions yet.', 'stripe-cli-demo'); ?></p>
        <?php else: ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'stripe-cli-demo'); ?></th>
                        <th><?php _e('User', 'stripe-cli-demo'); ?></th>
                        <th><?php _e('Product', 'stripe-cli-demo'); ?></th>
                        <th><?php _e('Amount', 'stripe-cli-demo'); ?></th>
                        <th><?php _e('Status', 'stripe-cli-demo'); ?></th>
                        <th><?php _e('Date', 'stripe-cli-demo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?php echo esc_html($txn->id); ?></td>
                            <td><?php echo esc_html($txn->user_email); ?></td>
                            <td><?php echo esc_html($txn->product_name); ?></td>
                            <td>$<?php echo number_format($txn->total, 2); ?></td>
                            <td>
                                <span class="txn-status status-<?php echo esc_attr($txn->status); ?>">
                                    <?php echo esc_html($txn->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($txn->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
```

### Step 6: List Test Membership Products

```php
private function render_test_membership_products() {
    $memberships = MeprCptModel::all('MeprProduct');
    ?>
    <div class="info-box" style="margin-top: 20px;">
        <h3><?php _e('Test Memberships', 'stripe-cli-demo'); ?></h3>
        <p><?php _e('Purchase these memberships to test webhooks:', 'stripe-cli-demo'); ?></p>

        <?php if (empty($memberships)): ?>
            <p><?php _e('No membership products found. Create one in MemberPress → Memberships.', 'stripe-cli-demo'); ?></p>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($memberships as $membership): ?>
                    <?php
                    $product = new MeprProduct($membership->ID);
                    $price = $product->price;
                    $period = $product->period_type;
                    $is_recurring = $product->is_recurring();
                    ?>
                    <div class="product-card">
                        <h4><?php echo esc_html($product->post_title); ?></h4>
                        <div class="product-price">
                            $<?php echo number_format($price, 2); ?>
                            <?php if ($is_recurring): ?>
                                /<?php echo esc_html($period); ?>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo esc_url($product->url()); ?>" class="button button-primary" target="_blank">
                            <?php _e('Test Purchase', 'stripe-cli-demo'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h4 style="margin-top: 20px;"><?php _e('CLI Event Triggers', 'stripe-cli-demo'); ?></h4>
        <p><?php _e('Trigger MemberPress-relevant events:', 'stripe-cli-demo'); ?></p>
        <pre>
# New subscription
stripe trigger checkout.session.completed

# Renewal payment
stripe trigger invoice.payment_succeeded

# Failed payment
stripe trigger invoice.payment_failed

# Cancellation
stripe trigger customer.subscription.deleted
        </pre>
    </div>
    <?php
}
```

### Step 7: Add Event Filtering

Update event viewer to filter MemberPress events:

```php
// In class-admin-pages.php render_events_page()
$show_memberpress = isset($_GET['source']) && $_GET['source'] === 'memberpress';

// Filter events
$events = get_option('stripe_cli_demo_webhook_events', array());
if ($show_memberpress) {
    $events = array_filter($events, function($e) {
        return isset($e['source']) && $e['source'] === 'memberpress';
    });
}
?>
<div class="event-filters">
    <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo-events'); ?>"
       class="button <?php echo !$show_memberpress ? 'button-primary' : ''; ?>">
        <?php _e('All Events', 'stripe-cli-demo'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo-events&source=memberpress'); ?>"
       class="button <?php echo $show_memberpress ? 'button-primary' : ''; ?>">
        <?php _e('MemberPress Events', 'stripe-cli-demo'); ?>
    </a>
</div>
```

## Files to Create/Modify

| File | Changes |
|------|---------|
| `includes/class-memberpress-integration.php` | **NEW** - MemberPress integration class |
| `stripe-cli-demo.php` | Conditionally load MemberPress integration |
| `includes/class-admin-pages.php` | Event filtering, MemberPress badges |
| `assets/css/admin.css` | MemberPress-specific styles |

## Testing Checklist

### Setup
- [ ] MemberPress activated
- [ ] Stripe Gateway configured in MemberPress
- [ ] Test membership product created
- [ ] Stripe CLI listening to MemberPress webhook URL

### Purchase Flow
- [ ] Complete test membership purchase
- [ ] `checkout.session.completed` received
- [ ] MemberPress transaction created
- [ ] User membership activated

### Renewal Flow
- [ ] Trigger `invoice.payment_succeeded`
- [ ] MemberPress transaction created
- [ ] Membership expiration extended

### Cancellation Flow
- [ ] Trigger `customer.subscription.deleted`
- [ ] MemberPress subscription marked cancelled
- [ ] User access revoked (per MemberPress settings)

### Failed Payment Flow
- [ ] Trigger `invoice.payment_failed`
- [ ] MemberPress subscription marked as failing
- [ ] User notified (per MemberPress settings)

## CLI Commands for Testing

```bash
# Start listener for MemberPress
stripe listen --forward-to yoursite.local/mepr/notify/stripe/

# Trigger subscription creation
stripe trigger checkout.session.completed

# Trigger renewal
stripe trigger invoice.payment_succeeded

# Trigger failed payment
stripe trigger invoice.payment_failed

# Trigger cancellation
stripe trigger customer.subscription.deleted

# Trigger refund
stripe trigger charge.refunded
```

## Dependencies

- MemberPress plugin (memberpress.com)
- MemberPress Stripe addon (included with MemberPress)
- Stripe account connected in MemberPress settings

## Estimated Effort

Medium-High - requires understanding MemberPress internals and webhook flow.

## Future: WooCommerce Integration

A similar integration could be built for WooCommerce:
- WooCommerce Stripe Gateway uses `/wc/v3/webhook/` or similar
- Hooks into `woocommerce_payment_complete`, `woocommerce_subscription_*`
- Shows WooCommerce orders and subscriptions

This is lower priority since the team primarily uses MemberPress.

## Notes

- MemberPress webhook URL may vary by configuration
- Test with logged-out user to simulate real customer
- MemberPress may have its own webhook logging (check MemberPress → Settings → Developer Tools)
- Some events require actual Stripe subscriptions (can't always use `stripe trigger`)
