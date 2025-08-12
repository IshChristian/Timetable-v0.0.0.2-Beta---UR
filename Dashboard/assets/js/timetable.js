// Enhanced Timetable Management JavaScript

// Define steps for the step navigation and initialize currentStep
const steps = ['campus', 'college', 'school', 'department', 'program', 'intake', 'group'];
let currentStep = 0;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
});

function initializeFilters() {
    // Handle step navigation
    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) nextBtn.addEventListener('click', nextStep);

    const prevBtn = document.getElementById('prevBtn');
    if (prevBtn) prevBtn.addEventListener('click', prevStep);

    // Handle form submission
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadTimetable();
        });
    }

    // Handle dropdown changes for cascading filters
    setupCascadingFilters();

    // Load initial timetable
    loadTimetable();
}

function nextStep() {
    if (currentStep < steps.length - 1) {
        document.getElementById(steps[currentStep] + '-step').classList.remove('active');
        document.querySelector(`[data-step="${steps[currentStep]}"]`).classList.remove('active');
        currentStep++;
        document.getElementById(steps[currentStep] + '-step').classList.add('active');
        document.querySelector(`[data-step="${steps[currentStep]}"]`).classList.add('active');
        updateStepButtons();
    }
}

function prevStep() {
    if (currentStep > 0) {
        document.getElementById(steps[currentStep] + '-step').classList.remove('active');
        document.querySelector(`[data-step="${steps[currentStep]}"]`).classList.remove('active');
        currentStep--;
        document.getElementById(steps[currentStep] + '-step').classList.add('active');
        document.querySelector(`[data-step="${steps[currentStep]}"]`).classList.add('active');
        updateStepButtons();
    }
}

function updateStepButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const applyBtn = document.getElementById('applyBtn');
    prevBtn.style.display = currentStep > 0 ? 'inline-block' : 'none';
    if (currentStep === steps.length - 1) {
        nextBtn.style.display = 'none';
        applyBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        applyBtn.style.display = 'none';
    }
}

function setupCascadingFilters() {
    // Campus -> College
    document.getElementById('campus_id').addEventListener('change', function() {
        loadColleges(this.value);
        // Reset downstream selects
        resetSelect('college_id');
        resetSelect('school_id');
        resetSelect('department_id');
        resetSelect('program_id');
        resetSelect('intake_id');
        resetSelect('group_id');
    });

    // College -> School
    document.getElementById('college_id').addEventListener('change', function() {
        loadSchools(this.value);
        resetSelect('school_id');
        resetSelect('department_id');
        resetSelect('program_id');
        resetSelect('intake_id');
        resetSelect('group_id');
    });

    // School -> Department
    document.getElementById('school_id').addEventListener('change', function() {
        loadDepartments(this.value);
        resetSelect('department_id');
        resetSelect('program_id');
        resetSelect('intake_id');
        resetSelect('group_id');
    });

    // Department -> Program
    document.getElementById('department_id').addEventListener('change', function() {
        loadPrograms(this.value);
        resetSelect('program_id');
        resetSelect('intake_id');
        resetSelect('group_id');
    });

    // Program -> Intake
    document.getElementById('program_id').addEventListener('change', function() {
        loadIntakes(this.value);
        resetSelect('intake_id');
        resetSelect('group_id');
    });

    // Intake -> Group
    document.getElementById('intake_id').addEventListener('change', function() {
        loadGroups(this.value);
        resetSelect('group_id');
    });
}

