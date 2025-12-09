<?php
/**
 * Admin pages for Stripe CLI Demo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_CLI_Demo_Admin_Pages {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only on our plugin pages
        if (strpos($hook, 'stripe-cli-demo') === false) {
            return;
        }

        wp_enqueue_style(
            'stripe-cli-demo-admin',
            STRIPE_CLI_DEMO_URL . 'assets/css/admin.css',
            array(),
            STRIPE_CLI_DEMO_VERSION
        );

        wp_enqueue_script(
            'stripe-cli-demo-admin',
            STRIPE_CLI_DEMO_URL . 'assets/js/admin.js',
            array('jquery'),
            STRIPE_CLI_DEMO_VERSION,
            true
        );

        wp_localize_script('stripe-cli-demo-admin', 'stripeCliDemo', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('stripe_cli_demo_nonce'),
            'publishableKey' => get_option('stripe_cli_demo_publishable_key', ''),
        ));
    }

    /**
     * Render the demo store page
     */
    public static function render_demo_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $publishable_key = get_option('stripe_cli_demo_publishable_key', '');
        $secret_key = get_option('stripe_cli_demo_secret_key', '');
        $webhook_secret = get_option('stripe_cli_demo_webhook_secret', '');

        $is_configured = !empty($publishable_key) && !empty($secret_key);
        ?>
        <div class="wrap stripe-cli-demo-wrap">
            <h1><?php _e('Stripe CLI Demo Store', 'stripe-cli-demo'); ?></h1>
            <p class="description"><?php _e('A minimal store to demonstrate Stripe CLI webhook testing', 'stripe-cli-demo'); ?></p>

            <?php if (!$is_configured): ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('Missing API Keys!', 'stripe-cli-demo'); ?></strong>
                        <?php _e('Please configure your Stripe API keys in', 'stripe-cli-demo'); ?>
                        <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo-settings'); ?>">
                            <?php _e('Settings', 'stripe-cli-demo'); ?>
                        </a>
                    </p>
                </div>
            <?php else: ?>

                <?php if (empty($webhook_secret)): ?>
                    <div class="notice notice-warning">
                        <p>
                            <strong><?php _e('Webhook Secret Missing!', 'stripe-cli-demo'); ?></strong>
                            <?php _e('Webhooks won\'t be verified. Run stripe listen and add the whsec_ secret to', 'stripe-cli-demo'); ?>
                            <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo-settings'); ?>">
                                <?php _e('Settings', 'stripe-cli-demo'); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="stripe-cli-demo-container">
                    <div class="product-card">
                        <h2 class="product-name"><?php _e('Demo Widget', 'stripe-cli-demo'); ?></h2>
                        <p class="product-description"><?php _e('A simple $1 product for testing Stripe webhooks', 'stripe-cli-demo'); ?></p>
                        <div class="product-price">$1.00</div>

                        <button type="button" id="stripe-cli-demo-buy-btn" class="button button-primary button-hero">
                            <?php _e('Buy Now', 'stripe-cli-demo'); ?>
                        </button>

                        <div id="stripe-cli-demo-status" class="status-message" style="display: none;"></div>
                    </div>

                    <div class="info-box">
                        <h3><?php _e('Testing Webhooks Locally', 'stripe-cli-demo'); ?></h3>
                        <p><?php _e('Run this command to forward Stripe webhooks to your WordPress site:', 'stripe-cli-demo'); ?></p>
                        <pre>stripe listen --forward-to <?php echo esc_url(home_url('/wp-json/stripe-cli-demo/v1/webhook')); ?></pre>

                        <p style="margin-top: 15px;"><strong><?php _e('For debugging (JSON output):', 'stripe-cli-demo'); ?></strong></p>
                        <pre>stripe listen --forward-to <?php echo esc_url(home_url('/wp-json/stripe-cli-demo/v1/webhook')); ?> --format JSON</pre>

                        <p style="margin-top: 15px;">
                            <?php _e('Copy the webhook signing secret (whsec_...) and add it to', 'stripe-cli-demo'); ?>
                            <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo-settings'); ?>"><?php _e('Settings', 'stripe-cli-demo'); ?></a>
                        </p>
                    </div>

                    <div class="info-box" style="margin-top: 20px;">
                        <h3><?php _e('Test Card Numbers', 'stripe-cli-demo'); ?></h3>
                        <table class="widefat" style="margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th><?php _e('Card Number', 'stripe-cli-demo'); ?></th>
                                    <th><?php _e('Description', 'stripe-cli-demo'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>4242 4242 4242 4242</code></td>
                                    <td><?php _e('Succeeds', 'stripe-cli-demo'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>4000 0000 0000 3220</code></td>
                                    <td><?php _e('Requires 3D Secure', 'stripe-cli-demo'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>4000 0000 0000 9995</code></td>
                                    <td><?php _e('Declined (insufficient funds)', 'stripe-cli-demo'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description" style="margin-top: 10px;">
                            <?php _e('Use any future expiry date and any 3-digit CVC.', 'stripe-cli-demo'); ?>
                        </p>
                    </div>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the webhook events page
     */
    public static function render_events_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle clear events action
        if (isset($_POST['clear_events']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_webhook_events')) {
            update_option('stripe_cli_demo_webhook_events', array());
            echo '<div class="notice notice-success"><p>' . __('Events cleared!', 'stripe-cli-demo') . '</p></div>';
        }

        $events = get_option('stripe_cli_demo_webhook_events', array());
        ?>
        <div class="wrap stripe-cli-demo-wrap">
            <h1><?php _e('Webhook Events', 'stripe-cli-demo'); ?></h1>
            <p class="description"><?php _e('Events received from Stripe (auto-refreshes every 5 seconds)', 'stripe-cli-demo'); ?></p>

            <div style="margin: 20px 0;">
                <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo'); ?>" class="button">
                    <?php _e('Back to Store', 'stripe-cli-demo'); ?>
                </a>

                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('clear_webhook_events'); ?>
                    <button type="submit" name="clear_events" class="button">
                        <?php _e('Clear Events', 'stripe-cli-demo'); ?>
                    </button>
                </form>

                <span class="auto-refresh-indicator" style="margin-left: 15px; color: #666;">
                    <?php _e('Auto-refreshing...', 'stripe-cli-demo'); ?>
                </span>
            </div>

            <div id="webhook-events-container">
                <?php if (empty($events)): ?>
                    <div class="no-events">
                        <h2><?php _e('No webhook events yet', 'stripe-cli-demo'); ?></h2>
                        <p><?php _e('Make a test purchase and watch events appear here!', 'stripe-cli-demo'); ?></p>
                        <p style="margin-top: 20px;">
                            <?php _e('Make sure', 'stripe-cli-demo'); ?> <code>stripe listen</code> <?php _e('is running in your terminal.', 'stripe-cli-demo'); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <div class="event-header">
                                <span class="event-type"><?php echo esc_html($event['event_type']); ?></span>
                                <span class="event-time"><?php echo esc_html($event['timestamp']); ?></span>
                            </div>
                            <div class="event-id">ID: <?php echo esc_html($event['event_id']); ?></div>
                            <span class="event-status status-<?php echo esc_attr($event['status']); ?>">
                                <?php echo esc_html($event['status']); ?>
                            </span>
                            <div class="event-data">
                                <pre><?php echo esc_html(json_encode($event['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <script>
            // Auto-refresh every 5 seconds
            setTimeout(function() {
                location.reload();
            }, 5000);
        </script>
        <?php
    }
}
