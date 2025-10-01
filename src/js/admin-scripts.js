/**
 * Admin JavaScript for Find Duplicate Images Plugin
 */

(function($) {
    'use strict';

    let scanInProgress = false;
    let stopRequested = false;

    $(document).ready(function() {
        
        /**
         * Start Scan Button
         */
        $('#fdi-start-scan').on('click', function(e) {
            e.preventDefault();
            
            if (scanInProgress) {
                alert('A scan is already in progress. Please wait for it to complete.');
                return;
            }
            
            const isResume = $(this).data('resume') === 'true';
            
            const confirmed = confirm(
                isResume 
                    ? 'This will resume the scan from where it was stopped.\n\nContinue?'
                    : 'This will scan your entire media library for duplicate images.\n\n' +
                      'For large libraries (100k+ images), this may take several minutes.\n\n' +
                      'You can stop the scan at any time and resume later.\n\nContinue?'
            );
            
            if (!confirmed) return;
            
            startScan(isResume);
        });
        
        /**
         * Stop Scan Button
         */
        $('#fdi-stop-scan').on('click', function(e) {
            e.preventDefault();
            
            if (!scanInProgress) {
                return;
            }
            
            const confirmed = confirm(
                'Are you sure you want to stop the scan?\n\n' +
                'Partial results will be saved and you can resume later.'
            );
            
            if (!confirmed) return;
            
            stopScan();
        });
        
        /**
         * Clear Cache Button
         */
        $('#fdi-clear-cache').on('click', function(e) {
            e.preventDefault();
            
            const confirmed = confirm('This will clear the cached scan results. You\'ll need to run a new scan. Continue?');
            if (!confirmed) return;
            
            clearCache();
        });
        
        /**
         * Start the scan process
         */
        function startScan(isResume = false) {
            scanInProgress = true;
            stopRequested = false;
            
            // Toggle button visibility
            $('#fdi-start-scan, #fdi-clear-cache').prop('disabled', true).hide();
            $('#fdi-stop-scan').prop('disabled', false).show();
            
            // Show progress bar
            $('#fdi-progress-container').slideDown();
            updateProgress(0, isResume ? 'Resuming scan...' : 'Initializing scan...');
            
            // Call AJAX to start scan
            $.ajax({
                url: fdiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fdi_start_scan',
                    nonce: fdiAjax.nonce,
                    resume: isResume ? 'true' : 'false'
                },
                success: function(response) {
                    if (response.success) {
                        const startOffset = response.data.processed || 0;
                        const total = response.data.total;
                        
                        // Update progress if resuming
                        if (response.data.resuming && startOffset > 0) {
                            const percentage = (startOffset / total) * 100;
                            updateProgress(percentage, `Resuming from ${startOffset.toLocaleString()} of ${total.toLocaleString()} images...`);
                        }
                        
                        // Start processing batches
                        processBatch(startOffset, total);
                    } else {
                        showError(response.data.message || 'Failed to start scan');
                        resetScanState();
                    }
                },
                error: function() {
                    showError('Network error. Please try again.');
                    resetScanState();
                }
            });
        }
        
        /**
         * Stop the scan process
         */
        function stopScan() {
            stopRequested = true;
            $('#fdi-stop-scan').prop('disabled', true).text('⏸️ Stopping...');
            updateProgress(null, 'Stopping scan and saving progress...');
            
            // Call AJAX to stop scan
            $.ajax({
                url: fdiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fdi_stop_scan',
                    nonce: fdiAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        updateProgress(
                            (data.processed / data.total) * 100,
                            `✓ Scan stopped. Processed ${data.processed.toLocaleString()} of ${data.total.toLocaleString()} images. Found ${data.duplicates} duplicate sets.`
                        );
                        
                        // Reload page after a moment
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showError(response.data.message || 'Failed to stop scan');
                        resetScanState();
                    }
                },
                error: function() {
                    showError('Network error while stopping scan.');
                    resetScanState();
                }
            });
        }
        
        /**
         * Process a batch of images
         */
        function processBatch(offset, total) {
            // Check if stop was requested
            if (stopRequested) {
                return; // Stop processing
            }
            
            $.ajax({
                url: fdiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fdi_process_batch',
                    nonce: fdiAjax.nonce,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        const percentage = data.percentage;
                        const processed = data.processed;
                        const totalItems = data.total;
                        
                        // Update progress
                        updateProgress(
                            percentage,
                            `Processing ${processed.toLocaleString()} of ${totalItems.toLocaleString()} images... (${percentage}%) - Found ${data.duplicates} duplicate sets`
                        );
                        
                        // Check if stop was requested before continuing
                        if (stopRequested) {
                            return;
                        }
                        
                        // Continue with next batch or finish
                        if (data.complete) {
                            finishScan(data.duplicates);
                        } else {
                            // Process next batch
                            processBatch(processed, totalItems);
                        }
                    } else {
                        showError(response.data.message || 'Error processing batch');
                        resetScanState();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Batch processing error:', error);
                    showError('Error processing images. Please try again.');
                    resetScanState();
                }
            });
        }
        
        /**
         * Finish scan and reload page
         */
        function finishScan(duplicateCount) {
            updateProgress(100, `✓ Scan complete! Found ${duplicateCount} duplicate sets.`);
            
            // Wait a moment then reload
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        }
        
        /**
         * Update progress bar and text
         */
        function updateProgress(percentage, text) {
            if (percentage !== null) {
                $('#fdi-progress-fill').css('width', percentage + '%');
            }
            $('#fdi-progress-text').text(text);
        }
        
        /**
         * Show error message
         */
        function showError(message) {
            $('#fdi-progress-text').html('<span style="color:#d63638;">❌ ' + message + '</span>');
        }
        
        /**
         * Reset scan state
         */
        function resetScanState() {
            scanInProgress = false;
            stopRequested = false;
            
            // Reset button visibility
            $('#fdi-start-scan, #fdi-clear-cache').prop('disabled', false).show();
            $('#fdi-stop-scan').prop('disabled', false).hide().text('⏸️ Stop Scan');
            
            // Hide progress after delay
            setTimeout(function() {
                $('#fdi-progress-container').slideUp();
            }, 3000);
        }
        
        /**
         * Clear cache
         */
        function clearCache() {
            $.ajax({
                url: fdiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fdi_clear_cache',
                    nonce: fdiAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert('Error clearing cache: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                }
            });
        }

        /**
         * Handle pagination page number input
         */
        $('.current-page').on('change', function() {
            const pageNum = parseInt($(this).val());
            const maxPages = parseInt($(this).attr('max'));
            const baseUrl = $(this).data('base-url');

            // Validate page number
            if (pageNum >= 1 && pageNum <= maxPages) {
                window.location.href = baseUrl + '&paged=' + pageNum;
            } else {
                // Reset to current page if invalid
                $(this).val($(this).attr('value'));
                alert('Please enter a valid page number between 1 and ' + maxPages);
            }
        });

        /**
         * Handle Enter key on pagination input
         */
        $('.current-page').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $(this).trigger('change');
            }
        });

        /**
         * Confirm deletion with detailed message
         */
        $('form[action*="delete_orphan_duplicates"]').on('submit', function(e) {
            const orphanCount = $(this).find('button[type="submit"]').text().match(/\d+/);
            if (orphanCount) {
                const confirmed = confirm(
                    'Are you sure you want to permanently delete ' + orphanCount[0] + ' orphan image(s)?\n\n' +
                    'This action cannot be undone. Only images not attached to any content will be deleted.'
                );
                
                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        /**
         * Add loading state to delete buttons
         */
        $('form[action*="delete_orphan_duplicates"]').on('submit', function() {
            const $button = $(this).find('button[type="submit"]');
            $button.prop('disabled', true).text('Deleting...');
        });

    });

})(jQuery);
