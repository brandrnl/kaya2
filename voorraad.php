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

// Load plugin files
add_action('plugins_loaded', 'gvs_load_plugin');
function gvs_load_plugin() {
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
        // Verwijder de automatische redirect naar login
        // We handelen login nu in de template zelf af
        include GVS_PLUGIN_DIR . 'mobile/template-mobile-scanner.php';
        exit;
    }
}