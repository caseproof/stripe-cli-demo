<?php
/**
 * Setup Wizard for Stripe CLI Demo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Stripe_CLI_Demo_Wizard {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_wizard_page'));
        add_action('admin_init', array($this, 'maybe_redirect_to_wizard'));
        add_action('wp_ajax_stripe_cli_demo_wizard_save', array($this, 'ajax_save_step'));
        add_action('wp_ajax_stripe_cli_demo_wizard_test', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_stripe_cli_demo_wizard_skip', array($this, 'ajax_skip_wizard'));
    }

    /**
     * Add hidden wizard page
     */
    public function add_wizard_page() {
        add_submenu_page(
            null, // Hidden from menu
            __('Setup Wizard', 'stripe-cli-demo'),
            __('Setup Wizard', 'stripe-cli-demo'),
            'manage_options',
            'stripe-cli-demo-wizard',
            array($this, 'render_wizard')
        );
    }

    /**
     * Redirect to wizard when setup is incomplete
     */
    public function maybe_redirect_to_wizard() {
        // Don't redirect on AJAX requests
        if (wp_doing_ajax()) {
            return;
        }

        // Don't redirect on bulk activations
        if (isset($_GET['activate-multi'])) {
            return;
        }

        // Don't redirect if already on wizard page
        if (isset($_GET['page']) && $_GET['page'] === 'stripe-cli-demo-wizard') {
            return;
        }

        // Check if this is a fresh activation redirect
        $is_activation_redirect = get_transient('stripe_cli_demo_activation_redirect');
        if ($is_activation_redirect) {
            delete_transient('stripe_cli_demo_activation_redirect');
        }

        // Check if setup is complete
        $publishable_key = get_option('stripe_cli_demo_publishable_key', '');
        $secret_key = get_option('stripe_cli_demo_secret_key', '');
        $webhook_secret = get_option('stripe_cli_demo_webhook_secret', '');

        $is_configured = !empty($publishable_key) && !empty($secret_key) && !empty($webhook_secret);

        // Redirect to wizard if:
        // 1. Fresh activation, OR
        // 2. Trying to access plugin pages but not configured
        if ($is_activation_redirect && !$is_configured) {
            wp_safe_redirect(admin_url('admin.php?page=stripe-cli-demo-wizard'));
            exit;
        }

        // Also redirect if accessing main plugin pages without configuration
        if (isset($_GET['page']) && strpos($_GET['page'], 'stripe-cli-demo') === 0) {
            // Allow settings page access so they can configure manually
            if ($_GET['page'] === 'stripe-cli-demo-settings') {
                return;
            }

            // Redirect demo and events pages to wizard if not configured
            if (!$is_configured && $_GET['page'] !== 'stripe-cli-demo-wizard') {
                wp_safe_redirect(admin_url('admin.php?page=stripe-cli-demo-wizard'));
                exit;
            }
        }
    }

    /**
     * Render the wizard page
     */
    public function render_wizard() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        $webhook_url = home_url('/wp-json/stripe-cli-demo/v1/webhook');

        // Get saved values
        $publishable_key = get_option('stripe_cli_demo_publishable_key', '');
        $secret_key = get_option('stripe_cli_demo_secret_key', '');
        $webhook_secret = get_option('stripe_cli_demo_webhook_secret', '');
        ?>
        <div class="stripe-cli-demo-wizard-wrap">
            <div class="wizard-container">
                <div class="wizard-header">
                    <h1><?php _e('Stripe CLI Demo Setup', 'stripe-cli-demo'); ?></h1>
                    <p><?php _e('Let\'s get you set up to test Stripe webhooks locally', 'stripe-cli-demo'); ?></p>
                </div>

                <div class="wizard-progress">
                    <div class="progress-step <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $current_step > 1 ? 'completed' : ''; ?>">
                        <span class="step-number">1</span>
                        <span class="step-label"><?php _e('API Keys', 'stripe-cli-demo'); ?></span>
                    </div>
                    <div class="progress-line <?php echo $current_step > 1 ? 'completed' : ''; ?>"></div>
                    <div class="progress-step <?php echo $current_step >= 2 ? 'active' : ''; ?> <?php echo $current_step > 2 ? 'completed' : ''; ?>">
                        <span class="step-number">2</span>
                        <span class="step-label"><?php _e('Stripe CLI', 'stripe-cli-demo'); ?></span>
                    </div>
                    <div class="progress-line <?php echo $current_step > 2 ? 'completed' : ''; ?>"></div>
                    <div class="progress-step <?php echo $current_step >= 3 ? 'active' : ''; ?> <?php echo $current_step > 3 ? 'completed' : ''; ?>">
                        <span class="step-number">3</span>
                        <span class="step-label"><?php _e('Webhook Secret', 'stripe-cli-demo'); ?></span>
                    </div>
                    <div class="progress-line <?php echo $current_step > 3 ? 'completed' : ''; ?>"></div>
                    <div class="progress-step <?php echo $current_step >= 4 ? 'active' : ''; ?>">
                        <span class="step-number">4</span>
                        <span class="step-label"><?php _e('Test', 'stripe-cli-demo'); ?></span>
                    </div>
                </div>

                <div class="wizard-content">
                    <!-- Step 1: API Keys -->
                    <div class="wizard-step <?php echo $current_step === 1 ? 'active' : ''; ?>" data-step="1">
                        <h2><?php _e('Enter Your Stripe API Keys', 'stripe-cli-demo'); ?></h2>
                        <p class="step-description">
                            <?php _e('Get your test API keys from the', 'stripe-cli-demo'); ?>
                            <a href="https://dashboard.stripe.com/test/apikeys" target="_blank"><?php _e('Stripe Dashboard', 'stripe-cli-demo'); ?></a>
                        </p>

                        <div class="notice notice-warning inline wizard-notice">
                            <p><strong><?php _e('Important:', 'stripe-cli-demo'); ?></strong> <?php _e('Only use TEST mode keys (pk_test_ and sk_test_). Never use live keys!', 'stripe-cli-demo'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="publishable_key"><?php _e('Publishable Key', 'stripe-cli-demo'); ?></label>
                            <input type="text" id="publishable_key" name="publishable_key"
                                   value="<?php echo esc_attr($publishable_key); ?>"
                                   placeholder="pk_test_..." class="large-text" />
                            <p class="description"><?php _e('Starts with pk_test_', 'stripe-cli-demo'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="secret_key"><?php _e('Secret Key', 'stripe-cli-demo'); ?></label>
                            <input type="password" id="secret_key" name="secret_key"
                                   value="<?php echo esc_attr($secret_key); ?>"
                                   placeholder="sk_test_..." class="large-text" />
                            <p class="description"><?php _e('Starts with sk_test_', 'stripe-cli-demo'); ?></p>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="button button-secondary wizard-skip"><?php _e('Skip Setup', 'stripe-cli-demo'); ?></button>
                            <button type="button" class="button button-primary wizard-next" data-step="1"><?php _e('Continue', 'stripe-cli-demo'); ?></button>
                        </div>
                    </div>

                    <!-- Step 2: Stripe CLI -->
                    <div class="wizard-step <?php echo $current_step === 2 ? 'active' : ''; ?>" data-step="2">
                        <h2><?php _e('Install & Run the Stripe CLI', 'stripe-cli-demo'); ?></h2>
                        <p class="step-description"><?php _e('The Stripe CLI forwards webhook events from Stripe to your local WordPress site.', 'stripe-cli-demo'); ?></p>

                        <div class="instruction-box">
                            <h3><?php _e('1. Install the Stripe CLI', 'stripe-cli-demo'); ?></h3>
                            <p><?php _e('If you haven\'t already, install it:', 'stripe-cli-demo'); ?></p>
                            <div class="code-tabs">
                                <button type="button" class="code-tab active" data-tab="mac"><?php _e('macOS', 'stripe-cli-demo'); ?></button>
                                <button type="button" class="code-tab" data-tab="windows"><?php _e('Windows', 'stripe-cli-demo'); ?></button>
                                <button type="button" class="code-tab" data-tab="linux"><?php _e('Linux', 'stripe-cli-demo'); ?></button>
                            </div>
                            <pre class="code-block" data-tab="mac">brew install stripe/stripe-cli/stripe</pre>
                            <pre class="code-block" data-tab="windows" style="display:none;">scoop install stripe</pre>
                            <pre class="code-block" data-tab="linux" style="display:none;"># Download from https://github.com/stripe/stripe-cli/releases</pre>
                        </div>

                        <div class="instruction-box">
                            <h3><?php _e('2. Login to Stripe', 'stripe-cli-demo'); ?></h3>
                            <pre class="code-block">stripe login</pre>
                        </div>

                        <div class="instruction-box">
                            <h3><?php _e('3. Start the Webhook Listener', 'stripe-cli-demo'); ?></h3>
                            <p><?php _e('Run this command in your terminal:', 'stripe-cli-demo'); ?></p>
                            <pre class="code-block copyable">stripe listen --forward-to <?php echo esc_url($webhook_url); ?></pre>
                            <button type="button" class="button copy-btn" data-copy="stripe listen --forward-to <?php echo esc_url($webhook_url); ?>">
                                <?php _e('Copy Command', 'stripe-cli-demo'); ?>
                            </button>

                            <p style="margin-top: 15px;"><strong><?php _e('For debugging:', 'stripe-cli-demo'); ?></strong></p>
                            <pre class="code-block copyable">stripe listen --forward-to <?php echo esc_url($webhook_url); ?> --format JSON</pre>
                            <button type="button" class="button copy-btn" data-copy="stripe listen --forward-to <?php echo esc_url($webhook_url); ?> --format JSON">
                                <?php _e('Copy Debug Command', 'stripe-cli-demo'); ?>
                            </button>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="button button-secondary wizard-back" data-step="2"><?php _e('Back', 'stripe-cli-demo'); ?></button>
                            <button type="button" class="button button-primary wizard-next" data-step="2"><?php _e('I\'ve Started the CLI', 'stripe-cli-demo'); ?></button>
                        </div>
                    </div>

                    <!-- Step 3: Webhook Secret -->
                    <div class="wizard-step <?php echo $current_step === 3 ? 'active' : ''; ?>" data-step="3">
                        <h2><?php _e('Enter Your Webhook Secret', 'stripe-cli-demo'); ?></h2>
                        <p class="step-description"><?php _e('When you started the Stripe CLI, it displayed a webhook signing secret. Copy it here.', 'stripe-cli-demo'); ?></p>

                        <div class="example-box">
                            <p><strong><?php _e('Look for this in your terminal:', 'stripe-cli-demo'); ?></strong></p>
                            <pre>> Ready! Your webhook signing secret is <span class="highlight">whsec_xxxxxxxxxxxxx</span></pre>
                        </div>

                        <div class="form-field">
                            <label for="webhook_secret"><?php _e('Webhook Signing Secret', 'stripe-cli-demo'); ?></label>
                            <input type="text" id="webhook_secret" name="webhook_secret"
                                   value="<?php echo esc_attr($webhook_secret); ?>"
                                   placeholder="whsec_..." class="large-text" />
                            <p class="description"><?php _e('Starts with whsec_', 'stripe-cli-demo'); ?></p>
                        </div>

                        <div class="notice notice-info inline wizard-notice">
                            <p><strong><?php _e('Note:', 'stripe-cli-demo'); ?></strong> <?php _e('The webhook secret changes each time you restart the Stripe CLI. You\'ll need to update it if you restart.', 'stripe-cli-demo'); ?></p>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="button button-secondary wizard-back" data-step="3"><?php _e('Back', 'stripe-cli-demo'); ?></button>
                            <button type="button" class="button button-primary wizard-next" data-step="3"><?php _e('Continue', 'stripe-cli-demo'); ?></button>
                        </div>
                    </div>

                    <!-- Step 4: Test -->
                    <div class="wizard-step <?php echo $current_step === 4 ? 'active' : ''; ?>" data-step="4">
                        <h2><?php _e('Test Your Setup', 'stripe-cli-demo'); ?></h2>
                        <p class="step-description"><?php _e('Let\'s verify everything is working correctly.', 'stripe-cli-demo'); ?></p>

                        <div class="test-section">
                            <h3><?php _e('Connection Test', 'stripe-cli-demo'); ?></h3>
                            <p><?php _e('Click the button below to test your Stripe API connection:', 'stripe-cli-demo'); ?></p>
                            <button type="button" id="test-connection-btn" class="button button-secondary">
                                <?php _e('Test API Connection', 'stripe-cli-demo'); ?>
                            </button>
                            <div id="test-result" class="test-result" style="display: none;"></div>
                        </div>

                        <div class="test-section">
                            <h3><?php _e('Webhook Test', 'stripe-cli-demo'); ?></h3>
                            <p><?php _e('In a separate terminal, run this command to trigger a test webhook:', 'stripe-cli-demo'); ?></p>
                            <pre class="code-block copyable">stripe trigger payment_intent.succeeded</pre>
                            <button type="button" class="button copy-btn" data-copy="stripe trigger payment_intent.succeeded">
                                <?php _e('Copy Command', 'stripe-cli-demo'); ?>
                            </button>
                            <p style="margin-top: 15px;">
                                <?php _e('Then check the', 'stripe-cli-demo'); ?>
                                <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo#webhook-events'); ?>" target="_blank"><?php _e('Webhook Events', 'stripe-cli-demo'); ?></a>
                                <?php _e('section to see if it was received.', 'stripe-cli-demo'); ?>
                            </p>
                        </div>

                        <div class="wizard-actions">
                            <button type="button" class="button button-secondary wizard-back" data-step="4"><?php _e('Back', 'stripe-cli-demo'); ?></button>
                            <button type="button" class="button button-primary wizard-finish"><?php _e('Finish Setup', 'stripe-cli-demo'); ?></button>
                        </div>
                    </div>

                    <!-- Complete -->
                    <div class="wizard-step" data-step="5">
                        <div class="wizard-complete">
                            <div class="complete-icon">&#10003;</div>
                            <h2><?php _e('You\'re All Set!', 'stripe-cli-demo'); ?></h2>
                            <p><?php _e('Your Stripe CLI Demo is configured and ready to use.', 'stripe-cli-demo'); ?></p>

                            <div class="complete-actions">
                                <a href="<?php echo admin_url('admin.php?page=stripe-cli-demo'); ?>" class="button button-primary button-hero">
                                    <?php _e('Go to Demo Store', 'stripe-cli-demo'); ?>
                                </a>
                            </div>

                            <div class="reminder-box">
                                <h3><?php _e('Remember:', 'stripe-cli-demo'); ?></h3>
                                <ul>
                                    <li><?php _e('Keep <code>stripe listen</code> running while testing', 'stripe-cli-demo'); ?></li>
                                    <li><?php _e('Use test card: <code>4242 4242 4242 4242</code>', 'stripe-cli-demo'); ?></li>
                                    <li><?php _e('Update webhook secret if you restart the CLI', 'stripe-cli-demo'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            <?php echo $this->get_wizard_styles(); ?>
        </style>

        <script>
            <?php echo $this->get_wizard_scripts(); ?>
        </script>
        <?php
    }

    /**
     * Get wizard CSS styles
     */
    private function get_wizard_styles() {
        return '
            .stripe-cli-demo-wizard-wrap {
                background: #f0f0f1;
                margin: -20px -20px 0 -20px;
                padding: 20px;
                min-height: 100vh;
            }

            .wizard-container {
                max-width: 700px;
                margin: 0 auto;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                overflow: hidden;
            }

            .wizard-header {
                background: linear-gradient(135deg, #635bff 0%, #4b45c6 100%);
                color: #fff;
                padding: 30px;
                text-align: center;
            }

            .wizard-header h1 {
                margin: 0 0 10px 0;
                color: #fff;
                font-size: 28px;
            }

            .wizard-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 16px;
            }

            .wizard-progress {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 30px;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
            }

            .progress-step {
                display: flex;
                flex-direction: column;
                align-items: center;
                opacity: 0.5;
            }

            .progress-step.active {
                opacity: 1;
            }

            .progress-step.completed {
                opacity: 1;
            }

            .progress-step .step-number {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: #e9ecef;
                color: #6c757d;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .progress-step.active .step-number {
                background: #635bff;
                color: #fff;
            }

            .progress-step.completed .step-number {
                background: #28a745;
                color: #fff;
            }

            .progress-step.completed .step-number::after {
                content: "✓";
            }

            .progress-step.completed .step-number {
                font-size: 0;
            }

            .progress-step.completed .step-number::after {
                font-size: 16px;
            }

            .step-label {
                font-size: 12px;
                color: #6c757d;
            }

            .progress-step.active .step-label {
                color: #1d2327;
                font-weight: 600;
            }

            .progress-line {
                width: 60px;
                height: 3px;
                background: #e9ecef;
                margin: 0 10px 20px;
            }

            .progress-line.completed {
                background: #28a745;
            }

            .wizard-content {
                padding: 30px;
            }

            .wizard-step {
                display: none;
            }

            .wizard-step.active {
                display: block;
            }

            .wizard-step h2 {
                margin: 0 0 10px 0;
                font-size: 22px;
            }

            .step-description {
                color: #6c757d;
                margin-bottom: 25px;
            }

            .form-field {
                margin-bottom: 20px;
            }

            .form-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .form-field input.large-text {
                width: 100%;
                padding: 10px 12px;
                font-size: 15px;
            }

            .form-field .description {
                margin-top: 5px;
                color: #6c757d;
            }

            .wizard-notice {
                margin: 20px 0 !important;
            }

            .instruction-box {
                background: #f8f9fa;
                border-radius: 6px;
                padding: 20px;
                margin-bottom: 20px;
            }

            .instruction-box h3 {
                margin: 0 0 10px 0;
                font-size: 16px;
            }

            .code-tabs {
                display: flex;
                gap: 5px;
                margin-bottom: 10px;
            }

            .code-tab {
                padding: 5px 12px;
                border: 1px solid #ddd;
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
            }

            .code-tab.active {
                background: #635bff;
                color: #fff;
                border-color: #635bff;
            }

            .code-block {
                background: #1a1a2e;
                color: #4ade80;
                padding: 15px;
                border-radius: 6px;
                font-size: 13px;
                overflow-x: auto;
                margin: 10px 0;
            }

            .copy-btn {
                margin-top: 10px;
            }

            .example-box {
                background: #e8f4fd;
                border-left: 4px solid #635bff;
                padding: 15px 20px;
                border-radius: 0 6px 6px 0;
                margin-bottom: 25px;
            }

            .example-box pre {
                background: #1a1a2e;
                color: #e0e0e0;
                padding: 12px;
                border-radius: 4px;
                margin: 10px 0 0 0;
            }

            .example-box .highlight {
                color: #ff6b6b;
                font-weight: 600;
            }

            .test-section {
                background: #f8f9fa;
                border-radius: 6px;
                padding: 20px;
                margin-bottom: 20px;
            }

            .test-section h3 {
                margin: 0 0 10px 0;
            }

            .test-result {
                margin-top: 15px;
                padding: 12px 15px;
                border-radius: 4px;
            }

            .test-result.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .test-result.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .wizard-actions {
                display: flex;
                justify-content: space-between;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e9ecef;
            }

            .wizard-complete {
                text-align: center;
                padding: 20px 0;
            }

            .complete-icon {
                width: 80px;
                height: 80px;
                background: #28a745;
                border-radius: 50%;
                color: #fff;
                font-size: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
            }

            .complete-actions {
                margin: 30px 0;
            }

            .complete-actions .button {
                margin: 0 10px;
            }

            .reminder-box {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 6px;
                padding: 20px;
                text-align: left;
                margin-top: 30px;
            }

            .reminder-box h3 {
                margin: 0 0 10px 0;
                color: #856404;
            }

            .reminder-box ul {
                margin: 0;
                padding-left: 20px;
            }

            .reminder-box li {
                margin-bottom: 8px;
                color: #856404;
            }

            .reminder-box code {
                background: rgba(0,0,0,0.1);
                padding: 2px 6px;
                border-radius: 3px;
            }
        ';
    }

    /**
     * Get wizard JavaScript
     */
    private function get_wizard_scripts() {
        return '
            (function($) {
                "use strict";

                var ajaxUrl = "' . admin_url('admin-ajax.php') . '";
                var nonce = "' . wp_create_nonce('stripe_cli_demo_wizard') . '";

                // Tab switching
                $(".code-tab").on("click", function() {
                    var tab = $(this).data("tab");
                    $(".code-tab").removeClass("active");
                    $(this).addClass("active");
                    $(".code-block[data-tab]").hide();
                    $(".code-block[data-tab=\"" + tab + "\"]").show();
                });

                // Copy button
                $(".copy-btn").on("click", function() {
                    var btn = $(this);
                    var text = btn.data("copy");
                    var originalText = btn.text();

                    // Try modern clipboard API first, fall back to execCommand
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(function() {
                            btn.text("Copied!");
                            setTimeout(function() {
                                btn.text(originalText);
                            }, 2000);
                        }).catch(function() {
                            // Fallback for clipboard API failure
                            fallbackCopy(text, btn, originalText);
                        });
                    } else {
                        fallbackCopy(text, btn, originalText);
                    }
                });

                // Fallback copy method for older browsers or non-HTTPS
                function fallbackCopy(text, btn, originalText) {
                    var textarea = document.createElement("textarea");
                    textarea.value = text;
                    textarea.style.position = "fixed";
                    textarea.style.opacity = "0";
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand("copy");
                        btn.text("Copied!");
                        setTimeout(function() {
                            btn.text(originalText);
                        }, 2000);
                    } catch (err) {
                        btn.text("Copy failed");
                        setTimeout(function() {
                            btn.text(originalText);
                        }, 2000);
                    }
                    document.body.removeChild(textarea);
                }

                // Next step
                $(".wizard-next").on("click", function() {
                    var step = $(this).data("step");
                    var data = { step: step };

                    // Collect form data based on step
                    if (step === 1) {
                        data.publishable_key = $("#publishable_key").val();
                        data.secret_key = $("#secret_key").val();

                        if (!data.publishable_key || !data.secret_key) {
                            alert("Please enter both API keys");
                            return;
                        }
                    } else if (step === 3) {
                        data.webhook_secret = $("#webhook_secret").val();

                        if (!data.webhook_secret) {
                            alert("Please enter your webhook secret");
                            return;
                        }
                    }

                    // Save and go to next step
                    $.post(ajaxUrl, {
                        action: "stripe_cli_demo_wizard_save",
                        nonce: nonce,
                        data: data
                    }, function(response) {
                        if (response.success) {
                            goToStep(step + 1);
                        } else {
                            alert(response.data.message || "An error occurred");
                        }
                    });
                });

                // Back step
                $(".wizard-back").on("click", function() {
                    var step = $(this).data("step");
                    goToStep(step - 1);
                });

                // Skip wizard
                $(".wizard-skip").on("click", function() {
                    if (confirm("Are you sure? You can always configure the plugin later in Settings.")) {
                        $.post(ajaxUrl, {
                            action: "stripe_cli_demo_wizard_skip",
                            nonce: nonce
                        }, function() {
                            window.location.href = "' . admin_url('admin.php?page=stripe-cli-demo') . '";
                        });
                    }
                });

                // Finish wizard
                $(".wizard-finish").on("click", function() {
                    $.post(ajaxUrl, {
                        action: "stripe_cli_demo_wizard_save",
                        nonce: nonce,
                        data: { step: "complete" }
                    }, function() {
                        goToStep(5);
                    });
                });

                // Test connection
                $("#test-connection-btn").on("click", function() {
                    var btn = $(this);
                    var result = $("#test-result");

                    btn.prop("disabled", true).text("Testing...");
                    result.hide();

                    $.post(ajaxUrl, {
                        action: "stripe_cli_demo_wizard_test",
                        nonce: nonce
                    }, function(response) {
                        btn.prop("disabled", false).text("Test API Connection");

                        if (response.success) {
                            result.removeClass("error").addClass("success")
                                  .html("<strong>Success!</strong> Connected to Stripe account: " + response.data.account)
                                  .show();
                        } else {
                            result.removeClass("success").addClass("error")
                                  .html("<strong>Error:</strong> " + response.data.message)
                                  .show();
                        }
                    });
                });

                function goToStep(step) {
                    $(".wizard-step").removeClass("active");
                    $(".wizard-step[data-step=\"" + step + "\"]").addClass("active");

                    // Update progress
                    $(".progress-step").each(function(index) {
                        var stepNum = index + 1;
                        $(this).removeClass("active completed");
                        if (stepNum < step) {
                            $(this).addClass("completed");
                        } else if (stepNum === step) {
                            $(this).addClass("active");
                        }
                    });

                    $(".progress-line").each(function(index) {
                        var lineAfterStep = index + 1;
                        $(this).removeClass("completed");
                        if (lineAfterStep < step) {
                            $(this).addClass("completed");
                        }
                    });
                }

            })(jQuery);
        ';
    }

    /**
     * AJAX: Save wizard step
     */
    public function ajax_save_step() {
        check_ajax_referer('stripe_cli_demo_wizard', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $data = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : array();
        $step = isset($data['step']) ? sanitize_text_field($data['step']) : '';

        if ($step === '1' || $step === 1) {
            // Validate keys exist
            if (!isset($data['publishable_key']) || !isset($data['secret_key'])) {
                wp_send_json_error(array('message' => 'Both API keys are required'));
            }

            $publishable_key = sanitize_text_field($data['publishable_key']);
            $secret_key = sanitize_text_field($data['secret_key']);

            // Validate keys are not empty
            if (empty($publishable_key) || empty($secret_key)) {
                wp_send_json_error(array('message' => 'Both API keys are required'));
            }

            // Validate key format
            if (strpos($publishable_key, 'pk_test_') !== 0) {
                wp_send_json_error(array('message' => 'Publishable key must start with pk_test_'));
            }
            if (strpos($secret_key, 'sk_test_') !== 0) {
                wp_send_json_error(array('message' => 'Secret key must start with sk_test_'));
            }

            update_option('stripe_cli_demo_publishable_key', $publishable_key);
            update_option('stripe_cli_demo_secret_key', $secret_key);
        }

        if ($step === '3' || $step === 3) {
            // Validate webhook secret exists
            if (!isset($data['webhook_secret'])) {
                wp_send_json_error(array('message' => 'Webhook secret is required'));
            }

            $webhook_secret = sanitize_text_field($data['webhook_secret']);

            if (empty($webhook_secret)) {
                wp_send_json_error(array('message' => 'Webhook secret is required'));
            }

            if (strpos($webhook_secret, 'whsec_') !== 0) {
                wp_send_json_error(array('message' => 'Webhook secret must start with whsec_'));
            }

            update_option('stripe_cli_demo_webhook_secret', $webhook_secret);
        }

        if ($step === 'complete') {
            update_option('stripe_cli_demo_wizard_completed', true);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Test Stripe connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('stripe_cli_demo_wizard', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $secret_key = get_option('stripe_cli_demo_secret_key', '');

        if (empty($secret_key)) {
            wp_send_json_error(array('message' => 'No secret key configured'));
        }

        try {
            if (!class_exists('\Stripe\Stripe')) {
                require_once STRIPE_CLI_DEMO_PATH . 'vendor/autoload.php';
            }

            \Stripe\Stripe::setApiKey($secret_key);
            $account = \Stripe\Account::retrieve();

            wp_send_json_success(array(
                'account' => $account->settings->dashboard->display_name ?? $account->id
            ));

        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * AJAX: Skip wizard
     */
    public function ajax_skip_wizard() {
        check_ajax_referer('stripe_cli_demo_wizard', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        update_option('stripe_cli_demo_wizard_completed', true);
        wp_send_json_success();
    }
}
