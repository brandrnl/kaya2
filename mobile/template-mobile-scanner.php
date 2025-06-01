<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title><?php _e('Kaya Scanner', 'gordijnen-voorraad'); ?></title>
    <?php wp_head(); ?>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #000;
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Splash Screen */
        .gvs-splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }
        
        .gvs-splash-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        
        .gvs-splash-logo {
            width: 200px;
            height: auto;
            animation: fadeInScale 1s ease-out;
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Main App */
        .gvs-mobile-header {
            background: #000;
            color: #fff;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #333;
        }
        
        .gvs-mobile-header h1 {
            font-size: 18px;
            font-weight: 300;
            margin: 0;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .gvs-mobile-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }
        /* Verberg originele Engelse tekst en voeg Nederlandse tekst toe */
#html5-qrcode-button-camera-permission {
    font-size: 0 !important;
}

#html5-qrcode-button-camera-permission:after {
    content: 'Camera Toestemming Vragen';
    font-size: 16px;
}

#html5-qrcode-button-camera-start {
    font-size: 0 !important;
}

#html5-qrcode-button-camera-start:after {
    content: 'Start Scannen';
    font-size: 16px;
}

#html5-qrcode-button-camera-stop {
    font-size: 0 !important;
}

#html5-qrcode-button-camera-stop:after {
    content: 'Stop Scannen';
    font-size: 16px;
}
        .gvs-scanner-section {
            background: #fff;
            border-radius: 0;
            padding: 20px;
            margin-bottom: 20px;
            color: #000;
        }
        
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }
        
        #qr-reader video {
            border-radius: 8px;
        }
        
        /* Verberg camera selector */
        #qr-reader__camera_selection {
            display: none !important;
        }
        
        #qr-reader__dashboard_section_csr button {
            background: #000 !important;
            color: #fff !important;
            border: none !important;
            padding: 12px 24px !important;
            border-radius: 0 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            font-weight: 300 !important;
            width: 100% !important;
            margin: 10px 0 !important;
        }
        
        #qr-reader__dashboard_section_csr button:hover {
            background: #333 !important;
        }
        
        .gvs-scan-result {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            color: #000;
            display: none;
            border: 1px solid #000;
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
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #000;
        }
        
        .gvs-result-icon {
            width: 40px;
            height: 40px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .gvs-result-icon svg {
            width: 24px;
            height: 24px;
            fill: white;
        }
        
        .gvs-result-title {
            font-size: 16px;
            font-weight: 300;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .gvs-result-details {
            background: #f8f8f8;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #000;
        }
        
        .gvs-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .gvs-detail-row:last-child {
            border-bottom: none;
        }
        
        .gvs-detail-label {
            font-weight: 300;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
        }
        
        .gvs-detail-value {
            font-weight: 500;
            color: #000;
        }
        
        .gvs-actions {
            display: flex;
            gap: 10px;
        }
        
        .gvs-btn {
            flex: 1;
            padding: 15px 24px;
            border: 1px solid #000;
            font-size: 14px;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .gvs-btn-primary {
            background: #000;
            color: #fff;
        }
        
        .gvs-btn-primary:hover {
            background: #333;
        }
        
        .gvs-btn-secondary {
            background: #fff;
            color: #000;
        }
        
        .gvs-btn-secondary:hover {
            background: #f0f0f0;
        }
        
        .gvs-scanner-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .gvs-message {
            padding: 15px;
            margin-bottom: 20px;
            font-weight: 300;
            border: 1px solid;
        }
        
        .gvs-message.success {
            background: #fff;
            color: #000;
            border-color: #000;
        }
        
        .gvs-message.error {
            background: #000;
            color: #fff;
            border-color: #000;
        }
        
        .gvs-message.info {
            background: #f8f8f8;
            color: #000;
            border-color: #000;
        }
        
        .gvs-loader {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .gvs-message .gvs-loader {
            border: 2px solid #000;
            border-top: 2px solid transparent;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .gvs-user-info {
            text-align: center;
            font-size: 12px;
            color: #fff;
            margin-top: 10px;
            font-weight: 300;
            letter-spacing: 0.5px;
        }
        
        /* Override Html5QrcodeScanner styles */
        #qr-reader__dashboard {
            background: transparent !important;
        }
        
        #qr-reader__dashboard_section_csr {
            text-align: center !important;
        }
        
        #qr-reader__dashboard_section_fsr {
            display: none !important;
        }
        
        #qr-reader__dashboard_section_swaplink {
            display: none !important;
        }
        
        #qr-reader__scan_region {
            background: transparent !important;
        }
        
        #qr-reader__camera_permission_button {
            background: #000 !important;
            color: #fff !important;
            border: none !important;
            padding: 15px 30px !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            font-weight: 300 !important;
        }
        
        @media (max-width: 480px) {
            .gvs-mobile-container {
                padding: 15px;
            }
            
            .gvs-scanner-section {
                padding: 15px;
            }
            
            .gvs-btn {
                font-size: 12px;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Splash Screen -->
    <div class="gvs-splash-screen" id="splashScreen">
        <img src="https://kayaexclusive.com/wp-content/uploads/2025/05/logo-wit.svg" alt="Kaya" class="gvs-splash-logo">
    </div>
    
    <!-- Main App -->
    <div class="gvs-mobile-header">
        <h1><?php _e('Kaya Scanner', 'gordijnen-voorraad'); ?></h1>
        <div class="gvs-user-info">
            <?php 
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                echo esc_html($current_user->display_name);
            } else {
                echo 'Niet ingelogd';
            }
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
                    <span class="gvs-detail-label"><?php _e('QR Code', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-qr"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Collectie', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-collectie"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Kleur', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-kleur"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Meters', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-meters"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Locatie', 'gordijnen-voorraad'); ?></span>
                    <span class="gvs-detail-value" id="result-locatie"></span>
                </div>
                <div class="gvs-detail-row">
                    <span class="gvs-detail-label"><?php _e('Datum', 'gordijnen-voorraad'); ?></span>
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
    
    <!-- Debug Info (verborgen) -->
    <div id="debug-info" style="display: none; position: fixed; bottom: 0; left: 0; right: 0; background: #000; color: #0f0; font-family: monospace; font-size: 10px; padding: 10px; max-height: 200px; overflow-y: auto;">
        <div id="debug-log"></div>
    </div>
    
    <script>
    // Debug helper
    window.debugLog = function(msg) {
        const log = document.getElementById('debug-log');
        const time = new Date().toLocaleTimeString();
        log.innerHTML = time + ': ' + msg + '<br>' + log.innerHTML;
    };
    
    // Toon debug info door 5x snel te tikken op de header
    let tapCount = 0;
    let tapTimer;
    document.querySelector('.gvs-mobile-header').addEventListener('click', function() {
        tapCount++;
        clearTimeout(tapTimer);
        tapTimer = setTimeout(() => tapCount = 0, 500);
        
        if (tapCount >= 5) {
            document.getElementById('debug-info').style.display = 
                document.getElementById('debug-info').style.display === 'none' ? 'block' : 'none';
            tapCount = 0;
        }
    });
    
    // Hide splash screen after 3 seconds
    setTimeout(function() {
        document.getElementById('splashScreen').classList.add('fade-out');
    }, 3000);
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>