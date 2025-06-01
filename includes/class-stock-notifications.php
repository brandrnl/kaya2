<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Stock_Notifications {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into rol deletion to check stock levels
        add_action('gvs_rol_deleted', [$this, 'check_stock_after_deletion'], 10, 2);
        
        // Add AJAX handler for test email
        add_action('wp_ajax_gvs_send_test_email', [$this, 'send_test_email']);
    }
    
    /**
     * Check stock levels after a rol is deleted and send notification if needed
     * 
     * @param int $kleur_id The kleur ID of the deleted rol
     * @param array $rol_data Data about the deleted rol
     */
    public function check_stock_after_deletion($kleur_id, $rol_data) {
        // Check if warnings are enabled
        if (get_option('gvs_enable_warnings', '1') !== '1') {
            return;
        }
        
        // Get the kleur object
        $kleur = GVS_Kleur::get_by_id($kleur_id);
        if (!$kleur) {
            return;
        }
        
        // Get current stock info
        $stock_info = $kleur->get_stock_info();
        
        // Check if stock is now below minimum
        $below_minimum = false;
        $shortage_info = [];
        
        if ($stock_info->aantal_rollen < $kleur->get_min_voorraad_rollen()) {
            $below_minimum = true;
            $shortage_info['rollen'] = [
                'current' => $stock_info->aantal_rollen,
                'minimum' => $kleur->get_min_voorraad_rollen(),
                'shortage' => $kleur->get_min_voorraad_rollen() - $stock_info->aantal_rollen
            ];
        }
        
        if ($stock_info->totaal_meters < $kleur->get_min_voorraad_meters()) {
            $below_minimum = true;
            $shortage_info['meters'] = [
                'current' => $stock_info->totaal_meters,
                'minimum' => $kleur->get_min_voorraad_meters(),
                'shortage' => $kleur->get_min_voorraad_meters() - $stock_info->totaal_meters
            ];
        }
        
        // Send notification if below minimum
        if ($below_minimum) {
            $this->send_low_stock_notification($kleur, $shortage_info, $rol_data);
        }
    }
    
    /**
     * Send low stock notification email
     */
    private function send_low_stock_notification($kleur, $shortage_info, $rol_data) {
        // Get collectie info
        $collectie = GVS_Collectie::get_by_id($kleur->get_collectie_id());
        if (!$collectie) {
            return;
        }
        
        // Get email settings
        $to = get_option('gvs_warning_email', get_option('admin_email'));
        $company_name = get_option('gvs_company_name', 'Gordijnen Voorraad');
        
        // Prepare email content
        $subject = sprintf(
            '[%s] WAARSCHUWING: Lage voorraad - %s %s', 
            $company_name,
            $collectie->get_naam(),
            $kleur->get_kleur_naam()
        );
        
        $message = $this->prepare_email_content($collectie, $kleur, $shortage_info, $rol_data);
        
        // Set HTML headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $company_name . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . get_option('admin_email')
        ];
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Log the notification
        if ($sent) {
            $this->log_notification($kleur->get_id(), $shortage_info);
        }
        
        return $sent;
    }
    
    /**
     * Prepare email content
     */
    private function prepare_email_content($collectie, $kleur, $shortage_info, $rol_data) {
        $company_name = get_option('gvs_company_name', 'Gordijnen Voorraad');
        $admin_url = admin_url('admin.php?page=gvs-collecties&action=kleuren&id=' . $collectie->get_id());
        
        // Get user who deleted the rol
        $user = get_userdata($rol_data['user_id']);
        $user_name = $user ? $user->display_name : 'Onbekend';
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: #dc3545;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px 5px 0 0;
                }
                .content {
                    background: #f9f9f9;
                    padding: 30px;
                    border: 1px solid #ddd;
                    border-radius: 0 0 5px 5px;
                }
                .alert-box {
                    background: #fff3cd;
                    border: 2px solid #dc3545;
                    color: #721c24;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .info-table {
                    width: 100%;
                    background: white;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                .info-table td {
                    padding: 10px;
                    border: 1px solid #ddd;
                }
                .info-table td:first-child {
                    background: #f0f0f0;
                    font-weight: bold;
                    width: 40%;
                }
                .shortage {
                    color: #dc3545;
                    font-weight: bold;
                    font-size: 18px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 24px;
                    background: #2271b1;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-top: 20px;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                }
                .trigger-info {
                    background: #f0f0f0;
                    padding: 10px;
                    border-radius: 5px;
                    margin-top: 20px;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>⚠️ LAGE VOORRAAD WAARSCHUWING</h1>
                <p style="margin: 0; font-size: 18px;"><?php echo esc_html($company_name); ?></p>
            </div>
            
            <div class="content">
                <div class="alert-box">
                    <strong>DIRECTE ACTIE VEREIST!</strong><br>
                    De voorraad voor onderstaand artikel is onder het minimum niveau gekomen.
                </div>
                
                <h2 style="color: #333; margin-bottom: 20px;">Artikel Details</h2>
                
                <table class="info-table">
                    <tr>
                        <td>Collectie</td>
                        <td><strong><?php echo esc_html($collectie->get_naam()); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Kleur</td>
                        <td><strong><?php echo esc_html($kleur->get_kleur_naam()); ?></strong></td>
                    </tr>
                </table>
                
                <h3 style="color: #dc3545; margin-top: 30px;">Voorraad Tekort:</h3>
                
                <table class="info-table">
                    <?php if (isset($shortage_info['rollen'])): ?>
                    <tr>
                        <td>Huidige aantal rollen</td>
                        <td class="shortage"><?php echo $shortage_info['rollen']['current']; ?> rollen</td>
                    </tr>
                    <tr>
                        <td>Minimum vereist</td>
                        <td><?php echo $shortage_info['rollen']['minimum']; ?> rollen</td>
                    </tr>
                    <tr>
                        <td>Tekort</td>
                        <td class="shortage">-<?php echo $shortage_info['rollen']['shortage']; ?> rollen</td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (isset($shortage_info['meters'])): ?>
                    <tr>
                        <td>Huidige meters</td>
                        <td class="shortage"><?php echo number_format($shortage_info['meters']['current'], 2, ',', '.'); ?> m</td>
                    </tr>
                    <tr>
                        <td>Minimum vereist</td>
                        <td><?php echo number_format($shortage_info['meters']['minimum'], 2, ',', '.'); ?> m</td>
                    </tr>
                    <tr>
                        <td>Tekort</td>
                        <td class="shortage">-<?php echo number_format($shortage_info['meters']['shortage'], 2, ',', '.'); ?> m</td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <div class="trigger-info">
                    <strong>Aanleiding:</strong> Rol uitgegeven door <?php echo esc_html($user_name); ?><br>
                    <strong>QR Code:</strong> <?php echo esc_html($rol_data['qr_code']); ?><br>
                    <strong>Meters:</strong> <?php echo number_format($rol_data['meters'], 2, ',', '.'); ?> m<br>
                    <strong>Tijdstip:</strong> <?php echo date('d-m-Y H:i', current_time('timestamp')); ?>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="<?php echo esc_url($admin_url); ?>" class="button">
                        Bekijk in Voorraadsysteem
                    </a>
                </div>
                
                <div class="footer">
                    <p>Deze e-mail is automatisch verstuurd door het <?php echo esc_html($company_name); ?> voorraadsysteem.</p>
                    <p>Om deze meldingen uit te schakelen, ga naar de <a href="<?php echo admin_url('admin.php?page=gvs-settings'); ?>">instellingen</a>.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Log notification in database
     */
    private function log_notification($kleur_id, $shortage_info) {
        $log = get_option('gvs_notification_log', []);
        
        $log[] = [
            'timestamp' => current_time('mysql'),
            'kleur_id' => $kleur_id,
            'shortage_info' => $shortage_info
        ];
        
        // Keep only last 100 entries
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        update_option('gvs_notification_log', $log);
        update_option('gvs_last_notification_sent', current_time('mysql'));
    }
    
    /**
     * Send test email (for admin testing)
     */
    public function send_test_email() {
        check_ajax_referer('gvs_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Geen rechten']);
        }
        
        $to = get_option('gvs_warning_email', get_option('admin_email'));
        $company_name = get_option('gvs_company_name', 'Gordijnen Voorraad');
        
        $subject = sprintf('[%s] Test Waarschuwing E-mail', $company_name);
        
        $message = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #2271b1; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Test E-mail</h1>
            </div>
            <div class="content">
                <p>Dit is een test e-mail van het ' . esc_html($company_name) . ' voorraadsysteem.</p>
                <p>Als u deze e-mail ontvangt, werken de e-mail notificaties correct.</p>
                <p><strong>Ingesteld e-mailadres:</strong> ' . esc_html($to) . '</p>
                <p><strong>Verzonden op:</strong> ' . current_time('d-m-Y H:i:s') . '</p>
            </div>
        </body>
        </html>';
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $company_name . ' <' . get_option('admin_email') . '>'
        ];
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            wp_send_json_success(['message' => 'Test e-mail verzonden naar ' . $to]);
        } else {
            wp_send_json_error(['message' => 'Fout bij verzenden test e-mail']);
        }
    }
}

// Initialize the notifications system
new GVS_Stock_Notifications();