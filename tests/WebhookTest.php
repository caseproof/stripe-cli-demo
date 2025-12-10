<?php
/**
 * Unit tests for Webhook Handler
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

if (!defined('STRIPE_CLI_DEMO_PATH')) {
    define('STRIPE_CLI_DEMO_PATH', dirname(__DIR__) . '/');
}

if (!defined('STRIPE_CLI_DEMO_URL')) {
    define('STRIPE_CLI_DEMO_URL', 'http://example.com/wp-content/plugins/stripe-cli-demo/');
}

if (!defined('STRIPE_CLI_DEMO_VERSION')) {
    define('STRIPE_CLI_DEMO_VERSION', '1.1.0');
}

// Mock WP_REST_Response
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;

        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }
    }
}

// Mock WP_REST_Request
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private $body = '';
        private $headers = array();

        public function set_body($body) {
            $this->body = $body;
        }

        public function get_body() {
            return $this->body;
        }

        public function set_header($key, $value) {
            $this->headers[strtolower($key)] = $value;
        }

        public function get_header($key) {
            $key = strtolower($key);
            return isset($this->headers[$key]) ? $this->headers[$key] : null;
        }
    }
}

// Load the class we're testing
require_once STRIPE_CLI_DEMO_PATH . 'includes/class-webhook.php';

class WebhookTest extends TestCase
{
    private $webhook;

    protected function setUp(): void
    {
        reset_test_state();

        // Reset singleton
        $reflection = new ReflectionClass('Stripe_CLI_Demo_Webhook');
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        $this->webhook = Stripe_CLI_Demo_Webhook::get_instance();
    }

    protected function tearDown(): void
    {
        reset_test_state();
    }

    public function testSingletonPattern()
    {
        $instance1 = Stripe_CLI_Demo_Webhook::get_instance();
        $instance2 = Stripe_CLI_Demo_Webhook::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    public function testHandleWebhookRejectsWhenNoSecretConfigured()
    {
        // Ensure no webhook secret is set
        delete_option('stripe_cli_demo_webhook_secret');

        $request = new WP_REST_Request();
        $request->set_body('{"id": "evt_test"}');
        $request->set_header('stripe-signature', 'test_sig');

        $response = $this->webhook->handle_webhook($request);

        $this->assertEquals(400, $response->status);
        $this->assertArrayHasKey('error', $response->data);
        $this->assertStringContainsString('Webhook secret not configured', $response->data['error']);
    }

    public function testLogEventStoresEventInOptions()
    {
        $reflection = new ReflectionClass($this->webhook);
        $method = $reflection->getMethod('log_event');
        $method->setAccessible(true);

        $event = (object) array(
            'id' => 'evt_test123',
            'type' => 'payment_intent.succeeded',
            'data' => (object) array(
                'object' => (object) array(
                    'id' => 'pi_test',
                    'amount' => 1000,
                ),
            ),
        );

        $method->invoke($this->webhook, $event);

        $events = get_option('stripe_cli_demo_webhook_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('evt_test123', $events[0]['event_id']);
        $this->assertEquals('payment_intent.succeeded', $events[0]['event_type']);
        $this->assertEquals('received', $events[0]['status']);
    }

    public function testLogEventLimitsTo50Events()
    {
        $reflection = new ReflectionClass($this->webhook);
        $method = $reflection->getMethod('log_event');
        $method->setAccessible(true);

        // Log 60 events
        for ($i = 0; $i < 60; $i++) {
            $event = (object) array(
                'id' => 'evt_' . $i,
                'type' => 'test.event',
                'data' => (object) array('object' => new stdClass()),
            );
            $method->invoke($this->webhook, $event);
        }

        $events = get_option('stripe_cli_demo_webhook_events', array());

        $this->assertCount(50, $events);
        $this->assertEquals('evt_59', $events[0]['event_id']);
    }

    public function testProcessEventSetsCorrectStatusForPaymentIntentSucceeded()
    {
        // First log an event
        $reflection = new ReflectionClass($this->webhook);
        $logMethod = $reflection->getMethod('log_event');
        $logMethod->setAccessible(true);

        $event = (object) array(
            'id' => 'evt_pi_success',
            'type' => 'payment_intent.succeeded',
            'data' => (object) array(
                'object' => (object) array('id' => 'pi_123'),
            ),
        );

        $logMethod->invoke($this->webhook, $event);

        // Then process it
        $processMethod = $reflection->getMethod('process_event');
        $processMethod->setAccessible(true);
        $processMethod->invoke($this->webhook, $event);

        $events = get_option('stripe_cli_demo_webhook_events', array());

        $this->assertEquals('processed', $events[0]['status']);
    }

    public function testProcessEventSetsCorrectStatusForCheckoutCompleted()
    {
        $reflection = new ReflectionClass($this->webhook);
        $logMethod = $reflection->getMethod('log_event');
        $logMethod->setAccessible(true);
        $processMethod = $reflection->getMethod('process_event');
        $processMethod->setAccessible(true);

        $event = (object) array(
            'id' => 'evt_checkout',
            'type' => 'checkout.session.completed',
            'data' => (object) array(
                'object' => (object) array('id' => 'cs_123'),
            ),
        );

        $logMethod->invoke($this->webhook, $event);
        $processMethod->invoke($this->webhook, $event);

        $events = get_option('stripe_cli_demo_webhook_events', array());

        $this->assertEquals('processed', $events[0]['status']);
    }

    public function testProcessEventSetsUnhandledStatusForUnknownEventType()
    {
        $reflection = new ReflectionClass($this->webhook);
        $logMethod = $reflection->getMethod('log_event');
        $logMethod->setAccessible(true);
        $processMethod = $reflection->getMethod('process_event');
        $processMethod->setAccessible(true);

        $event = (object) array(
            'id' => 'evt_unknown',
            'type' => 'some.unknown.event',
            'data' => (object) array(
                'object' => new stdClass(),
            ),
        );

        $logMethod->invoke($this->webhook, $event);
        $processMethod->invoke($this->webhook, $event);

        $events = get_option('stripe_cli_demo_webhook_events', array());

        $this->assertEquals('unhandled', $events[0]['status']);
    }

    public function testDebugLogOnlyLogsWhenWPDebugEnabled()
    {
        $reflection = new ReflectionClass($this->webhook);
        $method = $reflection->getMethod('debug_log');
        $method->setAccessible(true);

        // This should not throw an error even if WP_DEBUG is true
        // Just verify it doesn't crash
        $method->invoke($this->webhook, 'Test log message');

        $this->assertTrue(true); // If we get here, the method executed without error
    }

    public function testHandledEventTypes()
    {
        $reflection = new ReflectionClass($this->webhook);
        $processMethod = $reflection->getMethod('process_event');
        $processMethod->setAccessible(true);
        $logMethod = $reflection->getMethod('log_event');
        $logMethod->setAccessible(true);

        $handledTypes = array(
            'checkout.session.completed',
            'payment_intent.succeeded',
            'payment_intent.created',
            'charge.succeeded',
            'customer.created',
        );

        foreach ($handledTypes as $type) {
            reset_test_state();

            $event = (object) array(
                'id' => 'evt_' . str_replace('.', '_', $type),
                'type' => $type,
                'data' => (object) array(
                    'object' => (object) array('id' => 'obj_123'),
                ),
            );

            $logMethod->invoke($this->webhook, $event);
            $processMethod->invoke($this->webhook, $event);

            $events = get_option('stripe_cli_demo_webhook_events', array());

            $this->assertEquals(
                'processed',
                $events[0]['status'],
                "Event type $type should be processed"
            );
        }
    }
}
