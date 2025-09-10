document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('dsa-admin-calendar');
    if (!calendarEl || typeof FullCalendar === 'undefined' || typeof jQuery === 'undefined' || typeof dsaCalendarData === 'undefined') {
        console.error("DSA Calendar Error: A critical dependency is missing. Halting execution.");
        return;
    }

    var $ = jQuery;
    var l10n = dsaCalendarData.l10n || {};

    // --- Functionality for the interactive legend ---
    var visibleEventTypes = ['group_class', 'private_lesson', 'birthday', 'holiday'];

    $('.dsa-event-filter').on('change', function() {
        visibleEventTypes = $('.dsa-event-filter:checked').map(function() {
            return $(this).val();
        }).get();
        // Refetch events which will re-run the eventDidMount filter
        calendar.refetchEvents();
    });

    function esc_html(str) {
        var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'};
        if (str === null || typeof str === 'undefined') return '';
        return String(str).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    function esc_attr(str) {
        var map = {'"': '&quot;',"'": '&#39;'};
        if (str === null || typeof str === 'undefined') return '';
        return String(str).replace(/["']/g, function(m) { return map[m]; });
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth' },
        editable: false,
        selectable: true,
        firstDay: dsaCalendarData.firstDay ? parseInt(dsaCalendarData.firstDay, 10) : 1,

        events: function(fetchInfo, successCallback, failureCallback) {
            $.post(dsaCalendarData.ajax_url, {
                action: 'dsa_get_admin_calendar_events',
                nonce: dsaCalendarData.get_events_nonce
            }).done(function(response) {
                if (response.success) {
                    successCallback(response.data);
                } else {
                    failureCallback(new Error('Failed to fetch events from server.'));
                    alert('Error: Could not load calendar events.');
                }
            }).fail(function() {
                failureCallback(new Error('Server error while fetching events.'));
                alert('Error: Could not connect to the server to get calendar events.');
            });
        },

        eventDidMount: function(info) {
            var eventType = info.event.extendedProps.internalType;
            if ( ! visibleEventTypes.includes(eventType) ) {
                info.el.style.display = 'none';
            }
            return true;
        },

        dateClick: function(info) {
            var dialogHtml = '<div id="dsa-add-choice-dialog" title="Create New Event"><p>What would you like to create?</p></div>';
            if ($('#dsa-add-choice-dialog').length) { $('#dsa-add-choice-dialog').dialog('destroy').remove(); }
            
            $(dialogHtml).appendTo('body').dialog({
                resizable: false, modal: true,
                buttons: {
                    "Group Class": function() { 
                        openGroupClassModal(info.dateStr, calendar); 
                        $(this).dialog("close"); 
                    },
                    "Private Lesson": function() { 
                        openPrivateLessonModal(info.dateStr, calendar); 
                        $(this).dialog("close"); 
                    },
                    Cancel: function() { $(this).dialog("close"); }
                },
                close: function() { $(this).dialog('destroy').remove(); }
            });
        },

        eventClick: function(info) {
            info.jsEvent.preventDefault();
            var event = info.event;
            var props = event.extendedProps;

            if (props.internalType === 'birthday' || props.internalType === 'private_lesson' || props.internalType === 'holiday') {
                var simpleTitle = 'Details';
                if(props.internalType === 'birthday') simpleTitle = 'Birthday';
                if(props.internalType === 'private_lesson') simpleTitle = 'Lesson Details';
                if(props.internalType === 'holiday') simpleTitle = 'Holiday';
                
                var simpleDialogHtml = '<h4>' + esc_html(event.title) + '</h4>';

                if (props.internalType === 'private_lesson' || props.internalType === 'holiday') {
                    simpleDialogHtml += '<p style="margin-top:10px;"><a href="'+ event.url +'" class="button" target="_blank">Edit Event</a></p>';
                }

                $('<div title="' + simpleTitle + '">' + simpleDialogHtml + '</div>').appendTo('body').dialog({
                    modal: true, width: 450,
                    buttons: { "Close": function() { $(this).dialog('close'); } },
                    close: function() { $(this).dialog('destroy').remove(); }
                });
                return;
            }

            if (props.internalType === 'group_class') {
                var twoColumnHtml = 
                    '<div id="dsa-class-details-dialog" title="' + esc_attr(event.title) + '">' +
                        '<div style="display:flex; flex-wrap:wrap; gap: 20px;">' +
                            '<div style="flex: 1; min-width: 250px;">' +
                                '<h4>Class Details</h4>' +
                                '<p><strong>Group:</strong> ' + esc_html(props.groupName || 'N/A') + '</p>' +
                                '<p><strong>Date:</strong> ' + event.start.toLocaleDateString([], { year: 'numeric', month: 'long', day: 'numeric' }) + '</p>' +
                                '<p><strong>Time:</strong> ' + event.start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false }) + '</p>' +
                                '<p style="margin-top:20px;"><button type="button" class="button dsa-edit-event-btn">Edit Details</button></p>' +
                            '</div>' +
                            '<div style="flex: 1; min-width: 200px; border-left: 1px solid #ddd; padding-left: 20px;">' +
                                '<h4>Attendance Summary</h4>' +
                                '<div id="dsa-attendance-summary-container"><p>Loading stats...</p></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                var $dialog = $(twoColumnHtml).appendTo('body');

                $dialog.dialog({
                    modal: true, width: 'auto', maxWidth: 650, minWidth: 500,
                    buttons: {
                        "Edit Attendance": function() {
                            openAttendanceModal(props.classId, props.groupId, calendar);
                            $(this).dialog('close');
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
                        var thisDialog = $(this);
                        thisDialog.find('.dsa-edit-event-btn').on('click', function() { openEditGroupClassModal(event, calendar); thisDialog.dialog('close'); });

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
                                        if (response.data.attendance[student.id] && response.data.attendance[student.id].attended == '1') {
                                            presentStudents.push(esc_html(student.name));
                                        }
                                    });
                                }
                                if (presentStudents.length > 0) {
                                    summaryHtml += '<details>';
                                    summaryHtml += '<summary style="cursor: pointer; font-weight: bold; margin-bottom: 5px;">View Present Students</summary>';
                                    summaryHtml += '<ul style="margin-top: 10px; padding-left: 20px; list-style-type: disc; font-size: 0.9em;">';
                                    presentStudents.forEach(function(name) {
                                        summaryHtml += '<li>' + name + '</li>';
                                    });
                                    summaryHtml += '</ul>';
                                    summaryHtml += '</details>';
                                } else {
                                    summaryHtml += '<p><em>No students were marked as present for this class.</em></p>';
                                }
                                $summaryContainer.html(summaryHtml);
                            } else {
                                var errorMessage = (response && response.data && response.data.message) ? response.data.message : 'An unknown error occurred.';
                                $summaryContainer.html('<p>' + esc_html(errorMessage) + '</p>');
                            }
                        })
                        .fail(function() {
                            $summaryContainer.html('<p>Error: Could not connect to the server.</p>');
                        });
                    }
                });
            }
        }
    });
    calendar.render();
});