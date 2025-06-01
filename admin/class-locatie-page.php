<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Locatie_Page {
    
    /**
     * Render page
     */
    public function render_page() {
        // Check for delete action in GET (zoals WordPress standaard doet)
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('delete_locatie_' . $_GET['id']);
            $this->delete_locatie($_GET['id']);
            return;
        }
        
        // Handle form submissions
        $this->handle_actions();
        
        // Get action
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        
        switch ($action) {
            case 'edit':
            case 'new':
                $this->render_form();
                break;
            default:
                $this->render_list();
        }
    }
    
    /**
     * Handle actions
     */
    private function handle_actions() {
        if (!isset($_POST['gvs_action'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gvs_locatie_action')) {
            wp_die(__('Security check failed', 'gordijnen-voorraad'));
        }
        
        $action = $_POST['gvs_action'];
        
        switch ($action) {
            case 'save':
                $this->save_locatie();
                break;
        }
    }
    
    /**
     * Save locatie
     */
    private function save_locatie() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $naam = sanitize_text_field($_POST['naam']);
        
        // Check if name already exists
        if (GVS_Locatie::naam_exists($naam, $id)) {
            wp_die(__('Een locatie met deze naam bestaat al', 'gordijnen-voorraad'));
        }
        
        if ($id) {
            $locatie = GVS_Locatie::get_by_id($id);
        } else {
            $locatie = new GVS_Locatie();
        }
        
        $locatie->set_naam($naam);
        $locatie->set_beschrijving($_POST['beschrijving']);
        $locatie->set_actief(isset($_POST['actief']) ? 1 : 0);
        
        if ($locatie->save()) {
            wp_redirect(admin_url('admin.php?page=gvs-locaties&message=saved'));
            exit;
        }
    }
    
    /**
     * Delete locatie
     */
    private function delete_locatie($id) {
        $id = intval($id);
        
        if (!$id) {
            wp_redirect(admin_url('admin.php?page=gvs-locaties&message=error'));
            exit;
        }
        
        $locatie = GVS_Locatie::get_by_id($id);
        if (!$locatie) {
            wp_redirect(admin_url('admin.php?page=gvs-locaties&message=not_found'));
            exit;
        }
        
        // Check if location has rollen
        $rollen_count = $locatie->get_rollen_count();
        if ($rollen_count > 0) {
            wp_redirect(admin_url('admin.php?page=gvs-locaties&message=has_rollen&count=' . $rollen_count));
            exit;
        }
        
        if ($locatie->delete()) {
            wp_redirect(admin_url('admin.php?page=gvs-locaties&message=deleted'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=gvs-locaties&message=delete_failed'));
            exit;
        }
    }
    
    /**
     * Render list
     */
    private function render_list() {
        $locaties = GVS_Locatie::get_all();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Locaties', 'gordijnen-voorraad'); ?>
                <a href="<?php echo admin_url('admin.php?page=gvs-locaties&action=new'); ?>" class="page-title-action">
                    <?php _e('Nieuwe Locatie', 'gordijnen-voorraad'); ?>
                </a>
            </h1>
            
            <?php $this->show_message(); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="100"><?php _e('Naam', 'gordijnen-voorraad'); ?></th>
                        <th><?php _e('Beschrijving', 'gordijnen-voorraad'); ?></th>
                        <th width="100"><?php _e('Rollen', 'gordijnen-voorraad'); ?></th>
                        <th width="120"><?php _e('Totaal Meters', 'gordijnen-voorraad'); ?></th>
                        <th width="80"><?php _e('Status', 'gordijnen-voorraad'); ?></th>
                        <th width="150"><?php _e('Acties', 'gordijnen-voorraad'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($locaties)): ?>
                        <tr>
                            <td colspan="6"><?php _e('Geen locaties gevonden', 'gordijnen-voorraad'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($locaties as $locatie): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($locatie->naam); ?></strong>
                                </td>
                                <td><?php echo esc_html($locatie->beschrijving); ?></td>
                                <td><?php echo intval($locatie->aantal_rollen); ?></td>
                                <td><?php echo number_format($locatie->totaal_meters, 2, ',', '.'); ?> m</td>
                                <td>
                                    <?php if ($locatie->actief): ?>
                                        <span class="gvs-badge in"><?php _e('Actief', 'gordijnen-voorraad'); ?></span>
                                    <?php else: ?>
                                        <span class="gvs-badge out"><?php _e('Inactief', 'gordijnen-voorraad'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=gvs-locaties&action=edit&id=' . $locatie->id); ?>" class="button button-small">
                                        <?php _e('Bewerk', 'gordijnen-voorraad'); ?>
                                    </a>
                                    <?php if ($locatie->aantal_rollen == 0): ?>
                                        <?php
                                        $delete_url = wp_nonce_url(
                                            admin_url('admin.php?page=gvs-locaties&action=delete&id=' . $locatie->id),
                                            'delete_locatie_' . $locatie->id
                                        );
                                        ?>
                                        <a href="<?php echo $delete_url; ?>" 
                                           class="button button-small"
                                           onclick="return confirm('Weet u zeker dat u locatie <?php echo esc_js($locatie->naam); ?> wilt verwijderen?');">
                                            <?php _e('Verwijder', 'gordijnen-voorraad'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <div class="alignleft actions">
                    <p class="description">
                        <?php _e('Locaties met rollen kunnen niet worden verwijderd. Verplaats eerst alle rollen naar een andere locatie.', 'gordijnen-voorraad'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render form
     */
    private function render_form() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $locatie = $id ? GVS_Locatie::get_by_id($id) : new GVS_Locatie();
        
        ?>
        <div class="wrap">
            <h1><?php echo $id ? __('Bewerk Locatie', 'gordijnen-voorraad') : __('Nieuwe Locatie', 'gordijnen-voorraad'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('gvs_locatie_action'); ?>
                <input type="hidden" name="gvs_action" value="save">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="naam"><?php _e('Naam', 'gordijnen-voorraad'); ?></label></th>
                        <td>
                            <input type="text" id="naam" name="naam" value="<?php echo esc_attr($locatie->get_naam()); ?>" 
                                   class="regular-text" required pattern="[A-Z0-9]+" title="<?php esc_attr_e('Gebruik alleen hoofdletters en cijfers (bijv. A01, B12)', 'gordijnen-voorraad'); ?>">
                            <p class="description"><?php _e('Gebruik alleen hoofdletters en cijfers (bijv. A01, B12)', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="beschrijving"><?php _e('Beschrijving', 'gordijnen-voorraad'); ?></label></th>
                        <td>
                            <input type="text" id="beschrijving" name="beschrijving" 
                                   value="<?php echo esc_attr($locatie->get_beschrijving()); ?>" class="regular-text">
                            <p class="description"><?php _e('Bijv. "Stelling A, Vak 01"', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Status', 'gordijnen-voorraad'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="actief" value="1" <?php checked($locatie->is_actief()); ?>>
                                <?php _e('Actief', 'gordijnen-voorraad'); ?>
                            </label>
                            <p class="description"><?php _e('Inactieve locaties kunnen niet worden geselecteerd bij het toevoegen van nieuwe rollen', 'gordijnen-voorraad'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php if ($id && $locatie->get_rollen_count() > 0): ?>
                    <h2><?php _e('Rollen op deze locatie', 'gordijnen-voorraad'); ?></h2>
                    <?php
                    $rollen = $locatie->get_rollen();
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('QR Code', 'gordijnen-voorraad'); ?></th>
                                <th><?php _e('Collectie', 'gordijnen-voorraad'); ?></th>
                                <th><?php _e('Kleur', 'gordijnen-voorraad'); ?></th>
                                <th><?php _e('Meters', 'gordijnen-voorraad'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rollen as $rol): ?>
                                <tr>
                                    <td><code><?php echo esc_html($rol->qr_code); ?></code></td>
                                    <td><?php echo esc_html($rol->collectie_naam); ?></td>
                                    <td><?php echo esc_html($rol->kleur_naam); ?></td>
                                    <td><?php echo number_format($rol->meters, 2, ',', '.'); ?> m</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Opslaan', 'gordijnen-voorraad'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=gvs-locaties'); ?>" class="button">
                        <?php _e('Annuleer', 'gordijnen-voorraad'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Show message
     */
    private function show_message() {
        if (!isset($_GET['message'])) {
            return;
        }
        
        $messages = [
            'saved' => __('Locatie opgeslagen', 'gordijnen-voorraad'),
            'deleted' => __('Locatie verwijderd', 'gordijnen-voorraad'),
            'error' => __('Er is een fout opgetreden', 'gordijnen-voorraad'),
            'not_found' => __('Locatie niet gevonden', 'gordijnen-voorraad'),
            'delete_failed' => __('Locatie kon niet worden verwijderd', 'gordijnen-voorraad'),
        ];
        
        // Special handling for has_rollen message
        if ($_GET['message'] === 'has_rollen') {
            $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo sprintf(__('Deze locatie heeft %d rollen. Verplaats deze eerst naar een andere locatie voordat u deze locatie kunt verwijderen.', 'gordijnen-voorraad'), $count);
            echo '</p></div>';
            return;
        }
        
        $message = isset($messages[$_GET['message']]) ? $messages[$_GET['message']] : '';
        
        if ($message) {
            $type = in_array($_GET['message'], ['error', 'not_found', 'delete_failed']) ? 'error' : 'success';
            echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }
}