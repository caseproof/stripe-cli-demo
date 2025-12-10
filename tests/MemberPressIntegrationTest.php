<?php
/**
 * Unit tests for MemberPress Integration
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

// Mock MemberPress classes
class MeprTransaction {
    public $id = 1;
    public $trans_num = 'txn_123';
    public $user_id = 1;
    public $product_id = 1;
    public $total = 19.99;
    public $status = 'complete';
    public $gateway = 'stripe';
    public $subscription_id = 1;
    public $created_at = '2025-01-01 00:00:00';

    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}

class MeprSubscription {
    public $id = 1;
    public $subscr_id = 'sub_123';
    public $user_id = 1;
    public $product_id = 1;
    public $total = 19.99;
    public $status = 'active';
    public $gateway = 'stripe';

    public function __construct($data = array()) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}

class MeprEvent {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function get_data() {
        return $this->data;
    }
}

class MeprUser {
    public $ID = 1;
    public $user_email = 'test@example.com';
}

class MeprCtrlFactory {}

if (!defined('MEPR_VERSION')) {
    define('MEPR_VERSION', '1.0.0');
}

if (!defined('STRIPE_CLI_DEMO_PATH')) {
    define('STRIPE_CLI_DEMO_PATH', dirname(__DIR__) . '/');
}

if (!defined('STRIPE_CLI_DEMO_URL')) {
    define('STRIPE_CLI_DEMO_URL', 'http://example.com/wp-content/plugins/stripe-cli-demo/');
}

if (!defined('STRIPE_CLI_DEMO_VERSION')) {
    define('STRIPE_CLI_DEMO_VERSION', '1.1.0');
}

// Load the class we're testing
require_once STRIPE_CLI_DEMO_PATH . 'includes/class-memberpress-integration.php';

class MemberPressIntegrationTest extends TestCase
{
    private $integration;

    protected function setUp(): void
    {
        reset_test_state();

        // Reset singleton
        $reflection = new ReflectionClass('Stripe_CLI_Demo_MemberPress');
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);

        $this->integration = Stripe_CLI_Demo_MemberPress::get_instance();
    }

    protected function tearDown(): void
    {
        reset_test_state();
    }

    public function testSingletonPattern()
    {
        $instance1 = Stripe_CLI_Demo_MemberPress::get_instance();
        $instance2 = Stripe_CLI_Demo_MemberPress::get_instance();

        $this->assertSame($instance1, $instance2, 'Singleton should return same instance');
    }

    public function testIsMemberPressActive()
    {
        // MeprCtrlFactory class exists and MEPR_VERSION is defined
        $this->assertTrue(
            $this->integration->is_memberpress_active(),
            'Should detect MemberPress as active'
        );
    }

    public function testLogEventStoresInOptions()
    {
        // Use reflection to call private method
        $reflection = new ReflectionClass($this->integration);
        $method = $reflection->getMethod('log_event');
        $method->setAccessible(true);

        $method->invoke($this->integration, 'test_event', array(
            'user_email' => 'test@example.com',
            'product_name' => 'Test Product',
            'amount' => 19.99,
        ));

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events, 'Should have 1 event logged');
        $this->assertEquals('test_event', $events[0]['event_type']);
        $this->assertEquals('test@example.com', $events[0]['user_email']);
        $this->assertEquals('Test Product', $events[0]['product_name']);
        $this->assertEquals(19.99, $events[0]['amount']);
    }

    public function testLogEventLimitsTo50Events()
    {
        $reflection = new ReflectionClass($this->integration);
        $method = $reflection->getMethod('log_event');
        $method->setAccessible(true);

        // Log 60 events
        for ($i = 0; $i < 60; $i++) {
            $method->invoke($this->integration, 'test_event_' . $i, array());
        }

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(50, $events, 'Should limit to 50 events');
        $this->assertEquals('test_event_59', $events[0]['event_type'], 'Most recent event should be first');
    }

    public function testLogEventPrependsNewEvents()
    {
        $reflection = new ReflectionClass($this->integration);
        $method = $reflection->getMethod('log_event');
        $method->setAccessible(true);

        $method->invoke($this->integration, 'first_event', array());
        $method->invoke($this->integration, 'second_event', array());

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertEquals('second_event', $events[0]['event_type'], 'Newest event should be first');
        $this->assertEquals('first_event', $events[1]['event_type'], 'Older event should be second');
    }

    public function testOnTransactionStoreLogsStripeTransaction()
    {
        $txn = new MeprTransaction(array(
            'id' => 123,
            'trans_num' => 'ch_abc123',
            'user_id' => 1,
            'product_id' => 1,
            'total' => 29.99,
            'status' => 'pending',
            'gateway' => 'stripe',
            'subscription_id' => 0,
        ));

        $this->integration->on_transaction_store($txn);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events, 'Should log transaction');
        $this->assertEquals('mepr_txn_store', $events[0]['event_type']);
        $this->assertEquals(29.99, $events[0]['amount']);
    }

    public function testOnTransactionStoreIgnoresNonStripeGateway()
    {
        $txn = new MeprTransaction(array(
            'gateway' => 'paypal',
        ));

        $this->integration->on_transaction_store($txn);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(0, $events, 'Should not log non-Stripe transaction');
    }

    public function testOnTransactionCompleteLogsWithCompleteStatus()
    {
        $txn = new MeprTransaction(array(
            'id' => 123,
            'gateway' => 'stripe',
            'status' => 'complete',
            'total' => 19.99,
        ));

        $this->integration->on_transaction_complete($txn);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_txn_complete', $events[0]['event_type']);
        $this->assertEquals('complete', $events[0]['data']['status']);
    }

    public function testOnTransactionFailedLogsWithFailedStatus()
    {
        $txn = new MeprTransaction(array(
            'id' => 123,
            'gateway' => 'stripe',
            'status' => 'failed',
        ));

        $this->integration->on_transaction_failed($txn);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_txn_failed', $events[0]['event_type']);
        $this->assertEquals('failed', $events[0]['data']['status']);
    }

    public function testOnTransactionRefundedLogsWithRefundedStatus()
    {
        $txn = new MeprTransaction(array(
            'id' => 123,
            'gateway' => 'stripe',
        ));

        $this->integration->on_transaction_refunded($txn);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_txn_refunded', $events[0]['event_type']);
        $this->assertEquals('refunded', $events[0]['data']['status']);
    }

    public function testOnSubscriptionCreatedLogsEvent()
    {
        $sub = new MeprSubscription(array(
            'id' => 1,
            'subscr_id' => 'sub_xyz789',
            'user_id' => 1,
            'product_id' => 1,
            'total' => 49.99,
            'status' => 'active',
            'gateway' => 'stripe',
        ));

        $event = new MeprEvent($sub);
        $this->integration->on_subscription_created($event);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_subscription_created', $events[0]['event_type']);
        $this->assertEquals('sub_xyz789', $events[0]['data']['subscr_id']);
    }

    public function testOnSubscriptionStoppedLogsCancelledStatus()
    {
        $sub = new MeprSubscription(array(
            'gateway' => 'stripe',
        ));

        $event = new MeprEvent($sub);
        $this->integration->on_subscription_stopped($event);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_subscription_stopped', $events[0]['event_type']);
        $this->assertEquals('cancelled', $events[0]['data']['status']);
    }

    public function testOnStripeCheckoutPendingLogsEvent()
    {
        $txn = new MeprTransaction(array(
            'id' => 123,
            'gateway' => 'stripe',
            'total' => 19.99,
        ));

        $usr = new MeprUser();
        $usr->user_email = 'checkout@example.com';

        $this->integration->on_stripe_checkout_pending($txn, $usr);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_stripe_checkout_pending', $events[0]['event_type']);
        $this->assertEquals('checkout@example.com', $events[0]['data']['user_email']);
    }

    public function testOnStripeSubscriptionCreatedLogsEvent()
    {
        $txn = new MeprTransaction(array(
            'id' => 123,
            'gateway' => 'stripe',
            'user_id' => 1,
            'product_id' => 1,
            'total' => 29.99,
            'status' => 'complete',
        ));

        $sub = new MeprSubscription(array(
            'id' => 456,
            'subscr_id' => 'sub_test123',
        ));

        $this->integration->on_stripe_subscription_created($txn, $sub);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_stripe_subscription_created', $events[0]['event_type']);
        $this->assertEquals('sub_test123', $events[0]['data']['subscr_id']);
        $this->assertEquals(456, $events[0]['data']['subscription_id']);
    }

    public function testOnStripePaymentFailedLogsEvent()
    {
        $payment_intent = (object) array('id' => 'pi_failed123');

        $this->integration->on_stripe_payment_failed($payment_intent);

        $events = get_option('stripe_cli_demo_mepr_events', array());

        $this->assertCount(1, $events);
        $this->assertEquals('mepr_stripe_payment_failed', $events[0]['event_type']);
        $this->assertEquals('pi_failed123', $events[0]['data']['payment_intent_id']);
        $this->assertEquals('failed', $events[0]['data']['status']);
    }

    public function testGetTxnDetailsReturnsCorrectStructure()
    {
        $txn = new MeprTransaction(array(
            'id' => 999,
            'trans_num' => 'ch_details_test',
            'user_id' => 42,
            'product_id' => 10,
            'total' => 99.99,
            'status' => 'complete',
            'gateway' => 'stripe',
            'subscription_id' => 5,
        ));

        $reflection = new ReflectionClass($this->integration);
        $method = $reflection->getMethod('get_txn_details');
        $method->setAccessible(true);

        $details = $method->invoke($this->integration, $txn);

        $this->assertEquals(999, $details['txn_id']);
        $this->assertEquals('ch_details_test', $details['trans_num']);
        $this->assertEquals(42, $details['user_id']);
        $this->assertEquals(10, $details['product_id']);
        $this->assertEquals(99.99, $details['amount']);
        $this->assertEquals('complete', $details['status']);
        $this->assertEquals('stripe', $details['gateway']);
        $this->assertEquals(5, $details['subscription_id']);
    }

    public function testGetStatusColorReturnsCorrectColors()
    {
        $reflection = new ReflectionClass($this->integration);
        $method = $reflection->getMethod('get_status_color');
        $method->setAccessible(true);

        $this->assertEquals('#d4edda', $method->invoke($this->integration, 'complete'));
        $this->assertEquals('#d4edda', $method->invoke($this->integration, 'confirmed'));
        $this->assertEquals('#fff3cd', $method->invoke($this->integration, 'pending'));
        $this->assertEquals('#f8d7da', $method->invoke($this->integration, 'failed'));
        $this->assertEquals('#cce5ff', $method->invoke($this->integration, 'refunded'));
        $this->assertEquals('#e0e0e0', $method->invoke($this->integration, 'unknown_status'));
    }

    public function testGetEventsHtmlReturnsEmptyMessageWhenNoEvents()
    {
        $reflection = new ReflectionClass($this->integration);
        $method = $reflection->getMethod('get_events_html');
        $method->setAccessible(true);

        $html = $method->invoke($this->integration, array());

        $this->assertStringContainsString('No MemberPress events yet', $html);
    }

    public function testGetEventsHtmlRendersEvents()
    {
        $events = array(
            array(
                'timestamp' => '2025-01-01 12:00:00',
                'event_type' => 'mepr_txn_complete',
                'status' => 'logged',
                'user_email' => 'test@example.com',
                'product_name' => 'Premium Membership',
                'amount' => 49.99,
                'data' => array('txn_id' => 123),
            ),
        );

        $reflection = new ReflectionClass($this->integration);
        $method = $reflection->getMethod('get_events_html');
        $method->setAccessible(true);

        $html = $method->invoke($this->integration, $events);

        $this->assertStringContainsString('mepr_txn_complete', $html);
        $this->assertStringContainsString('test@example.com', $html);
        $this->assertStringContainsString('Premium Membership', $html);
        $this->assertStringContainsString('49.99', $html);
    }

    public function testAjaxGetMeprEventsRequiresValidNonce()
    {
        // Set invalid nonce
        $_POST['nonce'] = 'invalid_nonce';

        $this->integration->ajax_get_mepr_events();

        global $wp_json_response;

        $this->assertFalse($wp_json_response['success'], 'Should fail with invalid nonce');
        $this->assertEquals('Invalid security token', $wp_json_response['data']['message']);

        // Clean up
        unset($_POST['nonce']);
    }

    public function testAjaxClearMeprEventsClearsEvents()
    {
        // Add some events first
        update_option('stripe_cli_demo_mepr_events', array(
            array('event_type' => 'test'),
        ));

        $_POST['nonce'] = 'valid_nonce';

        $this->integration->ajax_clear_mepr_events();

        $events = get_option('stripe_cli_demo_mepr_events', array());
        $this->assertCount(0, $events, 'Events should be cleared');

        global $wp_json_response;
        $this->assertTrue($wp_json_response['success']);
    }

    public function testHooksAreRegistered()
    {
        global $wp_actions;

        // Check that key hooks are registered
        $expected_hooks = array(
            'mepr_txn_store',
            'mepr_txn_status_complete',
            'mepr_stripe_checkout_pending',
            'mepr_stripe_subscription_created',
        );

        foreach ($expected_hooks as $hook) {
            $this->assertArrayHasKey($hook, $wp_actions, "Hook $hook should be registered");
        }
    }
}
