<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Dashboard {
    
    /**
     * Render dashboard page
     */
    public function render_page() {
        ?>
        <div class="wrap gvs-dashboard">
            <h1 style="margin-bottom: 40px;"><?php _e('Gordijnen Voorraad Dashboard', 'gordijnen-voorraad'); ?></h1>
            
            <div id="gvs-dashboard-loading" class="notice notice-info">
                <p><?php _e('Dashboard wordt geladen...', 'gordijnen-voorraad'); ?></p>
            </div>
            
            <div id="gvs-dashboard-content" style="display: none;">
                <!-- Statistics -->
                <div class="gvs-stats-grid">
                    <div class="gvs-stat-box">
                        <h3><?php _e('Collecties', 'gordijnen-voorraad'); ?></h3>
                        <div class="gvs-stat-number" id="stat-collecties">-</div>
                    </div>
                    
                    <div class="gvs-stat-box">
                        <h3><?php _e('Kleuren', 'gordijnen-voorraad'); ?></h3>
                        <div class="gvs-stat-number" id="stat-kleuren">-</div>
                    </div>
                    
                    <div class="gvs-stat-box">
                        <h3><?php _e('Rollen', 'gordijnen-voorraad'); ?></h3>
                        <div class="gvs-stat-number" id="stat-rollen">-</div>
                    </div>
                    
                    <div class="gvs-stat-box">
                        <h3><?php _e('Locaties', 'gordijnen-voorraad'); ?></h3>
                        <div class="gvs-stat-number" id="stat-locaties">-</div>
                    </div>
                    
                    <div class="gvs-stat-box warning">
                        <h3><?php _e('Lage Voorraad', 'gordijnen-voorraad'); ?></h3>
                        <div class="gvs-stat-number" id="stat-lage-voorraad">-</div>
                    </div>
                </div>
                
                <div class="gvs-dashboard-grid">
                    <!-- Low Stock Warnings -->
                    <div class="gvs-dashboard-section">
                        <h2><?php _e('Lage Voorraad Waarschuwingen', 'gordijnen-voorraad'); ?></h2>
                        <div id="gvs-low-stock-list" class="gvs-list">
                            <p><?php _e('Laden...', 'gordijnen-voorraad'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Location Overview -->
                    <div class="gvs-dashboard-section">
                        <h2><?php _e('Locatie Overzicht', 'gordijnen-voorraad'); ?></h2>
                        <div id="gvs-location-overview" class="gvs-list">
                            <p><?php _e('Laden...', 'gordijnen-voorraad'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="gvs-dashboard-section full-width">
                    <h2><?php _e('Recente Activiteit', 'gordijnen-voorraad'); ?></h2>
                    <div id="gvs-recent-activity" class="gvs-list">
                        <p><?php _e('Laden...', 'gordijnen-voorraad'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load dashboard data
            $.ajax({
                url: gvs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gvs_get_dashboard_stats',
                    nonce: gvs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateDashboard(response.data);
                        $('#gvs-dashboard-loading').hide();
                        $('#gvs-dashboard-content').show();
                    } else {
                        $('#gvs-dashboard-loading').html('<p class="error">' + gvs_ajax.strings.error + '</p>');
                    }
                },
                error: function() {
                    $('#gvs-dashboard-loading').html('<p class="error">' + gvs_ajax.strings.error + '</p>');
                }
            });
            
            function updateDashboard(data) {
                // Update statistics (skip totaal_meters)
                $('#stat-collecties').text(data.totaal_collecties);
                $('#stat-kleuren').text(data.totaal_kleuren);
                $('#stat-rollen').text(data.totaal_rollen);
                $('#stat-locaties').text(data.totaal_locaties);
                $('#stat-lage-voorraad').text(data.lage_voorraad);
                
                // Update low stock list
                updateLowStock(data);
                
                // Update location overview
                updateLocationOverview(data.locatie_overzicht);
                
                // Update recent activity
                updateRecentActivity(data.recente_activiteit);
            }
            
            function updateLowStock(data) {
                var html = '';
                
                if (data.lage_voorraad === 0) {
                    html = '<p><?php _e('Geen lage voorraad waarschuwingen', 'gordijnen-voorraad'); ?></p>';
                } else {
                    // Fetch low stock items
                    $.ajax({
                        url: gvs_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'gvs_search_rollen',
                            nonce: gvs_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                var lowStockKleuren = {};
                                
                                // Group by kleur
                                response.data.forEach(function(rol) {
                                    var key = rol.collectie_naam + ' - ' + rol.kleur_naam;
                                    if (!lowStockKleuren[key]) {
                                        lowStockKleuren[key] = {
                                            count: 0,
                                            meters: 0
                                        };
                                    }
                                    lowStockKleuren[key].count++;
                                    lowStockKleuren[key].meters += parseFloat(rol.meters);
                                });
                                
                                html = '<table class="wp-list-table widefat fixed striped">';
                                html += '<thead><tr>';
                                html += '<th><?php _e('Collectie - Kleur', 'gordijnen-voorraad'); ?></th>';
                                html += '<th><?php _e('Rollen', 'gordijnen-voorraad'); ?></th>';
                                html += '<th><?php _e('Meters', 'gordijnen-voorraad'); ?></th>';
                                html += '</tr></thead><tbody>';
                                
                                for (var key in lowStockKleuren) {
                                    html += '<tr>';
                                    html += '<td>' + key + '</td>';
                                    html += '<td>' + lowStockKleuren[key].count + '</td>';
                                    html += '<td>' + lowStockKleuren[key].meters.toFixed(2) + ' m</td>';
                                    html += '</tr>';
                                }
                                
                                html += '</tbody></table>';
                                $('#gvs-low-stock-list').html(html);
                            }
                        }
                    });
                }
                
                $('#gvs-low-stock-list').html(html);
            }
            
            function updateLocationOverview(locaties) {
                var html = '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr>';
                html += '<th><?php _e('Locatie', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('Beschrijving', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('Rollen', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('Meters', 'gordijnen-voorraad'); ?></th>';
                html += '</tr></thead><tbody>';
                
                locaties.forEach(function(loc) {
                    html += '<tr>';
                    html += '<td><strong>' + loc.naam + '</strong></td>';
                    html += '<td>' + (loc.beschrijving || '-') + '</td>';
                    html += '<td>' + loc.aantal_rollen + '</td>';
                    html += '<td>' + parseFloat(loc.totaal_meters).toFixed(2) + ' m</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#gvs-location-overview').html(html);
            }
            
            function updateRecentActivity(activities) {
                var html = '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr>';
                html += '<th><?php _e('Datum/Tijd', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('Type', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('QR Code', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('Meters', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('Gebruiker', 'gordijnen-voorraad'); ?></th>';
                html += '<th><?php _e('Notitie', 'gordijnen-voorraad'); ?></th>';
                html += '</tr></thead><tbody>';
                
                if (activities.length === 0) {
                    html += '<tr><td colspan="6"><?php _e('Geen recente activiteit', 'gordijnen-voorraad'); ?></td></tr>';
                } else {
                    activities.forEach(function(act) {
                        var typeLabel = act.type === 'inkomend' ? 
                            '<span class="gvs-badge in"><?php _e('Inkomend', 'gordijnen-voorraad'); ?></span>' : 
                            '<span class="gvs-badge out"><?php _e('Uitgaand', 'gordijnen-voorraad'); ?></span>';
                        
                        html += '<tr>';
                        html += '<td>' + new Date(act.created_at).toLocaleString('nl-NL') + '</td>';
                        html += '<td>' + typeLabel + '</td>';
                        html += '<td>' + act.qr_code + '</td>';
                        html += '<td>' + parseFloat(act.meters).toFixed(2) + ' m</td>';
                        html += '<td>' + (act.user_name || '-') + '</td>';
                        html += '<td>' + (act.notitie || '-') + '</td>';
                        html += '</tr>';
                    });
                }
                
                html += '</tbody></table>';
                $('#gvs-recent-activity').html(html);
            }
        });
        </script>
        <?php
    }
}