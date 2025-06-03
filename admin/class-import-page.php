<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Import_Page {
    
    /**
     * Render page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Importeer Collecties uit Excel', 'gordijnen-voorraad'); ?></h1>
            
            <?php $this->show_message(); ?>
            
            <div class="gvs-import-container" style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4;">
                <h2><?php _e('Excel Bestand Uploaden', 'gordijnen-voorraad'); ?></h2>
                
                <form id="gvs-import-form">
                    <?php wp_nonce_field('gvs_import_excel', 'import_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="excel_file"><?php _e('Excel Bestand', 'gordijnen-voorraad'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required>
                                <p class="description">
                                    <?php _e('Upload een Excel bestand met collecties en kleuren.', 'gordijnen-voorraad'); ?><br>
                                    <?php _e('Kolom A: Volledige naam (bijv. "Alaska 01A")', 'gordijnen-voorraad'); ?><br>
                                    <?php _e('Kolom B: Kleurcode (bijv. "01A")', 'gordijnen-voorraad'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="import_mode"><?php _e('Import Modus', 'gordijnen-voorraad'); ?></label>
                            </th>
                            <td>
                                <select name="import_mode" id="import_mode">
                                    <option value="add_only"><?php _e('Alleen nieuwe toevoegen', 'gordijnen-voorraad'); ?></option>
                                    <option value="update_existing"><?php _e('Bestaande bijwerken', 'gordijnen-voorraad'); ?></option>
                                    <option value="replace_all"><?php _e('Alles vervangen (VOORZICHTIG!)', 'gordijnen-voorraad'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Kies hoe om te gaan met bestaande collecties en kleuren.', 'gordijnen-voorraad'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <?php _e('Standaard Voorraad Instellingen', 'gordijnen-voorraad'); ?>
                            </th>
                            <td>
                                <label>
                                    <?php _e('Min. voorraad rollen:', 'gordijnen-voorraad'); ?>
                                    <input type="number" name="default_min_rollen" id="default_min_rollen" value="5" min="0" style="width: 80px;">
                                </label>
                                <br><br>
                                <label>
                                    <?php _e('Min. voorraad meters:', 'gordijnen-voorraad'); ?>
                                    <input type="number" name="default_min_meters" id="default_min_meters" value="100" min="0" step="0.01" style="width: 80px;">
                                </label>
                                <p class="description">
                                    <?php _e('Deze waarden worden gebruikt voor alle geïmporteerde kleuren.', 'gordijnen-voorraad'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="gvs-analyze-btn" class="button button-secondary" style="display:none;">
                            <?php _e('Analyseer Bestand', 'gordijnen-voorraad'); ?>
                        </button>
                        <button type="button" id="gvs-import-btn" class="button button-primary" style="display:none;">
                            <?php _e('Start Import', 'gordijnen-voorraad'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Preview sectie -->
            <div id="gvs-import-preview" style="display:none; background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4;">
                <h2><?php _e('Preview Import Data', 'gordijnen-voorraad'); ?></h2>
                <div id="gvs-preview-content"></div>
            </div>
            
            <!-- Progress sectie -->
            <div id="gvs-import-progress" style="display:none; background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4;">
                <h2><?php _e('Import Voortgang', 'gordijnen-voorraad'); ?></h2>
                <div class="gvs-progress-bar" style="width: 100%; height: 30px; background: #f0f0f0; border-radius: 5px; overflow: hidden;">
                    <div id="gvs-progress-fill" style="width: 0%; height: 100%; background: #2196F3; transition: width 0.3s;"></div>
                </div>
                <p id="gvs-progress-text" style="margin-top: 10px;">0%</p>
                <div id="gvs-import-log" style="max-height: 300px; overflow-y: auto; margin-top: 20px; padding: 10px; background: #f5f5f5; font-family: monospace; font-size: 12px;"></div>
            </div>
            
            <div class="gvs-import-info" style="background: #f1f1f1; padding: 20px; margin-top: 20px; border-left: 4px solid #2196F3;">
                <h3><?php _e('Import Instructies', 'gordijnen-voorraad'); ?></h3>
                <ol>
                    <li><?php _e('Zorg dat uw Excel bestand twee kolommen heeft:', 'gordijnen-voorraad'); ?>
                        <ul>
                            <li><?php _e('Kolom A: De volledige naam inclusief kleurcode (bijv. "Alaska 01A")', 'gordijnen-voorraad'); ?></li>
                            <li><?php _e('Kolom B: Alleen de kleurcode (bijv. "01A")', 'gordijnen-voorraad'); ?></li>
                        </ul>
                    </li>
                    <li><?php _e('Het systeem zal automatisch de collectienaam extraheren door de kleurcode te verwijderen.', 'gordijnen-voorraad'); ?></li>
                    <li><?php _e('Dubbele collecties worden samengevoegd, alle unieke kleuren worden toegevoegd.', 'gordijnen-voorraad'); ?></li>
                    <li><?php _e('Na import kunt u de minimum voorraad waarden per kleur aanpassen indien nodig.', 'gordijnen-voorraad'); ?></li>
                </ol>
            </div>
        </div>
        
        <!-- Include SheetJS library -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        
        <script>
        jQuery(document).ready(function($) {
            let importData = null;
            
            // File change handler
            $('#excel_file').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    $('#gvs-analyze-btn').show();
                    $('#gvs-import-btn').hide();
                    $('#gvs-import-preview').hide();
                }
            });
            
            // Analyze button click
            $('#gvs-analyze-btn').on('click', function() {
                const file = $('#excel_file')[0].files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, {type: 'array'});
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        const jsonData = XLSX.utils.sheet_to_json(firstSheet, {header: 1});
                        
                        // Process data
                        importData = processExcelData(jsonData);
                        showPreview(importData);
                        
                        $('#gvs-import-btn').show();
                        
                    } catch (error) {
                        alert('Error reading file: ' + error.message);
                    }
                };
                reader.readAsArrayBuffer(file);
            });
            
            // Process Excel data
            function processExcelData(rows) {
                const collectiesMap = new Map();
                
                rows.forEach((row, index) => {
                    if (!row[0]) return; // Skip empty rows
                    
                    // Trim en normaliseer spaties
                    const fullName = row[0].toString().trim().replace(/\s+/g, ' ');
                    const kleurCodeRaw = row[1] !== undefined && row[1] !== null ? row[1].toString().trim() : '';
                    
                    // Format kleurcode - voeg leading zero toe voor getallen < 10
                    let kleurCode = kleurCodeRaw;
                    if (/^\d+$/.test(kleurCodeRaw)) {
                        // Het is een nummer, format met leading zero indien nodig
                        const num = parseInt(kleurCodeRaw);
                        if (num < 10 && num >= 0) {
                            kleurCode = '0' + num;
                        }
                    }
                    
                    // Extract collectie name door te kijken naar het patroon
                    let collectieNaam = fullName;
                    
                    if (kleurCode) {
                        // Probeer verschillende patronen
                        // 1. Naam eindigt met spatie + originele kleurcode (zoals het in Excel staat)
                        if (fullName.endsWith(' ' + kleurCodeRaw)) {
                            collectieNaam = fullName.substring(0, fullName.lastIndexOf(' ' + kleurCodeRaw)).trim();
                        }
                        // 2. Naam eindigt met spatie + geformatteerde kleurcode
                        else if (fullName.endsWith(' ' + kleurCode)) {
                            collectieNaam = fullName.substring(0, fullName.lastIndexOf(' ' + kleurCode)).trim();
                        }
                        // 3. Voor gevallen zoals "Aurora 01" waar Excel "1" heeft gemaakt
                        else if (kleurCodeRaw.match(/^\d+$/)) {
                            // Zoek naar "spatie + 0 + kleurcode" patroon
                            const paddedPattern = ' 0' + kleurCodeRaw;
                            if (fullName.endsWith(paddedPattern)) {
                                collectieNaam = fullName.substring(0, fullName.lastIndexOf(paddedPattern)).trim();
                            }
                        }
                    }
                    
                    // Normaliseer de collectienaam
                    collectieNaam = collectieNaam.trim();
                    
                    if (!collectiesMap.has(collectieNaam)) {
                        collectiesMap.set(collectieNaam, new Set());
                    }
                    
                    if (kleurCode) {
                        collectiesMap.get(collectieNaam).add(kleurCode);
                    }
                });
                
                // Convert to array format
                const result = [];
                collectiesMap.forEach((kleuren, collectie) => {
                    result.push({
                        naam: collectie,
                        kleuren: Array.from(kleuren).sort((a, b) => {
                            // Sorteer alfanumeriek
                            return a.localeCompare(b, undefined, {numeric: true});
                        })
                    });
                });
                
                return result.sort((a, b) => a.naam.localeCompare(b.naam));
            }
            
            // Show preview
            function showPreview(data) {
                let html = '<p><strong>Gevonden: ' + data.length + ' collecties</strong></p>';
                html += '<div style="max-height: 400px; overflow-y: auto;">';
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Collectie</th><th>Aantal Kleuren</th><th>Kleuren (eerste 10)</th></tr></thead>';
                html += '<tbody>';
                
                data.forEach(collectie => {
                    const kleurenDisplay = collectie.kleuren.slice(0, 10).join(', ');
                    const moreText = collectie.kleuren.length > 10 ? ' ... en ' + (collectie.kleuren.length - 10) + ' meer' : '';
                    
                    html += '<tr>';
                    html += '<td><strong>' + collectie.naam + '</strong></td>';
                    html += '<td>' + collectie.kleuren.length + '</td>';
                    html += '<td>' + kleurenDisplay + moreText + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
                
                $('#gvs-preview-content').html(html);
                $('#gvs-import-preview').show();
            }
            
            // Import button click
            $('#gvs-import-btn').on('click', function() {
                if (!importData || importData.length === 0) {
                    alert('Geen data om te importeren');
                    return;
                }
                
                if (!confirm('Weet u zeker dat u wilt importeren? Dit kan even duren.')) {
                    return;
                }
                
                // Start import
                $('#gvs-import-form').hide();
                $('#gvs-import-preview').hide();
                $('#gvs-import-progress').show();
                
                importCollecties(importData);
            });
            
            // Import collecties via AJAX
            function importCollecties(data) {
                const totalItems = data.length;
                let processed = 0;
                let importedCollecties = 0;
                let importedKleuren = 0;
                
                function processNext() {
                    if (processed >= totalItems) {
                        // Import complete
                        updateProgress(100, 'Import voltooid!');
                        addLog('=== IMPORT VOLTOOID ===');
                        addLog('Totaal collecties: ' + importedCollecties);
                        addLog('Totaal kleuren: ' + importedKleuren);
                        
                        setTimeout(() => {
                            if (confirm('Import voltooid! Wilt u naar de collecties pagina gaan?')) {
                                window.location.href = '<?php echo admin_url('admin.php?page=gvs-collecties'); ?>';
                            }
                        }, 1000);
                        return;
                    }
                    
                    const collectie = data[processed];
                    const progress = Math.round((processed / totalItems) * 100);
                    updateProgress(progress, 'Importeren: ' + collectie.naam);
                    
                    // Import via AJAX
                    $.ajax({
                        url: gvs_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'gvs_import_collectie',
                            nonce: $('#import_nonce').val(),
                            collectie_naam: collectie.naam,
                            kleuren: collectie.kleuren,
                            import_mode: $('#import_mode').val(),
                            default_min_rollen: $('#default_min_rollen').val(),
                            default_min_meters: $('#default_min_meters').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                addLog('✓ ' + collectie.naam + ': ' + response.data.message);
                                importedCollecties += response.data.collecties_added;
                                importedKleuren += response.data.kleuren_added;
                            } else {
                                addLog('✗ ' + collectie.naam + ': ' + (response.data.message || 'Fout'));
                            }
                        },
                        error: function() {
                            addLog('✗ ' + collectie.naam + ': Verbindingsfout');
                        },
                        complete: function() {
                            processed++;
                            processNext();
                        }
                    });
                }
                
                // Start processing
                processNext();
            }
            
            // Update progress
            function updateProgress(percentage, text) {
                $('#gvs-progress-fill').css('width', percentage + '%');
                $('#gvs-progress-text').text(percentage + '% - ' + (text || ''));
            }
            
            // Add log entry
            function addLog(message) {
                const timestamp = new Date().toLocaleTimeString();
                $('#gvs-import-log').append('[' + timestamp + '] ' + message + '\n');
                $('#gvs-import-log').scrollTop($('#gvs-import-log')[0].scrollHeight);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Show message
     */
    private function show_message() {
        if (!isset($_GET['message'])) {
            return;
        }
        
        $message = $_GET['message'];
        $type = 'success';
        $text = '';
        
        switch ($message) {
            case 'import_success':
                $text = __('Import succesvol voltooid!', 'gordijnen-voorraad');
                break;
        }
        
        if ($text) {
            echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . esc_html($text) . '</p></div>';
        }
    }
}