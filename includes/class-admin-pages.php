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
        add_action('wp_ajax_stripe_cli_demo_get_events', array($this, 'ajax_get_events'));
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

        // Handle clear events action
        if (isset($_POST['clear_events']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_webhook_events')) {
            update_option('stripe_cli_demo_webhook_events', array());
            echo '<div class="notice notice-success"><p>' . __('Events cleared!', 'stripe-cli-demo') . '</p></div>';
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
                        <h3><?php _e('Test Card Numbers', 'stripe-cli-demo'); ?></h3>
                        <table class="widefat">
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
                        <p class="description">
                            <?php _e('Use any future expiry date and any 3-digit CVC.', 'stripe-cli-demo'); ?>
                        </p>
                    </div>

                    <div class="info-box">
                        <h3><?php _e('Testing Webhooks Locally', 'stripe-cli-demo'); ?></h3>
                        <p><?php _e('Run this command to forward Stripe webhooks to your WordPress site:', 'stripe-cli-demo'); ?></p>
                        <?php $cmd1 = 'stripe listen --forward-to ' . esc_url(home_url('/wp-json/stripe-cli-demo/v1/webhook')); ?>
                        <div class="command-wrapper">
                            <pre><?php echo $cmd1; ?></pre>
                            <button type="button" class="button copy-btn" data-copy="<?php echo esc_attr($cmd1); ?>">
                                <?php _e('Copy', 'stripe-cli-demo'); ?>
                            </button>
                        </div>

                        <p><strong><?php _e('For debugging (JSON output):', 'stripe-cli-demo'); ?></strong></p>
                        <?php $cmd2 = 'stripe listen --forward-to ' . esc_url(home_url('/wp-json/stripe-cli-demo/v1/webhook')) . ' --format JSON'; ?>
                        <div class="command-wrapper">
                            <pre><?php echo $cmd2; ?></pre>
                            <button type="button" class="button copy-btn" data-copy="<?php echo esc_attr($cmd2); ?>">
                                <?php _e('Copy', 'stripe-cli-demo'); ?>
                            </button>
                        </div>

                        <p>
                            <?php _e('Copy the webhook signing secret (whsec_...) and add it to', 'stripe-cli-demo'); ?>
                            <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo-settings'); ?>"><?php _e('Settings', 'stripe-cli-demo'); ?></a>
                        </p>
                    </div>

                    <?php $events = get_option('stripe_cli_demo_webhook_events', array()); ?>
                    <div id="webhook-events" class="events-section">
                        <div class="events-header">
                            <h3><?php _e('Webhook Events', 'stripe-cli-demo'); ?></h3>
                            <div class="events-actions">
                                <span class="auto-refresh-indicator">
                                    <?php _e('Auto-refreshing every 10s', 'stripe-cli-demo'); ?>
                                </span>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('clear_webhook_events'); ?>
                                    <button type="submit" name="clear_events" class="button">
                                        <?php _e('Clear Events', 'stripe-cli-demo'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div id="webhook-events-container">
                            <?php if (empty($events)): ?>
                                <div class="no-events">
                                    <h2><?php _e('No webhook events yet', 'stripe-cli-demo'); ?></h2>
                                    <p><?php _e('Make a test purchase and watch events appear here!', 'stripe-cli-demo'); ?></p>
                                    <p>
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
                        (function($) {
                            var refreshInterval = 10000;
                            var eventsContainer = $('#webhook-events-container');

                            function refreshEvents() {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'stripe_cli_demo_get_events',
                                        nonce: '<?php echo wp_create_nonce('stripe_cli_demo_events'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success && response.data.html) {
                                            eventsContainer.html(response.data.html);
                                        }
                                    },
                                    complete: function() {
                                        setTimeout(refreshEvents, refreshInterval);
                                    }
                                });
                            }

                            setTimeout(refreshEvents, refreshInterval);
                        })(jQuery);
                    </script>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler to get events HTML
     */
    public function ajax_get_events() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (empty($nonce) || !wp_verify_nonce($nonce, 'stripe_cli_demo_events')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $events = get_option('stripe_cli_demo_webhook_events', array());

        ob_start();
        if (empty($events)): ?>
            <div class="no-events">
                <h2><?php _e('No webhook events yet', 'stripe-cli-demo'); ?></h2>
                <p><?php _e('Make a test purchase and watch events appear here!', 'stripe-cli-demo'); ?></p>
                <p style="margin-top: 20px;">
                    <?php _e('Make sure', 'stripe-cli-demo'); ?> <code>stripe listen</code> <?php _e('is running in your terminal.', 'stripe-cli-demo'); ?>
                </p>
            </div>
        <?php else:
            foreach ($events as $event): ?>
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
            <?php endforeach;
        endif;
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }
}
