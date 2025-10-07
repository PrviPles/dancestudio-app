// This function creates the main "View Details" modal for a class.
// It is separate so it can be called by the "Back" buttons in other modals.
function openViewDetailsModal(info, calendar) {
    var $ = jQuery;
    var event = info.event;
    var props = event.extendedProps;
    var l10n = dsaCalendarData.l10n || {};

    // Only create the modal for group classes
    if (props.internalType === 'group_class') {
        var choreoHtml = '';
        if (props.practicedChoreoNames && props.practicedChoreoNames.length > 0) {
            choreoHtml += '<h4 style="margin-top: 15px; margin-bottom: 5px;">Choreographies Practiced</h4><ul style="margin: 0; padding-left: 20px; font-size: 0.9em;">';
            props.practicedChoreoNames.forEach(function(name) {
                choreoHtml += '<li>' + escapeHtml(name) + '</li>';
            });
            choreoHtml += '</ul>';
        }

        var twoColumnHtml =
            `<div id="dsa-class-details-dialog" class="dsa-view-details-dialog" title="${escapeHtml(event.title)}">
                <div style="display:flex; flex-wrap:wrap; gap: 20px;">
                    <div style="flex: 1; min-width: 250px;">
                        <h4>Class Details</h4>
                        <p><strong>Group:</strong> ${escapeHtml(props.groupName || 'N/A')}</p>
                        <p><strong>Date:</strong> ${event.start.toLocaleDateString([], { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                        <p><strong>Time:</strong> ${event.start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })}</p>
                        ${choreoHtml}
                    </div>
                    <div style="flex: 1; min-width: 200px; border-left: 1px solid #ddd; padding-left: 20px;">
                        <h4>Attendance Summary</h4>
                        <div id="dsa-attendance-summary-container"><p>Loading stats...</p></div>
                    </div>
                </div>
            </div>`;

        var $dialog = $(twoColumnHtml).appendTo('body');

        $dialog.dialog({
            modal: true, width: 'auto', maxWidth: 650, minWidth: 500,
            buttons: {
                "Edit Details": function() {
                    $(this).dialog('close');
                    openEditGroupClassModal(info, calendar);
                },
                "Edit Attendance": function() {
                    $(this).dialog('close');
                    openAttendanceModal(info, calendar);
                },
                "Delete Event": function() {
                     if (confirm(l10n.areYouSure || 'Are you sure?')) {
                        $.post(ajaxurl, { action: 'dancestudio_app_delete_calendar_event', nonce: dsaCalendarData.delete_event_nonce, post_id: props.classId })
                        .done(function(response) {
                            if(response.success) {
                                calendar.refetchEvents();
                                $dialog.dialog('close');
                            } else {
                                alert('Error: ' + (response.data.message || 'Could not delete.'));
                            }
                        });
                    }
                },
                "Close": function() { $(this).dialog('close'); }
            },
            close: function() { $(this).dialog('destroy').remove(); },
            open: function() {
                var $summaryContainer = $('#dsa-attendance-summary-container');
                $.post(ajaxurl, { action: 'dsa_get_class_attendance_data', nonce: dsaCalendarData.get_attendance_nonce, class_id: props.classId, group_id: props.groupId })
                .done(function(response) {
                    if (response && response.success && response.data && response.data.summary) {
                        var summary = response.data.summary;
                        var summaryHtml = '<p><strong>' + summary.present + ' / ' + summary.total + '</strong> students attended</p>';
                        summaryHtml += '<p style="font-size: 2em; margin: 10px 0 20px;">' + summary.percentage + '%</p>';
                        var presentStudents = [];
                        if (response.data.students && response.data.attendance) {
                            response.data.students.forEach(function(student) {
                                var attData = response.data.attendance[student.id] || {};
                                var status = attData.status || (attData.attended == '1' ? 'present' : 'absent');
                                if (status === 'present' || status === 'incomplete') {
                                    presentStudents.push(escapeHtml(student.name));
                                }
                            });
                        }
                        if (presentStudents.length > 0) {
                            summaryHtml += '<details><summary style="cursor: pointer; font-weight: bold; margin-bottom: 5px;">View Present Students</summary>';
                            summaryHtml += '<ul style="margin-top: 10px; padding-left: 20px; list-style-type: disc; font-size: 0.9em;">';
                            presentStudents.forEach(function(name) { summaryHtml += '<li>' + name + '</li>'; });
                            summaryHtml += '</ul></details>';
                        }
                        $summaryContainer.html(summaryHtml);
                    } else {
                        $summaryContainer.html('<p>No attendance data recorded.</p>');
                    }
                })
                .fail(function() {
                    $summaryContainer.html('<p>Error: Could not connect to the server.</p>');
                });
            }
        });
    }
}

