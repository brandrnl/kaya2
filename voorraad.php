<?php
/**
 * Plugin Name: Gordijnen Voorraad
 * Plugin URI: https://www.brandr.nl
 * Description: Voorraadsysteem voor gordijnrollen met QR codes en locatiebeheer
 * Version: 1.0.0
 * Author: Brandr
 * License: GPL v2 or later
 * Text Domain: gordijnen-voorraad
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GVS_VERSION', '1.0.0');
define('GVS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GVS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GVS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'gvs_activate_plugin');
function gvs_activate_plugin() {
    require_once GVS_PLUGIN_DIR . 'includes/class-database.php';
    GVS_Database::create_tables();
    
    // Add rewrite rules for mobile scanner
    add_rewrite_rule('^gvs-mobile/?$', 'index.php?gvs_mobile=1', 'top');
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'gvs_deactivate_plugin');
function gvs_deactivate_plugin() {
    flush_rewrite_rules();
}

// Start session heel vroeg
add_action('init', 'gvs_start_session', 1);
function gvs_start_session() {
    if (!session_id()) {
        session_start();
    }
}

// Zorg dat cookies correct worden gezet voor AJAX
add_action('init', 'gvs_setup_ajax_cookies', 2);
function gvs_setup_ajax_cookies() {
    // Set een test cookie om te zorgen dat cookies werken
    if (!isset($_COOKIE['gvs_initialized'])) {
        setcookie('gvs_initialized', '1', time() + 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }
}

// Load plugin files
add_action('plugins_loaded', 'gvs_load_plugin');
function gvs_load_plugin() {
    // Extra session check voor mobile scanner
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'gvs-mobile') !== false) {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    // Load text domain
    load_plugin_textdomain('gordijnen-voorraad', false, dirname(GVS_PLUGIN_BASENAME) . '/languages');
    
    // Load classes
    require_once GVS_PLUGIN_DIR . 'includes/class-database.php';
    require_once GVS_PLUGIN_DIR . 'includes/class-collectie.php';
    require_once GVS_PLUGIN_DIR . 'includes/class-kleur.php';
    require_once GVS_PLUGIN_DIR . 'includes/class-locatie.php';
    require_once GVS_PLUGIN_DIR . 'includes/class-rol.php';
    require_once GVS_PLUGIN_DIR . 'includes/class-qr-generator.php';
    require_once GVS_PLUGIN_DIR . 'includes/class-stock-notifications.php';
    require_once GVS_PLUGIN_DIR . 'includes/ajax-handlers.php';
    // Load widgets
require_once GVS_PLUGIN_DIR . 'includes/widgets/class-gvs-widgets.php';
    
    // Load admin
    if (is_admin()) {
        require_once GVS_PLUGIN_DIR . 'admin/class-admin.php';
        new GVS_Admin();
    }
    
    // Load mobile scanner
    require_once GVS_PLUGIN_DIR . 'mobile/class-mobile-scanner.php';
    new GVS_Mobile_Scanner();
}

// Add rewrite rules
add_action('init', 'gvs_add_rewrite_rules');
function gvs_add_rewrite_rules() {
    add_rewrite_rule('^gvs-mobile/?$', 'index.php?gvs_mobile=1', 'top');
}

// Add query vars
add_filter('query_vars', 'gvs_query_vars');
function gvs_query_vars($vars) {
    $vars[] = 'gvs_mobile';
    return $vars;
}

// Handle mobile scanner template
add_action('template_redirect', 'gvs_mobile_template');
function gvs_mobile_template() {
    if (get_query_var('gvs_mobile')) {
        // Als geen cookie EN geen reload parameter, forceer reload
        if (!isset($_COOKIE['gvs_initialized']) && !isset($_GET['r'])) {
            setcookie('gvs_initialized', '1', time() + 86400, '/');
            
            // Redirect met reload parameter
            wp_redirect(add_query_arg('r', '1', $_SERVER['REQUEST_URI']));
            exit;
        }
        
        include GVS_PLUGIN_DIR . 'mobile/template-mobile-scanner.php';
        exit;
    }
}

// Force HTTPS voor admin-ajax.php als site HTTPS gebruikt
add_filter('admin_url', 'gvs_force_https_admin_url', 10, 3);
function gvs_force_https_admin_url($url, $path, $blog_id) {
    if (is_ssl() && strpos($url, 'admin-ajax.php') !== false) {
        $url = str_replace('http://', 'https://', $url);
    }
    return $url;
}

// Voeg security headers toe voor mobile scanner
add_action('send_headers', 'gvs_add_security_headers');
function gvs_add_security_headers() {
    if (get_query_var('gvs_mobile')) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}

// Voeg cache headers toe voor AJAX responses
add_action('wp_ajax_gvs_scan_qr', 'gvs_add_no_cache_headers', 1);
add_action('wp_ajax_nopriv_gvs_scan_qr', 'gvs_add_no_cache_headers', 1);
add_action('wp_ajax_gvs_mobile_login', 'gvs_add_no_cache_headers', 1);
add_action('wp_ajax_nopriv_gvs_mobile_login', 'gvs_add_no_cache_headers', 1);
add_action('wp_ajax_gvs_delete_rol', 'gvs_add_no_cache_headers', 1);
add_action('wp_ajax_nopriv_gvs_delete_rol', 'gvs_add_no_cache_headers', 1);

function gvs_add_no_cache_headers() {
    header('Cache-Control: no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Test connection endpoint
add_action('wp_ajax_gvs_test_connection', 'gvs_test_connection');
add_action('wp_ajax_nopriv_gvs_test_connection', 'gvs_test_connection');

function gvs_test_connection() {
    check_ajax_referer('gvs_ajax_nonce', 'nonce');
    wp_send_json_success(['message' => 'Connection OK', 'session_id' => session_id()]);
}