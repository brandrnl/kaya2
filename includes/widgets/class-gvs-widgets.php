<?php
// File: includes/class-dashboard-widgets.php

/**
 * Dashboard Widgets for Gordijnen Voorraad
 */
class GVS_Dashboard_Widgets {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widgets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_gvs_dashboard_quick_add', [$this, 'ajax_quick_add']);
        add_action('wp_ajax_gvs_dashboard_quick_scan', [$this, 'ajax_quick_scan']);
        add_action('wp_ajax_gvs_dashboard_delete_rol', [$this, 'ajax_delete_rol']);
    }
    
    /**
     * Add dashboard widgets
     */
    public function add_dashboard_widgets() {
        // Only for users who can manage options
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Recent Activity Widget
        wp_add_dashboard_widget(
            'gvs_recent_activity',
            __('Recente Activiteit', 'gordijnen-voorraad'),
            [$this, 'widget_recent_activity']
        );
        
        // Location Overview Widget
        wp_add_dashboard_widget(
            'gvs_location_overview',
            __('Locatie Overzicht', 'gordijnen-voorraad'),
            [$this, 'widget_location_overview']
        );
        
        // Quick Actions Widget
        wp_add_dashboard_widget(
            'gvs_quick_actions',
            __('Snelle Acties', 'gordijnen-voorraad'),
            [$this, 'widget_quick_actions']
        );
        
        // Low Stock Widget
        wp_add_dashboard_widget(
            'gvs_low_stock',
            __('Gordijnen Voorraad - Lage Voorraad', 'gordijnen-voorraad'),
            [$this, 'widget_low_stock']
        );
    }
    
    /**
     * Enqueue dashboard scripts
     */
    public function enqueue_dashboard_scripts($hook) {
        if ('index.php' !== $hook) {
            return;
        }
        
        // Add inline CSS
        wp_add_inline_style('dashboard', $this->get_dashboard_styles());
        
        // Add inline JS
        wp_add_inline_script('jquery', $this->get_dashboard_scripts());
    }
    
    /**
     * Widget: Recent Activity
     */
    public function widget_recent_activity() {
        global $wpdb;
        
        $activities = $wpdb->get_results("
            SELECT t.*, u.display_name as user_name
            FROM {$wpdb->prefix}gvs_transacties t
            LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
            ORDER BY t.created_at DESC
            LIMIT 10
        ");
        
        if (empty($activities)) {
            echo '<p>' . __('Geen recente activiteit', 'gordijnen-voorraad') . '</p>';
        } else {
            ?>
            <div class="gvs-activity-list">
                <?php foreach ($activities as $activity): ?>
                    <?php
                    $type_class = $activity->type === 'inkomend' ? 'in' : 'out';
                    $type_label = $activity->type === 'inkomend' ? __('IN', 'gordijnen-voorraad') : __('UIT', 'gordijnen-voorraad');
                    $time_ago = human_time_diff(strtotime($activity->created_at), current_time('timestamp'));
                    ?>
                    <div class="gvs-activity-item">
                        <span class="gvs-badge <?php echo $type_class; ?>"><?php echo $type_label; ?></span>
                        <div class="gvs-activity-info">
                            <strong><?php echo esc_html($activity->qr_code); ?></strong>
                            <span class="gvs-meters"><?php echo number_format($activity->meters, 2, ',', '.'); ?> m</span>
                            <div class="gvs-activity-meta">
                                <?php if ($activity->user_name): ?>
                                    <?php echo esc_html($activity->user_name); ?> • 
                                <?php endif; ?>
                                <?php echo $time_ago . ' ' . __('geleden', 'gordijnen-voorraad'); ?>
                            </div>
                            <?php if ($activity->notitie): ?>
                                <div class="gvs-activity-note"><?php echo esc_html($activity->notitie); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="gvs-dashboard-link">
                <a href="<?php echo admin_url('admin.php?page=gvs-dashboard'); ?>" class="button">
                    <?php _e('Volledig Dashboard', 'gordijnen-voorraad'); ?>
                </a>
            </p>
            <?php
        }
    }
    
    /**
     * Widget: Location Overview
     */
    public function widget_location_overview() {
        $locaties = GVS_Locatie::get_all();
        
        if (empty($locaties)) {
            echo '<p>' . __('Geen locaties gevonden', 'gordijnen-voorraad') . '</p>';
        } else {
            ?>
            <div class="gvs-location-grid">
                <?php foreach ($locaties as $locatie): ?>
                    <?php if ($locatie->actief && $locatie->aantal_rollen > 0): ?>
                        <?php
                        $percentage = min(100, ($locatie->aantal_rollen / 50) * 100); // Assume 50 max capacity
                        $status_class = $percentage > 80 ? 'high' : ($percentage > 50 ? 'medium' : 'low');
                        ?>
                        <div class="gvs-location-card <?php echo $status_class; ?>">
                            <div class="gvs-location-header">
                                <h4><?php echo esc_html($locatie->naam); ?></h4>
                                <span class="gvs-location-count"><?php echo $locatie->aantal_rollen; ?></span>
                            </div>
                            <?php if ($locatie->beschrijving): ?>
                                <div class="gvs-location-desc"><?php echo esc_html($locatie->beschrijving); ?></div>
                            <?php endif; ?>
                            <div class="gvs-location-bar">
                                <div class="gvs-location-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="gvs-location-meters"><?php echo number_format($locatie->totaal_meters, 0, ',', '.'); ?> m</div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <p class="gvs-dashboard-link">
                <a href="<?php echo admin_url('admin.php?page=gvs-locaties'); ?>" class="button">
                    <?php _e('Alle Locaties', 'gordijnen-voorraad'); ?>
                </a>
            </p>
            <?php
        }
    }
    
    /**
     * Widget: Quick Actions
     */
    public function widget_quick_actions() {
        $collecties = GVS_Collectie::get_all();
        $locaties = GVS_Locatie::get_active();
        ?>
        
        <div class="gvs-quick-actions">
            <!-- Tab buttons -->
            <div class="gvs-tabs">
                <button class="gvs-tab active" data-tab="add">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Toevoegen', 'gordijnen-voorraad'); ?>
                </button>
                <button class="gvs-tab" data-tab="scan">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Uitgeven', 'gordijnen-voorraad'); ?>
                </button>
            </div>
            
            <!-- Add tab -->
            <div class="gvs-tab-content" id="tab-add">
                <form id="gvs-quick-add-form">
                    <div class="gvs-form-row">
                        <select id="quick-collectie" name="collectie_id" required>
                            <option value=""><?php _e('Selecteer collectie...', 'gordijnen-voorraad'); ?></option>
                            <?php foreach ($collecties as $collectie): ?>
                                <option value="<?php echo $collectie->id; ?>"><?php echo esc_html($collectie->naam); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="gvs-form-row">
                        <select id="quick-kleur" name="kleur_id" required disabled>
                            <option value=""><?php _e('Eerst collectie selecteren', 'gordijnen-voorraad'); ?></option>
                        </select>
                    </div>
                    
                    <div class="gvs-form-row gvs-form-cols">
                        <select name="locatie" required>
                            <option value=""><?php _e('Locatie...', 'gordijnen-voorraad'); ?></option>
                            <?php foreach ($locaties as $locatie): ?>
                                <option value="<?php echo esc_attr($locatie->naam); ?>">
                                    <?php echo esc_html($locatie->naam); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="meters" placeholder="Meters" min="0.01" step="0.01" 
                               value="<?php echo get_option('gvs_default_meters', '50'); ?>" required>
                    </div>
                    
                    <button type="submit" class="button button-primary gvs-submit-btn">
                        <span class="#"></span>
                        <?php _e('Rol Toevoegen', 'gordijnen-voorraad'); ?>
                    </button>
                </form>
                
                <div id="quick-add-result" class="gvs-result"></div>
            </div>
            
            <!-- Scan tab -->
            <div class="gvs-tab-content" id="tab-scan" style="display:none;">
                <form id="gvs-quick-scan-form">
                    <div class="gvs-form-row">
                        <input type="text" name="qr_code" placeholder="<?php esc_attr_e('QR Code scannen of typen...', 'gordijnen-voorraad'); ?>" required>
                    </div>
                    
                    <button type="submit" class="button button-primary gvs-submit-btn">
                        <span class="#"></span>
                        <?php _e('Zoeken', 'gordijnen-voorraad'); ?>
                    </button>
                </form>
                
                <div id="quick-scan-result" class="gvs-result"></div>
            </div>
            
            <!-- Quick links -->
            <div class="gvs-quick-links">
                <a href="<?php echo admin_url('admin.php?page=gvs-voorraad'); ?>" class="gvs-link">
                    <span class="#"></span>
                    <?php _e('Voorraad', 'gordijnen-voorraad'); ?>
                </a>
                <a href="<?php echo home_url('/gvs-mobile/'); ?>" target="_blank" class="gvs-link">
                    <span class="#"></span>
                    <?php _e('Scanner', 'gordijnen-voorraad'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Widget: Low Stock
     */
    public function widget_low_stock() {
        $low_stock_items = GVS_Kleur::get_low_stock();
        
        if (empty($low_stock_items)) {
            echo '<div class="gvs-all-good">';
            echo '<span class="dashicons dashicons-yes-alt"></span>';
            echo '<p>' . __('Alle voorraden zijn op peil!', 'gordijnen-voorraad') . '</p>';
            echo '</div>';
        } else {
            ?>
            <div class="gvs-low-stock-list">
                <?php foreach (array_slice($low_stock_items, 0, 5) as $item): ?>
                    <?php
                    $rollen_shortage = max(0, $item->min_voorraad_rollen - $item->huidige_rollen);
                    $meters_shortage = max(0, $item->min_voorraad_meters - $item->huidige_meters);
                    ?>
                    <div class="gvs-low-stock-item">
                        <div class="gvs-item-header">
                            <strong><?php echo esc_html($item->collectie_naam); ?></strong>
                            <span class="gvs-kleur"><?php echo esc_html($item->kleur_naam); ?></span>
                        </div>
                        <div class="gvs-shortage-info">
                            <?php if ($rollen_shortage > 0): ?>
                                <span class="gvs-shortage">
                                    <span class="dashicons dashicons-warning"></span>
                                    -<?php echo $rollen_shortage; ?> rollen
                                </span>
                            <?php endif; ?>
                            <?php if ($meters_shortage > 0): ?>
                                <span class="gvs-shortage">
                                    <span class="dashicons dashicons-warning"></span>
                                    -<?php echo number_format($meters_shortage, 0, ',', '.'); ?> m
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="gvs-current-stock">
                            Huidig: <?php echo $item->huidige_rollen; ?> rollen • 
                            <?php echo number_format($item->huidige_meters, 0, ',', '.'); ?> m
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (count($low_stock_items) > 5): ?>
                    <p class="gvs-more-items">
                        <?php echo sprintf(__('... en nog %d andere items', 'gordijnen-voorraad'), count($low_stock_items) - 5); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <p class="gvs-dashboard-link">
                <a href="<?php echo admin_url('admin.php?page=gvs-dashboard'); ?>" class="button button-secondary">
                    <?php _e('Alle Waarschuwingen', 'gordijnen-voorraad'); ?>
                </a>
            </p>
            <?php
        }
    }
    
    /**
     * AJAX: Quick Add
     */
    public function ajax_quick_add() {
        check_ajax_referer('gvs_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen rechten', 'gordijnen-voorraad')]);
        }
        
        $kleur_id = intval($_POST['kleur_id']);
        $locatie = sanitize_text_field($_POST['locatie']);
        $meters = floatval($_POST['meters']);
        
        if (!$kleur_id || !$locatie || $meters <= 0) {
            wp_send_json_error(['message' => __('Ongeldige invoer', 'gordijnen-voorraad')]);
        }
        
        // Create rol
        $rol = new GVS_Rol();
        $rol->set_kleur_id($kleur_id);
        $rol->set_locatie($locatie);
        $rol->set_meters($meters);
        
        if ($rol->save()) {
            $kleur = GVS_Kleur::get_by_id($kleur_id);
            $collectie = GVS_Collectie::get_by_id($kleur->get_collectie_id());
            
            $html = '<div class="gvs-success-box">';
            $html .= '<span class="dashicons dashicons-yes"></span>';
            $html .= '<div class="gvs-success-info">';
            $html .= '<strong>' . __('Rol toegevoegd!', 'gordijnen-voorraad') . '</strong><br>';
            $html .= 'QR: <code>' . esc_html($rol->get_qr_code()) . '</code><br>';
            $html .= esc_html($collectie->get_naam()) . ' - ' . esc_html($kleur->get_kleur_naam());
            $html .= '</div>';
            $html .= '<a href="' . admin_url('admin-ajax.php?action=gvs_print_qr_codes&ids=' . $rol->get_id() . '&nonce=' . wp_create_nonce('gvs_ajax_nonce')) . '" target="_blank" class="button button-small">' . __('Print', 'gordijnen-voorraad') . '</a>';
            $html .= '</div>';
            
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => __('Fout bij toevoegen', 'gordijnen-voorraad')]);
        }
    }
    
    /**
     * AJAX: Quick Scan
     */
    public function ajax_quick_scan() {
        check_ajax_referer('gvs_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen rechten', 'gordijnen-voorraad')]);
        }
        
        $qr_code = sanitize_text_field($_POST['qr_code']);
        
        if (!$qr_code) {
            wp_send_json_error(['message' => __('Geen QR code opgegeven', 'gordijnen-voorraad')]);
        }
        
        $rol_data = GVS_Rol::get_by_qr_code($qr_code);
        
        if (!$rol_data) {
            wp_send_json_error(['message' => __('Rol niet gevonden', 'gordijnen-voorraad')]);
        }
        
        $html = '<div class="gvs-rol-found">';
        $html .= '<h4>' . __('Rol Gevonden', 'gordijnen-voorraad') . '</h4>';
        $html .= '<div class="gvs-rol-details">';
        $html .= '<div class="gvs-detail"><strong>' . __('Collectie:', 'gordijnen-voorraad') . '</strong> ' . esc_html($rol_data->collectie_naam) . '</div>';
        $html .= '<div class="gvs-detail"><strong>' . __('Kleur:', 'gordijnen-voorraad') . '</strong> ' . esc_html($rol_data->kleur_naam) . '</div>';
        $html .= '<div class="gvs-detail"><strong>' . __('Meters:', 'gordijnen-voorraad') . '</strong> ' . number_format($rol_data->meters, 2, ',', '.') . ' m</div>';
        $html .= '<div class="gvs-detail"><strong>' . __('Locatie:', 'gordijnen-voorraad') . '</strong> ' . esc_html($rol_data->locatie) . '</div>';
        $html .= '</div>';
        $html .= '<div class="gvs-rol-actions">';
        $html .= '<button type="button" class="button button-primary gvs-delete-rol" data-rol-id="' . esc_attr($rol_data->id) . '">';
        $html .= '<span class="dashicons dashicons-arrow-right-alt"></span> ' . __('Uitgeven', 'gordijnen-voorraad');
        $html .= '</button>';
        $html .= '<button type="button" class="button gvs-cancel">' . __('Annuleer', 'gordijnen-voorraad') . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * AJAX: Delete Rol
     */
    public function ajax_delete_rol() {
        check_ajax_referer('gvs_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Geen rechten', 'gordijnen-voorraad')]);
        }
        
        $rol_id = intval($_POST['rol_id']);
        
        if (!$rol_id) {
            wp_send_json_error(['message' => __('Geen rol ID', 'gordijnen-voorraad')]);
        }
        
        $rol = GVS_Rol::get_by_id($rol_id);
        if (!$rol) {
            wp_send_json_error(['message' => __('Rol niet gevonden', 'gordijnen-voorraad')]);
        }
        
        if ($rol->delete()) {
            $html = '<div class="gvs-success-box">';
            $html .= '<span class="dashicons dashicons-yes"></span>';
            $html .= '<strong>' . __('Rol uitgegeven!', 'gordijnen-voorraad') . '</strong>';
            $html .= '</div>';
            
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => __('Fout bij uitgeven', 'gordijnen-voorraad')]);
        }
    }
    
    /**
     * Get dashboard styles
     */
    private function get_dashboard_styles() {
        return '
        /* GVS Dashboard Widgets */
        #gvs_recent_activity .inside,
        #gvs_location_overview .inside,
        #gvs_quick_actions .inside,
        #gvs_low_stock .inside {
            padding: 15px;
            margin: 0;
        }
        
        /* Activity List */
        .gvs-activity-list {
            margin: 0 -12px;
        }
        .gvs-activity-item {
            display: flex;
            align-items: flex-start;
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f1;
            transition: background 0.1s;
        }
        .gvs-activity-item:hover {
            background: #f6f7f7;
        }
        .gvs-activity-item:last-child {
            border-bottom: none;
        }
        .gvs-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 12px;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .gvs-badge.in {
            background: #d1fae5;
            color: #065f46;
        }
        .gvs-badge.out {
            background: #fee2e2;
            color: #991b1b;
        }
        .gvs-activity-info {
            flex: 1;
        }
        .gvs-meters {
            font-weight: 600;
            color: #000;
            margin-left: 8px;
        }
        .gvs-activity-meta {
            font-size: 12px;
            color: #646970;
            margin-top: 2px;
        }
        .gvs-activity-note {
            font-size: 12px;
            color: #646970;
            font-style: italic;
            margin-top: 4px;
        }
        
        /* Location Grid */
        .gvs-location-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
            margin: 0 12px 12px;
        }
        .gvs-location-card {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: 12px;
            text-align: center;
            transition: all 0.2s;
        }
        .gvs-location-card:hover {
            border-color: #ccc;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .gvs-location-header h4 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 600;
        }
        .gvs-location-count {
            display: block;
            font-size: 24px;
            font-weight: 600;
            color: #000;
            margin-bottom: 4px;
        }
        .gvs-location-desc {
            font-size: 11px;
            color: #646970;
            margin-bottom: 8px;
        }
        .gvs-location-bar {
            height: 6px;
            background: #dcdcde;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .gvs-location-fill {
            height: 100%;
            background: #000;
            transition: width 0.3s;
        }
        .gvs-location-card.high .gvs-location-fill {
            background: #d63638;
        }
        .gvs-location-card.medium .gvs-location-fill {
            background: #dba617;
        }
        .gvs-location-meters {
            font-size: 12px;
            color: #646970;
        }
        
        /* Quick Actions */
        .gvs-quick-actions {
            margin: 0 12px;
        }
        .gvs-tabs {
            display: flex;
            margin-bottom: 16px;
            border-bottom: 2px solid #dcdcde;
        }
        .gvs-tab {
            flex: 1;
            padding: 8px 12px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #646970;
        }
        .gvs-tab:hover {
            color: #000;
        }
        .gvs-tab.active {
            color: #000;
            border-bottom-color: #000;
            font-weight: 600;
        }
        .gvs-tab .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        .gvs-form-row {
            margin-bottom: 12px;
        }
        .gvs-form-row select,
        .gvs-form-row input {
            width: 100%;
            margin: 0;
        }
        .gvs-form-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .gvs-submit-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .gvs-submit-btn .dashicons {
            margin: 0;
        }
        .gvs-result {
            margin-top: 16px;
        }
        .gvs-success-box {
            background: #d1fae5;
            border: 1px solid #10b981;
            border-radius: 4px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .gvs-success-box .dashicons {
            color: #065f46;
            font-size: 24px;
        }
        .gvs-success-info {
            flex: 1;
        }
        .gvs-success-info code {
            background: #065f46;
            color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        .gvs-rol-found {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: 16px;
        }
        .gvs-rol-found h4 {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 600;
        }
        .gvs-rol-details {
            margin-bottom: 16px;
        }
        .gvs-detail {
            margin-bottom: 6px;
            font-size: 13px;
        }
        .gvs-rol-actions {
            display: flex;
            gap: 8px;
        }
        .gvs-quick-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #dcdcde;
        }
        .gvs-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            text-decoration: none;
            color: #000;
            transition: all 0.2s;
        }
        .gvs-link:hover {
            background: #000;
            color: #fff !important;
            border-color: #000;
        }
        
        /* Low Stock */
        .gvs-low-stock-list {
            margin: 0 -12px;
        }
        .gvs-low-stock-item {
            padding: 12px;
            border-bottom: 1px solid #f0f0f1;
        }
        .gvs-low-stock-item:last-child {
            border-bottom: none;
        }
        .gvs-item-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        .gvs-kleur {
            color: #646970;
            font-size: 13px;
        }
        .gvs-shortage-info {
            display: flex;
            gap: 12px;
            margin-bottom: 4px;
        }
        .gvs-shortage {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #d63638;
            font-weight: 600;
            font-size: 13px;
        }
        .gvs-shortage .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .gvs-current-stock {
            font-size: 12px;
            color: #646970;
        }
        .gvs-all-good {
            text-align: center;
            padding: 32px 12px;
        }
        .gvs-all-good .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #00a32a;
            margin-bottom: 8px;
        }
        .gvs-all-good p {
            margin: 0;
            font-size: 14px;
            color: #00a32a;
            font-weight: 600;
        }
        .gvs-more-items {
            text-align: center;
            color: #646970;
            font-size: 12px;
            font-style: italic;
            margin: 12px;
        }
        
        /* Common */
        .gvs-dashboard-link {
            text-align: center;
            margin: 12px;
        }
        .gvs-dashboard-link .button {
            min-width: 120px;
        }
        ';
    }
    
    /**
     * Get dashboard scripts
     */
    private function get_dashboard_scripts() {
        $nonce = wp_create_nonce('gvs_dashboard_nonce');
        $ajax_nonce = wp_create_nonce('gvs_ajax_nonce');
        
        return "
        jQuery(document).ready(function($) {
            // Tab switching
            $('.gvs-tab').on('click', function() {
                var tab = $(this).data('tab');
                $(this).siblings().removeClass('active');
                $(this).addClass('active');
                $('.gvs-tab-content').hide();
                $('#tab-' + tab).show();
            });
            
            // Collectie change
            $('#quick-collectie').on('change', function() {
                var collectieId = $(this).val();
                var \$kleurSelect = $('#quick-kleur');
                
                if (collectieId) {
                    $.post(ajaxurl, {
                        action: 'gvs_get_kleuren_by_collectie',
                        collectie_id: collectieId,
                        nonce: '$ajax_nonce'
                    }, function(response) {
                        if (response.success) {
                            var options = '<option value=\"\">" . __('Selecteer kleur...', 'gordijnen-voorraad') . "</option>';
                            response.data.forEach(function(kleur) {
                                options += '<option value=\"' + kleur.id + '\">' + kleur.kleur_naam + '</option>';
                            });
                            \$kleurSelect.html(options).prop('disabled', false);
                        }
                    });
                } else {
                    \$kleurSelect.html('<option value=\"\">" . __('Eerst collectie selecteren', 'gordijnen-voorraad') . "</option>').prop('disabled', true);
                }
            });
            
            // Quick add form
            $('#gvs-quick-add-form').on('submit', function(e) {
                e.preventDefault();
                
                var \$form = $(this);
                var \$button = \$form.find('.gvs-submit-btn');
                var \$result = $('#quick-add-result');
                
                \$button.prop('disabled', true).text('" . __('Bezig...', 'gordijnen-voorraad') . "');
                
                $.post(ajaxurl, \$form.serialize() + '&action=gvs_dashboard_quick_add&nonce=$nonce', function(response) {
                    if (response.success) {
                        \$result.html(response.data.html);
                        \$form[0].reset();
                        $('#quick-kleur').html('<option value=\"\">" . __('Eerst collectie selecteren', 'gordijnen-voorraad') . "</option>').prop('disabled', true);
                        
                        setTimeout(function() {
                            \$result.fadeOut(function() {
                                \$result.empty().show();
                            });
                        }, 5000);
                    } else {
                        alert(response.data.message);
                    }
                }).always(function() {
                    \$button.prop('disabled', false).html('<span class=\"#\"></span> " . __('Rol Toevoegen', 'gordijnen-voorraad') . "');
                });
            });
            
            // Quick scan form
            $('#gvs-quick-scan-form').on('submit', function(e) {
                e.preventDefault();
                
                var \$form = $(this);
                var \$button = \$form.find('.gvs-submit-btn');
                var \$result = $('#quick-scan-result');
                
                \$button.prop('disabled', true).text('" . __('Zoeken...', 'gordijnen-voorraad') . "');
                
                $.post(ajaxurl, \$form.serialize() + '&action=gvs_dashboard_quick_scan&nonce=$nonce', function(response) {
                    if (response.success) {
                        \$result.html(response.data.html);
                    } else {
                        alert(response.data.message);
                        \$result.empty();
                    }
                }).always(function() {
                    \$button.prop('disabled', false).html('<span class=\"dashicons dashicons-search\"></span> " . __('Zoeken', 'gordijnen-voorraad') . "');
                });
            });
            
            // Delete rol
            $(document).on('click', '.gvs-delete-rol', function() {
                if (!confirm('" . __('Weet u zeker dat u deze rol wilt uitgeven?', 'gordijnen-voorraad') . "')) {
                    return;
                }
                
                var \$button = $(this);
                var rolId = \$button.data('rol-id');
                
                \$button.prop('disabled', true).text('" . __('Bezig...', 'gordijnen-voorraad') . "');
                
                $.post(ajaxurl, {
                    action: 'gvs_dashboard_delete_rol',
                    rol_id: rolId,
                    nonce: '$nonce'
                }, function(response) {
                    if (response.success) {
                        $('#quick-scan-result').html(response.data.html);
                        $('#gvs-quick-scan-form')[0].reset();
                        
                        setTimeout(function() {
                            $('#quick-scan-result').fadeOut(function() {
                                $(this).empty().show();
                            });
                        }, 3000);
                    } else {
                        alert(response.data.message);
                    }
                });
            });
            
            // Cancel button
            $(document).on('click', '.gvs-cancel', function() {
                $('#quick-scan-result').empty();
                $('#gvs-quick-scan-form')[0].reset();
            });
        });
        ";
    }
}

// Initialize dashboard widgets
new GVS_Dashboard_Widgets();