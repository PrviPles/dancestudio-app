/**
 * This file contains all the helper functions for creating
 * jQuery UI Dialogs (pop-ups) for the main calendar.
 * It is loaded before dsa-calendar.js and depends on dsaCalendarData.
 */

function openEditGroupClassModal(event, calendar) {
    var $ = jQuery;
    var props = event.extendedProps;
    var modalHtml = '<div title="Edit Class Details"><form id="dsa-edit-class-form"><input type="hidden" name="dsa_class_id" value="' + props.classId + '"><table class="form-table"><tr><th><label for="dsa-edit-class-label">Title:</label></th><td><input type="text" name="dsa_class_label" id="dsa-edit-class-label" class="widefat" value="' + event.title + '"></td></tr><tr><th><label for="dsa-edit-class-date">Date:</label></th><td><input type="date" name="dsa_class_date" id="dsa-edit-class-date" value="' + event.start.toISOString().slice(0, 10) + '"></td></tr><tr><th><label for="dsa-edit-class-start-time">Start Time:</label></th><td><input type="time" name="dsa_class_start_time" id="dsa-edit-class-start-time" value="' + event.start.toTimeString().slice(0, 5) + '"></td></tr><tr><th><label for="dsa-edit-group-select">Group:</label></th><td><select name="dsa_class_group_id" id="dsa-edit-group-select" class="widefat"></select></td></tr></table></form></div>';
    var $dialog = $(modalHtml).appendTo('body');
    $dialog.dialog({
        modal: true, width: 550,
        buttons: {
            "Save Changes": function() {
                var formData = $('#dsa-edit-class-form').serialize();
                $.post(ajaxurl, formData + '&action=dsa_update_class_session_ajax&nonce=' + dsaCalendarData.update_class_nonce)
                .done(function(response) {
                    if (response.success) { calendar.refetchEvents(); $dialog.dialog('close'); }
                    else { alert("Error: " + (response.data.message || 'Could not update.')); }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("--- AJAX Request Failed ---"); console.error("Status: " + textStatus); console.error("Error Thrown: " + errorThrown); console.error("Full Response Text from Server:"); console.error(jqXHR.responseText);
                    alert("A critical error occurred. Please check the browser console (F12) for the full response from the server.");
                });
            },
            Cancel: function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
            var selectedGroupId = props.groupId; 
            $.post(dsaCalendarData.ajax_url, { action: 'dsa_get_modal_dropdown_data', nonce: dsaCalendarData.get_dropdown_data_nonce }).done(function(response) {
                if (response.success) {
                    var $groupSelect = $('#dsa-edit-group-select');
                    $groupSelect.empty();
                    response.data.groups.forEach(function(group) { $groupSelect.append('<option value="' + group.id + '">' + group.text + '</option>'); });
                    $groupSelect.val(selectedGroupId);
                }
            });
        }
    });
}

