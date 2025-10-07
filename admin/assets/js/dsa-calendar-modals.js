/**
 * This file contains all the helper functions for creating
 * jQuery UI Dialogs (pop-ups) for the main calendar.
 * UPDATED with a new function to edit private lessons.
 */

function openEditGroupClassModal(info, calendar) {
    var $ = jQuery;
    var event = info.event;
    var props = event.extendedProps;

    var initiallyPracticed = props.practicedChoreos || [];

    var modalHtml = `
        <div title="Edit Class Details">
            <button type="button" class="button-link dsa-back-button" style="margin-bottom: 15px; padding-left: 0; text-decoration: none;">&larr; Back to Details</button>
            <form id="dsa-edit-class-form" style="margin-top: 15px;">
                <input type="hidden" name="dsa_class_id" value="${props.classId}">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><label for="dsa-edit-class-label">Title:</label></th>
                            <td><input type="text" name="dsa_class_label" id="dsa-edit-class-label" class="widefat" value="${event.title}"></td>
                        </tr>
                        <tr>
                            <th><label for="dsa-edit-class-date">Date:</label></th>
                            <td><input type="date" name="dsa_class_date" id="dsa-edit-class-date" value="${event.start.toISOString().slice(0, 10)}"></td>
                        </tr>
                        <tr>
                            <th><label for="dsa-edit-class-start-time">Start Time:</label></th>
                            <td><input type="time" name="dsa_class_start_time" id="dsa-edit-class-start-time" value="${event.start.toTimeString().slice(0, 5)}"></td>
                        </tr>
                        <tr>
                            <th><label for="dsa-edit-group-select">Group:</label></th>
                            <td><select name="dsa_class_group_id" id="dsa-edit-group-select" class="widefat"></select></td>
                        </tr>
                        <tr id="dsa-choreography-filter-row-modal">
                            <th><label for="dsa-choreography-difficulty-filter">Filter by Difficulty:</label></th>
                            <td><select id="dsa-choreography-difficulty-filter" class="widefat"></select></td>
                        </tr>
                        <tr id="dsa-choreography-row-modal">
                            <th style="vertical-align: top;"><label>Choreographies Practiced:</label></th>
                            <td><div id="dsa-choreography-checklist-wrapper-modal" style="height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;"></div></td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>`;

    var $dialog = $(modalHtml).appendTo('body');

    $dialog.dialog({
        modal: true,
        width: 600,
        buttons: {
            "Save Changes": function() {
                var formData = $('#dsa-edit-class-form').serialize();
                $.post(ajaxurl, formData + '&action=dsa_update_class_session_ajax&nonce=' + dsaCalendarData.update_class_nonce)
                .done(function(response) {
                    if (response.success) {
                        calendar.refetchEvents();
                        $dialog.dialog('close');
                    } else { alert("Error: " + (response.data.message || 'Could not update.')); }
                });
            },
            Cancel: function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
            var thisDialog = $(this);
            thisDialog.find('.dsa-back-button').on('click', function() {
                thisDialog.dialog('close');
                openViewDetailsModal(info, calendar);
            });

            var $groupSelect = $('#dsa-edit-group-select');
            var $choreoWrapper = $('#dsa-choreography-checklist-wrapper-modal');
            var $difficultyFilter = $('#dsa-choreography-difficulty-filter');
            
            var availableChoreos = props.availableChoreos || [];
            var allDifficulties = props.allDifficulties || [];

            if (availableChoreos.length > 0) {
                var checklistHtml = '';
                availableChoreos.forEach(function(choreo) {
                    var isChecked = initiallyPracticed.includes(choreo.id) ? 'checked' : '';
                    var fieldId = 'dsa-choreo-modal-' + choreo.id;
                    checklistHtml += `<div class="dsa-choreo-item" data-difficulty="${choreo.difficulty}" style="margin-bottom: 5px;">
                        <input type="checkbox" name="dsa_choreographies[${choreo.id}]" value="1" id="${fieldId}" ${isChecked}>
                        <label for="${fieldId}" style="display: inline-block; margin-left: 5px;">${choreo.title}</label>
                    </div>`;
                });
                $choreoWrapper.html(checklistHtml);
            } else {
                $choreoWrapper.html('<em>No choreographies are assigned to this group.</em>');
            }
            
            $difficultyFilter.append('<option value="all">All Difficulties</option>');
            if (allDifficulties.length > 0) {
                allDifficulties.forEach(function(term) {
                    $difficultyFilter.append(`<option value="${term.slug}">${term.name}</option>`);
                });
            }

            $difficultyFilter.on('change', function() {
                var selectedDifficulty = $(this).val();
                var $choreoItems = $choreoWrapper.find('.dsa-choreo-item');
                
                if (selectedDifficulty === 'all') {
                    $choreoItems.show();
                } else {
                    $choreoItems.hide();
                    $choreoItems.filter(`[data-difficulty="${selectedDifficulty}"]`).show();
                }
            });

            $.post(dsaCalendarData.ajax_url, { action: 'dsa_get_modal_dropdown_data', nonce: dsaCalendarData.get_dropdown_data_nonce }).done(function(response) {
                if (response.success) {
                    response.data.groups.forEach(function(group) {
                        $groupSelect.append('<option value="' + group.id + '">' + group.text + '</option>');
                    });
                    $groupSelect.val(props.groupId);
                }
            });
        }
    });
}

