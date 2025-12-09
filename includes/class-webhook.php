<?php
/**
 * Webhook handler for Stripe CLI Demo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_CLI_Demo_Webhook {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }

    /**
     * Register REST API endpoint for webhooks
     */
    public function register_webhook_endpoint() {
        register_rest_route('stripe-cli-demo/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Webhooks must be publicly accessible
        ));
    }

    /**
     * Log message only when WP_DEBUG is enabled
     */
    private function debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Stripe CLI Demo: ' . $message);
        }
    }

    /**
     * Handle incoming webhook
     */
    public function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');
        $webhook_secret = get_option('stripe_cli_demo_webhook_secret', '');

        $this->debug_log('Webhook received');

        // Require webhook secret - reject unsigned payloads
        if (empty($webhook_secret)) {
            $this->debug_log('No webhook secret configured - rejecting request');
            return new WP_REST_Response(array(
                'error' => 'Webhook secret not configured. Complete the setup wizard first.'
            ), 400);
        }

        // Verify webhook signature
        if (!class_exists('\Stripe\Stripe')) {
            $this->debug_log('Stripe SDK not loaded');
            return new WP_REST_Response(array('error' => 'Stripe SDK not loaded'), 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $webhook_secret
            );
        } catch (\UnexpectedValueException $e) {
            $this->debug_log('Invalid payload - ' . $e->getMessage());
            return new WP_REST_Response(array('error' => 'Invalid payload'), 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->debug_log('Invalid signature - ' . $e->getMessage());
            return new WP_REST_Response(array('error' => 'Invalid signature'), 400);
        }

        // Log the event
        $this->log_event($event);

        // Handle specific event types
        $this->process_event($event);

        return new WP_REST_Response(array(
            'status' => 'received',
            'type' => $event->type ?? $event['type'] ?? 'unknown'
        ), 200);
    }

    /**
     * Log webhook event to options table
     */
    private function log_event($event) {
        // Handle both object and array formats
        $event_data = is_object($event) ? $event : (object) $event;

        $log_entry = array(
            'timestamp' => current_time('Y-m-d H:i:s'),
            'event_id' => $event_data->id ?? 'unknown',
            'event_type' => $event_data->type ?? 'unknown',
            'status' => 'received',
            'data' => json_decode(json_encode($event_data->data->object ?? new stdClass()), true)
        );

        // Get existing events
        $events = get_option('stripe_cli_demo_webhook_events', array());

        // Add new event at the beginning
        array_unshift($events, $log_entry);

        // Keep only last 50 events
        $events = array_slice($events, 0, 50);

        // Save back
        update_option('stripe_cli_demo_webhook_events', $events);

        $this->debug_log('Logged event ' . $log_entry['event_type']);
    }

    /**
     * Process specific event types
     */
    private function process_event($event) {
        $event_data = is_object($event) ? $event : (object) $event;
        $type = $event_data->type ?? '';

        // Update the event status based on type
        $events = get_option('stripe_cli_demo_webhook_events', array());

        if (!empty($events)) {
            switch ($type) {
                case 'checkout.session.completed':
                    $events[0]['status'] = 'processed';
                    $this->debug_log('Checkout completed - ' . ($event_data->data->object->id ?? 'unknown'));
                    break;

                case 'payment_intent.succeeded':
                    $events[0]['status'] = 'processed';
                    $this->debug_log('Payment succeeded - ' . ($event_data->data->object->id ?? 'unknown'));
                    break;

                case 'payment_intent.created':
                    $events[0]['status'] = 'processed';
                    $this->debug_log('Payment intent created');
                    break;

                case 'charge.succeeded':
                    $events[0]['status'] = 'processed';
                    $this->debug_log('Charge succeeded');
                    break;

                case 'customer.created':
                    $events[0]['status'] = 'processed';
                    $this->debug_log('Customer created');
                    break;

                default:
                    $events[0]['status'] = 'unhandled';
                    $this->debug_log('Unhandled event type - ' . $type);
            }

            update_option('stripe_cli_demo_webhook_events', $events);
        }
    }
}
