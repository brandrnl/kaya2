<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add AJAX actions
add_action('wp_ajax_gvs_get_kleuren_by_collectie', 'gvs_ajax_get_kleuren_by_collectie');
add_action('wp_ajax_gvs_add_rollen', 'gvs_ajax_add_rollen');
add_action('wp_ajax_gvs_delete_rol', 'gvs_ajax_delete_rol');
add_action('wp_ajax_gvs_scan_qr', 'gvs_ajax_scan_qr');
add_action('wp_ajax_gvs_search_rollen', 'gvs_ajax_search_rollen');
add_action('wp_ajax_gvs_get_dashboard_stats', 'gvs_ajax_get_dashboard_stats');
add_action('wp_ajax_gvs_print_qr_codes', 'gvs_ajax_print_qr_codes');

/**
 * Get kleuren by collectie
 */
function gvs_ajax_get_kleuren_by_collectie() {
    check_ajax_referer('gvs_ajax_nonce', 'nonce');
    
    $collectie_id = isset($_POST['collectie_id']) ? intval($_POST['collectie_id']) : 0;
    
    if (!$collectie_id) {
        wp_send_json_error(['message' => 'Geen collectie ID opgegeven']);
    }
    
    $kleuren = GVS_Kleur::get_by_collectie($collectie_id);
    
    wp_send_json_success($kleuren);
}

/**
 * Add new rollen
 */
function gvs_ajax_add_rollen() {
    check_ajax_referer('gvs_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Geen rechten']);
    }
    
    $kleur_id = isset($_POST['kleur_id']) ? intval($_POST['kleur_id']) : 0;
    $locatie = isset($_POST['locatie']) ? sanitize_text_field($_POST['locatie']) : '';
    $aantal = isset($_POST['aantal']) ? intval($_POST['aantal']) : 1;
    $meters = isset($_POST['meters']) ? floatval($_POST['meters']) : 0;
    
    if (!$kleur_id || !$locatie || $aantal < 1 || $meters <= 0) {
        wp_send_json_error(['message' => 'Ongeldige invoer']);
    }
    
    // Create rollen
    $created = GVS_Rol::bulk_create($kleur_id, $locatie, $aantal, $meters);
    
    if (empty($created)) {
        wp_send_json_error(['message' => 'Fout bij aanmaken rollen']);
    }
    
    // Prepare response with QR codes
    $rollen_data = [];
    foreach ($created as $rol) {
        $kleur = GVS_Kleur::get_by_id($rol->get_kleur_id());
        $collectie = GVS_Collectie::get_by_id($kleur->get_collectie_id());
        
        $rollen_data[] = [
            'id' => $rol->get_id(),
            'qr_code' => $rol->get_qr_code(),
            'qr_url' => $rol->get_qr_image_url(),
            'collectie' => $collectie->get_naam(),
            'kleur' => $kleur->get_kleur_naam(),
            'meters' => $rol->get_meters(),
            'locatie' => $rol->get_locatie()
        ];
    }
    
    wp_send_json_success([
        'message' => sprintf('%d rollen aangemaakt', count($created)),
        'rollen' => $rollen_data
    ]);
}

/**
 * Delete rol
 */
function gvs_ajax_delete_rol() {
    check_ajax_referer('gvs_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Geen rechten']);
    }
    
    $rol_id = isset($_POST['rol_id']) ? intval($_POST['rol_id']) : 0;
    $notitie = isset($_POST['notitie']) ? sanitize_textarea_field($_POST['notitie']) : '';
    
    if (!$rol_id) {
        wp_send_json_error(['message' => 'Geen rol ID opgegeven']);
    }
    
    $rol = GVS_Rol::get_by_id($rol_id);
    if (!$rol) {
        wp_send_json_error(['message' => 'Rol niet gevonden']);
    }
    
    // Add note to transaction if provided
    if ($notitie) {
        global $wpdb;
        $wpdb->update(
            GVS_Database::get_table_name('transacties'),
            ['notitie' => $notitie],
            ['rol_id' => $rol_id, 'type' => 'uitgaand']
        );
    }
    
    if ($rol->delete()) {
        wp_send_json_success(['message' => 'Rol uitgegeven']);
    } else {
        wp_send_json_error(['message' => 'Fout bij uitgeven rol']);
    }
}