function openAttendanceModal(info, calendar) {
    var $ = jQuery;
    var classId = info.event.extendedProps.classId;
    var groupId = info.event.extendedProps.groupId;

    const status_config = {
        'present':    { label: 'Present',         color: '#28a745' },
        'absent':     { label: 'Absent',          color: '#dc3545' },
        'incomplete': { label: 'Incomplete',      color: '#ffc107' },
        'excused':    { label: 'Excused Absence', color: '#fd7e14' }
    };
    const text_color_for_background = '#ffffff';

    var dialogHtml = `
        <div title="Edit Attendance">
            <button type="button" class="button-link dsa-back-button" style="margin-bottom: 15px; padding-left: 0; text-decoration: none;">&larr; Back to Details</button>
            <div id="dsa-attendance-student-list" style="margin-top:15px;"><p>Loading...</p></div>
        </div>`;

    var $attDialog = $(dialogHtml).appendTo('body');

    $attDialog.dialog({
        modal: true, width: 550, minHeight: 300,
        buttons: {
            "Save Attendance": function() {
                var $saveBtn = $(this).parent().find('button:contains("Save Attendance")');
                $saveBtn.button('disable').text('Saving...');

                var payload = {
                    action: 'dsa_save_class_attendance',
                    nonce: dsaCalendarData.save_attendance_nonce,
                    class_id: classId,
                    attendance_data: {}
                };

                $('#dsa-attendance-form-' + classId).find('.dsa-attendance-row').each(function() {
                    var sid = $(this).data('student-id');
                    if (sid) {
                        payload.attendance_data[sid] = {
                            'status': $(this).find('.dsa-att-status').val(),
                            'remarks': $(this).find('.dsa-att-remarks').val() || ''
                        };
                    }
                });

                $.post(ajaxurl, payload, function(response) {
                    if (response.success) {
                        calendar.refetchEvents();
                        $attDialog.dialog('close');
                    } else {
                        alert('Error: ' + (response.data.message || 'Could not save.'));
                    }
                })
                .fail(function() { alert("A critical error occurred."); })
                .always(function(){ $saveBtn.button('enable').text('Save Attendance'); });
            },
            "Close": function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
            var thisDialog = $(this);

            thisDialog.find('.dsa-back-button').on('click', function() {
                thisDialog.dialog('close');
                openViewDetailsModal(info, calendar);
            });

            var $listContainer = $('#dsa-attendance-student-list');
            $.post(ajaxurl, { action: 'dsa_get_class_attendance_data', nonce: dsaCalendarData.get_attendance_nonce, class_id: classId, group_id: groupId }, function(response) {
                if (response.success && response.data.students && response.data.students.length > 0) {

                    var studentHtml = '<form id="dsa-attendance-form-' + classId + '"><table class="wp-list-table widefat striped"><thead><tr><th>Student</th><th style="width: 180px;">Status</th><th>Remarks</th></tr></thead><tbody>';

                    response.data.students.forEach(function(student) {
                        var attInfo = response.data.attendance[student.id] || {};
                        var current_status = attInfo.status || 'absent';
                        if (!attInfo.status && attInfo.attended && attInfo.attended == '1') {
                            current_status = 'present';
                        }
                        var rem = attInfo.remarks || '';
                        var initial_bg_color = status_config[current_status]?.color || '#ffffff';

                        studentHtml += `<tr class="dsa-attendance-row" data-student-id="${student.id}">`;
                        studentHtml += `<td>${student.name}</td>`;
                        studentHtml += `<td><select class="dsa-att-status widefat" style="background-color: ${initial_bg_color}; color: ${text_color_for_background}; border-color: ${initial_bg_color};">`;
                        
                        for (const [key, config] of Object.entries(status_config)) {
                            const selected = (key === current_status) ? 'selected' : '';
                            studentHtml += `<option value="${key}" ${selected}>${config.label}</option>`;
                        }
                        
                        studentHtml += '</select></td>';
                        studentHtml += `<td><input type="text" value="${rem}" class="dsa-att-remarks widefat" placeholder="Notes..." /></td>`;
                        studentHtml += '</tr>';
                    });
                    studentHtml += '</tbody></table></form>';
                    $listContainer.html(studentHtml);

                } else {
                    $listContainer.html('<p>' + (response.data?.message || 'No students found for this group.') + '</p>');
                }
            }).fail(function() { $listContainer.html('<p>AJAX request failed.</p>'); });

            $listContainer.on('change', '.dsa-att-status', function() {
                var selectedStatus = $(this).val();
                var newBgColor = status_config[selectedStatus]?.color || '#ffffff';
                $(this).css({
                    'background-color': newBgColor,
                    'color': text_color_for_background,
                    'border-color': newBgColor
                });
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
                    if (response.success) {
                        calendar.refetchEvents();
                        $dialog.dialog('close');
                    } else {
                        var errorMessage = (response.data && response.data.message) ? response.data.message : 'An unknown error occurred.';
                        $dialog.find('.dsa-modal-messages').html('<p>' + errorMessage + '</p>').show();
                    }
                })
                .fail(function() {
                    alert("A critical error occurred.");
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
    var modalHtml = `<div title="Log New Private Lesson"><form id="dsa-add-lesson-form"><div class="dsa-modal-messages" style="display:none; color: red; margin-bottom: 10px;"></div><table class="form-table">
        <tr><th><label for="dsa-lesson-title-add">Title:</label></th><td><input type="text" id="dsa-lesson-title-add" name="dsa_lesson_title" value="Private Lesson" class="widefat" required></td></tr>
        <tr><th><label for="dsa-lesson-date-add">Date:</label></th><td><input type="date" id="dsa-lesson-date-add" name="dsa_lesson_date" value="${dateStr}" required></td></tr>
        <tr><th><label for="dsa-lesson-start-time-add">Start Time:</label></th><td><input type="time" id="dsa-lesson-start-time-add" name="dsa_lesson_start_time" required></td></tr>
        <tr><th><label for="dsa-lesson-student1-add">Leader:</label></th><td><select name="dsa_lesson_student1_id" id="dsa-lesson-student1-add" class="dsa-student-search-dropdown" style="width:100%;"></select></td></tr>
        <tr><th><label for="dsa-lesson-student2-add">Follower:</label></th><td><select name="dsa_lesson_student2_id" id="dsa-lesson-student2-add" class="dsa-student-search-dropdown" style="width:100%;"></select></td></tr>
        <tr><th><label for="dsa-lesson-teacher-add">Teacher:</label></th><td><select name="dsa_lesson_teacher_id" id="dsa-lesson-teacher-add" class="dsa-teacher-dropdown" style="width:100%;"><option>Loading...</option></select></td></tr>
        <tr><td colspan="2"><hr></td></tr>
        <tr><th><label for="dsa_practiced_dance_modal">Dance:</label></th><td>
            <select id="dsa_practiced_dance_modal" class="widefat"></select>
        </td></tr>
        <tr><th style="vertical-align: top;"><label>Figures Practiced:</label></th><td>
            <div id="dsa-figures-checklist-wrapper-modal" style="height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                <em>Please select a dance above.</em>
            </div>
            <span class="spinner" style="float:none; vertical-align: middle;"></span>
        </td></tr>
    </table></form></div>`;
    
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
                .fail(function() {
                    alert("A critical error occurred.");
                });
            },
            Cancel: function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
            $('.dsa-student-search-dropdown').select2({
                dropdownParent: $dialog.parent(),
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'dsa_search_students',
                            nonce: dsaCalendarData.search_students_nonce,
                            term: params.term
                        };
                    },
                    processResults: function (data) {
                        if (data && data.success && data.data && Array.isArray(data.data.results)) {
                            return { results: data.data.results };
                        }
                        console.error('Received invalid data from server for student search:', data);
                        return { results: [] };
                    },
                    cache: true
                },
                placeholder: 'Type to search for a student...',
                minimumInputLength: 2,
            });

            $.post(dsaCalendarData.ajax_url, { action: 'dsa_get_modal_dropdown_data', nonce: dsaCalendarData.get_dropdown_data_nonce }).done(function(response) {
                if (response.success) {
                    var $teacherSelect = $dialog.find('.dsa-teacher-dropdown');
                    $teacherSelect.empty().append('<option value="0">-- Select Teacher --</option>');
                    response.data.teachers.forEach(function(user) { $teacherSelect.append('<option value="' + user.id + '">' + user.text + '</option>'); });
                }
            });

            const danceDropdown = $('#dsa_practiced_dance_modal');
            const figuresWrapper = $('#dsa-figures-checklist-wrapper-modal');
            const figuresSpinner = figuresWrapper.next('.spinner');

            $.post(ajaxurl, {
                action: 'dsa_get_all_dances_taxonomy',
                nonce: dsaCalendarData.get_figures_nonce 
            }).done(function(response) {
                if (response.success) {
                    danceDropdown.html('<option value="0">-- Select a Dance --</option>');
                    response.data.forEach(function(dance) {
                        danceDropdown.append(`<option value="${dance.id}">${dance.name}</option>`);
                    });
                }
            });

            danceDropdown.on('change', function() {
                const danceTermId = $(this).val();
                if (!danceTermId || danceTermId <= 0) {
                    figuresWrapper.html('<em>Please select a dance above.</em>');
                    return;
                }

                figuresSpinner.addClass('is-active');
                figuresWrapper.html('');

                $.post(ajaxurl, {
                    action: 'dsa_get_figures_for_dance',
                    nonce: dsaCalendarData.get_figures_nonce,
                    dance_id: danceTermId
                }).done(function(response) {
                    if (response.success && response.data.length > 0) {
                        let checklistHtml = '';
                        response.data.forEach(function(figure) {
                            const fieldId = 'dsa-figure-modal-' + figure.id;
                            checklistHtml += `
                                <div style="margin-bottom: 5px;">
                                    <input type="checkbox" name="dsa_practiced_figures[]" value="${figure.id}" id="${fieldId}">
                                    <label for="${fieldId}" style="display: inline-block; margin-left: 5px;">${figure.title}</label>
                                </div>`;
                        });
                        figuresWrapper.html(checklistHtml);
                    } else {
                        figuresWrapper.html('<em>No figures found for this dance.</em>');
                    }
                }).always(function() {
                    figuresSpinner.removeClass('is-active');
                });
            });
        }
    });
}

