<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Settings_Page {
    
    /**
     * Render page
     */
    public function render_page() {
        // Debug: Log what's being submitted
        if (!empty($_POST)) {
            error_log('GVS Settings POST data: ' . print_r($_POST, true));
        }
        
        // Handle form submission
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        // Handle cleanup action
        if (isset($_POST['cleanup_action']) && $_POST['cleanup_action'] === 'cleanup') {
            error_log('GVS: Cleanup action triggered');
            $this->cleanup_old_transactions();
        }
        
        // Handle export
        if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
            $this->export_csv();
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Gordijnen Voorraad Instellingen', 'gordijnen-voorraad'); ?></h1>
            
            <?php $this->show_message(); ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('gvs_settings'); ?>
                
                <h2><?php _e('Algemene Instellingen', 'gordijnen-voorraad'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gvs_company_name"><?php _e('Bedrijfsnaam', 'gordijnen-voorraad'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gvs_company_name" name="gvs_company_name" 
                                   value="<?php echo esc_attr(get_option('gvs_company_name', '')); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Wordt gebruikt op QR labels en rapporten', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gvs_default_meters"><?php _e('Standaard meters per rol', 'gordijnen-voorraad'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="gvs_default_meters" name="gvs_default_meters" 
                                   value="<?php echo esc_attr(get_option('gvs_default_meters', '50')); ?>" 
                                   min="1" step="0.01" class="small-text">
                            <p class="description"><?php _e('Standaard waarde bij het toevoegen van nieuwe rollen', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('QR Code Instellingen', 'gordijnen-voorraad'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gvs_qr_prefix"><?php _e('QR Code Prefix', 'gordijnen-voorraad'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="gvs_qr_prefix" name="gvs_qr_prefix" 
                                   value="<?php echo esc_attr(get_option('gvs_qr_prefix', 'GVS')); ?>" 
                                   class="small-text" pattern="[A-Z]+" maxlength="5">
                            <p class="description"><?php _e('Prefix voor alle QR codes (alleen hoofdletters, max 5 karakters)', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gvs_qr_size"><?php _e('QR Code grootte', 'gordijnen-voorraad'); ?></label>
                        </th>
                        <td>
                            <select id="gvs_qr_size" name="gvs_qr_size">
                                <?php
                                $current_size = get_option('gvs_qr_size', '300');
                                $sizes = ['200' => '200x200', '300' => '300x300', '400' => '400x400', '500' => '500x500'];
                                foreach ($sizes as $value => $label) {
                                    echo '<option value="' . $value . '" ' . selected($current_size, $value, false) . '>' . $label . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Grootte van gegenereerde QR codes in pixels', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Voorraad Waarschuwingen', 'gordijnen-voorraad'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gvs_enable_warnings"><?php _e('Waarschuwingen inschakelen', 'gordijnen-voorraad'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="gvs_enable_warnings" name="gvs_enable_warnings" value="1" 
                                       <?php checked(get_option('gvs_enable_warnings', '1'), '1'); ?>>
                                <?php _e('Toon waarschuwingen voor lage voorraad op dashboard', 'gordijnen-voorraad'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gvs_warning_email"><?php _e('E-mail voor waarschuwingen', 'gordijnen-voorraad'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="gvs_warning_email" name="gvs_warning_email" 
                                   value="<?php echo esc_attr(get_option('gvs_warning_email', get_option('admin_email'))); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('E-mailadres voor lage voorraad notificaties (nog niet geïmplementeerd)', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Data Management', 'gordijnen-voorraad'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Export Data', 'gordijnen-voorraad'); ?></th>
                        <td>
                            <a href="<?php echo wp_nonce_url(add_query_arg('action', 'export_csv'), 'gvs_export'); ?>" 
                               class="button">
                                <?php _e('Export Voorraad naar CSV', 'gordijnen-voorraad'); ?>
                            </a>
                            <p class="description"><?php _e('Download alle voorraadgegevens als CSV bestand', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Database Onderhoud', 'gordijnen-voorraad'); ?></th>
                        <td>
                            <div style="margin-bottom: 10px;">
                                <select id="cleanup_period" name="cleanup_period_value" style="margin-right: 10px;">
                                    <option value="all"><?php _e('Alle transacties', 'gordijnen-voorraad'); ?></option>
                                    <option value="1_week"><?php _e('Ouder dan 1 week', 'gordijnen-voorraad'); ?></option>
                                    <option value="1_month"><?php _e('Ouder dan 1 maand', 'gordijnen-voorraad'); ?></option>
                                    <option value="3_months"><?php _e('Ouder dan 3 maanden', 'gordijnen-voorraad'); ?></option>
                                    <option value="6_months" selected><?php _e('Ouder dan 6 maanden', 'gordijnen-voorraad'); ?></option>
                                    <option value="1_year"><?php _e('Ouder dan 1 jaar', 'gordijnen-voorraad'); ?></option>
                                </select>
                                <button type="submit" name="cleanup_action" value="cleanup" class="button" 
                                        onclick="return confirm('Weet u zeker dat u de geselecteerde transacties wilt verwijderen?');">
                                    <?php _e('Transacties Opschonen', 'gordijnen-voorraad'); ?>
                                </button>
                            </div>
                            <p class="description"><?php _e('Verwijder transacties volgens de geselecteerde periode', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="submit" class="button button-primary">
                        <?php _e('Instellingen Opslaan', 'gordijnen-voorraad'); ?>
                    </button>
                </p>
            </form>
            
            <hr>
            
            <h2><?php _e('Systeem Informatie', 'gordijnen-voorraad'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Plugin Versie', 'gordijnen-voorraad'); ?></th>
                    <td><?php echo GVS_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php _e('Database Versie', 'gordijnen-voorraad'); ?></th>
                    <td><?php echo get_option('gvs_db_version', 'Onbekend'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Totaal Collecties', 'gordijnen-voorraad'); ?></th>
                    <td><?php echo $this->get_count('collecties'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Totaal Kleuren', 'gordijnen-voorraad'); ?></th>
                    <td><?php echo $this->get_count('kleuren'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Totaal Rollen', 'gordijnen-voorraad'); ?></th>
                    <td><?php echo $this->get_count('rollen'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Totaal Transacties', 'gordijnen-voorraad'); ?></th>
                    <td><?php echo $this->get_count('transacties'); ?></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        // Check nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gvs_settings')) {
            return;
        }
        
        // Save settings
        update_option('gvs_company_name', sanitize_text_field($_POST['gvs_company_name']));
        update_option('gvs_default_meters', floatval($_POST['gvs_default_meters']));
        update_option('gvs_qr_prefix', sanitize_text_field($_POST['gvs_qr_prefix']));
        update_option('gvs_qr_size', intval($_POST['gvs_qr_size']));
        update_option('gvs_enable_warnings', isset($_POST['gvs_enable_warnings']) ? '1' : '0');
        update_option('gvs_warning_email', sanitize_email($_POST['gvs_warning_email']));
        
        // Redirect with success message
        wp_redirect(add_query_arg([
            'page' => 'gvs-settings',
            'message' => 'saved'
        ], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Show message
     */
    private function show_message() {
        if (!isset($_GET['message'])) {
            return;
        }
        
        $messages = [
            'saved' => __('Instellingen opgeslagen', 'gordijnen-voorraad'),
            'cleanup' => __('Transacties opgeschoond', 'gordijnen-voorraad'),
        ];
        
        $message = isset($messages[$_GET['message']]) ? $messages[$_GET['message']] : '';
        
        if ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }
    
    /**
     * Get count for table
     */
    private function get_count($table) {
        global $wpdb;
        $table_name = GVS_Database::get_table_name($table);
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
    
    /**
     * Cleanup old transactions
     */
    private function cleanup_old_transactions() {
        // Check nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gvs_settings')) {
            return;
        }
        
        global $wpdb;
        $table = GVS_Database::get_table_name('transacties');
        
        $period = isset($_POST['cleanup_period_value']) ? $_POST['cleanup_period_value'] : '6_months';
        
        // Determine date limit based on selected period
        switch ($period) {
            case 'all':
                // Delete all transactions with TRUNCATE for better performance
                $deleted = $wpdb->query("TRUNCATE TABLE $table");
                
                // If TRUNCATE fails (due to foreign keys), fall back to DELETE
                if ($deleted === false) {
                    $deleted = $wpdb->query("DELETE FROM $table");
                }
                
                // Debug: log the exact query and result
                error_log('GVS Cleanup ALL - Table: ' . $table);
                error_log('GVS Cleanup ALL - Deleted rows: ' . $deleted);
                error_log('GVS Cleanup ALL - Last error: ' . $wpdb->last_error);
                
                // Force count check
                $remaining = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                error_log('GVS Cleanup ALL - Remaining rows: ' . $remaining);
                break;
            case '1_week':
                $date_limit = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case '1_month':
                $date_limit = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
            case '3_months':
                $date_limit = date('Y-m-d H:i:s', strtotime('-3 months'));
                break;
            case '6_months':
                $date_limit = date('Y-m-d H:i:s', strtotime('-6 months'));
                break;
            case '1_year':
                $date_limit = date('Y-m-d H:i:s', strtotime('-1 year'));
                break;
            default:
                $date_limit = date('Y-m-d H:i:s', strtotime('-6 months'));
        }
        
        // Delete transactions based on date limit (unless 'all' was selected)
        if ($period !== 'all') {
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table WHERE created_at < %s",
                $date_limit
            ));
        }
        
        // Log the cleanup action
        error_log(sprintf('GVS Database cleanup: Deleted %d transactions (%s)', $deleted, $period));
        
        wp_redirect(add_query_arg([
            'page' => 'gvs-settings',
            'message' => 'cleanup'
        ], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Export data to CSV
     */
    private function export_csv() {
        // Check nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'gvs_export')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        // Get all rollen with details
        $results = $wpdb->get_results("
            SELECT r.qr_code, c.naam as collectie, k.kleur_naam as kleur, 
                   r.meters, r.locatie, r.created_at
            FROM {$wpdb->prefix}gvs_rollen r
            JOIN {$wpdb->prefix}gvs_kleuren k ON r.kleur_id = k.id
            JOIN {$wpdb->prefix}gvs_collecties c ON k.collectie_id = c.id
            ORDER BY c.naam, k.kleur_naam
        ", ARRAY_A);
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="gordijnen-voorraad-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, ['QR Code', 'Collectie', 'Kleur', 'Meters', 'Locatie', 'Datum'], ';');
        
        // Add data
        foreach ($results as $row) {
            fputcsv($output, [
                $row['qr_code'],
                $row['collectie'],
                $row['kleur'],
                $row['meters'],
                $row['locatie'],
                date('Y-m-d', strtotime($row['created_at']))
            ], ';');
        }
        
        fclose($output);
        exit;
    }
}