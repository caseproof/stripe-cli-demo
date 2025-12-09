<?php
/**
 * Checkout handler for Stripe CLI Demo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_CLI_Demo_Checkout {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_stripe_cli_demo_create_checkout', array($this, 'create_checkout_session'));
    }

    /**
     * Create Stripe Checkout Session via AJAX
     */
    public function create_checkout_session() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'stripe_cli_demo_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        // Check for Stripe SDK
        if (!class_exists('\Stripe\Stripe')) {
            wp_send_json_error(array('message' => 'Stripe SDK not loaded. Check your API keys in Settings.'));
        }

        $secret_key = get_option('stripe_cli_demo_secret_key', '');
        if (empty($secret_key)) {
            wp_send_json_error(array('message' => 'Stripe Secret Key not configured'));
        }

        try {
            \Stripe\Stripe::setApiKey($secret_key);

            // Create Checkout Session with inline price_data
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Demo Widget',
                            'description' => 'A simple $1 product for testing Stripe webhooks',
                        ],
                        'unit_amount' => 100, // $1.00 in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => admin_url('admin.php?page=stripe-cli-demo&status=success&session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => admin_url('admin.php?page=stripe-cli-demo&status=cancelled'),
                'metadata' => [
                    'product_id' => 'demo_widget',
                    'demo' => 'stripe_cli_webhook_testing',
                    'source' => 'wordpress_plugin'
                ]
            ]);

            wp_send_json_success(array(
                'checkout_url' => $session->url,
                'session_id' => $session->id
            ));

        } catch (\Stripe\Exception\ApiErrorException $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array(
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ));
        }
    }
}
