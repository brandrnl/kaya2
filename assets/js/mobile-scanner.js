jQuery(document).ready(function($) {
    'use strict';
    
    // Debug logging
    function log(msg) {
        console.log('[GVS Scanner] ' + msg);
        if (window.debugLog) window.debugLog(msg);
    }
    
    // Globale variabelen
    let scanner = null;
    let isScanning = false;
    let currentRolId = null;
    
    // Sound effect - Authentiek barcode scanner geluid
    function playBeep() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const currentTime = audioContext.currentTime;
            
            // Creëer de klassieke "BEEP" van een barcode scanner
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            // Bandpass filter voor het typische scanner geluid
            const filter = audioContext.createBiquadFilter();
            filter.type = 'bandpass';
            filter.frequency.value = 1800;
            filter.Q.value = 15;
            
            // Verbindingen
            oscillator.connect(filter);
            filter.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            // Gebruik sawtooth voor rijker geluid met harmonischen
            oscillator.type = 'sawtooth';
            oscillator.frequency.setValueAtTime(1800, currentTime);
            
            // Snelle attack, steady volume, snelle release - precies zoals echte scanners
            gainNode.gain.setValueAtTime(0, currentTime);
            gainNode.gain.linearRampToValueAtTime(0.6, currentTime + 0.005); // 5ms attack
            gainNode.gain.setValueAtTime(0.6, currentTime + 0.070); // Hold voor 65ms
            gainNode.gain.linearRampToValueAtTime(0, currentTime + 0.080); // 10ms release
            
            // Start en stop - totaal 80ms (typische scanner beep duur)
            oscillator.start(currentTime);
            oscillator.stop(currentTime + 0.08);
            
            log('Scanner beep played!');
        } catch (e) {
            log('Audio error: ' + e);
        }
    }
    
    // Success sound - Bevestigingsgeluid voor uitgeven
    function playSuccessSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const currentTime = audioContext.currentTime;
            
            // Twee tonen voor een positief "ding-dong" geluid
            
            // Eerste toon
            const osc1 = audioContext.createOscillator();
            const gain1 = audioContext.createGain();
            osc1.connect(gain1);
            gain1.connect(audioContext.destination);
            
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(523.25, currentTime); // C5
            gain1.gain.setValueAtTime(0, currentTime);
            gain1.gain.linearRampToValueAtTime(0.4, currentTime + 0.01);
            gain1.gain.linearRampToValueAtTime(0.4, currentTime + 0.09);
            gain1.gain.linearRampToValueAtTime(0, currentTime + 0.1);
            
            // Tweede toon (hoger)
            const osc2 = audioContext.createOscillator();
            const gain2 = audioContext.createGain();
            osc2.connect(gain2);
            gain2.connect(audioContext.destination);
            
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(659.25, currentTime + 0.1); // E5
            gain2.gain.setValueAtTime(0, currentTime + 0.1);
            gain2.gain.linearRampToValueAtTime(0.4, currentTime + 0.11);
            gain2.gain.linearRampToValueAtTime(0.4, currentTime + 0.24);
            gain2.gain.linearRampToValueAtTime(0, currentTime + 0.25);
            
            // Start oscillators
            osc1.start(currentTime);
            osc1.stop(currentTime + 0.1);
            osc2.start(currentTime + 0.1);
            osc2.stop(currentTime + 0.25);
            
            log('Success sound played!');
        } catch (e) {
            log('Audio error: ' + e);
        }
    }
    
    // Initialiseer scanner
    function initScanner() {
        log('Initializing scanner...');
        
        // Stop eventuele bestaande scanner
        if (scanner && scanner.isScanning) {
            scanner.stop().then(() => {
                scanner = null;
                createNewScanner();
            }).catch(() => {
                scanner = null;
                createNewScanner();
            });
        } else {
            createNewScanner();
        }
    }
    
    // Maak nieuwe scanner
    function createNewScanner() {
        log('Creating new scanner instance...');
        
        // Leeg de container
        $('#qr-reader').empty();
        
        // Maak nieuwe Html5Qrcode instance
        scanner = new Html5Qrcode("qr-reader");
        
        // Start de scanner
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                let cameraId = devices[devices.length - 1].id; // Laatste camera (meestal achtercamera)
                
                // Zoek specifiek naar achtercamera
                devices.forEach(device => {
                    if (device.label.toLowerCase().includes('back') || 
                        device.label.toLowerCase().includes('rear') ||
                        device.label.toLowerCase().includes('environment')) {
                        cameraId = device.id;
                    }
                });
                
                log('Starting camera: ' + cameraId);
                
                scanner.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 }
                    },
                    onScanSuccess,
                    onScanFailure
                ).then(() => {
                    log('Scanner started successfully');
                    isScanning = true;
                    $('#start-scan').hide();
                    $('#stop-scan').show();
                    showMessage('Scanner actief...', 'info');
                }).catch(err => {
                    log('Failed to start scanner: ' + err);
                    showMessage('Kan camera niet starten', 'error');
                });
            } else {
                log('No cameras found');
                showMessage('Geen camera gevonden', 'error');
            }
        }).catch(err => {
            log('Camera error: ' + err);
            showMessage('Camera fout', 'error');
        });
    }
    
    // Scan success
    function onScanSuccess(decodedText, decodedResult) {
        log('Scan success: ' + decodedText);
        
        if (!isScanning) return;
        
        // Stop scanning
        isScanning = false;
        
        // DIRECT feedback - geluid en vibratie
        playBeep();
        if (navigator.vibrate) navigator.vibrate(200);
        
        // Stop de scanner
        scanner.stop().then(() => {
            log('Scanner stopped after scan');
            $('#qr-reader').empty();
            $('#start-scan').show();
            $('#stop-scan').hide();
        }).catch(err => {
            log('Error stopping scanner: ' + err);
        });
        
        // Verwerk QR code
        processQRCode(decodedText);
    }
    
    // Scan failure (wordt constant aangeroepen, negeer)
    function onScanFailure(error) {
        // Negeer
    }
    
    // Verwerk QR code
    function processQRCode(qrCode) {
        log('Processing QR: ' + qrCode);
        showMessage('<span class="gvs-loader"></span>QR code verwerken...', 'info');
        
        $.ajax({
            url: gvs_mobile.ajax_url,
            type: 'POST',
            data: {
                action: 'gvs_scan_qr',
                qr_code: qrCode,
                nonce: gvs_mobile.nonce
            },
            success: function(response) {
                log('Scan response: ' + JSON.stringify(response));
                if (response.success) {
                    displayRolInfo(response.data.rol);
                } else {
                    showMessage(response.data.message || 'Rol niet gevonden', 'error');
                    setTimeout(() => {
                        initScanner();
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                log('Scan error: ' + status + ' - ' + error);
                showMessage('Verbindingsfout', 'error');
                setTimeout(() => {
                    initScanner();
                }, 2000);
            }
        });
    }
    
    // Toon rol info
    function displayRolInfo(rol) {
        log('Display rol: ' + JSON.stringify(rol));
        
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
    
    // Toon bericht
    function showMessage(message, type) {
        $('#gvs-messages').html('<div class="gvs-message ' + type + '">' + message + '</div>');
    }
    
    // Wis berichten
    function clearMessages() {
        $('#gvs-messages').empty();
    }
    
    // Event handlers
    
    // Start scan
    $('#start-scan').on('click', function() {
        log('Start button clicked');
        initScanner();
    });
    
    // Stop scan
    $('#stop-scan').on('click', function() {
        log('Stop button clicked');
        if (scanner && isScanning) {
            isScanning = false;
            scanner.stop().then(() => {
                log('Scanner stopped');
                $('#qr-reader').empty();
                $('#start-scan').show();
                $('#stop-scan').hide();
                clearMessages();
            }).catch(err => {
                log('Error stopping: ' + err);
            });
        }
    });
    
    // Nieuwe scan
    $('#new-scan-btn').on('click', function() {
        log('New scan clicked');
        $('#scan-result').removeClass('show');
        currentRolId = null;
        clearMessages();
        
        setTimeout(() => {
            initScanner();
        }, 300);
    });
    
    // Uitgeven
    $('#uitgeven-btn').on('click', function() {
        log('Uitgeven clicked - Rol ID: ' + currentRolId);
        
        if (!currentRolId) {
            log('No rol ID!');
            return;
        }
        
        if (!confirm('Weet u zeker dat u deze rol wilt uitgeven?')) {
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).html('<span class="gvs-loader"></span>Bezig...');
        
        log('Sending delete request...');
        
        $.ajax({
            url: gvs_mobile.ajax_url,
            type: 'POST',
            data: {
                action: 'gvs_delete_rol',
                rol_id: currentRolId,
                nonce: gvs_mobile.nonce
            },
            success: function(response) {
                log('Delete response: ' + JSON.stringify(response));
                
                if (response.success) {
                    showMessage('Rol uitgegeven!', 'success');
                    $('#scan-result').removeClass('show');
                    currentRolId = null;
                    
                    // Reset de knop direct
                    $btn.prop('disabled', false).text(originalText);
                    
                    // Feedback
                    if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
                    playSuccessSound(); // Gebruik success geluid voor uitgeven
                    
                    // Start nieuwe scan
                    setTimeout(() => {
                        initScanner();
                    }, 1500);
                } else {
                    showMessage(response.data.message || 'Fout bij uitgeven', 'error');
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                log('Delete error: ' + status + ' - ' + error);
                log('Response: ' + xhr.responseText);
                showMessage('Verbindingsfout', 'error');
                $btn.prop('disabled', false).text(originalText);
            },
            complete: function() {
                // Zorg ervoor dat de knop ALTIJD wordt gereset
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Log belangrijke info bij start
    log('Mobile scanner loaded');
    log('AJAX URL: ' + gvs_mobile.ajax_url);
    log('User logged in: ' + gvs_mobile.is_logged_in);
});