// Helper function to prevent XSS issues.
function escapeHtml(str) {
    var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'};
    if (str === null || typeof str === 'undefined') return '';
    return String(str).replace(/[&<>"']/g, function(m) { return map[m]; });
}


document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('dsa-admin-calendar');
    if (!calendarEl || typeof FullCalendar === 'undefined' || typeof jQuery === 'undefined' || typeof dsaCalendarData === 'undefined') {
        console.error("DSA Calendar Error: A critical dependency is missing. Halting execution.");
        return;
    }

    var $ = jQuery;
    var l10n = dsaCalendarData.l10n || {};

    var visibleEventTypes = ['group_class', 'private_lesson', 'birthday', 'holiday'];
    $('.dsa-event-filter').on('change', function() {
        visibleEventTypes = $('.dsa-event-filter:checked').map(function() { return $(this).val(); }).get();
        calendar.refetchEvents();
    });

    const getCalendarView = () => window.innerWidth < 768 ? 'timeGridDay' : 'dayGridMonth';
    const getCalendarHeader = () => window.innerWidth < 768 ?
        { left: 'prev,next', center: 'title', right: 'listWeek,timeGridDay' } :
        { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth' };

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: getCalendarView(),
        headerToolbar: getCalendarHeader(),
        editable: false,
        selectable: true,
        firstDay: dsaCalendarData.firstDay ? parseInt(dsaCalendarData.firstDay, 10) : 1,
        
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },

        windowResize: function(view) {
            const newView = getCalendarView();
            if (newView !== calendar.view.type) calendar.changeView(newView);
            calendar.setOption('headerToolbar', getCalendarHeader());
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            $.post(dsaCalendarData.ajax_url, {
                action: 'dsa_get_admin_calendar_events',
                nonce: dsaCalendarData.get_events_nonce
            }).done(function(response) {
                if (response.success) successCallback(response.data);
                else failureCallback(new Error('Failed to fetch events from server.'));
            }).fail(function() {
                failureCallback(new Error('Server error while fetching events.'));
            });
        },
        eventDidMount: function(info) {
            var eventType = info.event.extendedProps.internalType;
            if (!visibleEventTypes.includes(eventType)) info.el.style.display = 'none';
            return true;
        },
        dateClick: function(info) {
            var dialogHtml = '<div id="dsa-add-choice-dialog" title="Create New Event"><p>What would you like to create?</p></div>';
            if ($('#dsa-add-choice-dialog').length) $('#dsa-add-choice-dialog').dialog('destroy').remove();
            $(dialogHtml).appendTo('body').dialog({
                resizable: false, modal: true,
                buttons: {
                    "Group Class": function() { openGroupClassModal(info.dateStr, calendar); $(this).dialog("close"); },
                    "Private Lesson": function() { openPrivateLessonModal(info.dateStr, calendar); $(this).dialog("close"); },
                    Cancel: function() { $(this).dialog("close"); }
                },
                close: function() { $(this).dialog('destroy').remove(); }
            });
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            var event = info.event;
            var props = event.extendedProps;

            if (props.internalType === 'private_lesson') {
                var simpleTitle = 'Lesson Details';
                var $content = $('<div>');

                $content.append( $('<h4>').text(event.title) );

                if (props.notes) {
                    $content.append($('<p style="font-style: italic; color: #555;">').text(props.notes));
                }
                
                $content.append('<hr style="margin: 15px 0;">');

                if (props.teacherName) {
                    $content.append($('<p>').append('<strong>Teacher:</strong> ').append(document.createTextNode(props.teacherName)));
                }
                if (props.startTime) {
                    $content.append($('<p>').append('<strong>Time:</strong> ').append(document.createTextNode(props.startTime)));
                }

                if (props.practicedFigures && props.practicedFigures.length > 0) {
                    var figuresHtml = props.practicedFigures.join(', ');
                    $content.append($('<p>').append('<strong>Figures Practiced:</strong> ').append(document.createTextNode(figuresHtml)));
                }

                if (props.weddingDate || (props.weddingSongs && props.weddingSongs.length > 0) || (props.student1_id && props.student2_id)) {
                    var $coupleDetails = $('<div class="dsa-couple-calendar-details" style="margin-top:15px;">');
                    $coupleDetails.append('<h5>Couple Details</h5>');
                    if (props.weddingDate) {
                        var dateParts = props.weddingDate.split('-');
                        var formattedDate = dateParts[2] + '.' + dateParts[1] + '.' + dateParts[0] + '.';
                        $coupleDetails.append($('<p>').append('<strong>Wedding Date:</strong> ').append(document.createTextNode(formattedDate)));
                    }
                    if (props.weddingSongs && props.weddingSongs.length > 0) {
                        var songNames = props.weddingSongs.map(function(song) { return song.name; }).filter(Boolean).join(', ');
                        if (songNames) {
                            $coupleDetails.append($('<p>').append('<strong>Wedding Song(s):</strong> ').append(document.createTextNode(songNames)));
                        }
                    }
                    if (props.student1_id && props.student2_id) {
                        var profileUrl = `admin.php?page=dsa-couples-tab&action=view_couple_details&user1_id=${props.student1_id}&user2_id=${props.student2_id}`;
                        $coupleDetails.append(`<p style="margin-top: 10px;"><a href="${profileUrl}" class="button button-secondary" target="_blank">Open Full Profile</a></p>`);
                    }
                    $content.append($coupleDetails);
                }
                
                if (props.lessonHistory && props.lessonHistory.length > 0) {
                    var $history = $('<div class="dsa-couple-history" style="margin-top:20px;">');
                    $history.append('<hr><h5 style="margin-top:15px;">Complete Lesson History</h5>');
                    var $historyTable = $('<table style="width:100%; text-align:left; font-size: 0.9em;">');
                    var $thead = $('<thead><tr><th style="padding:4px 8px 4px 0;">Date</th><th>Notes</th><th>Figures Practiced</th><th>Teacher</th><th>Actions</th></tr></thead>');
                    var $tbody = $('<tbody>');
                    
                    props.lessonHistory.forEach(function(lesson) {
                        var dateParts = lesson.date.split('-');
                        var formattedDate = dateParts[2] + '.' + dateParts[1] + '.';
                        var actionCell = `<button type="button" class="button button-small dsa-edit-history-button" data-lesson-id="${lesson.id}">Edit</button>`;
                        
                        var notesText = lesson.notes ? (lesson.notes.length > 30 ? lesson.notes.substring(0, 30) + '...' : lesson.notes) : '—';
                        var figuresText = lesson.figures || '—';

                        $tbody.append(`<tr>
                            <td style="padding:4px 8px 4px 0;">${escapeHtml(formattedDate)}</td>
                            <td style="padding:4px 8px;" title="${escapeHtml(lesson.notes)}">${escapeHtml(notesText)}</td>
                            <td style="padding:4px 8px;">${escapeHtml(figuresText)}</td>
                            <td style="padding:4px 8px;">${escapeHtml(lesson.teacher)}</td>
                            <td style="padding:4px 8px;">${actionCell}</td>
                        </tr>`);
                    });
                    $historyTable.append($thead).append($tbody);
                    $history.append($historyTable);
                    $content.append($history);
                }

                var $buttonContainer = $('<p style="margin-top:20px; display: flex; gap: 10px; align-items: center;"></p>');
                var $editButton = $('<a href="'+ event.url +'" class="button" target="_blank">Edit This Event</a>');
                var $deleteButton = $('<button type="button" class="button-link-delete dsa-delete-private-lesson-button">Delete Event</button>');
                
                $buttonContainer.append($editButton).append($deleteButton);
                $content.append($buttonContainer);

                var $dialog = $('<div title="' + simpleTitle + '"></div>').append($content).appendTo('body').dialog({
                    modal: true, width: 550,
                    buttons: { "Close": function() { $(this).dialog('close'); } },
                    close: function() { $(this).dialog('destroy').remove(); }
                });

                $dialog.on('click', '.dsa-edit-history-button', function() {
                    var lessonId = $(this).data('lesson-id');
                    if (lessonId && typeof openEditPrivateLessonModal === 'function') {
                        openEditPrivateLessonModal(lessonId, calendar);
                        $dialog.dialog('close'); 
                    }
                });

                $dialog.on('click', '.dsa-delete-private-lesson-button', function() {
                    if (confirm(l10n.areYouSure || 'Are you sure you want to delete this event?')) {
                        var postId = event.id.split('_')[1];
                        $.post(ajaxurl, {
                            action: 'dancestudio_app_delete_calendar_event',
                            nonce: dsaCalendarData.delete_event_nonce,
                            post_id: postId
                        })
                        .done(function(response) {
                            if (response.success) {
                                calendar.refetchEvents();
                                $dialog.dialog('close');
                            } else {
                                alert('Error: ' + (response.data.message || 'Could not delete the event.'));
                            }
                        })
                        .fail(function() {
                            alert('A server error occurred.');
                        });
                    }
                });
                return;
            }

            if (props.internalType === 'birthday' || props.internalType === 'holiday') {
                var title = (props.internalType === 'birthday') ? 'Birthday' : 'Holiday';
                var html = '<h4>' + escapeHtml(event.title) + '</h4>';
                if (props.internalType === 'holiday') {
                    html += '<p style="margin-top:10px;"><a href="'+ event.url +'" class="button" target="_blank">Edit Event</a></p>';
                }
                $('<div title="' + title + '">' + html + '</div>').appendTo('body').dialog({
                    modal: true, width: 450,
                    buttons: { "Close": function() { $(this).dialog('close'); } },
                    close: function() { $(this).dialog('destroy').remove(); }
                });
                return;
            }

            if (props.internalType === 'group_class') {
                openViewDetailsModal(info, calendar);
            }
        }
    });
    calendar.render();
});