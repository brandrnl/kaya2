jQuery(document).ready(function($) {
    'use strict';
    
    // Global functions
    window.gvsConfirmDelete = function(message) {
        return confirm(message || gvs_ajax.strings.confirm_delete);
    };
    
    // Handle delete buttons with confirmation
    $(document).on('click', '.gvs-delete-btn', function(e) {
        if (!gvsConfirmDelete()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Handle AJAX forms
    $(document).on('submit', '.gvs-ajax-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('[type="submit"]');
        var originalText = $submit.text();
        
        // Disable submit and show loading
        $submit.prop('disabled', true).text(gvs_ajax.strings.loading);
        
        // Clear previous messages
        $('.gvs-message').remove();
        
        $.ajax({
            url: gvs_ajax.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&nonce=' + gvs_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $form.before('<div class="notice notice-success gvs-message"><p>' + response.data.message + '</p></div>');
                    
                    // Reset form if needed
                    if ($form.hasClass('gvs-reset-on-success')) {
                        $form[0].reset();
                    }
                    
                    // Reload page if needed
                    if ($form.hasClass('gvs-reload-on-success')) {
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                    
                    // Trigger custom event
                    $form.trigger('gvs-form-success', [response]);
                } else {
                    // Show error message
                    $form.before('<div class="notice notice-error gvs-message"><p>' + (response.data.message || gvs_ajax.strings.error) + '</p></div>');
                }
            },
            error: function() {
                $form.before('<div class="notice notice-error gvs-message"><p>' + gvs_ajax.strings.error + '</p></div>');
            },
            complete: function() {
                // Re-enable submit
                $submit.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Auto-hide messages after 5 seconds
    $(document).on('DOMNodeInserted', '.gvs-message', function() {
        var $message = $(this);
        setTimeout(function() {
            $message.fadeOut(function() {
                $message.remove();
            });
        }, 5000);
    });
    
    // Handle modal close on background click
    $(document).on('click', '.gvs-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Handle escape key for modals
    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            $('.gvs-modal').hide();
        }
    });
    
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.gvs-select2').select2({
            width: '100%'
        });
    }
    
    // Handle dependent selects
    $(document).on('change', '[data-depends-on]', function() {
        var $this = $(this);
        var dependsOn = $this.data('depends-on');
        var $dependent = $('[name="' + dependsOn + '"]');
        
        if ($dependent.val()) {
            $this.prop('disabled', false);
        } else {
            $this.prop('disabled', true).val('');
        }
    });
    
    // Print functionality
    window.gvsPrint = function(content) {
        var printWindow = window.open('', '_blank');
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.print();
    };
    
    // Export table to CSV
    window.gvsExportTable = function(tableId, filename) {
        var csv = [];
        var rows = document.querySelectorAll('#' + tableId + ' tr');
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (var j = 0; j < cols.length; j++) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
            
            csv.push(row.join(','));
        }
        
        var csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
        var downloadLink = document.createElement('a');
        downloadLink.download = filename || 'export.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    };
    
    // Handle number inputs with decimal
    $(document).on('input', 'input[type="number"][step*="."]', function() {
        var val = $(this).val();
        if (val && !isNaN(val)) {
            var decimals = $(this).attr('step').split('.')[1].length || 0;
            $(this).val(parseFloat(val).toFixed(decimals));
        }
    });
    
    // Toggle password visibility
    $(document).on('click', '.toggle-password', function() {
        var $input = $($(this).data('target'));
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).find('span').toggleClass('dashicons-visibility dashicons-hidden');
    });
    
    // Sortable tables
    $('.gvs-sortable').on('click', 'th', function() {
        var table = $(this).parents('table').eq(0);
        var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()));
        this.asc = !this.asc;
        if (!this.asc) { rows = rows.reverse(); }
        for (var i = 0; i < rows.length; i++) { table.append(rows[i]); }
    });
    
    function comparer(index) {
        return function(a, b) {
            var valA = getCellValue(a, index), valB = getCellValue(b, index);
            return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB);
        };
    }
    
    function getCellValue(row, index) {
        return $(row).children('td').eq(index).text();
    }
    
    // Bulk actions
    $('#gvs-bulk-action').on('change', function() {
        var action = $(this).val();
        if (action && $('.gvs-bulk-check:checked').length > 0) {
            if (action === 'delete' && !gvsConfirmDelete()) {
                $(this).val('');
                return;
            }
            $('#gvs-bulk-form').submit();
        }
    });
    
    // Check all checkboxes
    $('#gvs-check-all').on('change', function() {
        $('.gvs-bulk-check').prop('checked', $(this).prop('checked'));
    });
    
    // Update check all status
    $(document).on('change', '.gvs-bulk-check', function() {
        var total = $('.gvs-bulk-check').length;
        var checked = $('.gvs-bulk-check:checked').length;
        $('#gvs-check-all').prop('checked', total === checked);
    });
    
    // Live search
    $('#gvs-live-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#gvs-search-table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Tooltips
    if ($.fn.tooltip) {
        $('.gvs-tooltip').tooltip();
    }
    
    // Date picker
    if ($.fn.datepicker) {
        $('.gvs-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Color picker
    if ($.fn.wpColorPicker) {
        $('.gvs-color-picker').wpColorPicker();
    }
    
    // Media uploader
    $(document).on('click', '.gvs-upload-button', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $input = $($button.data('target'));
        
        var mediaUploader = wp.media({
            title: 'Selecteer bestand',
            button: {
                text: 'Gebruik dit bestand'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
            if ($button.data('preview')) {
                $($button.data('preview')).attr('src', attachment.url);
            }
        });
        
        mediaUploader.open();
    });
    
    // Tab navigation
    $('.gvs-tabs').on('click', '.gvs-tab', function(e) {
        e.preventDefault();
        var $tab = $(this);
        var target = $tab.attr('href');
        
        // Update active states
        $tab.siblings().removeClass('active');
        $tab.addClass('active');
        
        // Show/hide content
        $(target).siblings('.gvs-tab-content').hide();
        $(target).show();
        
        // Save state
        if (window.localStorage) {
            localStorage.setItem('gvs_active_tab_' + $tab.parent().attr('id'), target);
        }
    });
    
    // Restore tab state
    $('.gvs-tabs').each(function() {
        var $tabs = $(this);
        var tabId = $tabs.attr('id');
        if (window.localStorage && tabId) {
            var activeTab = localStorage.getItem('gvs_active_tab_' + tabId);
            if (activeTab && $(activeTab).length) {
                $tabs.find('[href="' + activeTab + '"]').click();
            }
        }
    });
    
    // Ajax loading indicator
    $(document).ajaxStart(function() {
        $('body').addClass('gvs-loading');
    }).ajaxStop(function() {
        $('body').removeClass('gvs-loading');
    });
    
    // Smooth scroll to anchors
    $('a[href^="#"]:not([href="#"])').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
});