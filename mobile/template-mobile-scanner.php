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
        
        /* Splash Screen - Always visible first */
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
            z-index: 10000;
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
        
        /* Login Screen */
        .gvs-login-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease-in;
        }
        
        .gvs-login-screen.show {
            display: flex;
            opacity: 1;
        }
        
        .gvs-login-container {
            width: 90%;
            max-width: 400px;
            padding: 40px 30px;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .gvs-login-logo {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .gvs-login-logo img {
            width: 150px;
            height: auto;
        }
        
        .gvs-login-form h2 {
            text-align: center;
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 40px;
            letter-spacing: 1px;
        }
        
        .gvs-form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .gvs-form-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .gvs-form-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .gvs-form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .gvs-form-group .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            opacity: 0.5;
        }
        
        .gvs-form-group .icon svg {
            width: 100%;
            height: 100%;
            fill: #fff;
        }
        
        .gvs-login-button {
            width: 100%;
            padding: 16px;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 30px;
        }
        
        .gvs-login-button:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
        
        .gvs-login-button:active {
            transform: translateY(0);
        }
        
        .gvs-login-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .gvs-login-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #ff6b6b;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .gvs-login-error.show {
            display: block;
        }
        
        .gvs-login-footer {
            text-align: center;
            margin-top: 40px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }
        
        /* Main App (hidden initially) */
        .gvs-main-app {
            display: none;
        }
        
        .gvs-main-app.active {
            display: block;
        }
        
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
    <!-- Splash Screen (Always shows first) -->
    <div class="gvs-splash-screen" id="splashScreen">
        <img src="https://kayaexclusive.com/wp-content/uploads/2025/05/logo-wit.svg" alt="Kaya" class="gvs-splash-logo">
    </div>
    
    <!-- Login Screen -->
    <div class="gvs-login-screen" id="loginScreen">
        <div class="gvs-login-container">
            <div class="gvs-login-logo">
                <img src="https://kayaexclusive.com/wp-content/uploads/2025/05/logo-wit.svg" alt="Kaya">
            </div>
            
            <form id="gvs-login-form" class="gvs-login-form">
                <h2><?php _e('Inloggen', 'gordijnen-voorraad'); ?></h2>
                
                <div class="gvs-login-error" id="loginError"></div>
                
                <div class="gvs-form-group">
                    <span class="icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </span>
                    <input type="text" id="username" name="username" placeholder="<?php _e('Gebruikersnaam', 'gordijnen-voorraad'); ?>" required autocomplete="username">
                </div>
                
                <div class="gvs-form-group">
                    <span class="icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password" placeholder="<?php _e('Wachtwoord', 'gordijnen-voorraad'); ?>" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="gvs-login-button" id="loginButton">
                    <?php _e('INLOGGEN', 'gordijnen-voorraad'); ?>
                </button>
            </form>
            
            <div class="gvs-login-footer">
                <p><?php _e('Kaya Voorraad Scanner', 'gordijnen-voorraad'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Main App -->
    <div class="gvs-main-app" id="mainApp">
        <div class="gvs-mobile-header">
            <h1><?php _e('Kaya Scanner', 'gordijnen-voorraad'); ?></h1>
            <div class="gvs-user-info" id="userInfo"></div>
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
    </div>
    
    <?php wp_footer(); ?>
    
    <script>
    // Custom login handler
    jQuery(document).ready(function($) {
        let splashMinTime = 2000; // Minimum tijd splash screen
        let splashStartTime = Date.now();
        
        // Check if user is already logged in
        const isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
        const currentUser = <?php echo is_user_logged_in() ? json_encode(wp_get_current_user()->display_name) : 'null'; ?>;
        
        // Wait for minimum splash time
        setTimeout(function() {
            $('#splashScreen').addClass('fade-out');
            
            setTimeout(function() {
                if (isLoggedIn) {
                    // User is logged in, show main app
                    $('#userInfo').text(currentUser);
                    $('#mainApp').addClass('active');
                } else {
                    // Show login screen
                    $('#loginScreen').addClass('show');
                }
            }, 500);
        }, splashMinTime);
        
        // Handle login form
        $('#gvs-login-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $('#loginButton');
            const $error = $('#loginError');
            
            // Disable button and show loading
            $button.prop('disabled', true).html('<span class="gvs-loader"></span> <?php _e('BEZIG...', 'gordijnen-voorraad'); ?>');
            $error.removeClass('show');
            
            // Perform AJAX login
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'gvs_mobile_login',
                    username: $('#username').val(),
                    password: $('#password').val(),
                    nonce: '<?php echo wp_create_nonce('gvs_mobile_login'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Login successful
                        $('#userInfo').text(response.data.display_name);
                        $('#loginScreen').fadeOut(300, function() {
                            $('#mainApp').addClass('active');
                        });
                    } else {
                        // Show error
                        $error.text(response.data.message || '<?php _e('Ongeldige gebruikersnaam of wachtwoord', 'gordijnen-voorraad'); ?>').addClass('show');
                        $button.prop('disabled', false).text('<?php _e('INLOGGEN', 'gordijnen-voorraad'); ?>');
                    }
                },
                error: function() {
                    $error.text('<?php _e('Verbindingsfout. Probeer het opnieuw.', 'gordijnen-voorraad'); ?>').addClass('show');
                    $button.prop('disabled', false).text('<?php _e('INLOGGEN', 'gordijnen-voorraad'); ?>');
                }
            });
        });
    });
    </script>
</body>
</html>