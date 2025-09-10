jQuery(document).ready(function($) {
    'use strict';

    if (typeof dsaEnrollmentHistory === 'undefined') {
        return;
    }

    // When a date span is clicked
    $('#dsa-enrollment-history-table').on('click', '.dsa-editable-date', function() {
        var $span = $(this);
        var $input = $span.next('.dsa-date-input');

        $span.hide();
        $input.show().focus();
    });

    // When a date input is changed or loses focus
    $('#dsa-enrollment-history-table').on('blur change', '.dsa-date-input', function() {
        var $input = $(this);
        var $span = $input.prev('.dsa-editable-date');
        var newDate = $input.val();
        var recordId = $input.data('record-id');
        var dateType = $input.data('date-type');

        // Show a spinner next to the input
        var $spinner = $('<span class="spinner is-active" style="float: none; vertical-align: middle; margin-left: 5px;"></span>');
        $input.after($spinner);
        $input.prop('disabled', true);

        // Send the new date to the server
        $.post(ajaxurl, {
            action: 'dsa_update_enrollment_date',
            nonce: dsaEnrollmentHistory.nonce,
            record_id: recordId,
            date_type: dateType,
            new_date: newDate
        })
        .done(function(response) {
            if (response.success) {
                // Update the span with the new, formatted date from the server
                $span.text(response.data.formatted_date);
            } else {
                alert('Error: ' + (response.data.message || 'Could not update date.'));
                // On error, revert the input to the original value
                $input.val($span.data('original-date'));
            }
        })
        .fail(function() {
            alert('A server error occurred.');
            $input.val($span.data('original-date'));
        })
        .always(function() {
            // Hide the input and show the span again
            $spinner.remove();
            $input.prop('disabled', false).hide();
            $span.show();
        });
    });
});