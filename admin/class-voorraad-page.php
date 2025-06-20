<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Voorraad_Page {
    
    /**
     * Render voorraad page
     */
    public function render_page() {
        ?>
        <div class="wrap gvs-voorraad">
            <h1>
                <?php _e('Voorraad Beheer', 'gordijnen-voorraad'); ?>
                <button class="page-title-action" id="gvs-add-rollen-btn">
                    <?php _e('Nieuwe Rollen Toevoegen', 'gordijnen-voorraad'); ?>
                </button>
            </h1>
            
            <!-- Filters -->
            <div class="gvs-filters">
                <div class="gvs-filter-row">
                    <select id="filter-collectie" class="gvs-filter">
                        <option value=""><?php _e('Alle Collecties', 'gordijnen-voorraad'); ?></option>
                        <?php
                        $collecties = GVS_Collectie::get_all();
                        foreach ($collecties as $collectie) {
                            echo '<option value="' . esc_attr($collectie->id) . '">' . esc_html($collectie->naam) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <select id="filter-kleur" class="gvs-filter">
                        <option value=""><?php _e('Alle Kleuren', 'gordijnen-voorraad'); ?></option>
                    </select>
                    
                    <select id="filter-locatie" class="gvs-filter">
                        <option value=""><?php _e('Alle Locaties', 'gordijnen-voorraad'); ?></option>
                        <?php
                        $locaties = GVS_Locatie::get_active();
                        foreach ($locaties as $locatie) {
                            echo '<option value="' . esc_attr($locatie->naam) . '">' . esc_html($locatie->naam) . ' - ' . esc_html($locatie->beschrijving) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <input type="text" id="filter-search" class="gvs-filter" placeholder="<?php esc_attr_e('Zoek op QR code...', 'gordijnen-voorraad'); ?>">
                    
                    <button class="button" id="gvs-filter-btn"><?php _e('Filter', 'gordijnen-voorraad'); ?></button>
                    <button class="button" id="gvs-reset-filter-btn"><?php _e('Reset', 'gordijnen-voorraad'); ?></button>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Selecteer bulk actie', 'gordijnen-voorraad'); ?></label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e('Bulk acties', 'gordijnen-voorraad'); ?></option>
                        <option value="print_qr"><?php _e('Print QR Codes', 'gordijnen-voorraad'); ?></option>
                        <option value="uitgeven"><?php _e('Uitgeven', 'gordijnen-voorraad'); ?></option>
                    </select>
                    <button class="button action" id="doaction"><?php _e('Toepassen', 'gordijnen-voorraad'); ?></button>
                    <span class="displaying-num" id="selected-count">0 items geselecteerd</span>
                </div>
            </div>
            
            <!-- Results Table -->
            <div id="gvs-voorraad-table-wrapper">
                <table class="wp-list-table widefat fixed striped gvs-sortable-table">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all">
                            </td>
                            <th class="sortable" data-sort="qr_code">
                                <?php _e('QR Code', 'gordijnen-voorraad'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="sortable" data-sort="collectie">
                                <?php _e('Collectie', 'gordijnen-voorraad'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="sortable" data-sort="kleur">
                                <?php _e('Kleur', 'gordijnen-voorraad'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="sortable" data-sort="meters">
                                <?php _e('Meters', 'gordijnen-voorraad'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="sortable" data-sort="locatie">
                                <?php _e('Locatie', 'gordijnen-voorraad'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th class="sortable sorted desc" data-sort="created_at">
                                <?php _e('Datum', 'gordijnen-voorraad'); ?>
                                <span class="sorting-indicator"></span>
                            </th>
                            <th><?php _e('Acties', 'gordijnen-voorraad'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="gvs-voorraad-tbody">
                        <tr>
                            <td colspan="8" class="loading"><?php _e('Voorraad laden...', 'gordijnen-voorraad'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add CSS for sortable columns -->
        <style>
        .gvs-sortable-table th.sortable {
            cursor: pointer;
            position: relative;
            padding-right: 25px;
        }
        
        .gvs-sortable-table th.sortable:hover {
            background: #f0f0f0;
        }
        
        .gvs-sortable-table th.sortable .sorting-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-style: solid;
            visibility: hidden;
        }
        
        .gvs-sortable-table th.sortable.sorted .sorting-indicator {
            visibility: visible;
        }
        
        .gvs-sortable-table th.sortable.sorted.asc .sorting-indicator {
            border-width: 0 4px 6px 4px;
            border-color: transparent transparent #333 transparent;
        }
        
        .gvs-sortable-table th.sortable.sorted.desc .sorting-indicator {
            border-width: 6px 4px 0 4px;
            border-color: #333 transparent transparent transparent;
        }
        </style>
        
        <!-- Add Rollen Modal -->
        <div id="gvs-add-rollen-modal" class="gvs-modal" style="display:none;">
            <div class="gvs-modal-content">
                <span class="gvs-modal-close">&times;</span>
                <h2><?php _e('Nieuwe Rollen Toevoegen', 'gordijnen-voorraad'); ?></h2>
                
                <form id="gvs-add-rollen-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="add-collectie"><?php _e('Collectie', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <select id="add-collectie" name="collectie_id" required>
                                    <option value=""><?php _e('Selecteer collectie', 'gordijnen-voorraad'); ?></option>
                                    <?php foreach ($collecties as $collectie): ?>
                                        <option value="<?php echo esc_attr($collectie->id); ?>">
                                            <?php echo esc_html($collectie->naam); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="add-kleur"><?php _e('Kleur', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <select id="add-kleur" name="kleur_id" required disabled>
                                    <option value=""><?php _e('Selecteer eerst collectie', 'gordijnen-voorraad'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="add-locatie"><?php _e('Locatie', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <select id="add-locatie" name="locatie" required>
                                    <option value=""><?php _e('Selecteer locatie', 'gordijnen-voorraad'); ?></option>
                                    <?php foreach ($locaties as $locatie): ?>
                                        <option value="<?php echo esc_attr($locatie->naam); ?>">
                                            <?php echo esc_html($locatie->naam); ?> - <?php echo esc_html($locatie->beschrijving); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="add-aantal"><?php _e('Aantal rollen', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <input type="number" id="add-aantal" name="aantal" min="1" value="1" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="add-meters"><?php _e('Meters per rol', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <input type="number" id="add-meters" name="meters" min="0.01" step="0.01" value="<?php echo get_option('gvs_default_meters', '50'); ?>" required>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Genereer QR Codes', 'gordijnen-voorraad'); ?></button>
                        <button type="button" class="button gvs-modal-cancel"><?php _e('Annuleer', 'gordijnen-voorraad'); ?></button>
                    </p>
                </form>
                
                <div id="gvs-add-result" style="display:none;"></div>
            </div>
        </div>
        
        <!-- View QR Modal -->
        <div id="gvs-view-qr-modal" class="gvs-modal" style="display:none;">
            <div class="gvs-modal-content small">
                <span class="gvs-modal-close">&times;</span>
                <h2><?php _e('QR Code', 'gordijnen-voorraad'); ?></h2>
                <div id="gvs-qr-display" class="gvs-qr-display"></div>
                <p class="submit" style="text-align: center;">
                    <button type="button" class="button button-primary" id="gvs-print-single-qr"><?php _e('Print deze QR Code', 'gordijnen-voorraad'); ?></button>
                </p>
            </div>
        </div>
        
        <!-- Delete Rol Modal -->
        <div id="gvs-delete-rol-modal" class="gvs-modal" style="display:none;">
            <div class="gvs-modal-content small">
                <span class="gvs-modal-close">&times;</span>
                <h2><?php _e('Rol Uitgeven', 'gordijnen-voorraad'); ?></h2>
                
                <form id="gvs-delete-rol-form">
                    <input type="hidden" id="delete-rol-id" name="rol_id">
                    
                    <p><?php _e('Weet u zeker dat u deze rol wilt uitgeven?', 'gordijnen-voorraad'); ?></p>
                    
                    <div id="gvs-delete-rol-info"></div>
                    
                    <p>
                        <label for="delete-notitie"><?php _e('Notitie (optioneel):', 'gordijnen-voorraad'); ?></label><br>
                        <textarea id="delete-notitie" name="notitie" rows="3" class="large-text"></textarea>
                    </p>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Rol Uitgeven', 'gordijnen-voorraad'); ?></button>
                        <button type="button" class="button gvs-modal-cancel"><?php _e('Annuleer', 'gordijnen-voorraad'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            console.log('Voorraad page loaded');
            var currentFilters = {};
            var currentSort = {
                by: 'created_at',
                order: 'DESC'
            };
            
            // Load initial data
            loadVoorraad();
            
            // Sortable column click handler
            $('.gvs-sortable-table th.sortable').on('click', function() {
                var $th = $(this);
                var sortBy = $th.data('sort');
                
                // Toggle sort order if clicking the same column
                if (currentSort.by === sortBy) {
                    currentSort.order = currentSort.order === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    currentSort.by = sortBy;
                    currentSort.order = 'ASC';
                }
                
                // Update visual indicators
                $('.gvs-sortable-table th.sortable').removeClass('sorted asc desc');
                $th.addClass('sorted ' + currentSort.order.toLowerCase());
                
                // Reload data with new sort
                loadVoorraad();
            });
            
            // Filter button click
            $('#gvs-filter-btn').on('click', function() {
                loadVoorraad();
            });
            
            // Reset filters
            $('#gvs-reset-filter-btn').on('click', function() {
                $('#filter-collectie').val('');
                $('#filter-kleur').val('').html('<option value=""><?php _e('Alle Kleuren', 'gordijnen-voorraad'); ?></option>');
                $('#filter-locatie').val('');
                $('#filter-search').val('');
                loadVoorraad();
            });
            
            // Collectie change - update kleuren
            $('#filter-collectie, #add-collectie').on('change', function() {
                var collectieId = $(this).val();
                var targetSelect = $(this).attr('id') === 'filter-collectie' ? '#filter-kleur' : '#add-kleur';
                
                if (collectieId) {
                    $.ajax({
                        url: gvs_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'gvs_get_kleuren_by_collectie',
                            collectie_id: collectieId,
                            nonce: gvs_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                var options = '<option value="">' + 
                                    (targetSelect === '#filter-kleur' ? '<?php _e('Alle Kleuren', 'gordijnen-voorraad'); ?>' : '<?php _e('Selecteer kleur', 'gordijnen-voorraad'); ?>') + 
                                    '</option>';
                                
                                response.data.forEach(function(kleur) {
                                    options += '<option value="' + kleur.id + '">' + kleur.kleur_naam + '</option>';
                                });
                                
                                $(targetSelect).html(options).prop('disabled', false);
                            }
                        }
                    });
                } else {
                    $(targetSelect).html('<option value="">' + 
                        (targetSelect === '#filter-kleur' ? '<?php _e('Alle Kleuren', 'gordijnen-voorraad'); ?>' : '<?php _e('Selecteer eerst collectie', 'gordijnen-voorraad'); ?>') + 
                        '</option>').prop('disabled', targetSelect === '#add-kleur');
                }
            });
            
            // Load voorraad
            function loadVoorraad() {
                var filters = {
                    collectie_id: $('#filter-collectie').val(),
                    kleur_id: $('#filter-kleur').val(),
                    locatie: $('#filter-locatie').val(),
                    search: $('#filter-search').val(),
                    sort_by: currentSort.by,
                    sort_order: currentSort.order
                };
                
                $('#gvs-voorraad-tbody').html('<tr><td colspan="8" class="loading"><?php _e('Laden...', 'gordijnen-voorraad'); ?></td></tr>');
                
                $.ajax({
                    url: gvs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gvs_search_rollen',
                        nonce: gvs_ajax.nonce,
                        ...filters
                    },
                    success: function(response) {
                        if (response.success) {
                            displayVoorraad(response.data);
                        } else {
                            $('#gvs-voorraad-tbody').html('<tr><td colspan="8" class="error">' + response.data.message + '</td></tr>');
                        }
                    },
                    error: function() {
                        $('#gvs-voorraad-tbody').html('<tr><td colspan="8" class="error">' + gvs_ajax.strings.error + '</td></tr>');
                    }
                });
            }
            
            // Display voorraad
            function displayVoorraad(rollen) {
                var html = '';
                
                if (rollen.length === 0) {
                    html = '<tr><td colspan="8"><?php _e('Geen rollen gevonden', 'gordijnen-voorraad'); ?></td></tr>';
                } else {
                    rollen.forEach(function(rol) {
                        html += '<tr>';
                        html += '<td class="check-column"><input type="checkbox" class="rol-checkbox" value="' + rol.id + '"></td>';
                        html += '<td><code>' + rol.qr_code + '</code></td>';
                        html += '<td>' + rol.collectie_naam + '</td>';
                        html += '<td>' + rol.kleur_naam + '</td>';
                        html += '<td>' + parseFloat(rol.meters).toFixed(2) + ' m</td>';
                        html += '<td>' + rol.locatie + '</td>';
                        html += '<td>' + new Date(rol.created_at).toLocaleDateString('nl-NL') + '</td>';
                        html += '<td>';
                        html += '<button class="button button-small gvs-view-qr" data-qr="' + rol.qr_code + '" data-id="' + rol.id + '"><?php _e('QR', 'gordijnen-voorraad'); ?></button> ';
                        html += '<button class="button button-small gvs-delete-rol" data-id="' + rol.id + '" data-info="' + 
                                rol.collectie_naam + ' - ' + rol.kleur_naam + ' - ' + rol.meters + 'm"><?php _e('Uitgeven', 'gordijnen-voorraad'); ?></button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                }
                
                $('#gvs-voorraad-tbody').html(html);
                updateSelectedCount();
            }
            
// Modal handlers
$('#gvs-add-rollen-btn').on('click', function() {
    resetAddRollenForm();
    $('#gvs-add-rollen-modal').show();
});

// Reset form function
function resetAddRollenForm() {
    $('#gvs-add-rollen-form')[0].reset();
    $('#gvs-add-result').hide().empty();
    $('#gvs-add-rollen-form').show();
    $('#add-kleur').html('<option value=""><?php _e('Selecteer eerst collectie', 'gordijnen-voorraad'); ?></option>').prop('disabled', true);
    // Reset submit button
    $('#gvs-add-rollen-form button[type="submit"]').prop('disabled', false).text('<?php _e('Genereer QR Codes', 'gordijnen-voorraad'); ?>');
}

// Global function for reset button
window.gvsResetAndShowForm = function() {
    resetAddRollenForm();
};
            
            // View QR
            $(document).on('click', '.gvs-view-qr', function() {
                var qrCode = $(this).data('qr');
                var rolId = $(this).data('id');
                var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(qrCode);
                
                $('#gvs-qr-display').html(
                    '<img src="' + qrUrl + '" alt="QR Code"><br>' +
                    '<strong>' + qrCode + '</strong>'
                ).data('rol-id', rolId);
                $('#gvs-view-qr-modal').show();
            });
            
            // Select all checkbox
            $('#cb-select-all').on('change', function() {
                $('.rol-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedCount();
            });
            
            // Individual checkbox change
            $(document).on('change', '.rol-checkbox', function() {
                updateSelectedCount();
            });
            
            // Update selected count
            function updateSelectedCount() {
                var count = $('.rol-checkbox:checked').length;
                $('#selected-count').text(count + ' items geselecteerd');
                
                // Update select all checkbox
                var total = $('.rol-checkbox').length;
                $('#cb-select-all').prop('checked', count === total && total > 0);
            }
            
            // Bulk actions
            $('#doaction').on('click', function(e) {
                e.preventDefault();
                
                var action = $('#bulk-action-selector-top').val();
                if (action === '-1') {
                    alert('<?php _e('Selecteer een bulk actie', 'gordijnen-voorraad'); ?>');
                    return;
                }
                
                var selectedIds = [];
                $('.rol-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                
                if (selectedIds.length === 0) {
                    alert('<?php _e('Selecteer minimaal één rol', 'gordijnen-voorraad'); ?>');
                    return;
                }
                
                if (action === 'print_qr') {
                    // Print QR codes
                    gvsPrintQRCodes(selectedIds.join(','));
                } else if (action === 'uitgeven') {
                    // Bulk uitgeven
                    if (confirm('<?php _e('Weet u zeker dat u de geselecteerde rollen wilt uitgeven?', 'gordijnen-voorraad'); ?> (' + selectedIds.length + ' rollen)')) {
                        bulkUitgeven(selectedIds);
                    }
                }
            });
            
            // Bulk uitgeven function
            function bulkUitgeven(rolIds) {
                var $button = $('#doaction');
                var originalText = $button.text();
                
                $button.prop('disabled', true).text('<?php _e('Bezig...', 'gordijnen-voorraad'); ?>');
                
                // Process each rol
                var processed = 0;
                var errors = 0;
                
                function processNext() {
                    if (processed >= rolIds.length) {
                        // All done
                        var message = '<?php _e('Klaar!', 'gordijnen-voorraad'); ?> ';
                        message += (processed - errors) + ' <?php _e('rollen uitgegeven', 'gordijnen-voorraad'); ?>';
                        if (errors > 0) {
                            message += ', ' + errors + ' <?php _e('fouten', 'gordijnen-voorraad'); ?>';
                        }
                        alert(message);
                        
                        $button.prop('disabled', false).text(originalText);
                        loadVoorraad(); // Reload the table
                        return;
                    }
                    
                    var rolId = rolIds[processed];
                    
                    $.ajax({
                        url: gvs_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'gvs_delete_rol',
                            rol_id: rolId,
                            nonce: gvs_ajax.nonce,
                            notitie: 'Bulk uitgave'
                        },
                        success: function(response) {
                            if (!response.success) {
                                errors++;
                            }
                        },
                        error: function() {
                            errors++;
                        },
                        complete: function() {
                            processed++;
                            processNext();
                        }
                    });
                }
                
                processNext();
            }
            
            // Print single QR
            $('#gvs-print-single-qr').on('click', function() {
                var rolId = $('#gvs-qr-display').data('rol-id');
                if (rolId) {
                    gvsPrintQRCodes(rolId);
                }
            });
            
            // Delete rol
            $(document).on('click', '.gvs-delete-rol', function() {
                var rolId = $(this).data('id');
                var info = $(this).data('info');
                
                $('#delete-rol-id').val(rolId);
                $('#gvs-delete-rol-info').html('<strong>' + info + '</strong>');
                $('#gvs-delete-rol-modal').show();
            });
            
            // Delete rol form submit
            $('#gvs-delete-rol-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $submit = $form.find('button[type="submit"]');
                var originalText = $submit.text();
                
                $submit.prop('disabled', true).text('<?php _e('Bezig...', 'gordijnen-voorraad'); ?>');
                
                $.ajax({
                    url: gvs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gvs_delete_rol',
                        rol_id: $('#delete-rol-id').val(),
                        notitie: $('#delete-notitie').val(),
                        nonce: gvs_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#gvs-delete-rol-modal').hide();
                            loadVoorraad();
                        } else {
                            alert(response.data.message || gvs_ajax.strings.error);
                        }
                    },
                    error: function() {
                        alert(gvs_ajax.strings.error);
                    },
                    complete: function() {
                        $submit.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            // Add rollen form submit
            $('#gvs-add-rollen-form').on('submit', function(e) {
                e.preventDefault();
                
                console.log('Form submitted'); // Debug
                
                var $form = $(this);
                var $submit = $form.find('button[type="submit"]');
                
                $submit.prop('disabled', true).text('<?php _e('Bezig...', 'gordijnen-voorraad'); ?>');
                
                var formData = {
                    action: 'gvs_add_rollen',
                    nonce: gvs_ajax.nonce,
                    kleur_id: $('#add-kleur').val(),
                    locatie: $('#add-locatie').val(),
                    aantal: $('#add-aantal').val(),
                    meters: $('#add-meters').val()
                };
                
                
                $.ajax({
                    url: gvs_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Show result
                            var html = '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
                            html += '<div class="gvs-qr-grid">';
                            
                            response.data.rollen.forEach(function(rol) {
                                html += '<div class="gvs-qr-item">';
                                html += '<img src="' + rol.qr_url + '" alt="QR">';
                                html += '<div>' + rol.qr_code + '</div>';
                                html += '<div>' + rol.collectie + ' - ' + rol.kleur + '</div>';
                                html += '<div>' + rol.meters + 'm - ' + rol.locatie + '</div>';
                                html += '</div>';
                            });
                            
                            html += '</div>';
                            
                            // Add print button
                            html += '<p class="submit" style="text-align: center; margin-top: 20px;">';
                            html += '<button type="button" class="button button-primary" onclick="gvsPrintQRCodes(\'' + response.data.rollen.map(r => r.id).join(',') + '\')">';
                            html += '<?php _e('Print QR Labels', 'gordijnen-voorraad'); ?>';
                            html += '</button> ';
                            html += '<button type="button" class="button" onclick="gvsResetAndShowForm()">';
html += '<?php _e('Nieuwe Rollen Toevoegen', 'gordijnen-voorraad'); ?>';
html += '</button>';
                            html += '</p>';
                            
                            $('#gvs-add-result').html(html).show();
                            $form.hide();
                            
                            // Reload table
                            loadVoorraad();
                        } else {
                            alert(response.data.message);
                        }
                        
                        $submit.prop('disabled', false).text('<?php _e('Genereer QR Codes', 'gordijnen-voorraad'); ?>');
                    },
                    error: function() {
                        alert(gvs_ajax.strings.error);
                        $submit.prop('disabled', false).text('<?php _e('Genereer QR Codes', 'gordijnen-voorraad'); ?>');
                    }
                });
            });
            
            // Print QR Codes function
            window.gvsPrintQRCodes = function(rolIds) {
                console.log('gvsPrintQRCodes called with:', rolIds);
                
                if (!rolIds) {
                    alert('Geen rollen geselecteerd om te printen');
                    return;
                }
                
                // Ensure rolIds is a string
                if (Array.isArray(rolIds)) {
                    rolIds = rolIds.join(',');
                }
                
                var url = '<?php echo admin_url('admin-ajax.php'); ?>?action=gvs_print_qr_codes&ids=' + encodeURIComponent(rolIds) + '&nonce=' + gvs_ajax.nonce;
                console.log('Opening print URL:', url);
                
                // Try different methods to open the window
                var printWindow = window.open(url, '_blank');
                
                // Fallback if popup is blocked
                if (!printWindow || printWindow.closed || typeof printWindow.closed == 'undefined') {
                    // Create a temporary link and click it
                    var link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            };
        });
        </script>
        <?php
    }
}