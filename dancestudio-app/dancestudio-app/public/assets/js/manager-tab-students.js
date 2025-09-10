jQuery(document).ready(function($) {
    'use strict';
    
    if (typeof dsaManagerData === 'undefined') {
        console.error('DSA Error: Manager data object is missing.');
        return;
    }

    // --- 1. CREATE New Student via AJAX ---
    $('#dsa-create-student-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitButton = form.find('.dsa-submit-button');
        var spinner = form.find('.spinner');

        submitButton.prop('disabled', true);
        spinner.addClass('is-active');

        var formData = form.serialize();
        formData += '&action=dsa_create_student_ajax';

        $.post(dsaManagerData.ajax_url, formData)
        .done(function(response) {
            if (response.success) {
                $('#dsa-no-students-row').remove();
                var newRow = $(response.data.html).hide();
                $('#dsa-all-students-table tbody').prepend(newRow);
                newRow.fadeIn();
                form[0].reset();
            } else {
                alert('Error: ' + (response.data.message || 'Could not create student.'));
            }
        }).fail(function() {
            alert('An unexpected error occurred.');
        }).always(function() {
            submitButton.prop('disabled', false);
            spinner.removeClass('is-active');
        });
    });

    // --- 2. EDIT Existing Student via AJAX Modal ---
    $('#dsa-all-students-table').on('click', '.dsa-edit-student-button', function(e) {
        e.preventDefault();
        var studentId = $(this).data('student-id');
        
        $.post(dsaManagerData.ajax_url, { 
            action: 'dsa_get_student_data', 
            student_id: studentId, 
            nonce: dsaManagerData.manage_student_nonce 
        })
        .done(function(response) {
            if (response.success) {
                $('#dsa_edit_student_id').val(studentId);
                $('#dsa_edit_first_name').val(response.data.first_name);
                $('#dsa_edit_last_name').val(response.data.last_name);
                $('#dsa_edit_email').val(response.data.email);
                $('#dsa_edit_birth_date').val(response.data.birth_date);
                $('#dsa_edit_phone').val(response.data.phone);

                $('#dsa-edit-student-modal').dialog({
                    modal: true, width: 500,
                    buttons: {
                        "Save Changes": function() {
                            var modal = $(this);
                            var editForm = $('#dsa-edit-student-form');
                            var formData = editForm.serialize() + '&action=dsa_update_student_data';
                            $.post(dsaManagerData.ajax_url, formData)
                            .done(function(saveResponse) {
                                if (saveResponse.success) {
                                    var updatedRow = $('#dsa-student-row-' + studentId);
                                    var newFullName = $('#dsa_edit_first_name').val() + ' ' + $('#dsa_edit_last_name').val();
                                    updatedRow.find('.dsa-full-name').text(newFullName.trim());
                                    updatedRow.find('.dsa-email').text($('#dsa_edit_email').val());
                                    updatedRow.find('.dsa-phone').text($('#dsa_edit_phone').val() || '—');
                                    modal.dialog("close");
                                } else {
                                    alert('Error: ' + (saveResponse.data.message || 'Could not save changes.'));
                                }
                            });
                        },
                        Cancel: function() { $(this).dialog("close"); }
                    }
                });
            } else {
                alert('Error: ' + (response.data.message || 'Could not fetch student data.'));
            }
        });
    });

    // --- 3. DELETE Existing Student with a Confirmation Modal ---
    $('#dsa-all-students-table').on('click', '.dsa-delete-student-button', function(e) {
        e.preventDefault();
        var studentId = $(this).data('student-id');
        var row = $('#dsa-student-row-' + studentId);
        var studentName = row.find('.dsa-full-name').text();

        // Create the confirmation dialog's HTML
        var dialogHtml = '<div id="dsa-delete-confirm-dialog" title="Delete Student?">' +
                         '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>' +
                         'Are you sure you want to permanently delete <strong>' + studentName + '</strong>? This action cannot be undone.</p>' +
                         '</div>';

        // Append the dialog to the body and open it
        $(dialogHtml).appendTo('body').dialog({
            resizable: false,
            modal: true,
            buttons: {
                "Delete Student": function() {
                    var modal = $(this);
                    
                    $.post(dsaManagerData.ajax_url, {
                        action: 'dsa_delete_student_ajax',
                        student_id: studentId,
                        nonce: dsaManagerData.manage_student_nonce
                    })
                    .done(function(response) {
                        if (response.success) {
                            row.fadeOut(400, function() { $(this).remove(); });
                        } else {
                            alert('Error: ' + (response.data.message || 'Could not delete student.'));
                        }
                    })
                    .fail(function() {
                        alert('A server error occurred.');
                    })
                    .always(function() {
                        modal.dialog("close");
                    });
                },
                Cancel: function() {
                    $(this).dialog("close");
                }
            },
            close: function() {
                // Clean up the dialog from the DOM when it's closed
                $(this).dialog('destroy').remove();
            }
        });
    });

    // --- 4. Live Filtering Logic ---
    function applyStudentFilters() {
        var nameFilter = $('#dsa_filter_name').val().toLowerCase();
        var $tableRows = $('#dsa-all-students-table tbody tr.dsa-student-row');
        var anyVisible = false;

        $tableRows.each(function() {
            var $row = $(this);
            var fullName = $row.find('.dsa-full-name').text().toLowerCase();

            if (fullName.includes(nameFilter)) {
                $row.show();
                anyVisible = true;
            } else {
                $row.hide();
            }
        });
        
        if (anyVisible) {
            $('#dsa-no-filter-results').hide();
        } else {
            $('#dsa-no-filter-results').show();
        }
    }
    $('#dsa_filter_name').on('keyup', applyStudentFilters);
});