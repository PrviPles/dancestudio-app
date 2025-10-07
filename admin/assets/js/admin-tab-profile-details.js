jQuery(document).ready(function($) {
    'use strict';
    
    if (typeof dsaProfileData === 'undefined') {
        return; // Exit if our localized data isn't here
    }

    const modal = $('#dsa-edit-student-profile-modal');

    // 1. Handle the "Edit Details" button click
    $('body').on('click', '#dsa-edit-profile-details-button', function() {
        const studentId = $(this).data('student-id');
        const spinner = $('<span class="spinner is-active" style="vertical-align: middle; margin-left: 5px;"></span>');
        $(this).after(spinner);

        // Fetch student data via AJAX
        $.post(ajaxurl, {
            action: 'dsa_get_student_details_for_edit',
            nonce: dsaProfileData.get_nonce,
            student_id: studentId
        }).done(function(response) {
            if (response.success) {
                // Populate the modal form with the fetched data
                const data = response.data;
                $('#dsa_edit_student_id').val(studentId);
                $('#dsa_edit_first_name').val(data.first_name);
                $('#dsa_edit_last_name').val(data.last_name);
                $('#dsa_edit_email').val(data.email);
                $('#dsa_edit_phone').val(data.phone);
                $('#dsa_edit_street').val(data.street);
                $('#dsa_edit_postal_code').val(data.postal_code);
                $('#dsa_edit_city').val(data.city);
                $('#dsa_edit_birth_date').val(data.birth_date);
                $('#dsa_edit_is_retired').prop('checked', data.is_retired === '1');
                $('#dsa_edit_family_discount').prop('checked', data.family_discount === '1');
                
                // Open the dialog
                modal.dialog('open');
            } else {
                alert('Error: ' + (response.data.message || 'Could not fetch student data.'));
            }
        }).fail(function() {
            alert('A server error occurred while fetching data.');
        }).always(function() {
            spinner.remove();
        });
    });

    // 2. Initialize the jQuery UI Dialog
    modal.dialog({
        autoOpen: false,
        modal: true,
        width: 550,
        buttons: {
            "Save Changes": function() {
                const form = $('#dsa-edit-student-profile-form');
                const studentId = $('#dsa_edit_student_id').val();
                
                // Send updated data via AJAX
                $.post(ajaxurl, form.serialize() + '&action=dsa_save_student_details_from_modal')
                .done(function(response) {
                    if (response.success) {
                        // Update the static text on the page with the new values from the form
                        $('#dsa-view-first-name').text($('#dsa_edit_first_name').val());
                        $('#dsa-view-last-name').text($('#dsa_edit_last_name').val());
                        $('#dsa-view-email a').attr('href', 'mailto:' + $('#dsa_edit_email').val()).text($('#dsa_edit_email').val());
                        $('#dsa-view-phone').text($('#dsa_edit_phone').val() || '—');
                        $('#dsa-view-street').text($('#dsa_edit_street').val() || '—');
                        $('#dsa-view-postal-code').text($('#dsa_edit_postal_code').val() || '—');
                        $('#dsa-view-city').text($('#dsa_edit_city').val() || '—');
                        $('#dsa-view-birth-date').text($('#dsa_edit_birth_date').val() || '—'); // Note: Date format will be yyyy-mm-dd
                        $('#dsa-view-is-retired').text($('#dsa_edit_is_retired').is(':checked') ? 'Yes' : 'No');
                        $('#dsa-view-family-discount').text($('#dsa_edit_family_discount').is(':checked') ? 'Yes' : 'No');
                        
                        modal.dialog('close');
                    } else {
                        alert('Error: ' + (response.data.message || 'Could not save changes.'));
                    }
                }).fail(function() {
                    alert('A server error occurred while saving.');
                });
            },
            "Cancel": function() {
                $(this).dialog('close');
            }
        }
    });
});