/**
 * Opens a modal to EDIT an existing private lesson.
 * @param {number} lessonId The ID of the dsa_private_lesson post.
 * @param {object} calendar The FullCalendar instance.
 */
function openEditPrivateLessonModal(lessonId, calendar) {
    var $ = jQuery;

    // 1. Create the basic modal structure
    var modalHtml = `<div title="Edit Private Lesson"><form id="dsa-edit-lesson-form"><div class="dsa-modal-messages" style="display:none; color: red; margin-bottom: 10px;"></div><p><em>Loading lesson details...</em></p></form></div>`;
    var $dialog = $(modalHtml).appendTo('body');

    // 2. Initialize the dialog
    $dialog.dialog({
        modal: true,
        width: 550,
        buttons: {
            "Save Changes": function() {
                var formData = $('#dsa-edit-lesson-form').serialize();
                $.post(dsaCalendarData.ajax_url, formData + '&action=dsa_update_private_lesson_ajax&nonce=' + dsaCalendarData.update_lesson_nonce)
                .done(function(response) {
                    if (response.success) {
                        calendar.refetchEvents();
                        $dialog.dialog('close');
                    } else {
                        $dialog.find('.dsa-modal-messages').html('<p>' + (response.data.message || 'An error occurred.') + '</p>').show();
                    }
                })
                .fail(function() {
                    alert("A critical server error occurred.");
                });
            },
            Cancel: function() { $(this).dialog('close'); }
        },
        close: function() { $(this).dialog('destroy').remove(); },
        open: function() {
            // 3. Fetch the lesson data via AJAX
            $.post(dsaCalendarData.ajax_url, {
                action: 'dsa_get_private_lesson_details',
                nonce: dsaCalendarData.get_lesson_details_nonce,
                lesson_id: lessonId
            }).done(function(response) {
                if (!response.success) {
                    $dialog.find('form').html('<p style="color:red;">Could not load lesson details.</p>');
                    return;
                }

                var data = response.data;
                
                // 4. Build the full form HTML with the fetched data
                var formHtml = `
                    <input type="hidden" name="dsa_lesson_id" value="${lessonId}">
                    <table class="form-table">
                        <tr><th><label for="dsa-lesson-title-edit">Title:</label></th><td><input type="text" id="dsa-lesson-title-edit" name="dsa_lesson_title" value="${data.title}" class="widefat" required></td></tr>
                        <tr><th><label for="dsa-lesson-date-edit">Date:</label></th><td><input type="date" id="dsa-lesson-date-edit" name="dsa_lesson_date" value="${data.date}" required></td></tr>
                        <tr><th><label for="dsa-lesson-start-time-edit">Start Time:</label></th><td><input type="time" id="dsa-lesson-start-time-edit" name="dsa_lesson_start_time" value="${data.start_time}" required></td></tr>
                        <tr><th><label for="dsa-lesson-student1-edit">Leader:</label></th><td><select name="dsa_lesson_student1_id" id="dsa-lesson-student1-edit" class="dsa-student-search-dropdown" style="width:100%;"></select></td></tr>
                        <tr><th><label for="dsa-lesson-student2-edit">Follower:</label></th><td><select name="dsa_lesson_student2_id" id="dsa-lesson-student2-edit" class="dsa-student-search-dropdown" style="width:100%;"></select></td></tr>
                        <tr><th><label for="dsa-lesson-teacher-edit">Teacher:</label></th><td><select name="dsa_lesson_teacher_id" id="dsa-lesson-teacher-edit" class="dsa-teacher-dropdown" style="width:100%;"><option>Loading...</option></select></td></tr>
                        <tr><th style="vertical-align: top;"><label for="dsa_lesson_notes_edit">Notes:</label></th><td><textarea name="dsa_lesson_notes" id="dsa_lesson_notes_edit" rows="3" class="widefat">${data.notes}</textarea></td></tr>
                        <tr><td colspan="2"><hr></td></tr>
                        <tr><th><label for="dsa_practiced_dance_modal_edit">Dance:</label></th><td><select id="dsa_practiced_dance_modal_edit" class="widefat"></select></td></tr>
                        <tr><th style="vertical-align: top;"><label>Figures Practiced:</label></th><td>
                            <div id="dsa-figures-checklist-wrapper-modal-edit" style="height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                                <em>Please select a dance above.</em>
                            </div>
                            <span class="spinner" style="float:none; vertical-align: middle;"></span>
                        </td></tr>
                    </table>`;

                // 5. Populate the form and initialize Select2 and other dynamic elements
                $dialog.find('form').html(formHtml);

                $('.dsa-student-search-dropdown').each(function() {
                    var $select = $(this);
                    var studentId = $select.attr('id') === 'dsa-lesson-student1-edit' ? data.student1_id : data.student2_id;
                    var studentName = $select.attr('id') === 'dsa-lesson-student1-edit' ? data.student1_name : data.student2_name;

                    if (studentId && studentName) {
                        var option = new Option(studentName, studentId, true, true);
                        $select.append(option).trigger('change');
                    }

                    $select.select2({
                        dropdownParent: $dialog.parent(),
                        ajax: {
                            url: ajaxurl,
                            dataType: 'json',
                            delay: 250,
                            data: params => ({ action: 'dsa_search_students', nonce: dsaCalendarData.search_students_nonce, term: params.term }),
                            processResults: data => ({ results: (data.success ? data.data.results : []) }),
                            cache: true
                        },
                        placeholder: 'Type to search...',
                        minimumInputLength: 2,
                    });
                });
                
                $.post(dsaCalendarData.ajax_url, { action: 'dsa_get_modal_dropdown_data', nonce: dsaCalendarData.get_dropdown_data_nonce }).done(function(response) {
                    if (response.success) {
                        var $teacherSelect = $dialog.find('.dsa-teacher-dropdown');
                        $teacherSelect.empty().append('<option value="0">-- Select Teacher --</option>');
                        response.data.teachers.forEach(user => $teacherSelect.append(`<option value="${user.id}">${user.text}</option>`));
                        $teacherSelect.val(data.teacher_id);
                    }
                });

                const danceDropdown = $('#dsa_practiced_dance_modal_edit');
                const figuresWrapper = $('#dsa-figures-checklist-wrapper-modal-edit');
                const figuresSpinner = figuresWrapper.next('.spinner');

                function loadFiguresForDance(danceId) {
                    if (!danceId || danceId <= 0) {
                        figuresWrapper.html('<em>Please select a dance above.</em>');
                        return;
                    }
                    figuresSpinner.addClass('is-active');
                    figuresWrapper.html('');
                    $.post(ajaxurl, {
                        action: 'dsa_get_figures_for_dance',
                        nonce: dsaCalendarData.get_figures_nonce,
                        dance_id: danceId,
                        post_id: lessonId 
                    }).done(function(response) {
                        if (response.success && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(function(figure) {
                                const isChecked = data.practiced_figures.includes(figure.id) ? 'checked' : '';
                                html += `<div><input type="checkbox" name="dsa_practiced_figures[]" value="${figure.id}" id="fig-edit-${figure.id}" ${isChecked}><label for="fig-edit-${figure.id}"> ${figure.title}</label></div>`;
                            });
                            figuresWrapper.html(html);
                        } else {
                            figuresWrapper.html('<em>No figures found for this dance.</em>');
                        }
                    }).always(() => figuresSpinner.removeClass('is-active'));
                }

                $.post(ajaxurl, { action: 'dsa_get_all_dances_taxonomy', nonce: dsaCalendarData.get_figures_nonce }).done(function(response) {
                    if (response.success) {
                        danceDropdown.html('<option value="0">-- Select a Dance --</option>');
                        response.data.forEach(dance => danceDropdown.append(`<option value="${dance.id}">${dance.name}</option>`));
                        if (data.practiced_dance) {
                            danceDropdown.val(data.practiced_dance);
                            loadFiguresForDance(data.practiced_dance);
                        }
                    }
                });

                danceDropdown.on('change', function() {
                    loadFiguresForDance($(this).val());
                });

            });
        }
    });
}