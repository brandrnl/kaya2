<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Load page classes
        $this->load_page_classes();
    }
    
    /**
     * Load page classes
     */
    private function load_page_classes() {
        require_once GVS_PLUGIN_DIR . 'admin/class-dashboard.php';
        require_once GVS_PLUGIN_DIR . 'admin/class-collectie-page.php';
        require_once GVS_PLUGIN_DIR . 'admin/class-locatie-page.php';
        require_once GVS_PLUGIN_DIR . 'admin/class-voorraad-page.php';
        require_once GVS_PLUGIN_DIR . 'admin/class-settings-page.php';
    }
    
    /**
     * Add menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            __('Gordijnen Voorraad', 'gordijnen-voorraad'),
            __('Gordijnen Voorraad', 'gordijnen-voorraad'),
            'manage_options',
            'gvs-dashboard',
            [new GVS_Dashboard(), 'render_page'],
            'dashicons-archive',
            30
        );
        
        // Dashboard (rename first submenu)
        add_submenu_page(
            'gvs-dashboard',
            __('Dashboard', 'gordijnen-voorraad'),
            __('Dashboard', 'gordijnen-voorraad'),
            'manage_options',
            'gvs-dashboard'
        );
        
        // Collecties
        add_submenu_page(
            'gvs-dashboard',
            __('Collecties', 'gordijnen-voorraad'),
            __('Collecties', 'gordijnen-voorraad'),
            'manage_options',
            'gvs-collecties',
            [new GVS_Collectie_Page(), 'render_page']
        );
        
        // Locaties
        add_submenu_page(
            'gvs-dashboard',
            __('Locaties', 'gordijnen-voorraad'),
            __('Locaties', 'gordijnen-voorraad'),
            'manage_options',
            'gvs-locaties',
            [new GVS_Locatie_Page(), 'render_page']
        );
        
        // Voorraad
        add_submenu_page(
            'gvs-dashboard',
            __('Voorraad', 'gordijnen-voorraad'),
            __('Voorraad', 'gordijnen-voorraad'),
            'manage_options',
            'gvs-voorraad',
            [new GVS_Voorraad_Page(), 'render_page']
        );
        
        // Mobile Scanner
        add_submenu_page(
            'gvs-dashboard',
            __('Mobile Scanner', 'gordijnen-voorraad'),
            __('Mobile Scanner', 'gordijnen-voorraad'),
            'manage_options',
            'gvs-mobile-scanner',
            [$this, 'render_mobile_link']
        );
        
        // Settings
        add_submenu_page(
            'gvs-dashboard',
            __('Instellingen', 'gordijnen-voorraad'),
            __('Instellingen', 'gordijnen-voorraad'),
            'manage_options',
            'gvs-settings',
            [new GVS_Settings_Page(), 'render_page']
        );
    }
    
    /**
     * Render mobile scanner link page
     */
    public function render_mobile_link() {
        $mobile_url = home_url('/gvs-mobile/');
        ?>
        <div class="wrap">
            <h1><?php _e('Mobile Scanner', 'gordijnen-voorraad'); ?></h1>
            
            <div class="gvs-mobile-info">
                <p><?php _e('Gebruik de mobile scanner om QR codes te scannen met uw telefoon of tablet.', 'gordijnen-voorraad'); ?></p>
                
                <div class="gvs-mobile-url">
                    <h3><?php _e('Scanner URL:', 'gordijnen-voorraad'); ?></h3>
                    <input type="text" value="<?php echo esc_url($mobile_url); ?>" readonly class="regular-text" id="gvs-mobile-url">
                    <button class="button" onclick="gvsCopyUrl()"><?php _e('Kopieer', 'gordijnen-voorraad'); ?></button>
                </div>
                
                <div class="gvs-qr-code">
                    <h3><?php _e('QR Code voor mobiele toegang:', 'gordijnen-voorraad'); ?></h3>
                    <img src="<?php echo esc_url(GVS_QR_Generator::generate_url($mobile_url, 300)); ?>" alt="Mobile Scanner QR">
                </div>
                
                <p>
                    <a href="<?php echo esc_url($mobile_url); ?>" target="_blank" class="button button-primary">
                        <?php _e('Open Mobile Scanner', 'gordijnen-voorraad'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <script>
        function gvsCopyUrl() {
            var input = document.getElementById('gvs-mobile-url');
            input.select();
            document.execCommand('copy');
            alert('<?php _e('URL gekopieerd!', 'gordijnen-voorraad'); ?>');
        }
        </script>
        
        <style>
        .gvs-mobile-info {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .gvs-mobile-url {
            margin: 20px 0;
        }
        .gvs-mobile-url input {
            width: 400px;
            max-width: 100%;
        }
        .gvs-qr-code {
            margin: 30px 0;
        }
        .gvs-qr-code img {
            border: 1px solid #ddd;
            padding: 10px;
            background: #fff;
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only load on our pages
        if (strpos($hook, 'gvs-') === false && strpos($hook, 'gordijnen-voorraad') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'gvs-admin',
            GVS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            GVS_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'gvs-admin',
            GVS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            GVS_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('gvs-admin', 'gvs_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gvs_ajax_nonce'),
            'strings' => [
                'confirm_delete' => __('Weet u zeker dat u dit wilt verwijderen?', 'gordijnen-voorraad'),
                'loading' => __('Laden...', 'gordijnen-voorraad'),
                'error' => __('Er is een fout opgetreden', 'gordijnen-voorraad')
            ]
        ]);
    }
}