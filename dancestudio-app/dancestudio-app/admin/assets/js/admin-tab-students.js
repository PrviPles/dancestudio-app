jQuery(document).ready(function($) {
    'use strict';

    if (typeof dsaStudentTabData === 'undefined') {
        return;
    }

    // Handle the "Edit Enrollment" button click
    $('#the-list').on('click', '.dsa-edit-enrollments-button', function() {
        var studentId = $(this).data('student-id');
        var studentName = $(this).data('student-name');
        var modal = $('#dsa-enrollment-modal');
        var modalContent = modal.find('.dsa-modal-content');
        
        modal.find('#dsa_enroll_student_id').val(studentId);
        
        modal.dialog('option', 'title', 'Manage Enrollments for ' + studentName);
        modalContent.html('<p>Loading groups... <span class="spinner is-active" style="float:none;"></span></p>');
        modal.dialog('open');

        $.post(ajaxurl, {
            action: 'dsa_get_enrollment_modal_data',
            nonce: dsaStudentTabData.get_nonce,
            student_id: studentId
        }).done(function(response) {
            if (response.success) {
                var html = '<fieldset>';
                if (response.data.all_groups.length > 0) {
                    response.data.all_groups.forEach(function(group) {
                        var isChecked = response.data.enrolled_in.includes(group.id.toString());
                        html += '<label style="display: block; margin-bottom: 8px;">';
                        html += '<input type="checkbox" class="dsa-modal-enroll-checkbox" value="' + group.id + '" ' + (isChecked ? 'checked' : '') + '>';
                        html += ' ' + group.name;
                        html += '</label>';
                    });
                } else {
                    html += '<p>No groups have been created yet.</p>';
                }
                html += '</fieldset>';
                modalContent.html(html);
            } else {
                modalContent.html('<p style="color:red;">Error: Could not load group data.</p>');
            }
        });
    });

    // Handle the checkbox change event inside the modal
    $('#dsa-enrollment-modal').on('change', '.dsa-modal-enroll-checkbox', function() {
        var checkbox = $(this);
        var studentId = $('#dsa_enroll_student_id').val();
        var groupId = checkbox.val();
        var isEnrolled = checkbox.is(':checked');

        checkbox.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'dsa_update_user_enrollment',
            nonce: dsaStudentTabData.update_nonce,
            user_id: studentId,
            group_id: groupId,
            is_enrolled: isEnrolled
        }).always(function() {
            checkbox.prop('disabled', false);
        });
    });

    // Initialize the main dialog box
    if ($('#dsa-enrollment-modal').length) {
        $('#dsa-enrollment-modal').dialog({
            autoOpen: false,
            modal: true,
            width: 400,
            buttons: {
                "Done": function() {
                    $(this).dialog('close');
                    location.reload(); 
                }
            }
        });
    }
});