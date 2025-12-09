<?php
/**
 * Settings page for Stripe CLI Demo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_CLI_Demo_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            __('Stripe CLI Demo', 'stripe-cli-demo'),
            __('Stripe CLI Demo', 'stripe-cli-demo'),
            'manage_options',
            'stripe-cli-demo',
            array('Stripe_CLI_Demo_Admin_Pages', 'render_demo_page'),
            'dashicons-money-alt',
            30
        );

        // Settings submenu
        add_submenu_page(
            'stripe-cli-demo',
            __('Settings', 'stripe-cli-demo'),
            __('Settings', 'stripe-cli-demo'),
            'manage_options',
            'stripe-cli-demo-settings',
            array($this, 'render_settings_page')
        );

        // Webhook Events submenu
        add_submenu_page(
            'stripe-cli-demo',
            __('Webhook Events', 'stripe-cli-demo'),
            __('Webhook Events', 'stripe-cli-demo'),
            'manage_options',
            'stripe-cli-demo-events',
            array('Stripe_CLI_Demo_Admin_Pages', 'render_events_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting('stripe_cli_demo_settings', 'stripe_cli_demo_publishable_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('stripe_cli_demo_settings', 'stripe_cli_demo_secret_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('stripe_cli_demo_settings', 'stripe_cli_demo_webhook_secret', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));

        // Settings section
        add_settings_section(
            'stripe_cli_demo_api_settings',
            __('Stripe API Credentials', 'stripe-cli-demo'),
            array($this, 'render_api_section'),
            'stripe-cli-demo-settings'
        );

        // Publishable Key
        add_settings_field(
            'stripe_cli_demo_publishable_key',
            __('Publishable Key', 'stripe-cli-demo'),
            array($this, 'render_publishable_key_field'),
            'stripe-cli-demo-settings',
            'stripe_cli_demo_api_settings'
        );

        // Secret Key
        add_settings_field(
            'stripe_cli_demo_secret_key',
            __('Secret Key', 'stripe-cli-demo'),
            array($this, 'render_secret_key_field'),
            'stripe-cli-demo-settings',
            'stripe_cli_demo_api_settings'
        );

        // Webhook Secret
        add_settings_field(
            'stripe_cli_demo_webhook_secret',
            __('Webhook Secret', 'stripe-cli-demo'),
            array($this, 'render_webhook_secret_field'),
            'stripe-cli-demo-settings',
            'stripe_cli_demo_api_settings'
        );
    }

    /**
     * Render API section description
     */
    public function render_api_section() {
        ?>
        <p><?php _e('Enter your Stripe test mode API credentials below.', 'stripe-cli-demo'); ?></p>
        <p>
            <strong><?php _e('Get your API keys:', 'stripe-cli-demo'); ?></strong>
            <a href="https://dashboard.stripe.com/test/apikeys" target="_blank">
                <?php _e('Stripe Dashboard → Developers → API Keys', 'stripe-cli-demo'); ?>
            </a>
        </p>
        <div class="notice notice-warning inline" style="margin: 15px 0;">
            <p>
                <strong><?php _e('Important:', 'stripe-cli-demo'); ?></strong>
                <?php _e('Only use TEST mode keys (starting with pk_test_ and sk_test_). Never use live keys for testing!', 'stripe-cli-demo'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render publishable key field
     */
    public function render_publishable_key_field() {
        $value = get_option('stripe_cli_demo_publishable_key', '');
        ?>
        <input type="text"
               name="stripe_cli_demo_publishable_key"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="pk_test_..."
        />
        <p class="description"><?php _e('Starts with pk_test_', 'stripe-cli-demo'); ?></p>
        <?php
    }

    /**
     * Render secret key field
     */
    public function render_secret_key_field() {
        $value = get_option('stripe_cli_demo_secret_key', '');
        ?>
        <input type="password"
               name="stripe_cli_demo_secret_key"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="sk_test_..."
        />
        <p class="description"><?php _e('Starts with sk_test_', 'stripe-cli-demo'); ?></p>
        <?php
    }

    /**
     * Render webhook secret field
     */
    public function render_webhook_secret_field() {
        $value = get_option('stripe_cli_demo_webhook_secret', '');
        ?>
        <input type="password"
               name="stripe_cli_demo_webhook_secret"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="whsec_..."
        />
        <p class="description">
            <?php _e('Get this from the Stripe CLI when you run:', 'stripe-cli-demo'); ?><br>
            <code>stripe listen --forward-to <?php echo esc_url(home_url('/wp-json/stripe-cli-demo/v1/webhook')); ?></code>
        </p>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields('stripe_cli_demo_settings');
                do_settings_sections('stripe-cli-demo-settings');
                submit_button(__('Save Settings', 'stripe-cli-demo'));
                ?>
            </form>

            <hr style="margin: 30px 0;">

            <h2><?php _e('Quick Start Guide', 'stripe-cli-demo'); ?></h2>

            <div class="card" style="max-width: 800px; padding: 20px;">
                <h3><?php _e('1. Start the Stripe CLI Listener', 'stripe-cli-demo'); ?></h3>
                <p><?php _e('In your terminal, run:', 'stripe-cli-demo'); ?></p>
                <pre style="background: #1a1a2e; color: #4ade80; padding: 15px; border-radius: 6px; overflow-x: auto;">stripe listen --forward-to <?php echo esc_url(home_url('/wp-json/stripe-cli-demo/v1/webhook')); ?></pre>

                <h3 style="margin-top: 20px;"><?php _e('2. For Debugging (JSON Output)', 'stripe-cli-demo'); ?></h3>
                <pre style="background: #1a1a2e; color: #4ade80; padding: 15px; border-radius: 6px; overflow-x: auto;">stripe listen --forward-to <?php echo esc_url(home_url('/wp-json/stripe-cli-demo/v1/webhook')); ?> --format JSON</pre>

                <h3 style="margin-top: 20px;"><?php _e('3. Copy the Webhook Secret', 'stripe-cli-demo'); ?></h3>
                <p><?php _e('When the CLI starts, it shows a webhook signing secret (whsec_...). Copy that and paste it in the Webhook Secret field above.', 'stripe-cli-demo'); ?></p>

                <h3 style="margin-top: 20px;"><?php _e('4. Test It!', 'stripe-cli-demo'); ?></h3>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo'); ?>" class="button button-primary">
                        <?php _e('Go to Demo Store', 'stripe-cli-demo'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
}
