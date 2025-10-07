jQuery(document).ready(function($) {
    'use strict';
    
    // Check if the localized data from PHP exists
    if (typeof dsaUserProfile === 'undefined') {
        return;
    }

    // --- AJAX functionality for enrollment checkboxes ---
    $('.dsa-enrollment-checkbox').on('change', function() {
        var checkbox = $(this);
        var userId = checkbox.data('user-id');
        var groupId = checkbox.val();
        var isEnrolled = checkbox.is(':checked');
        
        // Add a spinner for visual feedback
        var spinner = $('<span class="spinner is-active" style="float: none; vertical-align: middle; margin-left: 5px;"></span>');
        checkbox.parent('label').append(spinner);

        $.post(dsaUserProfile.ajax_url, {
            action: 'dsa_update_user_enrollment',
            nonce: dsaUserProfile.nonce,
            user_id: userId,
            group_id: groupId,
            is_enrolled: isEnrolled
        })
        .done(function(response) {
            // Success or error, the action is complete
        })
        .fail(function() {
            // Handle server errors
            alert('An error occurred on the server.');
            // Revert the checkbox since the action failed
            checkbox.prop('checked', !isEnrolled);
        })
        .always(function() {
            // Always remove the spinner
            spinner.remove();
        });
    });

});