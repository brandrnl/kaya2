jQuery(document).ready(function($) {
    'use strict';
    
    let html5QrcodeScanner = null;
    let lastScannedCode = null;
    let currentRolId = null;
    
    // Initialize scanner
    function initScanner() {
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
        };
        
        html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", config);
    }
    
    // Start scanning
    function startScanning() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.render(onScanSuccess, onScanError);
            $('#start-scan').hide();
            $('#stop-scan').show();
            showMessage(gvs_mobile.strings.scanning, 'info');
        }
    }
    
    // Stop scanning
    function stopScanning() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
            $('#start-scan').show();
            $('#stop-scan').hide();
            clearMessages();
        }
    }
    
    // On successful scan
    function onScanSuccess(decodedText, decodedResult) {
        // Prevent duplicate scans
        if (decodedText === lastScannedCode) {
            return;
        }
        
        lastScannedCode = decodedText;
        
        // Vibrate if available
        if (navigator.vibrate) {
            navigator.vibrate(200);
        }
        
        // Stop scanner
        stopScanning();
        
        // Show loading
        showMessage('<span class="gvs-loader"></span>' + gvs_mobile.strings.scan_success, 'success');
        
        // Lookup QR code
        $.ajax({
            url: gvs_mobile.ajax_url,
            type: 'POST',
            data: {
                action: 'gvs_scan_qr',
                qr_code: decodedText,
                nonce: gvs_mobile.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayRolInfo(response.data.rol);
                } else {
                    showMessage(response.data.message || gvs_mobile.strings.not_found, 'error');
                    setTimeout(function() {
                        $('#new-scan-btn').click();
                    }, 2000);
                }
            },
            error: function() {
                showMessage(gvs_mobile.strings.error, 'error');
                setTimeout(function() {
                    $('#new-scan-btn').click();
                }, 2000);
            }
        });
    }
    
    // On scan error
    function onScanError(errorMessage) {
        // Ignore errors - they happen frequently during scanning
    }
    
    // Display rol information
    function displayRolInfo(rol) {
        currentRolId = rol.id;
        
        $('#result-qr').text(rol.qr_code);
        $('#result-collectie').text(rol.collectie);
        $('#result-kleur').text(rol.kleur);
        $('#result-meters').text(rol.meters + ' m');
        $('#result-locatie').text(rol.locatie);
        $('#result-datum').text(new Date(rol.created_at).toLocaleDateString('nl-NL'));
        
        $('#scan-result').addClass('show');
        clearMessages();
    }
    
    // Show message
    function showMessage(message, type) {
        const html = '<div class="gvs-message ' + type + '">' + message + '</div>';
        $('#gvs-messages').html(html);
    }
    
    // Clear messages
    function clearMessages() {
        $('#gvs-messages').empty();
    }
    
    // Handle start scan button
    $('#start-scan').on('click', function() {
        startScanning();
    });
    
    // Handle stop scan button
    $('#stop-scan').on('click', function() {
        stopScanning();
    });
    
    // Handle new scan button
    $('#new-scan-btn').on('click', function() {
        $('#scan-result').removeClass('show');
        currentRolId = null;
        lastScannedCode = null;
        setTimeout(function() {
            startScanning();
        }, 300);
    });
    
    // Handle uitgeven button
    $('#uitgeven-btn').on('click', function() {
        if (!currentRolId) {
            return;
        }
        
        if (!confirm(gvs_mobile.strings.confirm_delete)) {
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).html('<span class="gvs-loader"></span> ' + gvs_mobile.strings.scanning);
        
        $.ajax({
            url: gvs_mobile.ajax_url,
            type: 'POST',
            data: {
                action: 'gvs_delete_rol',
                rol_id: currentRolId,
                nonce: gvs_mobile.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(gvs_mobile.strings.deleted, 'success');
                    $('#scan-result').removeClass('show');
                    currentRolId = null;
                    lastScannedCode = null;
                    
                    // Vibrate success pattern
                    if (navigator.vibrate) {
                        navigator.vibrate([100, 50, 100]);
                    }
                    
                    setTimeout(function() {
                        startScanning();
                    }, 1500);
                } else {
                    showMessage(response.data.message || gvs_mobile.strings.error, 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showMessage(gvs_mobile.strings.error, 'error');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Initialize on load
    initScanner();
    
    // PWA installation
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install button if not already installed
        if (!window.matchMedia('(display-mode: standalone)').matches) {
            showInstallPrompt();
        }
    });
    
    function showInstallPrompt() {
        const installBtn = $('<button class="gvs-btn gvs-btn-primary" style="margin-bottom: 20px; width: 100%;">Installeer als App</button>');
        
        installBtn.on('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                
                if (outcome === 'accepted') {
                    installBtn.remove();
                }
                
                deferredPrompt = null;
            }
        });
        
        $('.gvs-scanner-section').before(installBtn);
    }
    
    // Handle back button
    window.addEventListener('popstate', function(e) {
        if ($('#scan-result').hasClass('show')) {
            e.preventDefault();
            $('#new-scan-btn').click();
        }
    });
    
    // Prevent accidental navigation
    $(window).on('beforeunload', function() {
        if (html5QrcodeScanner && $('#stop-scan').is(':visible')) {
            return 'Scanner is actief. Weet u zeker dat u wilt verlaten?';
        }
    });
});