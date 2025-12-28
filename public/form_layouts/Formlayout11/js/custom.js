/**
 * Formlayout11 - Custom JavaScript
 * Handles category card selection, nice-select initialization, and layout-specific interactions
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        
        // Initialize nice-select on all fl11 selects
        if ($.fn.niceSelect) {
            $('.fl11-select').niceSelect();
        }

        // Category card single-select functionality
        initCategoryCards();

        // Time slot selection styling
        initTimeSlots();

        // File input preview
        initFileUpload();

    });

    /**
     * Initialize category card selection
     * Single-select mode - clicking one deselects others
     */
    function initCategoryCards() {
        var $cards = $('.fl11-service-card');
        var $categorySelect = $('#categorySelect');

        $cards.on('click', function() {
            var $this = $(this);
            var categoryId = $this.data('category-id');
            var categoryName = $this.data('category-name');

            // Deselect all cards
            $cards.removeClass('selected');
            $cards.find('.fl11-card-checkbox').removeClass('checked');

            // Select clicked card
            $this.addClass('selected');
            $this.find('.fl11-card-checkbox').addClass('checked');

            // Update dropdown value
            $categorySelect.val(categoryId);
            
            // If using nice-select, update it
            if ($.fn.niceSelect) {
                $categorySelect.niceSelect('update');
            }

            // Update summary panel
            $('#summaryCategory').text(categoryName);

            // Trigger change event to load services via AJAX
            $categorySelect.trigger('change');
        });

        // Sync card selection when dropdown changes
        $categorySelect.on('change', function() {
            var selectedId = $(this).val();
            
            if (selectedId) {
                $cards.each(function() {
                    var $card = $(this);
                    if ($card.data('category-id') == selectedId) {
                        $card.addClass('selected');
                        $card.find('.fl11-card-checkbox').addClass('checked');
                        $('#summaryCategory').text($card.data('category-name'));
                    } else {
                        $card.removeClass('selected');
                        $card.find('.fl11-card-checkbox').removeClass('checked');
                    }
                });
            } else {
                // No selection - deselect all
                $cards.removeClass('selected');
                $cards.find('.fl11-card-checkbox').removeClass('checked');
                $('#summaryCategory').text('None yet');
            }
        });
    }

    /**
     * Initialize time slot selection styling
     */
    function initTimeSlots() {
        // Listen for time slot changes (they're dynamically added via AJAX)
        $(document).on('change', 'input[name="duration"]', function() {
            var selectedTime = $(this).val();
            
            // Update summary
            if (selectedTime) {
                var formattedTime = selectedTime.replace('-', ' - ');
                $('#summaryTime').text(formattedTime);
            }
        });

        // Handle time slot clicks for better UX
        $(document).on('click', '.fl11-time-slot-label', function() {
            var $radio = $(this).prev('input[type="radio"]');
            if (!$radio.prop('checked')) {
                $radio.prop('checked', true).trigger('change');
            }
        });
    }

    /**
     * Initialize file upload with preview
     */
    function initFileUpload() {
        $('.fl11-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            var $label = $(this).siblings('.fl11-file-label');
            
            if (fileName) {
                $label.find('span').text(fileName);
            } else {
                $label.find('span').text('Choose file or drag here');
            }
        });

        // Drag and drop support
        var $dropZone = $('.fl11-file-upload');
        
        $dropZone.on('dragover', function(e) {
            e.preventDefault();
            $(this).find('.fl11-file-label').css('border-color', '#00e5cc');
        });

        $dropZone.on('dragleave', function(e) {
            e.preventDefault();
            $(this).find('.fl11-file-label').css('border-color', '#e2e8f0');
        });

        $dropZone.on('drop', function(e) {
            e.preventDefault();
            var files = e.originalEvent.dataTransfer.files;
            if (files.length) {
                $(this).find('.fl11-file-input')[0].files = files;
                $(this).find('.fl11-file-input').trigger('change');
            }
            $(this).find('.fl11-file-label').css('border-color', '#e2e8f0');
        });
    }

    /**
     * Override service select population to add price data attribute
     * This hooks into the existing AJAX response handler
     */
    $(document).ajaxSuccess(function(event, xhr, settings) {
        // Check if this is the service loading AJAX call
        if (settings.url && settings.url.indexOf('get-services-by-category') !== -1) {
            setTimeout(function() {
                // Update nice-select if used
                if ($.fn.niceSelect) {
                    $('#serviceSelect').niceSelect('update');
                }
            }, 100);
        }

        // Check if this is the time slots AJAX call
        if (settings.url && settings.url.indexOf('appointment-duration') !== -1) {
            setTimeout(function() {
                styleTimeSlots();
            }, 100);
        }
    });

    /**
     * Apply fl11 styling to dynamically loaded time slots
     */
    function styleTimeSlots() {
        var $timeContainer = $('#timeSlots');
        
        // Check if we're in Formlayout11
        if (!$('.fl11-booking-container').length) return;

        // Find radio buttons and wrap them with our styling
        $timeContainer.find('input[type="radio"][name="duration"]').each(function() {
            var $radio = $(this);
            
            // Skip if already styled
            if ($radio.hasClass('fl11-styled')) return;
            
            $radio.addClass('fl11-styled');
            
            // Get the label text
            var $label = $radio.next('label');
            if ($label.length) {
                var labelText = $label.text();
                
                // Create new structure
                var $wrapper = $('<div class="fl11-time-slot"></div>');
                $radio.wrap($wrapper);
                $label.addClass('fl11-time-slot-label');
            }
        });
    }

})(jQuery);
