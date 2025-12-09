# Planning: Multiple Test Products

> **Status:** Planned
> **Priority:** Low
> **Complexity:** Low

## Overview

Add multiple test products with varying prices to demonstrate different payment scenarios and webhook behaviors.

## Goals

1. Provide variety in test scenarios (different amounts, currencies)
2. Show how product metadata flows through webhooks
3. Enable testing of edge cases (small amounts, large amounts)

## Proposed Products

| Product | Price | Purpose |
|---------|-------|---------|
| Demo Widget | $1.00 | Current product, basic test |
| Premium Widget | $25.00 | Higher value transaction |
| Micro Widget | $0.50 | Minimum viable amount |
| Enterprise Widget | $100.00 | Larger transaction testing |

## Implementation Plan

### Step 1: Update Product Configuration

Create a centralized product registry in the plugin:

```php
// includes/class-products.php
class Stripe_CLI_Demo_Products {

    public static function get_products() {
        return array(
            'demo_widget' => array(
                'name' => 'Demo Widget',
                'description' => 'A simple $1 product for testing',
                'price' => 100, // cents
                'currency' => 'usd',
            ),
            'premium_widget' => array(
                'name' => 'Premium Widget',
                'description' => 'A $25 product for higher-value testing',
                'price' => 2500,
                'currency' => 'usd',
            ),
            'micro_widget' => array(
                'name' => 'Micro Widget',
                'description' => 'A $0.50 minimum amount test',
                'price' => 50,
                'currency' => 'usd',
            ),
            'enterprise_widget' => array(
                'name' => 'Enterprise Widget',
                'description' => 'A $100 larger transaction test',
                'price' => 10000,
                'currency' => 'usd',
            ),
        );
    }

    public static function get_product($id) {
        $products = self::get_products();
        return isset($products[$id]) ? $products[$id] : null;
    }
}
```

### Step 2: Update Demo Store UI

Modify `class-admin-pages.php` to render multiple product cards:

```php
public static function render_demo_page() {
    $products = Stripe_CLI_Demo_Products::get_products();
    ?>
    <div class="stripe-cli-demo-container">
        <div class="products-grid">
            <?php foreach ($products as $id => $product): ?>
                <div class="product-card" data-product-id="<?php echo esc_attr($id); ?>">
                    <h2 class="product-name"><?php echo esc_html($product['name']); ?></h2>
                    <p class="product-description"><?php echo esc_html($product['description']); ?></p>
                    <div class="product-price">
                        $<?php echo number_format($product['price'] / 100, 2); ?>
                    </div>
                    <button type="button" class="button button-primary stripe-cli-demo-buy-btn">
                        <?php _e('Buy Now', 'stripe-cli-demo'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
```

### Step 3: Update Checkout Handler

Modify `class-checkout.php` to accept product ID:

```php
public function create_checkout_session() {
    // ... nonce/capability checks ...

    $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : 'demo_widget';
    $product = Stripe_CLI_Demo_Products::get_product($product_id);

    if (!$product) {
        wp_send_json_error(array('message' => 'Invalid product'));
    }

    $session = \Stripe\Checkout\Session::create([
        'line_items' => [[
            'price_data' => [
                'currency' => $product['currency'],
                'product_data' => [
                    'name' => $product['name'],
                    'description' => $product['description'],
                ],
                'unit_amount' => $product['price'],
            ],
            'quantity' => 1,
        ]],
        'metadata' => [
            'product_id' => $product_id,
            // ... rest of metadata
        ],
        // ... rest of config
    ]);
}
```

### Step 4: Update JavaScript

Modify `assets/js/admin.js` to pass product ID:

```javascript
$('.stripe-cli-demo-buy-btn').on('click', function() {
    var productId = $(this).closest('.product-card').data('product-id');

    $.ajax({
        // ...
        data: {
            action: 'stripe_cli_demo_create_checkout',
            nonce: stripeCliDemo.nonce,
            product_id: productId
        },
        // ...
    });
});
```

### Step 5: Update CSS

Add grid layout to `assets/css/admin.css`:

```css
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.product-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}
```

## Files to Modify

| File | Changes |
|------|---------|
| `includes/class-products.php` | **NEW** - Product registry |
| `stripe-cli-demo.php` | Add require for class-products.php |
| `includes/class-admin-pages.php` | Render product grid |
| `includes/class-checkout.php` | Accept product_id parameter |
| `assets/js/admin.js` | Pass product_id in AJAX |
| `assets/css/admin.css` | Grid layout styles |

## Testing Checklist

- [ ] All 4 products display correctly
- [ ] Each product creates correct Stripe Checkout session
- [ ] Webhook events show correct product metadata
- [ ] Event viewer displays product info
- [ ] Mobile responsive grid layout

## Dependencies

None - uses existing Stripe inline price_data approach.

## Estimated Effort

Low - straightforward UI and data structure changes.
