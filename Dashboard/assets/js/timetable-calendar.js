document.addEventListener('DOMContentLoaded', function() {
    // Load on page load
    loadCalendar();

    // Load on filter change/submit
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadCalendar();
    });
});

function loadCalendar() {
    const loading = document.getElementById('loadingIndicator');
    const grid = document.getElementById('calendarGrid');
    if (loading) loading.style.display = 'block';
    if (grid) grid.innerHTML = '';

    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);

    fetch('fetch_timetable.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            if (loading) loading.style.display = 'none';
            if (data.success && Array.isArray(data.timetable)) {
                renderCalendarDayView(data.timetable);
            } else {
                grid.innerHTML = '<div class="alert alert-info mt-3">No classes scheduled for the selected criteria.</div>';
            }
        })
        .catch(() => {
            if (loading) loading.style.display = 'none';
            grid.innerHTML = '<div class="alert alert-danger mt-3">Failed to load timetable.</div>';
        });
}

function renderCalendarDayView(timetable) {
    const grid = document.getElementById('calendarGrid');
    if (!grid) return;

    // Group by day
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    let html = '';
    days.forEach(day => {
        html += `<div class="timetable-dayview" style="margin-bottom:32px;">
            <div style="font-size:18px;font-weight:600;margin-bottom:12px;">${day}</div>
            <div style="display:flex;">
                <div class="timetable-hours">`;
        for(let h=8;h<=17;h++) html += `<div>${h.toString().padStart(2,'0')}:00</div>`;
        html += `</div>
                <div class="timetable-events" style="height:480px;position:relative;">`;

        // Assign colors for modules
        const colors = ['module-yellow','module-purple','module-blue','module-green','module-pink'];
        const colorMap = {};
        let colorIdx = 0;

        // Filter sessions for this day
        const sessions = timetable.filter(s => s.Day === day);
        sessions.forEach(session => {
            // Assign color by module code
            const mod = session.module_code;
            if (!colorMap[mod]) colorMap[mod] = colors[colorIdx++ % colors.length];

            // Calculate top/height
            const start = parseTime(session.Time.split(' - ')[0]);
            const end = parseTime(session.Time.split(' - ')[1]);
            const base = 8*60; // 08:00
            const top = ((start - base)/60)*48;
            const height = Math.max(36, ((end-start)/60)*48);

            html += `<div class="timetable-event ${colorMap[mod]}"
                style="top:${top}px;height:${height}px;">
                <div class="event-time">${session.Time}</div>
                <div>
                    <div class="event-title">${session.module_name}</div>
                    <div class="event-meta">
                        ${session.Facility} | ${session.lecturer}<br>
                        <span style="color:#00bcd4;">${session.Group}</span>
                    </div>
                </div>
            </div>`;
        });

        html += `</div></div></div>`;
    });
    grid.innerHTML = html;
}

function parseTime(str) {
    // "08:30" => 510
    const [h, m] = str.split(':').map(Number);
    return h*60 + m;
}