<?php
/**
 * MemberPress Integration for Stripe CLI Demo
 *
 * Provides visibility into MemberPress Stripe webhook handling.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_CLI_Demo_MemberPress {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Only initialize if MemberPress is active
        if (!$this->is_memberpress_active()) {
            return;
        }

        add_action('admin_menu', array($this, 'add_memberpress_submenu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_stripe_cli_demo_get_mepr_events', array($this, 'ajax_get_mepr_events'));
        add_action('wp_ajax_stripe_cli_demo_clear_mepr_events', array($this, 'ajax_clear_mepr_events'));

        // Hook into MemberPress transaction events - try both underscore and dash versions
        add_action('mepr_txn_store', array($this, 'on_transaction_store'), 10, 2);
        add_action('mepr-txn-store', array($this, 'on_transaction_store'), 10, 2);
        add_action('mepr_txn_status_complete', array($this, 'on_transaction_complete'), 10, 1);
        add_action('mepr-txn-status-complete', array($this, 'on_transaction_complete'), 10, 1);
        add_action('mepr_txn_status_failed', array($this, 'on_transaction_failed'), 10, 1);
        add_action('mepr-txn-status-failed', array($this, 'on_transaction_failed'), 10, 1);
        add_action('mepr_txn_status_refunded', array($this, 'on_transaction_refunded'), 10, 1);
        add_action('mepr-txn-status-refunded', array($this, 'on_transaction_refunded'), 10, 1);

        // Hook into MemberPress subscription events
        add_action('mepr_event_subscription_created', array($this, 'on_subscription_created'), 10, 1);
        add_action('mepr-event-subscription-created', array($this, 'on_subscription_created'), 10, 1);
        add_action('mepr_event_subscription_stopped', array($this, 'on_subscription_stopped'), 10, 1);
        add_action('mepr-event-subscription-stopped', array($this, 'on_subscription_stopped'), 10, 1);
        add_action('mepr_event_subscription_paused', array($this, 'on_subscription_paused'), 10, 1);
        add_action('mepr-event-subscription-paused', array($this, 'on_subscription_paused'), 10, 1);
        add_action('mepr_event_subscription_resumed', array($this, 'on_subscription_resumed'), 10, 1);
        add_action('mepr-event-subscription-resumed', array($this, 'on_subscription_resumed'), 10, 1);

        // Hook into MemberPress member events
        add_action('mepr_event_member_signup_completed', array($this, 'on_member_signup'), 10, 1);
        add_action('mepr-event-member-signup-completed', array($this, 'on_member_signup'), 10, 1);

        // Also hook into the Stripe-specific hooks
        add_action('mepr_stripe_checkout_pending', array($this, 'on_stripe_checkout_pending'), 10, 2);
        add_action('mepr_stripe_subscription_created', array($this, 'on_stripe_subscription_created'), 10, 2);
        add_action('mepr_stripe_payment_failed', array($this, 'on_stripe_payment_failed'), 10, 1);

        // Debug: Log ALL actions that start with 'mepr' to see what's firing
        add_action('all', array($this, 'debug_all_mepr_hooks'), 10, 10);

        $this->debug_log('MemberPress integration initialized');

        // Debug: Verify hooks are registered
        add_action('init', array($this, 'debug_registered_hooks'), 999);
    }

    /**
     * Check if MemberPress is active
     */
    public function is_memberpress_active() {
        return class_exists('MeprCtrlFactory') && defined('MEPR_VERSION');
    }

    /**
     * Check if Stripe Gateway is configured
     */
    public function is_stripe_gateway_active() {
        if (!$this->is_memberpress_active()) {
            return false;
        }

        if (!class_exists('MeprOptions')) {
            return false;
        }

        $mepr_options = MeprOptions::fetch();
        $payment_methods = $mepr_options->payment_methods(false);

        foreach ($payment_methods as $pm) {
            if ($pm instanceof MeprStripeGateway) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Stripe gateway instances
     */
    public function get_stripe_gateways() {
        if (!$this->is_memberpress_active() || !class_exists('MeprOptions')) {
            return array();
        }

        $mepr_options = MeprOptions::fetch();
        $payment_methods = $mepr_options->payment_methods(false);
        $stripe_gateways = array();

        foreach ($payment_methods as $pm) {
            if ($pm instanceof MeprStripeGateway) {
                $stripe_gateways[] = $pm;
            }
        }

        return $stripe_gateways;
    }

    /**
     * Get the webhook URL for a Stripe gateway
     */
    public function get_webhook_url($gateway) {
        if (!$gateway || !method_exists($gateway, 'notify_url')) {
            return '';
        }

        return $gateway->notify_url('whk');
    }

    /**
     * Add MemberPress submenu
     */
    public function add_memberpress_submenu() {
        add_submenu_page(
            'stripe-cli-demo',
            __('MemberPress Events', 'stripe-cli-demo'),
            __('MemberPress Events', 'stripe-cli-demo'),
            'manage_options',
            'stripe-cli-demo-memberpress',
            array($this, 'render_memberpress_page'),
            1  // Position: after main menu, before settings
        );
    }

    /**
     * Enqueue scripts for MemberPress page
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'stripe-cli-demo-memberpress') === false) {
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
        ));
    }

    /**
     * Render the MemberPress integration page
     */
    public function render_memberpress_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $mepr_active = $this->is_memberpress_active();
        $stripe_active = $this->is_stripe_gateway_active();
        $stripe_gateways = $this->get_stripe_gateways();
        ?>
        <div class="wrap stripe-cli-demo-wrap">
            <h1><?php _e('MemberPress Events', 'stripe-cli-demo'); ?></h1>
            <p class="description"><?php _e('Monitor MemberPress Stripe transactions and webhook events', 'stripe-cli-demo'); ?></p>

            <?php if (!$mepr_active): ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('MemberPress Not Active!', 'stripe-cli-demo'); ?></strong>
                        <?php _e('Please install and activate MemberPress to use this integration.', 'stripe-cli-demo'); ?>
                    </p>
                </div>
            <?php elseif (!$stripe_active): ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Stripe Gateway Not Configured!', 'stripe-cli-demo'); ?></strong>
                        <?php _e('Go to MemberPress → Settings → Payments to set up Stripe.', 'stripe-cli-demo'); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="stripe-cli-demo-container">
                    <?php $this->render_mepr_events(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render MemberPress events section
     */
    private function render_mepr_events() {
        $events = get_option('stripe_cli_demo_mepr_events', array());
        ?>
        <div class="events-section">
            <div class="events-header">
                <h3><?php _e('MemberPress Events Log', 'stripe-cli-demo'); ?></h3>
                <div class="events-actions">
                    <span class="auto-refresh-indicator">
                        <?php _e('Auto-refreshing every 10s', 'stripe-cli-demo'); ?>
                    </span>
                    <button type="button" class="button" id="clear-mepr-events">
                        <?php _e('Clear Events', 'stripe-cli-demo'); ?>
                    </button>
                </div>
            </div>

            <div id="mepr-events-container">
                <?php echo $this->get_events_html($events); ?>
            </div>
        </div>

        <script>
            (function($) {
                var refreshInterval = 10000;

                function refreshMeprEvents() {
                    $.ajax({
                        url: stripeCliDemo.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'stripe_cli_demo_get_mepr_events',
                            nonce: stripeCliDemo.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data.html) {
                                $('#mepr-events-container').html(response.data.html);
                            }
                        },
                        complete: function() {
                            setTimeout(refreshMeprEvents, refreshInterval);
                        }
                    });
                }

                setTimeout(refreshMeprEvents, refreshInterval);

                $('#clear-mepr-events').on('click', function() {
                    if (!confirm('<?php _e('Clear all MemberPress events?', 'stripe-cli-demo'); ?>')) {
                        return;
                    }
                    $.ajax({
                        url: stripeCliDemo.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'stripe_cli_demo_clear_mepr_events',
                            nonce: stripeCliDemo.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#mepr-events-container').html(response.data.html);
                            }
                        }
                    });
                });
            })(jQuery);
        </script>
        <?php
    }

    /**
     * Get events HTML
     */
    private function get_events_html($events) {
        if (empty($events)) {
            return '<div class="no-events">
                <h2>' . __('No MemberPress events yet', 'stripe-cli-demo') . '</h2>
                <p>' . __('Make a test purchase through MemberPress and watch events appear here.', 'stripe-cli-demo') . '</p>
                <p>' . __('Make sure <code>stripe listen</code> is running with the MemberPress webhook URL.', 'stripe-cli-demo') . '</p>
            </div>';
        }

        $html = '';
        foreach ($events as $event) {
            $status_class = isset($event['status']) ? 'status-' . $event['status'] : '';
            $html .= '<div class="event-card">';
            $html .= '<div class="event-header">';
            $html .= '<span class="event-type">' . esc_html($event['event_type']) . '</span>';
            $html .= '<span class="event-time">' . esc_html($event['timestamp']) . '</span>';
            $html .= '</div>';

            if (!empty($event['user_email'])) {
                $html .= '<div class="event-id">User: ' . esc_html($event['user_email']) . '</div>';
            }
            if (!empty($event['product_name'])) {
                $html .= '<div class="event-id">Product: ' . esc_html($event['product_name']) . '</div>';
            }
            if (!empty($event['amount'])) {
                $html .= '<div class="event-id">Amount: $' . esc_html(number_format($event['amount'], 2)) . '</div>';
            }

            $html .= '<span class="event-status ' . esc_attr($status_class) . '">' . esc_html($event['status'] ?? 'logged') . '</span>';

            if (!empty($event['data'])) {
                $html .= '<div class="event-data">';
                $html .= '<pre>' . esc_html(json_encode($event['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    /**
     * AJAX handler for getting events
     */
    public function ajax_get_mepr_events() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (empty($nonce) || !wp_verify_nonce($nonce, 'stripe_cli_demo_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $events = get_option('stripe_cli_demo_mepr_events', array());
        wp_send_json_success(array('html' => $this->get_events_html($events)));
    }

    /**
     * AJAX handler for clearing events
     */
    public function ajax_clear_mepr_events() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (empty($nonce) || !wp_verify_nonce($nonce, 'stripe_cli_demo_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        update_option('stripe_cli_demo_mepr_events', array());
        wp_send_json_success(array('html' => $this->get_events_html(array())));
    }

    /**
     * Render recent transactions section
     */
    private function render_recent_transactions() {
        global $wpdb;
        $table = $wpdb->prefix . 'mepr_transactions';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }

        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.user_email, p.post_title as product_name
             FROM {$table} t
             LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
             LEFT JOIN {$wpdb->posts} p ON t.product_id = p.ID
             WHERE t.gateway LIKE %s
             ORDER BY t.created_at DESC
             LIMIT 10",
            '%stripe%'
        ));
        ?>
        <div class="info-box" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3><?php _e('Recent MemberPress Stripe Transactions', 'stripe-cli-demo'); ?></h3>

            <?php if (empty($transactions)): ?>
                <p style="color: #666;"><?php _e('No Stripe transactions yet.', 'stripe-cli-demo'); ?></p>
            <?php else: ?>
                <table class="widefat" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'stripe-cli-demo'); ?></th>
                            <th><?php _e('User', 'stripe-cli-demo'); ?></th>
                            <th><?php _e('Product', 'stripe-cli-demo'); ?></th>
                            <th><?php _e('Amount', 'stripe-cli-demo'); ?></th>
                            <th><?php _e('Status', 'stripe-cli-demo'); ?></th>
                            <th><?php _e('Trans #', 'stripe-cli-demo'); ?></th>
                            <th><?php _e('Date', 'stripe-cli-demo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?php echo esc_html($txn->id); ?></td>
                                <td><?php echo esc_html($txn->user_email); ?></td>
                                <td><?php echo esc_html($txn->product_name); ?></td>
                                <td>$<?php echo esc_html(number_format($txn->total, 2)); ?></td>
                                <td>
                                    <span class="txn-status" style="padding: 2px 8px; border-radius: 3px; font-size: 11px; background: <?php echo $this->get_status_color($txn->status); ?>;">
                                        <?php echo esc_html($txn->status); ?>
                                    </span>
                                </td>
                                <td><code style="font-size: 11px;"><?php echo esc_html(substr($txn->trans_num, 0, 20)); ?><?php echo strlen($txn->trans_num) > 20 ? '...' : ''; ?></code></td>
                                <td><?php echo esc_html(date('M j, Y g:i a', strtotime($txn->created_at))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=memberpress-trans'); ?>" class="button">
                    <?php _e('View All Transactions', 'stripe-cli-demo'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Get status color for display
     */
    private function get_status_color($status) {
        $colors = array(
            'complete' => '#d4edda',
            'confirmed' => '#d4edda',
            'pending' => '#fff3cd',
            'failed' => '#f8d7da',
            'refunded' => '#cce5ff',
        );
        return isset($colors[$status]) ? $colors[$status] : '#e0e0e0';
    }

    /**
     * Render membership products section
     */
    private function render_membership_products() {
        if (!class_exists('MeprProduct')) {
            return;
        }

        $products = MeprProduct::get_all();
        ?>
        <div class="info-box" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3><?php _e('Test Memberships', 'stripe-cli-demo'); ?></h3>
            <p><?php _e('Purchase these memberships to trigger webhook events:', 'stripe-cli-demo'); ?></p>

            <?php if (empty($products)): ?>
                <p style="color: #666;">
                    <?php _e('No membership products found.', 'stripe-cli-demo'); ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=memberpressproduct'); ?>">
                        <?php _e('Create one', 'stripe-cli-demo'); ?>
                    </a>
                </p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $mepr_product = new MeprProduct($product->ID);
                        $price = $mepr_product->price;
                        $is_one_time = $mepr_product->is_one_time_payment();
                        $is_recurring = !$is_one_time;
                        $period = $is_recurring ? $mepr_product->period . ' ' . $mepr_product->period_type : '';
                        ?>
                        <div style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px; text-align: center;">
                            <?php if ($is_recurring): ?>
                                <span style="background: #667eea; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 10px; text-transform: uppercase;">
                                    <?php _e('Recurring', 'stripe-cli-demo'); ?>
                                </span>
                            <?php else: ?>
                                <span style="background: #48bb78; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 10px; text-transform: uppercase;">
                                    <?php _e('One-Time', 'stripe-cli-demo'); ?>
                                </span>
                            <?php endif; ?>

                            <h4 style="margin: 10px 0 5px;"><?php echo esc_html($mepr_product->post_title); ?></h4>

                            <div style="font-size: 24px; font-weight: bold; color: #1e3a5f;">
                                $<?php echo esc_html(number_format($price, 2)); ?>
                                <?php if ($is_recurring): ?>
                                    <span style="font-size: 14px; font-weight: normal; color: #666;">
                                        /<?php echo esc_html($period); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a href="<?php echo esc_url($mepr_product->url()); ?>" class="button button-primary" target="_blank" style="margin-top: 10px;">
                                <?php _e('Test Purchase', 'stripe-cli-demo'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
                <h4 style="margin-top: 0;"><?php _e('CLI Event Triggers', 'stripe-cli-demo'); ?></h4>
                <p style="margin-bottom: 10px;"><?php _e('Trigger MemberPress-relevant events without making purchases:', 'stripe-cli-demo'); ?></p>
                <pre style="margin: 0; padding: 10px; background: #1e1e1e; color: #d4d4d4; border-radius: 4px; font-size: 12px;">stripe trigger checkout.session.completed
stripe trigger invoice.payment_succeeded
stripe trigger invoice.payment_failed
stripe trigger customer.subscription.deleted
stripe trigger charge.refunded</pre>
            </div>
        </div>
        <?php
    }

    /**
     * Log a MemberPress event
     */
    private function log_event($event_type, $data = array()) {
        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'event_type' => $event_type,
            'status' => 'logged',
            'user_email' => isset($data['user_email']) ? $data['user_email'] : '',
            'product_name' => isset($data['product_name']) ? $data['product_name'] : '',
            'amount' => isset($data['amount']) ? $data['amount'] : '',
            'data' => $data,
        );

        $events = get_option('stripe_cli_demo_mepr_events', array());
        array_unshift($events, $log_entry);
        $events = array_slice($events, 0, 50);
        update_option('stripe_cli_demo_mepr_events', $events);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Stripe CLI Demo MemberPress: ' . $event_type);
        }
    }

    /**
     * Get transaction details for logging
     */
    private function get_txn_details($txn) {
        $user = get_userdata($txn->user_id);
        $product = get_post($txn->product_id);

        return array(
            'txn_id' => $txn->id,
            'trans_num' => $txn->trans_num,
            'user_id' => $txn->user_id,
            'user_email' => $user ? $user->user_email : '',
            'product_id' => $txn->product_id,
            'product_name' => $product ? $product->post_title : '',
            'amount' => $txn->total,
            'status' => $txn->status,
            'gateway' => $txn->gateway,
            'subscription_id' => $txn->subscription_id,
        );
    }

    /**
     * Hook: Transaction stored
     */
    public function on_transaction_store($txn) {
        $this->debug_log('on_transaction_store called with gateway: ' . (isset($txn->gateway) ? $txn->gateway : 'NOT SET'));

        if (!$this->is_stripe_transaction($txn)) {
            $this->debug_log('Skipping - not a Stripe transaction');
            return;
        }

        $this->debug_log('Processing Stripe transaction');
        $details = $this->get_txn_details($txn);
        $this->log_event('mepr_txn_store', $details);
    }

    /**
     * Check if a transaction uses a Stripe gateway
     */
    private function is_stripe_transaction($txn) {
        if (!isset($txn->gateway) || empty($txn->gateway)) {
            return false;
        }

        // Try to get the payment method object
        if (method_exists($txn, 'payment_method')) {
            $pm = $txn->payment_method();
            if ($pm && $pm instanceof MeprStripeGateway) {
                $this->debug_log('Detected Stripe gateway via payment_method()');
                return true;
            }
        }

        // Fallback: Check MeprOptions for the gateway
        if (class_exists('MeprOptions')) {
            $mepr_options = MeprOptions::fetch();
            $pm = $mepr_options->payment_method($txn->gateway);
            if ($pm && $pm instanceof MeprStripeGateway) {
                $this->debug_log('Detected Stripe gateway via MeprOptions');
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a subscription uses a Stripe gateway
     */
    private function is_stripe_subscription($sub) {
        if (!$sub || !isset($sub->gateway) || empty($sub->gateway)) {
            return false;
        }

        // Check MeprOptions for the gateway
        if (class_exists('MeprOptions')) {
            $mepr_options = MeprOptions::fetch();
            $pm = $mepr_options->payment_method($sub->gateway);
            if ($pm && $pm instanceof MeprStripeGateway) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hook: Transaction complete
     */
    public function on_transaction_complete($txn) {
        if (!$this->is_stripe_transaction($txn)) {
            return;
        }

        $details = $this->get_txn_details($txn);
        $details['status'] = 'complete';
        $this->log_event('mepr_txn_complete', $details);
    }

    /**
     * Hook: Transaction failed
     */
    public function on_transaction_failed($txn) {
        if (!$this->is_stripe_transaction($txn)) {
            return;
        }

        $details = $this->get_txn_details($txn);
        $details['status'] = 'failed';
        $this->log_event('mepr_txn_failed', $details);
    }

    /**
     * Hook: Transaction refunded
     */
    public function on_transaction_refunded($txn) {
        if (!$this->is_stripe_transaction($txn)) {
            return;
        }

        $details = $this->get_txn_details($txn);
        $details['status'] = 'refunded';
        $this->log_event('mepr_txn_refunded', $details);
    }

    /**
     * Hook: Subscription created
     */
    public function on_subscription_created($event) {
        $sub = $event->get_data();

        if (!$this->is_stripe_subscription($sub)) {
            return;
        }

        $user = get_userdata($sub->user_id);
        $product = get_post($sub->product_id);

        $this->log_event('mepr_subscription_created', array(
            'subscription_id' => $sub->id,
            'subscr_id' => $sub->subscr_id,
            'user_id' => $sub->user_id,
            'user_email' => $user ? $user->user_email : '',
            'product_id' => $sub->product_id,
            'product_name' => $product ? $product->post_title : '',
            'amount' => $sub->total,
            'status' => $sub->status,
            'gateway' => $sub->gateway,
        ));
    }

    /**
     * Hook: Subscription stopped
     */
    public function on_subscription_stopped($event) {
        $sub = $event->get_data();

        if (!$this->is_stripe_subscription($sub)) {
            return;
        }

        $user = get_userdata($sub->user_id);
        $product = get_post($sub->product_id);

        $this->log_event('mepr_subscription_stopped', array(
            'subscription_id' => $sub->id,
            'subscr_id' => $sub->subscr_id,
            'user_id' => $sub->user_id,
            'user_email' => $user ? $user->user_email : '',
            'product_id' => $sub->product_id,
            'product_name' => $product ? $product->post_title : '',
            'status' => 'cancelled',
            'gateway' => $sub->gateway,
        ));
    }

    /**
     * Hook: Subscription paused
     */
    public function on_subscription_paused($event) {
        $sub = $event->get_data();

        if (!$this->is_stripe_subscription($sub)) {
            return;
        }

        $user = get_userdata($sub->user_id);
        $product = get_post($sub->product_id);

        $this->log_event('mepr_subscription_paused', array(
            'subscription_id' => $sub->id,
            'subscr_id' => $sub->subscr_id,
            'user_id' => $sub->user_id,
            'user_email' => $user ? $user->user_email : '',
            'product_id' => $sub->product_id,
            'product_name' => $product ? $product->post_title : '',
            'status' => 'paused',
            'gateway' => $sub->gateway,
        ));
    }

    /**
     * Hook: Subscription resumed
     */
    public function on_subscription_resumed($event) {
        $sub = $event->get_data();

        if (!$this->is_stripe_subscription($sub)) {
            return;
        }

        $user = get_userdata($sub->user_id);
        $product = get_post($sub->product_id);

        $this->log_event('mepr_subscription_resumed', array(
            'subscription_id' => $sub->id,
            'subscr_id' => $sub->subscr_id,
            'user_id' => $sub->user_id,
            'user_email' => $user ? $user->user_email : '',
            'product_id' => $sub->product_id,
            'product_name' => $product ? $product->post_title : '',
            'status' => 'active',
            'gateway' => $sub->gateway,
        ));
    }

    /**
     * Hook: Member signup completed
     */
    public function on_member_signup($event) {
        $txn = $event->get_data();

        if (!$txn || strpos($txn->gateway, 'stripe') === false) {
            return;
        }

        $details = $this->get_txn_details($txn);
        $this->log_event('mepr_member_signup', $details);
    }

    /**
     * Hook: Stripe checkout pending (fired by MeprStripeGateway)
     */
    public function on_stripe_checkout_pending($txn, $usr) {
        $this->debug_log('on_stripe_checkout_pending fired');
        $details = $this->get_txn_details($txn);
        $details['user_email'] = $usr->user_email;
        $this->log_event('mepr_stripe_checkout_pending', $details);
    }

    /**
     * Hook: Stripe subscription created (fired by MeprStripeGateway)
     */
    public function on_stripe_subscription_created($txn, $sub) {
        $this->debug_log('on_stripe_subscription_created fired');
        $user = get_userdata($txn->user_id);
        $product = get_post($txn->product_id);

        $this->log_event('mepr_stripe_subscription_created', array(
            'txn_id' => $txn->id,
            'subscription_id' => $sub->id,
            'subscr_id' => $sub->subscr_id,
            'user_id' => $txn->user_id,
            'user_email' => $user ? $user->user_email : '',
            'product_id' => $txn->product_id,
            'product_name' => $product ? $product->post_title : '',
            'amount' => $txn->total,
            'status' => $txn->status,
            'gateway' => $txn->gateway,
        ));
    }

    /**
     * Hook: Stripe payment failed (fired by MeprStripeGateway)
     */
    public function on_stripe_payment_failed($payment_intent) {
        $this->debug_log('on_stripe_payment_failed fired');
        $this->log_event('mepr_stripe_payment_failed', array(
            'payment_intent_id' => is_object($payment_intent) ? $payment_intent->id : $payment_intent,
            'status' => 'failed',
        ));
    }

    /**
     * Debug logging
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Stripe CLI Demo MemberPress: ' . $message);
        }
    }

    /**
     * Debug: Check if our hooks are registered
     */
    public function debug_registered_hooks() {
        global $wp_filter;

        $hooks_to_check = array('mepr_txn_store', 'mepr-txn-store');

        foreach ($hooks_to_check as $hook) {
            if (isset($wp_filter[$hook])) {
                $this->debug_log("Hook '$hook' has " . count($wp_filter[$hook]->callbacks) . " priority levels registered");
                foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $id => $callback) {
                        $this->debug_log("  Priority $priority: $id");
                    }
                }
            } else {
                $this->debug_log("Hook '$hook' has NO callbacks registered");
            }
        }
    }

    /**
     * Debug hook to catch all MemberPress-related actions
     */
    public function debug_all_mepr_hooks() {
        $current_action = current_action();

        // Only log mepr-related hooks to avoid spam
        if (strpos($current_action, 'mepr') === 0 || strpos($current_action, 'mepr-') === 0) {
            // Skip noisy hooks
            $skip = array('mepr_db_get_col', 'mepr_db_get_records', 'mepr_db_get_one_record', 'mepr_db_search_in_col');
            foreach ($skip as $s) {
                if (strpos($current_action, $s) !== false) {
                    return;
                }
            }
            $this->debug_log('HOOK FIRED: ' . $current_action);
        }
    }
}
