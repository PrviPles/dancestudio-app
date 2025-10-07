jQuery(document).ready(function($) {
    'use strict';
    
    if (typeof dsaEnrollmentData === 'undefined') return;

    const studentId = dsaEnrollmentData.studentId;
    const nonce = dsaEnrollmentData.nonce;
    const historyTable = $('#dsa-enrollment-history-table');

    // --- 1. Enroll Button & Modal ---
    $('body').on('click', '.dsa-enroll-modal-button', function() {
        const enrollModal = $('#dsa-enrollment-modal');
        const groupSelect = $('#dsa-group-to-enroll');
        groupSelect.html('<option>Loading...</option>');

        $.post(ajaxurl, { action: 'dsa_get_groups_for_enrollment_modal', nonce: nonce, student_id: studentId }).done(function(response) {
            groupSelect.empty();
            if (response.success && response.data.length > 0) {
                $.each(response.data, function(i, group) {
                    groupSelect.append($('<option>', { value: group.id, text: group.text }));
                });
            } else {
                groupSelect.append('<option value="0">No available groups</option>');
            }
        });

        enrollModal.dialog({
            modal: true, width: 400,
            buttons: {
                "Enroll Student": function() {
                    const thisDialog = $(this);
                    thisDialog.parent().find('button:contains("Enroll Student")').button('disable');
                    $.post(ajaxurl, {
                        action: 'dsa_enroll_student_with_date', nonce: nonce, student_id: studentId,
                        group_id: groupSelect.val(), enroll_date: $('#dsa-enroll-date').val()
                    }).done(function(res) {
                        if (res.success) { location.reload(); } 
                        else { 
                            alert('Error: ' + (res.data.message || 'Could not enroll.')); 
                            thisDialog.parent().find('button:contains("Enroll Student")').button('enable');
                        }
                    });
                },
                "Cancel": function() { $(this).dialog('close'); }
            }
        });
    });

    // --- 2. Drop Out Button & Modal ---
    historyTable.on('click', '.dsa-dropout-button', function() {
        const dropoutModal = $('#dsa-dropout-modal');
        const groupId = $(this).data('group-id');
        $('#dsa-dropout-group-id').val(groupId);

        dropoutModal.dialog({
            modal: true, width: 400,
            buttons: {
                "Drop Out Student": function() {
                    const thisDialog = $(this);
                    thisDialog.parent().find('button:contains("Drop Out Student")').button('disable');
                    $.post(ajaxurl, {
                        action: 'dsa_dropout_student_with_date', nonce: nonce, student_id: studentId,
                        group_id: $('#dsa-dropout-group-id').val(), dropout_date: $('#dsa-dropout-date').val()
                    }).done(function(res) {
                        if (res.success) { location.reload(); } 
                        else { 
                            alert('Error: ' + (res.data.message || 'Could not drop out.')); 
                            thisDialog.parent().find('button:contains("Drop Out Student")').button('enable');
                        }
                    });
                },
                "Cancel": function() { $(this).dialog('close'); }
            }
        });
    });

    // --- 3. Inline Date Editing ---
    historyTable.on('click', '.dsa-editable-date', function() {
        $(this).hide().next('.dsa-date-input').show().focus();
    });

    historyTable.on('blur change', '.dsa-date-input', function() {
        const input = $(this);
        const span = input.prev('.dsa-editable-date');
        const spinner = $('<span class="spinner is-active" style="float:none;vertical-align:middle;"></span>');
        
        if (input.val() === span.data('original-date')) {
            input.hide();
            span.show();
            return;
        }

        input.prop('disabled', true).after(spinner);

        $.post(ajaxurl, {
            action: 'dsa_update_enrollment_date', nonce: nonce,
            record_id: input.closest('tr').data('record-id'),
            date_type: input.data('date-type'),
            new_date: input.val()
        }).done(function(response) {
            if (response.success) {
                span.text(response.data.formatted_date).data('original-date', input.val());
            } else {
                alert('Error: ' + (response.data.message || 'Could not update date.'));
            }
        }).always(function() {
            spinner.remove();
            input.prop('disabled', false).hide();
            span.show();
        });
    });
    
    // --- 4. Delete Enrollment Record ---
    historyTable.on('click', '.dsa-delete-enrollment-button', function(e) {
        e.preventDefault();
        
        if ( ! confirm('Are you sure you want to permanently delete this enrollment record? This action cannot be undone.') ) {
            return;
        }

        const button = $(this);
        const recordId = button.data('record-id');
        const row = button.closest('tr');

        button.text('Deleting...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'dsa_delete_enrollment_record',
            nonce: nonce,
            record_id: recordId
        }).done(function(response) {
            if (response.success) {
                row.fadeOut(400, function() { $(this).remove(); });
            } else {
                alert('Error: ' + (response.data.message || 'Could not delete record.'));
                button.text('Delete').prop('disabled', false);
            }
        }).fail(function() {
            alert('A server error occurred.');
            button.text('Delete').prop('disabled', false);
        });
    });
});