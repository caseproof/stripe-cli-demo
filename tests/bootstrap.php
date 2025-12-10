<?php
/**
 * PHPUnit bootstrap file for Stripe CLI Demo tests
 */

// Define test mode
define('STRIPE_CLI_DEMO_TESTING', true);

// Mock WordPress functions if not in WP environment
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        global $wp_actions;
        if (!isset($wp_actions)) {
            $wp_actions = array();
        }
        $wp_actions[$hook][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args,
        );
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        global $wp_actions, $wp_actions_fired;
        if (!isset($wp_actions_fired)) {
            $wp_actions_fired = array();
        }
        $wp_actions_fired[] = array('hook' => $hook, 'args' => $args);

        if (isset($wp_actions[$hook])) {
            foreach ($wp_actions[$hook] as $action) {
                call_user_func_array($action['callback'], $args);
            }
        }
    }
}

if (!function_exists('get_option')) {
    function get_option($key, $default = false) {
        global $wp_options;
        if (!isset($wp_options)) {
            $wp_options = array();
        }
        return isset($wp_options[$key]) ? $wp_options[$key] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($key, $value) {
        global $wp_options;
        if (!isset($wp_options)) {
            $wp_options = array();
        }
        $wp_options[$key] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($key) {
        global $wp_options;
        if (isset($wp_options[$key])) {
            unset($wp_options[$key]);
        }
        return true;
    }
}

if (!function_exists('current_time')) {
    function current_time($format) {
        return date($format);
    }
}

if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        return (object) array(
            'ID' => $user_id,
            'user_email' => 'test@example.com',
            'display_name' => 'Test User',
        );
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id) {
        return (object) array(
            'ID' => $post_id,
            'post_title' => 'Test Product',
            'post_type' => 'memberpressproduct',
        );
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags(trim($str));
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return $nonce === 'valid_nonce';
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'valid_nonce';
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        global $current_user_capabilities;
        if (!isset($current_user_capabilities)) {
            $current_user_capabilities = array('manage_options' => true);
        }
        return isset($current_user_capabilities[$capability]) && $current_user_capabilities[$capability];
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        global $wp_json_response;
        $wp_json_response = array('success' => true, 'data' => $data);
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        global $wp_json_response;
        $wp_json_response = array('success' => false, 'data' => $data);
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Helper to reset global state between tests
function reset_test_state() {
    global $wp_options, $wp_actions, $wp_actions_fired, $wp_json_response;
    $wp_options = array();
    $wp_actions = array();
    $wp_actions_fired = array();
    $wp_json_response = null;
}
