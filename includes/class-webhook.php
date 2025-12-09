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
     * Handle incoming webhook
     */
    public function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');
        $webhook_secret = get_option('stripe_cli_demo_webhook_secret', '');

        // Log that we received a webhook (for debugging)
        error_log('Stripe CLI Demo: Webhook received');

        // If no webhook secret configured, still try to process but log warning
        if (empty($webhook_secret)) {
            error_log('Stripe CLI Demo: No webhook secret configured - skipping signature verification');

            // Try to decode the payload directly
            $event = json_decode($payload);
            if (!$event || !isset($event->type)) {
                return new WP_REST_Response(array('error' => 'Invalid payload'), 400);
            }
        } else {
            // Verify webhook signature
            if (!class_exists('\Stripe\Stripe')) {
                error_log('Stripe CLI Demo: Stripe SDK not loaded');
                return new WP_REST_Response(array('error' => 'Stripe SDK not loaded'), 500);
            }

            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sig_header,
                    $webhook_secret
                );
            } catch (\UnexpectedValueException $e) {
                error_log('Stripe CLI Demo: Invalid payload - ' . $e->getMessage());
                return new WP_REST_Response(array('error' => 'Invalid payload'), 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                error_log('Stripe CLI Demo: Invalid signature - ' . $e->getMessage());
                return new WP_REST_Response(array('error' => 'Invalid signature'), 400);
            }
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

        error_log('Stripe CLI Demo: Logged event ' . $log_entry['event_type']);
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
                    error_log('Stripe CLI Demo: Checkout completed - ' . ($event_data->data->object->id ?? 'unknown'));
                    break;

                case 'payment_intent.succeeded':
                    $events[0]['status'] = 'processed';
                    error_log('Stripe CLI Demo: Payment succeeded - ' . ($event_data->data->object->id ?? 'unknown'));
                    break;

                case 'payment_intent.created':
                    $events[0]['status'] = 'processed';
                    error_log('Stripe CLI Demo: Payment intent created');
                    break;

                case 'charge.succeeded':
                    $events[0]['status'] = 'processed';
                    error_log('Stripe CLI Demo: Charge succeeded');
                    break;

                case 'customer.created':
                    $events[0]['status'] = 'processed';
                    error_log('Stripe CLI Demo: Customer created');
                    break;

                default:
                    $events[0]['status'] = 'unhandled';
                    error_log('Stripe CLI Demo: Unhandled event type - ' . $type);
            }

            update_option('stripe_cli_demo_webhook_events', $events);
        }
    }
}
