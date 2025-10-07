jQuery(document).ready(function($) {
    // Check if we are on the calendar tab and the calendar data exists
    var calendarEl = document.getElementById('dsa-manager-calendar');
    if (!calendarEl || typeof dsaManagerData === 'undefined' || typeof FullCalendar === 'undefined') {
        return;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: dsaManagerData.events || [],
        firstDay: dsaManagerData.firstDay ? parseInt(dsaManagerData.firstDay, 10) : 1,
    });

    calendar.render();
});