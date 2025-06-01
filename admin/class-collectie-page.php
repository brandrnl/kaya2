<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Collectie_Page {
    
    /**
     * Render page
     */
    public function render_page() {
        // Check for delete action in GET (zoals WordPress standaard doet)
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('delete_collectie_' . $_GET['id']);
            $this->delete_collectie($_GET['id']);
            return;
        }
        
        // Check for delete kleur action
        if (isset($_GET['action']) && $_GET['action'] === 'delete_kleur' && isset($_GET['kleur_id'])) {
            check_admin_referer('delete_kleur_' . $_GET['kleur_id']);
            $this->delete_kleur($_GET['kleur_id'], $_GET['collectie_id']);
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
            case 'kleuren':
                $this->render_kleuren();
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
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gvs_collectie_action')) {
            wp_die(__('Security check failed', 'gordijnen-voorraad'));
        }
        
        $action = $_POST['gvs_action'];
        
        switch ($action) {
            case 'save':
                $this->save_collectie();
                break;
            case 'save_kleur':
                $this->save_kleur();
                break;
        }
    }
    
    /**
     * Save collectie
     */
    private function save_collectie() {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id) {
            $collectie = GVS_Collectie::get_by_id($id);
        } else {
            $collectie = new GVS_Collectie();
        }
        
        $collectie->set_naam($_POST['naam']);
        $collectie->set_beschrijving($_POST['beschrijving']);
        
        if ($collectie->save()) {
            wp_redirect(admin_url('admin.php?page=gvs-collecties&message=saved'));
            exit;
        }
    }
    
    /**
     * Delete collectie
     */
    private function delete_collectie($id) {
        $id = intval($id);
        
        if ($id) {
            $collectie = GVS_Collectie::get_by_id($id);
            if ($collectie && $collectie->delete()) {
                wp_redirect(admin_url('admin.php?page=gvs-collecties&message=deleted'));
                exit;
            }
        }
        
        wp_redirect(admin_url('admin.php?page=gvs-collecties&message=error'));
        exit;
    }
    
    /**
     * Save kleur
     */
    private function save_kleur() {
        $id = isset($_POST['kleur_id']) ? intval($_POST['kleur_id']) : 0;
        $collectie_id = isset($_POST['collectie_id']) ? intval($_POST['collectie_id']) : 0;
        
        if ($id) {
            $kleur = GVS_Kleur::get_by_id($id);
        } else {
            $kleur = new GVS_Kleur();
        }
        
        $kleur->set_collectie_id($collectie_id);
        $kleur->set_kleur_naam($_POST['kleur_naam']);
        $kleur->set_min_voorraad_rollen($_POST['min_voorraad_rollen']);
        $kleur->set_min_voorraad_meters($_POST['min_voorraad_meters']);
        
        if ($kleur->save()) {
            wp_redirect(admin_url('admin.php?page=gvs-collecties&action=kleuren&id=' . $collectie_id . '&message=kleur_saved'));
            exit;
        }
    }
    
    /**
     * Delete kleur
     */
    private function delete_kleur($kleur_id, $collectie_id) {
        $kleur_id = intval($kleur_id);
        $collectie_id = intval($collectie_id);
        
        if ($kleur_id) {
            $kleur = GVS_Kleur::get_by_id($kleur_id);
            if ($kleur && $kleur->delete()) {
                wp_redirect(admin_url('admin.php?page=gvs-collecties&action=kleuren&id=' . $collectie_id . '&message=kleur_deleted'));
                exit;
            }
        }
        
        wp_redirect(admin_url('admin.php?page=gvs-collecties&action=kleuren&id=' . $collectie_id . '&message=error'));
        exit;
    }
    
    /**
     * Render list
     */
    private function render_list() {
        $collecties = GVS_Collectie::get_all();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Collecties', 'gordijnen-voorraad'); ?>
                <a href="<?php echo admin_url('admin.php?page=gvs-collecties&action=new'); ?>" class="page-title-action">
                    <?php _e('Nieuwe Collectie', 'gordijnen-voorraad'); ?>
                </a>
            </h1>
            
            <?php $this->show_message(); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Naam', 'gordijnen-voorraad'); ?></th>
                        <th><?php _e('Beschrijving', 'gordijnen-voorraad'); ?></th>
                        <th width="100"><?php _e('Kleuren', 'gordijnen-voorraad'); ?></th>
                        <th width="100"><?php _e('Rollen', 'gordijnen-voorraad'); ?></th>
                        <th width="120"><?php _e('Totaal Meters', 'gordijnen-voorraad'); ?></th>
                        <th width="200"><?php _e('Acties', 'gordijnen-voorraad'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($collecties)): ?>
                        <tr>
                            <td colspan="6"><?php _e('Geen collecties gevonden', 'gordijnen-voorraad'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($collecties as $collectie): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($collectie->naam); ?></strong>
                                </td>
                                <td><?php echo esc_html($collectie->beschrijving); ?></td>
                                <td><?php echo intval($collectie->aantal_kleuren); ?></td>
                                <td><?php echo intval($collectie->aantal_rollen); ?></td>
                                <td><?php echo number_format($collectie->totaal_meters, 2, ',', '.'); ?> m</td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=gvs-collecties&action=kleuren&id=' . $collectie->id); ?>" class="button button-small">
                                        <?php _e('Kleuren', 'gordijnen-voorraad'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=gvs-collecties&action=edit&id=' . $collectie->id); ?>" class="button button-small">
                                        <?php _e('Bewerk', 'gordijnen-voorraad'); ?>
                                    </a>
                                    <?php if ($collectie->aantal_rollen == 0): ?>
                                        <?php
                                        $delete_url = wp_nonce_url(
                                            admin_url('admin.php?page=gvs-collecties&action=delete&id=' . $collectie->id),
                                            'delete_collectie_' . $collectie->id
                                        );
                                        ?>
                                        <a href="<?php echo $delete_url; ?>" 
                                           class="button button-small"
                                           onclick="return confirm('<?php esc_attr_e('Weet u zeker dat u deze collectie wilt verwijderen?', 'gordijnen-voorraad'); ?>');">
                                            <?php _e('Verwijder', 'gordijnen-voorraad'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render form
     */
    private function render_form() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $collectie = $id ? GVS_Collectie::get_by_id($id) : new GVS_Collectie();
        
        ?>
        <div class="wrap">
            <h1><?php echo $id ? __('Bewerk Collectie', 'gordijnen-voorraad') : __('Nieuwe Collectie', 'gordijnen-voorraad'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('gvs_collectie_action'); ?>
                <input type="hidden" name="gvs_action" value="save">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="naam"><?php _e('Naam', 'gordijnen-voorraad'); ?></label></th>
                        <td>
                            <input type="text" id="naam" name="naam" value="<?php echo esc_attr($collectie->get_naam()); ?>" 
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="beschrijving"><?php _e('Beschrijving', 'gordijnen-voorraad'); ?></label></th>
                        <td>
                            <textarea id="beschrijving" name="beschrijving" rows="4" 
                                      class="large-text"><?php echo esc_textarea($collectie->get_beschrijving() ?? ''); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Opslaan', 'gordijnen-voorraad'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=gvs-collecties'); ?>" class="button">
                        <?php _e('Annuleer', 'gordijnen-voorraad'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render kleuren
     */
    private function render_kleuren() {
        $collectie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $collectie = GVS_Collectie::get_by_id($collectie_id);
        
        if (!$collectie) {
            wp_die(__('Collectie niet gevonden', 'gordijnen-voorraad'));
        }
        
        $kleuren = GVS_Kleur::get_by_collectie($collectie_id);
        $edit_kleur_id = isset($_GET['edit_kleur']) ? intval($_GET['edit_kleur']) : 0;
        $edit_kleur = $edit_kleur_id ? GVS_Kleur::get_by_id($edit_kleur_id) : null;
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo sprintf(__('Kleuren voor %s', 'gordijnen-voorraad'), esc_html($collectie->get_naam())); ?>
                <a href="<?php echo admin_url('admin.php?page=gvs-collecties'); ?>" class="page-title-action">
                    <?php _e('Terug naar Collecties', 'gordijnen-voorraad'); ?>
                </a>
            </h1>
            
            <?php $this->show_message(); ?>
            
            <!-- Add/Edit Kleur Form -->
            <div class="gvs-kleur-form" style="background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ccd0d4;">
                <h2><?php echo $edit_kleur ? __('Bewerk Kleur', 'gordijnen-voorraad') : __('Nieuwe Kleur Toevoegen', 'gordijnen-voorraad'); ?></h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('gvs_collectie_action'); ?>
                    <input type="hidden" name="gvs_action" value="save_kleur">
                    <input type="hidden" name="collectie_id" value="<?php echo $collectie_id; ?>">
                    <input type="hidden" name="kleur_id" value="<?php echo $edit_kleur_id; ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="kleur_naam"><?php _e('Kleur Naam', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <input type="text" id="kleur_naam" name="kleur_naam" 
                                       value="<?php echo $edit_kleur ? esc_attr($edit_kleur->get_kleur_naam()) : ''; ?>" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="min_voorraad_rollen"><?php _e('Min. Voorraad Rollen', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <input type="number" id="min_voorraad_rollen" name="min_voorraad_rollen" 
                                       value="<?php echo $edit_kleur ? $edit_kleur->get_min_voorraad_rollen() : 5; ?>" 
                                       min="0" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="min_voorraad_meters"><?php _e('Min. Voorraad Meters', 'gordijnen-voorraad'); ?></label></th>
                            <td>
                                <input type="number" id="min_voorraad_meters" name="min_voorraad_meters" 
                                       value="<?php echo $edit_kleur ? $edit_kleur->get_min_voorraad_meters() : 100; ?>" 
                                       min="0" step="0.01" required>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php echo $edit_kleur ? __('Bijwerken', 'gordijnen-voorraad') : __('Toevoegen', 'gordijnen-voorraad'); ?>
                        </button>
                        <?php if ($edit_kleur): ?>
                            <a href="<?php echo admin_url('admin.php?page=gvs-collecties&action=kleuren&id=' . $collectie_id); ?>" 
                               class="button"><?php _e('Annuleer', 'gordijnen-voorraad'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            <!-- Kleuren List -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Kleur Naam', 'gordijnen-voorraad'); ?></th>
                        <th width="150"><?php _e('Min. Rollen', 'gordijnen-voorraad'); ?></th>
                        <th width="150"><?php _e('Min. Meters', 'gordijnen-voorraad'); ?></th>
                        <th width="150"><?php _e('Huidige Rollen', 'gordijnen-voorraad'); ?></th>
                        <th width="150"><?php _e('Huidige Meters', 'gordijnen-voorraad'); ?></th>
                        <th width="100"><?php _e('Status', 'gordijnen-voorraad'); ?></th>
                        <th width="150"><?php _e('Acties', 'gordijnen-voorraad'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kleuren)): ?>
                        <tr>
                            <td colspan="7"><?php _e('Geen kleuren gevonden', 'gordijnen-voorraad'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kleuren as $kleur): ?>
                            <?php
                            $is_low = $kleur->huidige_rollen < $kleur->min_voorraad_rollen || 
                                     $kleur->huidige_meters < $kleur->min_voorraad_meters;
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($kleur->kleur_naam); ?></strong></td>
                                <td><?php echo intval($kleur->min_voorraad_rollen); ?></td>
                                <td><?php echo number_format($kleur->min_voorraad_meters, 2, ',', '.'); ?> m</td>
                                <td><?php echo intval($kleur->huidige_rollen); ?></td>
                                <td><?php echo number_format($kleur->huidige_meters, 2, ',', '.'); ?> m</td>
                                <td>
                                    <?php if ($is_low): ?>
                                        <span class="gvs-badge out"><?php _e('Lage voorraad', 'gordijnen-voorraad'); ?></span>
                                    <?php else: ?>
                                        <span class="gvs-badge in"><?php _e('OK', 'gordijnen-voorraad'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=gvs-collecties&action=kleuren&id=' . $collectie_id . '&edit_kleur=' . $kleur->id); ?>" class="button button-small">
                                        <?php _e('Bewerk', 'gordijnen-voorraad'); ?>
                                    </a>
                                    <?php if ($kleur->huidige_rollen == 0): ?>
                                        <?php
                                        $delete_url = wp_nonce_url(
                                            admin_url('admin.php?page=gvs-collecties&action=delete_kleur&kleur_id=' . $kleur->id . '&collectie_id=' . $collectie_id),
                                            'delete_kleur_' . $kleur->id
                                        );
                                        ?>
                                        <a href="<?php echo $delete_url; ?>" 
                                           class="button button-small"
                                           onclick="return confirm('<?php esc_attr_e('Weet u zeker dat u deze kleur wilt verwijderen?', 'gordijnen-voorraad'); ?>');">
                                            <?php _e('Verwijder', 'gordijnen-voorraad'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
            'saved' => __('Collectie opgeslagen', 'gordijnen-voorraad'),
            'deleted' => __('Collectie verwijderd', 'gordijnen-voorraad'),
            'kleur_saved' => __('Kleur opgeslagen', 'gordijnen-voorraad'),
            'kleur_deleted' => __('Kleur verwijderd', 'gordijnen-voorraad'),
            'error' => __('Er is een fout opgetreden', 'gordijnen-voorraad'),
        ];
        
        $message = isset($messages[$_GET['message']]) ? $messages[$_GET['message']] : '';
        
        if ($message) {
            $type = $_GET['message'] === 'error' ? 'error' : 'success';
            echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }
}