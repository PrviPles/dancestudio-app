jQuery(document).ready(function($) {
    // Check if the necessary data from PHP exists
    if (typeof dsaEnrollmentData === 'undefined') {
        return;
    }

    var groupId = dsaEnrollmentData.groupId;
    var nonce = dsaEnrollmentData.nonce;
    var spinner = $('#dsa-enroll-student-fields .spinner');
    var messagesDiv = $('#dsa-enrollment-messages');

    // --- Handler for Enrolling a Student ---
    $('#dsa-enroll-student-button').on('click', function(e) {
        e.preventDefault();
        spinner.addClass('is-active');
        messagesDiv.slideUp().removeClass('notice-error notice-success');
        
        var studentId = $('#dsa_student_to_enroll').val();
        if (studentId == '0') {
            spinner.removeClass('is-active');
            alert('Please select a student to enroll.');
            return;
        }

        $.post(ajaxurl, {
            action: 'dsa_handle_enrollment_ajax',
            nonce: nonce,
            sub_action: 'enroll',
            group_id: groupId,
            student_id: studentId
        }).done(function(response) {
            if (response.success) {
                $('#dsa-no-members-row').remove();
                $('#dsa-active-members-list').append(response.data.html);
                
                $('#dsa_student_to_enroll option[value="' + studentId + '"]').remove();
                $('#dsa_student_to_enroll').val('0');

                messagesDiv.html('<p>Student enrolled successfully!</p>').addClass('notice notice-success').slideDown();
            } else {
                messagesDiv.html('<p>Error: ' + response.data.message + '</p>').addClass('notice notice-error').slideDown();
            }
        }).fail(function() {
            messagesDiv.html('<p>An unknown AJAX error occurred.</p>').addClass('notice notice-error').slideDown();
        }).always(function() {
            spinner.removeClass('is-active');
        });
    });

    // --- Handler for Dropping Out a Student ---
    $('#dsa-active-members-table').on('click', '.dsa-dropout-button', function() {
        if (!confirm('Are you sure you want to drop this student from the group?')) {
            return;
        }

        var button = $(this);
        var studentId = button.data('student-id');
        var row = $('#dsa-member-row-' + studentId);
        
        button.text('Dropping out...').prop('disabled', true);
        messagesDiv.slideUp().removeClass('notice-error notice-success');

        $.post(ajaxurl, {
            action: 'dsa_handle_enrollment_ajax',
            nonce: nonce,
            sub_action: 'dropout',
            group_id: groupId,
            student_id: studentId
        }).done(function(response) {
            if (response.success) {
                var studentName = row.find('strong').text();
                $('#dsa_student_to_enroll').append($('<option>', {
                    value: studentId,
                    text: studentName
                }));
                
                row.fadeOut(300, function() { $(this).remove(); });

                messagesDiv.html('<p>Student has been dropped out.</p>').addClass('notice notice-success').slideDown();
            } else {
                messagesDiv.html('<p>Error: ' + response.data.message + '</p>').addClass('notice notice-error').slideDown();
                button.text('Drop Out').prop('disabled', false);
            }
        }).fail(function() {
            messagesDiv.html('<p>An unknown AJAX error occurred.</p>').addClass('notice notice-error').slideDown();
            button.text('Drop Out').prop('disabled', false);
        });
    });
});