function openGroupClassModal(dateStr, calendar) {
    var $ = jQuery;
    var modalHtml = '<div title="Log New Group Class"><form id="dsa-add-class-form"><div class="dsa-modal-messages" style="display:none; color: red; margin-bottom: 10px;"></div><table class="form-table"><tr><th><label for="dsa-class-label-add">Title:</label></th><td><input type="text" id="dsa-class-label-add" name="dsa_class_label" class="widefat" required></td></tr><tr><th><label for="dsa-class-date-add">Date:</label></th><td><input type="date" id="dsa-class-date-add" name="dsa_class_date" value="' + dateStr + '" required></td></tr><tr><th><label for="dsa-class-start-time-add">Start Time:</label></th><td><input type="time" id="dsa-class-start-time-add" name="dsa_class_start_time" required></td></tr><tr><th><label for="dsa-add-group-select">Group:</label></th><td><select name="dsa_class_group_id" id="dsa-add-group-select" class="widefat"><option value="0">Loading...</option></select></td></tr></table></form></div>';
    var $dialog = $(modalHtml).appendTo('body');
    $dialog.dialog({
        modal: true, width: 550,
        buttons: {
            "Save Class": function() {
                var formData = $('#dsa-add-class-form').serialize();
                $.post(ajaxurl, formData + '&action=dsa_add_class_session_ajax&nonce=' + dsaCalendarData.add_class_nonce)
                .done(function(response) {
                    console.log("AJAX request succeeded. Raw response:", response);
                    if (response.success) {
                        calendar.refetchEvents(); 
                        $dialog.dialog('close'); 
                    } else { 
                        var errorMessage = (response.data && response.data.message) ? response.data.message : 'An unknown error occurred.';
                        $dialog.find('.dsa-modal-messages').html('<p>' + errorMessage + '</p>').show(); 
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("--- AJAX Request Failed ---"); console.error("Status: " + textStatus); console.error("Error Thrown: " + errorThrown); console.error("Full Response Text from Server:"); console.error(jqXHR.responseText);
                    alert("A critical error occurred. Please check the browser console (F12) for the full response from the server.");
                });
            },
            Cancel: function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
            $.post(dsaCalendarData.ajax_url, { action: 'dsa_get_modal_dropdown_data', nonce: dsaCalendarData.get_dropdown_data_nonce }).done(function(response) {
                if (response.success) {
                    var $groupSelect = $('#dsa-add-group-select');
                    $groupSelect.empty().append('<option value="0">-- Select Group --</option>');
                    response.data.groups.forEach(function(group) { $groupSelect.append('<option value="' + group.id + '">' + group.text + '</option>'); });
                }
            });
        }
    });
}

function openPrivateLessonModal(dateStr, calendar) {
    var $ = jQuery;
    var modalHtml = '<div title="Log New Private Lesson"><form id="dsa-add-lesson-form"><div class="dsa-modal-messages" style="display:none; color: red; margin-bottom: 10px;"></div><table class="form-table"><tr><th><label for="dsa-lesson-title-add">Title:</label></th><td><input type="text" id="dsa-lesson-title-add" name="dsa_lesson_title" value="Private Lesson" class="widefat" required></td></tr><tr><th><label for="dsa-lesson-date-add">Date:</label></th><td><input type="date" id="dsa-lesson-date-add" name="dsa_lesson_date" value="' + dateStr + '" required></td></tr><tr><th><label for="dsa-lesson-start-time-add">Start Time:</label></th><td><input type="time" id="dsa-lesson-start-time-add" name="dsa_lesson_start_time" required></td></tr><tr><th><label for="dsa-lesson-student1-add">Student 1:</label></th><td><select name="dsa_lesson_student1_id" id="dsa-lesson-student1-add" class="dsa-student-dropdown"><option>Loading...</option></select></td></tr><tr><th><label for="dsa-lesson-student2-add">Student 2:</label></th><td><select name="dsa_lesson_student2_id" id="dsa-lesson-student2-add" class="dsa-student-dropdown"><option>-- None --</option></select></td></tr><tr><th><label for="dsa-lesson-teacher-add">Teacher:</label></th><td><select name="dsa_lesson_teacher_id" id="dsa-lesson-teacher-add" class="dsa-teacher-dropdown"><option>Loading...</option></select></td></tr></table></form></div>';
    var $dialog = $(modalHtml).appendTo('body');
    $dialog.dialog({
        modal: true, width: 550,
        buttons: {
            "Save Lesson": function() {
                var formData = $('#dsa-add-lesson-form').serialize();
                $.post(dsaCalendarData.ajax_url, formData + '&action=dsa_add_private_lesson_ajax&nonce=' + dsaCalendarData.add_lesson_nonce)
                .done(function(response) {
                    if (response.success) { calendar.refetchEvents(); $dialog.dialog('close'); }
                    else { $dialog.find('.dsa-modal-messages').html('<p>' + (response.data.message || 'An error occurred.') + '</p>').show(); }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("--- AJAX Request Failed ---"); console.error("Status: " + textStatus); console.error("Error Thrown: " + errorThrown); console.error("Full Response Text from Server:"); console.error(jqXHR.responseText);
                    alert("A critical error occurred. Please check the browser console (F12) for the full response from the server.");
                });
            },
            Cancel: function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
             $.post(dsaCalendarData.ajax_url, { action: 'dsa_get_modal_dropdown_data', nonce: dsaCalendarData.get_dropdown_data_nonce }).done(function(response) {
                if (response.success) {
                    var $studentSelects = $dialog.find('.dsa-student-dropdown');
                    var $teacherSelect = $dialog.find('.dsa-teacher-dropdown');
                    $studentSelects.empty().append('<option value="0">-- Select Student --</option>');
                    $teacherSelect.empty().append('<option value="0">-- Select Teacher --</option>');
                    response.data.students.forEach(function(user) { $studentSelects.append('<option value="' + user.id + '">' + user.text + '</option>'); });
                    $studentSelects.filter('[name="dsa_lesson_student2_id"]').prepend('<option value="0">-- None --</option>').val(0);
                    response.data.teachers.forEach(function(user) { $teacherSelect.append('<option value="' + user.id + '">' + user.text + '</option>'); });
                }
            });
        }
    });
}

function openAttendanceModal(classId, groupId, calendar) {
    var $ = jQuery;
    var dialogId = 'dsa-attendance-dialog-' + classId;
    var listContainerId = 'dsa-attendance-student-list-' + classId;
    var dialogHtml = '<div id="' + dialogId + '" title="Edit Attendance"><div id="' + listContainerId + '"><p>Loading...</p></div></div>';
    
    if ($('#' + dialogId).length) { $('#' + dialogId).dialog('destroy').remove(); }
    var $attDialog = $(dialogHtml).appendTo('body');

    $attDialog.dialog({
        modal: true, width: 550, minHeight: 300,
        buttons: {
            "Save Attendance": function() {
                var $saveBtn = $(this).parent().find('button:contains("Save Attendance")');
                $saveBtn.button('disable').text('Saving...');
                var payload = { action: 'dsa_save_class_attendance', nonce: dsaCalendarData.save_attendance_nonce, class_id: classId, attendance_data: {} };
                $('#dsa-attendance-form-' + classId).find('.dsa-attendance-row').each(function() {
                    var sid = $(this).data('student-id');
                    if (sid) {
                        payload.attendance_data[sid] = { 'attended': $(this).find('.dsa-att-check').is(':checked') ? '1' : '0', 'remarks': $(this).find('.dsa-att-remarks').val() || '' };
                    }
                });
                $.post(ajaxurl, payload, function(response) {
                    if (response.success) {
                        alert('Attendance Saved!');
                        calendar.refetchEvents();
                        $attDialog.dialog('close');
                    } else {
                        alert('Error: ' + (response.data.message || 'Could not save.'));
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("--- AJAX Request Failed ---"); console.error("Status: " + textStatus); console.error("Error Thrown: " + errorThrown); console.error("Full Response Text from Server:"); console.error(jqXHR.responseText);
                    alert("A critical error occurred. Please check the browser console (F12) for the full response from the server.");
                })
                .always(function(){
                     $saveBtn.button('enable').text('Save Attendance');
                });
            },
            "Close": function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
            var $listContainer = $('#' + listContainerId);
            $.post(ajaxurl, { action: 'dsa_get_class_attendance_data', nonce: dsaCalendarData.get_attendance_nonce, class_id: classId, group_id: groupId }, function(response) {
                if (response.success && response.data.students && response.data.students.length > 0) {
                    var studentHtml = '<form id="dsa-attendance-form-' + classId + '"><table class="wp-list-table widefat striped"><thead><tr><th>Student</th><th style="text-align:center;">Attended</th><th>Remarks</th></tr></thead><tbody>';
                    response.data.students.forEach(function(student) {
                        var attInfo = response.data.attendance[student.id] || {};
                        var isAtt = attInfo.attended == '1';
                        var rem = attInfo.remarks || '';
                        var checkId = 'att-check-' + student.id;
                        studentHtml += '<tr class="dsa-attendance-row" data-student-id="' + student.id + '">';
                        studentHtml += '<td><label for="' + checkId + '">' + student.name + '</label></td>';
                        studentHtml += '<td style="text-align:center;"><input id="' + checkId + '" type="checkbox" class="dsa-att-check" ' + (isAtt ? 'checked' : '') + ' /></td>';
                        studentHtml += '<td><input type="text" value="' + rem + '" class="dsa-att-remarks widefat" placeholder="Notes..." /></td>';
                        studentHtml += '</tr>';
                    });
                    studentHtml += '</tbody></table></form>';
                    $listContainer.html(studentHtml);
                } else {
                    var errorMessage = (response.data && response.data.message) ? response.data.message : 'No students found for this group.';
                    $listContainer.html('<p>' + errorMessage + '</p>');
                }
            }).fail(function() { $listContainer.html('<p>AJAX request failed.</p>'); });
        }
    });
}