/**
 * Scan QR code
 */
function gvs_ajax_scan_qr() {
    check_ajax_referer('gvs_ajax_nonce', 'nonce');
    
    $qr_code = isset($_POST['qr_code']) ? sanitize_text_field($_POST['qr_code']) : '';
    
    if (!$qr_code) {
        wp_send_json_error(['message' => 'Geen QR code opgegeven']);
    }
    
    $rol_data = GVS_Rol::get_by_qr_code($qr_code);
    
    if (!$rol_data) {
        wp_send_json_error(['message' => 'Rol niet gevonden']);
    }
    
    wp_send_json_success([
        'rol' => [
            'id' => $rol_data->id,
            'qr_code' => $rol_data->qr_code,
            'collectie' => $rol_data->collectie_naam,
            'kleur' => $rol_data->kleur_naam,
            'meters' => $rol_data->meters,
            'locatie' => $rol_data->locatie,
            'created_at' => $rol_data->created_at
        ]
    ]);
}

/**
 * Search rollen
 */
function gvs_ajax_search_rollen() {
    check_ajax_referer('gvs_ajax_nonce', 'nonce');
    
    $filters = [
        'collectie_id' => isset($_POST['collectie_id']) ? intval($_POST['collectie_id']) : 0,
        'kleur_id' => isset($_POST['kleur_id']) ? intval($_POST['kleur_id']) : 0,
        'locatie' => isset($_POST['locatie']) ? sanitize_text_field($_POST['locatie']) : '',
        'search' => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : ''
    ];
    
    // Remove empty filters
    $filters = array_filter($filters);
    
    $rollen = GVS_Rol::get_all($filters);
    
    wp_send_json_success($rollen);
}

/**
 * Get dashboard statistics
 */
function gvs_ajax_get_dashboard_stats() {
    check_ajax_referer('gvs_ajax_nonce', 'nonce');
    
    global $wpdb;
    
    // Basic stats
    $stats = [
        'totaal_collecties' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gvs_collecties"),
        'totaal_kleuren' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gvs_kleuren"),
        'totaal_rollen' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gvs_rollen"),
        'totaal_meters' => $wpdb->get_var("SELECT COALESCE(SUM(meters), 0) FROM {$wpdb->prefix}gvs_rollen"),
        'totaal_locaties' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gvs_locaties WHERE actief = 1")
    ];
    
    // Low stock
    $stats['lage_voorraad'] = count(GVS_Kleur::get_low_stock());
    
    // Recent activity
    $stats['recente_activiteit'] = $wpdb->get_results("
        SELECT t.*, u.display_name as user_name
        FROM {$wpdb->prefix}gvs_transacties t
        LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
        ORDER BY t.created_at DESC
        LIMIT 10
    ");
    
    // Locatie overview
    $stats['locatie_overzicht'] = GVS_Locatie::get_all();
    
    wp_send_json_success($stats);
}

/**
 * Print QR codes
 */
function gvs_ajax_print_qr_codes() {
    // Check nonce
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'gvs_ajax_nonce')) {
        wp_die('Security check failed');
    }
    
    $ids_string = isset($_GET['ids']) ? $_GET['ids'] : '';
    $ids = array_filter(array_map('intval', explode(',', $ids_string)));
    
    error_log('Print QR Codes - IDs received: ' . print_r($ids, true));
    
    if (empty($ids)) {
        wp_die('Geen rollen geselecteerd. Ontvangen: ' . $ids_string);
    }
    
    $rollen = [];
    foreach ($ids as $id) {
        if ($id > 0) {
            $rol = GVS_Rol::get_by_id($id);
            if ($rol) {
                $rollen[] = $rol;
                error_log('Rol found: ' . $rol->get_qr_code());
            } else {
                error_log('Rol not found for ID: ' . $id);
            }
        }
    }
    
    if (empty($rollen)) {
        wp_die('Geen geldige rollen gevonden. IDs geprobeerd: ' . implode(', ', $ids));
    }
    
    error_log('Total rollen to print: ' . count($rollen));
    
    // Generate and output print page
    echo GVS_QR_Generator::generate_bulk_print($rollen);
    exit;
}