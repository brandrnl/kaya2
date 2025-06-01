jQuery(document).ready(function($) {
    'use strict';
    
    let html5QrcodeScanner = null;
    let lastScannedCode = null;
    let currentRolId = null;
    
    // Success scan sound
    function playSuccessSound() {
        // Create audio context for beep sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        
        // Create oscillator for beep
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Configure the beep sound
        oscillator.frequency.value = 1000; // High pitch beep
        oscillator.type = 'sine';
        
        // Fade in and out for smooth sound
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.01);
        gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.1);
        
        // Play the beep
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
        
        // Second beep after 150ms
        setTimeout(() => {
            const oscillator2 = audioContext.createOscillator();
            const gainNode2 = audioContext.createGain();
            
            oscillator2.connect(gainNode2);
            gainNode2.connect(audioContext.destination);
            
            oscillator2.frequency.value = 1500; // Higher pitch for second beep
            oscillator2.type = 'sine';
            
            gainNode2.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode2.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.01);
            gainNode2.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.1);
            
            oscillator2.start(audioContext.currentTime);
            oscillator2.stop(audioContext.currentTime + 0.1);
        }, 150);
    }
    
    // Wait for splash screen to disappear
    setTimeout(function() {
        console.log('Mobile scanner ready');
    }, 3500);
    
    // Initialize scanner with back camera only
    function initScanner() {
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
            // Force gebruik van achtercamera
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true,
            defaultZoomValueIfSupported: 1.5
        };
        
        html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", config);
    }
    
    // Start scanning
    function startScanning() {
        // Check if first time (camera permission not yet granted)
        if (!localStorage.getItem('gvs_camera_permission_shown')) {
            showMessage(
                '<strong>Camera toegang vereist</strong><br>' +
                'Om QR codes te scannen heeft deze app toegang tot uw camera nodig. ' +
                'Klik op "Toestaan" wanneer uw browser om toestemming vraagt.',
                'info'
            );
            localStorage.setItem('gvs_camera_permission_shown', 'true');
        }
        
        if (html5QrcodeScanner) {
            // Custom success and error handlers
            const onScanSuccess = function(decodedText, decodedResult) {
                onScanSuccessHandler(decodedText, decodedResult);
            };
            
            const onScanError = function(errorMessage) {
                // Check for specific permission errors
                if (errorMessage.includes('NotAllowedError')) {
                    showMessage('Camera toegang geweigerd. Controleer uw browser instellingen.', 'error');
                    stopScanning();
                } else if (errorMessage.includes('NotFoundError')) {
                    showMessage('Geen camera gevonden op dit apparaat.', 'error');
                    stopScanning();
                }
                // Ignore other errors - they happen frequently during scanning
            };
            
            // Override render to use back camera
            html5QrcodeScanner.render(onScanSuccess, onScanError);
            
            // Force select back camera after render
            setTimeout(function() {
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length > 0) {
                        // Find back camera
                        let backCameraId = null;
                        
                        devices.forEach(device => {
                            if (device.label.toLowerCase().includes('back') || 
                                device.label.toLowerCase().includes('rear') ||
                                device.label.toLowerCase().includes('environment')) {
                                backCameraId = device.id;
                            }
                        });
                        
                        // If no back camera found, use last camera (usually back on mobile)
                        if (!backCameraId && devices.length > 1) {
                            backCameraId = devices[devices.length - 1].id;
                        }
                        
                        // If we found a back camera, restart with it
                        if (backCameraId) {
                            html5QrcodeScanner.clear();
                            
                            const html5Qrcode = new Html5Qrcode("qr-reader");
                            html5Qrcode.start(
                                backCameraId,
                                {
                                    fps: 10,
                                    qrbox: { width: 250, height: 250 }
                                },
                                onScanSuccess,
                                onScanError
                            ).then(() => {
                                $('#start-scan').hide();
                                $('#stop-scan').show();
                                showMessage(gvs_mobile.strings.scanning, 'info');
                                
                                // Store instance for stopping later
                                window.html5QrcodeInstance = html5Qrcode;
                            }).catch(err => {
                                console.error('Failed to start with back camera:', err);
                                showMessage('Kan camera niet starten. Probeer opnieuw.', 'error');
                            });
                        }
                    }
                }).catch(err => {
                    console.error('Unable to get cameras:', err);
                    showMessage('Kan geen camera\'s vinden op dit apparaat.', 'error');
                });
            }, 500);
            
            $('#start-scan').hide();
            $('#stop-scan').show();
            showMessage(gvs_mobile.strings.scanning, 'info');
        }
    }
    
    // Stop scanning
    function stopScanning() {
        if (window.html5QrcodeInstance) {
            window.html5QrcodeInstance.stop().then(() => {
                window.html5QrcodeInstance.clear();
                window.html5QrcodeInstance = null;
            }).catch(err => {
                console.error('Error stopping scanner:', err);
            });
        } else if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
        }
        
        $('#start-scan').show();
        $('#stop-scan').hide();
        clearMessages();
    }
    
    // On successful scan
    function onScanSuccessHandler(decodedText, decodedResult) {
        // Prevent duplicate scans
        if (decodedText === lastScannedCode) {
            return;
        }
        
        lastScannedCode = decodedText;
        
        // Vibrate if available
        if (navigator.vibrate) {
            navigator.vibrate(200);
        }
        
        // Play success sound
        try {
            playSuccessSound();
        } catch (e) {
            console.log('Could not play sound:', e);
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
                    
                    // Play success sound for deletion
                    try {
                        playSuccessSound();
                    } catch (e) {
                        console.log('Could not play sound:', e);
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