function resetSelect(selectId) {
    const select = document.getElementById(selectId);
    if (select) {
        select.innerHTML = `<option value="">All ${capitalizeFirstLetter(selectId.replace('_id', 's'))}</option>`;
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function loadColleges(campusId) {
    const collegeSelect = document.getElementById('college_id');
    collegeSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('filter_data.php?type=colleges&parent_id=' + encodeURIComponent(campusId))
        .then(res => res.json())
        .then(data => {
            collegeSelect.innerHTML = '<option value="">All Colleges</option>';
            if (data.success) {
                data.data.forEach(college => {
                    collegeSelect.innerHTML += `<option value="${college.id}">${college.name}</option>`;
                });
            }
        });
}

function loadSchools(collegeId) {
    const schoolSelect = document.getElementById('school_id');
    schoolSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('filter_data.php?type=schools&parent_id=' + encodeURIComponent(collegeId))
        .then(res => res.json())
        .then(data => {
            schoolSelect.innerHTML = '<option value="">All Schools</option>';
            if (data.success) {
                data.data.forEach(school => {
                    schoolSelect.innerHTML += `<option value="${school.id}">${school.name}</option>`;
                });
            }
        });
}

function loadDepartments(schoolId) {
    const departmentSelect = document.getElementById('department_id');
    departmentSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('filter_data.php?type=departments&parent_id=' + encodeURIComponent(schoolId))
        .then(res => res.json())
        .then(data => {
            departmentSelect.innerHTML = '<option value="">All Departments</option>';
            if (data.success) {
                data.data.forEach(dept => {
                    departmentSelect.innerHTML += `<option value="${dept.id}">${dept.name}</option>`;
                });
            }
        });
}

function loadPrograms(departmentId) {
    const programSelect = document.getElementById('program_id');
    programSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('filter_data.php?type=programs&parent_id=' + encodeURIComponent(departmentId))
        .then(res => res.json())
        .then(data => {
            programSelect.innerHTML = '<option value="">All Programs</option>';
            if (data.success) {
                data.data.forEach(program => {
                    programSelect.innerHTML += `<option value="${program.id}">${program.name}</option>`;
                });
            }
        });
}

function loadIntakes(programId) {
    const intakeSelect = document.getElementById('intake_id');
    intakeSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('filter_data.php?type=intakes&parent_id=' + encodeURIComponent(programId))
        .then(res => res.json())
        .then(data => {
            intakeSelect.innerHTML = '<option value="">All Intakes</option>';
            if (data.success) {
                data.data.forEach(intake => {
                    let label = `${intake.year}/${intake.month}`;
                    intakeSelect.innerHTML += `<option value="${intake.id}">${label}</option>`;
                });
            }
        });
}

function loadGroups(intakeId) {
    const groupSelect = document.getElementById('group_id');
    groupSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('filter_data.php?type=groups&parent_id=' + encodeURIComponent(intakeId))
        .then(res => res.json())
        .then(data => {
            groupSelect.innerHTML = '<option value="">All Groups</option>';
            if (data.success) {
                data.data.forEach(group => {
                    groupSelect.innerHTML += `<option value="${group.id}">${group.name}</option>`;
                });
            }
        });
}

function loadTimetable() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const timetableCards = document.getElementById('timetableCards');
    if (loadingIndicator) loadingIndicator.style.display = 'block';

    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }

    fetch('fetch_timetable.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            if (data.success && Array.isArray(data.timetable)) {
                renderTimetable(data.timetable);
            } else {
                timetableCards.innerHTML = '<div class="alert alert-warning">No timetable data found.</div>';
            }
        })
        .catch(error => {
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            timetableCards.innerHTML = '<div class="alert alert-danger">Error loading timetable data.</div>';
        });
}

function renderTimetable(timetableData) {
    const container = document.getElementById('timetableCards');
    if (!timetableData || timetableData.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No classes scheduled for the selected criteria.</div>';
        return;
    }
    // Group by day
    const groupedByDay = {};
    timetableData.forEach(session => {
        if (!groupedByDay[session.day]) {
            groupedByDay[session.day] = [];
        }
        groupedByDay[session.day].push(session);
    });

    let html = '<div class="timetable-grid">';
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    days.forEach(day => {
        html += `<div class="day-column">`;
        html += `<div class="day-header">${day}</div>`;
        if (groupedByDay[day]) {
            groupedByDay[day].sort((a, b) => a.start_time.localeCompare(b.start_time));
            groupedByDay[day].forEach(session => {
                html += `
                    <div class="session-card" onclick="showSessionDetails(${session.id})">
                        <div class="session-time">${session.start_time} - ${session.end_time}</div>
                        <div class="session-module">${session.module_name}</div>
                        <div class="session-lecturer">${session.lecturer_name}</div>
                        <div class="session-facility">${session.facility_name}</div>
                        <div class="session-groups">${session.groups}</div>
                    </div>
                `;
            });
        }
        html += '</div>';
    });
    html += '</div>';
    container.innerHTML = html;
}

function showSessionDetails(sessionId) {
    // Implementation for showing session details in slide panel
    alert('Show details for session ID: ' + sessionId);
}