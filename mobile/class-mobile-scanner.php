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
        
        // Belangrijk: genereer nonce voor ALLE gebruikers
        add_action('init', [$this, 'setup_nonce_for_visitors']);
    }
    
    /**
     * Setup nonce for all visitors
     */
    public function setup_nonce_for_visitors() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
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
            GVS_VERSION . '.' . time(), // Force refresh tijdens development
            true
        );
        
        // BELANGRIJK: Zorg dat AJAX URL en nonce correct worden doorgegeven
        wp_localize_script('gvs-mobile-scanner', 'gvs_mobile', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gvs_ajax_nonce'),
            'is_logged_in' => is_user_logged_in(),
            'strings' => [
                'scanning' => __('Scannen...', 'gordijnen-voorraad'),
                'scan_success' => __('Scan succesvol!', 'gordijnen-voorraad'),
                'scan_error' => __('Fout bij scannen', 'gordijnen-voorraad'),
                'not_found' => __('Rol niet gevonden', 'gordijnen-voorraad'),
                'confirm_delete' => __('Weet u zeker dat u deze rol wilt uitgeven?', 'gordijnen-voorraad'),
                'deleted' => __('Rol uitgegeven', 'gordijnen-voorraad'),
                'error' => __('Er is een fout opgetreden', 'gordijnen-voorraad'),
                'processing' => __('Bezig...', 'gordijnen-voorraad')
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
        
        <!-- Debug info -->
        <script>
        console.log('GVS Mobile Scanner loaded');
        console.log('AJAX URL:', '<?php echo admin_url('admin-ajax.php'); ?>');
        console.log('User logged in:', <?php echo is_user_logged_in() ? 'true' : 'false'; ?>);
        </script>
        <?php
    }
}