<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title><?php _e('Gordijnen Voorraad Scanner', 'gordijnen-voorraad'); ?></title>
    <?php wp_head(); ?>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f2f5;
            color: #333;
            overflow-x: hidden;
        }
        
        .gvs-mobile-header {
            background: #2271b1;
            color: white;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .gvs-mobile-header h1 {
            font-size: 20px;
            font-weight: 500;
            margin: 0;
        }
        
        .gvs-mobile-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .gvs-scanner-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        #qr-reader video {
            border-radius: 8px;
        }
        
        .gvs-scan-result {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: none;
        }
        
        .gvs-scan-result.show {
            display: block;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .gvs-result-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .gvs-result-icon {
            width: 50px;
            height: 50px;
            background: #4ade80;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .gvs-result-icon svg {
            width: 30px;
            height: 30px;
            fill: white;
        }
        
        .gvs-result-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .gvs-result-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .gvs-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .gvs-detail-row:last-child {
            border-bottom: none;
        }
        
        .gvs-detail-label {
            font-weight: 500;
            color: #666;
        }
        
        .gvs-detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .gvs-actions {
            display: flex;
            gap: 10px;
        }
        
        .gvs-btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .gvs-btn-primary {
            background: #dc2626;
            color: white;
        }
        
        .gvs-btn-primary:hover {
            background: #b91c1c;
        }
        
        .gvs-btn-secondary {
            background: #e5e7eb;
            color: #333;
        }
        
        .gvs-btn-secondary:hover {
            background: #d1d5db;
        }
        
        .gvs-scanner-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .gvs-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .gvs-message.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .gvs-message.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .gvs-message.info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .gvs-loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2271b1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .gvs-user-info {
            text-align: right;
            font-size: 14px;
            color: rgba(255,255,255,0.8);
            margin-top: 5px;
        }
        
        @media (max-width: 480px) {
            .gvs-mobile-container {
                padding: 15px;
            }
            
            .gvs-scanner-section {
                padding: 15px;
            }
            
            .gvs-btn {
                font-size: 14px;
                padding: 10px 20px;
            }
        }
        
        /* QR Scanner specific styles */
        #qr-reader__scan_region {
            background: transparent !important;
        }
        
        #qr-reader__dashboard_section_swaplink {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="gvs-mobile-header">
        <h1><?php _e('Gordijnen Voorraad Scanner', 'gordijnen-voorraad'); ?></h1>
        <div class="gvs-user-info">
            <?php 
            $current_user = wp_get_current_user();
            echo sprintf(__('Ingelogd als: %s', 'gordijnen-voorraad'), esc_html($current_user->display_name));
            ?>
        </div>
    </div>
    
    <div class="gvs-mobile-container">
        <div id="gvs-messages"></div>
        
        <div class="gvs-scanner-section">
            <div class="gvs-scanner-controls">
                <button id="start-scan" class="gvs-btn gvs-btn-primary" style="flex: 1;">
                    <?php _e('Start Scanner', 'gordijnen-voorraad'); ?>
                </button>
                <button id="stop-scan" class="gvs-btn gvs-btn-secondary" style="display: none; flex: 1;">
                    <?php _e('Stop Scanner', 'gordijnen-voorraad'); ?>
                </button>
            </div>
            
            <div id="qr-reader"></div>
        </div>
        
        <div id="scan-result" class="gvs-scan-result">
            <div class="gvs-result-header">
                <div class="gvs-result-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </div>
                <div class="gvs-result-title"><?php _e('Rol Gevonden', 'gordijnen-voorraad'); ?></div>
            </div>
            
            <div class="gvs-result-details">
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('QR Code:', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-qr"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Collectie:', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-collectie"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Kleur:', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-kleur"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Meters:', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-meters"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Locatie:', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-locatie"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Datum:', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-datum"></span>
                </div>
            </div>
            
            <div class="gvs-actions">
                <button id="uitgeven-btn" class="gvs-btn gvs-btn-primary">
                    <?php _e('Rol Uitgeven', 'gordijnen-voorraad'); ?>
                </button>
                <button id="new-scan-btn" class="gvs-btn gvs-btn-secondary">
                    <?php _e('Nieuwe Scan', 'gordijnen-voorraad'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>