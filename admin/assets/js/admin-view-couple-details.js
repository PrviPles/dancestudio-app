jQuery(document).ready(function($) {
    'use strict';

    // Ensure our localized data from PHP is available
    if (typeof dsaCoupleDetailsData === 'undefined') {
        return;
    }

    const modal = $('#dsa-add-lesson-modal');
    const addButton = $('#dsa-add-lesson-for-couple-button');

    // 1. Initialize the jQuery UI Dialog for the modal
    modal.dialog({
        autoOpen: false,
        modal: true,
        width: 550,
        buttons: {
            "Save Lesson": function() {
                const form = $('#dsa-add-lesson-modal-form');
                const saveButton = $(this).parent().find('button:contains("Save Lesson")');
                
                // Disable button and show spinner for better UX
                saveButton.prop('disabled', true).text('Saving...');

                // Get the student IDs from the button that opened the modal
                const student1_id = addButton.data('user1-id');
                const student2_id = addButton.data('user2-id');

                // Prepare the data to send via AJAX
                let formData = form.serialize();
                formData += '&action=dsa_add_private_lesson_ajax';
                formData += '&nonce=' + dsaCoupleDetailsData.nonce;
                formData += '&dsa_lesson_student1_id=' + student1_id;
                formData += '&dsa_lesson_student2_id=' + student2_id;

                // Send the data
                $.post(dsaCoupleDetailsData.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        // Success! Reload the page to see the new lesson in the history table.
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data.message || 'Could not save the lesson.'));
                        saveButton.prop('disabled', false).text('Save Lesson');
                    }
                })
                .fail(function() {
                    alert('A server error occurred. Please try again.');
                    saveButton.prop('disabled', false).text('Save Lesson');
                });
            },
            "Cancel": function() {
                $(this).dialog('close');
            }
        },
        close: function() {
            // Reset the form when the modal is closed
            $('#dsa-add-lesson-modal-form')[0].reset();
        }
    });

    // 2. Add a click handler to the main button to open the modal
    addButton.on('click', function() {
        modal.dialog('open');
    });
});