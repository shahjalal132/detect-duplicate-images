/**
 * Admin JavaScript for Find Duplicate Images Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
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

