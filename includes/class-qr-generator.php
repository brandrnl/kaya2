<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_QR_Generator {
    
    /**
     * Generate QR code URL using external API
     */
    public static function generate_url($data, $size = 300) {
        $api_url = 'https://api.qrserver.com/v1/create-qr-code/';
        
        $params = [
            'data' => $data,
            'size' => $size . 'x' . $size,
            'margin' => 10,
            'format' => 'png'
        ];
        
        return add_query_arg($params, $api_url);
    }
    
    /**
     * Save QR code image locally (fallback)
     */
    public static function save_locally($data, $filename = null) {
        // Get QR image from API
        $url = self::generate_url($data);
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'sslverify' => false
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        if (empty($image_data)) {
            return false;
        }
        
        // Generate filename if not provided
        if (!$filename) {
            $filename = 'qr_' . sanitize_file_name($data) . '.png';
        }
        
        // Save to uploads directory
        $upload_dir = wp_upload_dir();
        $qr_dir = $upload_dir['basedir'] . '/gvs-qr-codes';
        
        // Create directory if not exists
        if (!file_exists($qr_dir)) {
            wp_mkdir_p($qr_dir);
        }
        
        $file_path = $qr_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/gvs-qr-codes/' . $filename;
        
        // Save file
        $result = file_put_contents($file_path, $image_data);
        
        return $result !== false ? $file_url : false;
    }
    
    /**
     * Generate printable QR label HTML
     */
    public static function generate_label_html($rol_data) {
        $qr_url = self::generate_url($rol_data->qr_code, 300);
        
        $html = '
        <div class="gvs-qr-label" style="width: 10cm; height: 12cm; padding: 20px; border: 2px solid #000; text-align: center; page-break-inside: avoid; margin: 10px auto; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <div style="border-bottom: 2px solid #2271b1; padding-bottom: 15px; margin-bottom: 20px;">
                <h2 style="margin: 0; font-size: 24px; color: #2271b1; font-family: Arial, sans-serif;">' . esc_html(get_option('gvs_company_name', 'Gordijnen Voorraad')) . '</h2>
            </div>
            <img src="' . esc_url($qr_url) . '" alt="QR Code" style="width: 300px; height: 300px; margin: 20px auto; display: block;">
            <div style="margin-top: 20px; font-family: Arial, sans-serif;">
                <div style="font-size: 20px; font-weight: bold; margin-bottom: 15px; color: #000;">' . esc_html($rol_data->qr_code) . '</div>
                <div style="font-size: 18px; margin-bottom: 8px; color: #333;">
                    <strong>Collectie:</strong> ' . esc_html($rol_data->collectie_naam) . '
                </div>
                <div style="font-size: 18px; margin-bottom: 8px; color: #333;">
                    <strong>Kleur:</strong> ' . esc_html($rol_data->kleur_naam) . '
                </div>
                <div style="font-size: 18px; margin-bottom: 8px; color: #333;">
                    <strong>Meters:</strong> ' . esc_html($rol_data->meters) . ' m
                </div>
                <div style="font-size: 18px; margin-bottom: 8px; color: #333;">
                    <strong>Locatie:</strong> ' . esc_html($rol_data->locatie) . '
                </div>
                <div style="font-size: 14px; color: #666; margin-top: 15px;">
                    ' . date('d-m-Y H:i') . '
                </div>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Generate bulk print page
     */
    public static function generate_bulk_print($rollen) {
        $company_name = get_option('gvs_company_name', 'Gordijnen Voorraad');
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>QR Codes - ' . esc_html($company_name) . '</title>
            <style>
                @page { 
                    size: A4;
                    margin: 0;
                }
                
                * {
                    box-sizing: border-box;
                }
                
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 0;
                    background: #fff;
                }
                
                .page {
                    width: 210mm;
                    height: 297mm;
                    padding: 20mm;
                    margin: 0 auto;
                    background: white;
                    page-break-after: always;
                    position: relative;
                }
                
                .page:last-child {
                    page-break-after: auto;
                }
                
                .qr-label-full {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    text-align: center;
                    border: 3px solid #2271b1;
                    border-radius: 10px;
                    padding: 40px;
                }
                
                .company-header {
                    font-size: 36px;
                    color: #2271b1;
                    font-weight: bold;
                    margin-bottom: 40px;
                    padding-bottom: 20px;
                    border-bottom: 3px solid #2271b1;
                    width: 100%;
                }
                
                .qr-code-container {
                    margin: 40px 0;
                }
                
                .qr-code-container img {
                    width: 400px;
                    height: 400px;
                    padding: 20px;
                    border: 2px solid #ddd;
                    background: white;
                }
                
                .qr-info {
                    margin-top: 40px;
                    font-size: 24px;
                    line-height: 1.8;
                }
                
                .qr-code-text {
                    font-size: 32px;
                    font-weight: bold;
                    color: #000;
                    margin: 30px 0;
                    padding: 15px 30px;
                    background: #f0f0f0;
                    border-radius: 5px;
                }
                
                .info-row {
                    margin: 15px 0;
                    display: flex;
                    justify-content: center;
                    gap: 10px;
                }
                
                .info-label {
                    font-weight: bold;
                    color: #666;
                }
                
                .info-value {
                    color: #333;
                }
                
                .date-footer {
                    position: absolute;
                    bottom: 20mm;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-size: 14px;
                    color: #999;
                }
                
                @media screen {
                    body {
                        background: #f5f5f5;
                    }
                    
                    .page {
                        margin: 20px auto;
                        box-shadow: 0 0 20px rgba(0,0,0,0.1);
                    }
                    
                    .print-button {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 15px 30px;
                        background: #2271b1;
                        color: white;
                        border: none;
                        border-radius: 5px;
                        font-size: 18px;
                        cursor: pointer;
                        z-index: 1000;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
                    }
                    
                    .print-button:hover {
                        background: #135e96;
                    }
                }
                
                @media print {
                    .print-button {
                        display: none;
                    }
                    
                    .page {
                        margin: 0;
                        box-shadow: none;
                    }
                }
            </style>
        </head>
        <body>
            <button class="print-button" onclick="window.print()">🖨️ Print Alle QR Codes</button>';
        
        foreach ($rollen as $rol) {
            $qr_url = self::generate_url($rol->get_qr_code(), 400);
            
            // Get additional info
            $kleur = GVS_Kleur::get_by_id($rol->get_kleur_id());
            $collectie = GVS_Collectie::get_by_id($kleur->get_collectie_id());
            
            $html .= '
            <div class="page">
                <div class="qr-label-full">
                    <div class="company-header">' . esc_html($company_name) . '</div>
                    
                    <div class="qr-code-container">
                        <img src="' . esc_url($qr_url) . '" alt="QR Code">
                    </div>
                    
                    <div class="qr-code-text">' . esc_html($rol->get_qr_code()) . '</div>
                    
                    <div class="qr-info">
                        <div class="info-row">
                            <span class="info-label">Collectie:</span>
                            <span class="info-value">' . esc_html($collectie->get_naam()) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kleur:</span>
                            <span class="info-value">' . esc_html($kleur->get_kleur_naam()) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Meters:</span>
                            <span class="info-value">' . esc_html($rol->get_meters()) . ' m</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Locatie:</span>
                            <span class="info-value">' . esc_html($rol->get_locatie()) . '</span>
                        </div>
                    </div>
                    
                    <div class="date-footer">
                        Geprint op: ' . date('d-m-Y H:i') . '
                    </div>
                </div>
            </div>';
        }
        
        $html .= '
            <script>
                window.onload = function() {
                    // Auto print after 0.5 second
                    setTimeout(function() {
                        window.print();
                    }, 500);
                }
            </script>
        </body>
        </html>';
        
        return $html;
    }
}