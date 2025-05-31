<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Mobile_Scanner {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_mobile_scripts']);
        add_action('wp_head', [$this, 'add_pwa_meta']);
    }
    
    /**
     * Enqueue mobile scripts
     */
    public function enqueue_mobile_scripts() {
        if (!get_query_var('gvs_mobile')) {
            return;
        }
        
        // Enqueue QR scanner library
        wp_enqueue_script(
            'html5-qrcode',
            'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
            [],
            '2.3.8',
            true
        );
        
        // Enqueue our mobile script
        wp_enqueue_script(
            'gvs-mobile-scanner',
            GVS_PLUGIN_URL . 'assets/js/mobile-scanner.js',
            ['jquery', 'html5-qrcode'],
            GVS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('gvs-mobile-scanner', 'gvs_mobile', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gvs_ajax_nonce'),
            'strings' => [
                'scanning' => __('Scannen...', 'gordijnen-voorraad'),
                'scan_success' => __('Scan succesvol!', 'gordijnen-voorraad'),
                'scan_error' => __('Fout bij scannen', 'gordijnen-voorraad'),
                'not_found' => __('Rol niet gevonden', 'gordijnen-voorraad'),
                'confirm_delete' => __('Weet u zeker dat u deze rol wilt uitgeven?', 'gordijnen-voorraad'),
                'deleted' => __('Rol uitgegeven', 'gordijnen-voorraad'),
                'error' => __('Er is een fout opgetreden', 'gordijnen-voorraad')
            ]
        ]);
    }
    
    /**
     * Add PWA meta tags
     */
    public function add_pwa_meta() {
        if (!get_query_var('gvs_mobile')) {
            return;
        }
        ?>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#2271b1">
        <link rel="manifest" href="<?php echo GVS_PLUGIN_URL; ?>mobile/manifest.json">
        <?php
    }
}