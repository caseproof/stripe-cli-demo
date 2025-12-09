<?php
/**
 * Plugin Name: Stripe CLI Demo
 * Plugin URI: https://github.com/your-repo/stripe-cli-demo
 * Description: A demo plugin to teach developers how to use the Stripe CLI for local webhook testing.
 * Version: 1.0.0
 * Author: Your Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stripe-cli-demo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('STRIPE_CLI_DEMO_VERSION', '1.0.0');
define('STRIPE_CLI_DEMO_PATH', plugin_dir_path(__FILE__));
define('STRIPE_CLI_DEMO_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Stripe_CLI_Demo {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once STRIPE_CLI_DEMO_PATH . 'includes/class-settings.php';
        require_once STRIPE_CLI_DEMO_PATH . 'includes/class-admin-pages.php';
        require_once STRIPE_CLI_DEMO_PATH . 'includes/class-checkout.php';
        require_once STRIPE_CLI_DEMO_PATH . 'includes/class-webhook.php';
        require_once STRIPE_CLI_DEMO_PATH . 'includes/class-wizard.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Load Stripe PHP library if credentials are set
        $this->maybe_load_stripe();

        // Initialize admin
        if (is_admin()) {
            Stripe_CLI_Demo_Settings::get_instance();
            Stripe_CLI_Demo_Admin_Pages::get_instance();
            Stripe_CLI_Demo_Wizard::get_instance();
        }

        // Initialize checkout handler (AJAX)
        Stripe_CLI_Demo_Checkout::get_instance();

        // Initialize webhook handler
        Stripe_CLI_Demo_Webhook::get_instance();
    }

    /**
     * Load and configure Stripe SDK
     */
    private function maybe_load_stripe() {
        $secret_key = get_option('stripe_cli_demo_secret_key', '');

        if (!empty($secret_key)) {
            // Include Stripe PHP library (bundled with plugin)
            if (!class_exists('\Stripe\Stripe')) {
                require_once STRIPE_CLI_DEMO_PATH . 'vendor/autoload.php';
            }
            \Stripe\Stripe::setApiKey($secret_key);
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create webhook events log option
        if (false === get_option('stripe_cli_demo_webhook_events')) {
            add_option('stripe_cli_demo_webhook_events', array());
        }

        // Set redirect flag for setup wizard
        set_transient('stripe_cli_demo_activation_redirect', true, 30);

        // Flush rewrite rules for webhook endpoint
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Get plugin option
     */
    public static function get_option($key, $default = '') {
        return get_option('stripe_cli_demo_' . $key, $default);
    }
}

// Initialize the plugin
Stripe_CLI_Demo::get_instance();
