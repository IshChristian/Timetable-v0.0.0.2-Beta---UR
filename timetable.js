
        $(document).ready(function() {
            let currentStep = 0;
            const steps = ['campus', 'college', 'school', 'department', 'program', 'intake', 'group'];
            let timetableData = [];
            
            // Initialize Select2 for all select elements
            $('.form-select').select2({
                width: '100%',
                theme: 'default'
            });
            
            // Load initial data and timetable
            loadCampuses();
            loadTimetable(); // Load all timetable data initially
            updateCurrentDate();
            
            // Add change event listeners for academic period filters
            $('#academic_year_id').on('change', function() {
                loadTimetable();
            });
            
            $('#semester').on('change', function() {
                loadTimetable();
            });
            
            // Handle next button click
            $('#nextBtn').click(function() {
                if (currentStep < steps.length - 1) {
                    const currentField = steps[currentStep];
                    const nextField = steps[currentStep + 1];
                    
                    // Load dependent data before moving to next step
                    if (currentField === 'campus') {
                        handleCampusChange();
                    } else if (currentField === 'college') {
                        handleCollegeChange();
                    } else if (currentField === 'school') {
                        handleSchoolChange();
                    } else if (currentField === 'department') {
                        handleDepartmentChange();
                    } else if (currentField === 'program') {
                        handleProgramChange();
                    } else if (currentField === 'intake') {
                        handleIntakeChange();
                    }
                    
                    // Move to next step
                    $('.filter-step').removeClass('active');
                    $(`#${nextField}-step`).addClass('active');
                    
                    // Update step indicator
                    $(`.step[data-step="${currentField}"]`).addClass('completed').removeClass('active');
                    $(`.step[data-step="${nextField}"]`).addClass('active');
                    
                    // Show/hide navigation buttons
                    $('#prevBtn').show();
                    if (currentStep + 1 === steps.length - 1) {
                        $('#nextBtn').hide();
                        $('#applyBtn').show();
                    }
                    
                    currentStep++;
                    
                    // Load timetable with current filters
                    loadTimetable();
                }
            });
            
            // Handle previous button click
            $('#prevBtn').click(function() {
                if (currentStep > 0) {
                    const currentField = steps[currentStep];
                    const prevField = steps[currentStep - 1];
                    
                    // Move to previous step
                    $('.filter-step').removeClass('active');
                    $(`#${prevField}-step`).addClass('active');
                    
                    // Update step indicator
                    $(`.step[data-step="${currentField}"]`).removeClass('active completed');
                    $(`.step[data-step="${prevField}"]`).addClass('active').removeClass('completed');
                    
                    // Show/hide navigation buttons
                    $('#nextBtn').show();
                    $('#applyBtn').hide();
                    if (currentStep - 1 === 0) {
                        $('#prevBtn').hide();
                    }
                    
                    currentStep--;
                    
                    // Load timetable with current filters
                    loadTimetable();
                }
            });
            
            // Handle form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                loadTimetable();
            });
            
            // Add change event listeners for all select elements
            $('#campus_id').on('change', function() {
                handleCampusChange();
                loadTimetable();
            });
            
            $('#college_id').on('change', function() {
                handleCollegeChange();
                loadTimetable();
            });
            
            $('#school_id').on('change', function() {
                handleSchoolChange();
                loadTimetable();
            });
            
            $('#department_id').on('change', function() {
                handleDepartmentChange();
                loadTimetable();
            });
            
            $('#program_id').on('change', function() {
                handleProgramChange();
                loadTimetable();
            });
            
            $('#intake_id').on('change', function() {
                handleIntakeChange();
                loadTimetable();
            });

            $('#group_id').on('change', function() {
                loadTimetable();
            });
        });

        function showLoading() {
            $('#loadingIndicator').show();
        }

        function hideLoading() {
            $('#loadingIndicator').hide();
        }

        function loadCampuses() {
            $.ajax({
                url: 'Dashboard/get_organization_structure.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const campusSelect = $('#campus_id');
                        campusSelect.empty().append('<option value="">All Campuses</option>');
                        response.data.forEach(campus => {
                            campusSelect.append(`<option value="${campus.id}">${campus.name}</option>`);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading campuses:', error);
                }
            });
        }

        function handleCampusChange() {
            const campusId = $('#campus_id').val();
            const collegeSelect = $('#college_id');
            
            // Reset dependent dropdowns
            collegeSelect.empty().append('<option value="">All Colleges</option>');
            $('#school_id').empty().append('<option value="">All Schools</option>');
            $('#department_id').empty().append('<option value="">All Departments</option>');
            $('#program_id').empty().append('<option value="">All Programs</option>');
            $('#intake_id').empty().append('<option value="">All Intakes</option>');
            $('#group_id').empty().append('<option value="">All Groups</option>');
            
            if (campusId) {
                $.ajax({
                    url: 'Dashboard/get_organization_structure.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            const campus = response.data.find(c => c.id === campusId);
                            if (campus && campus.colleges) {
                                campus.colleges.forEach(college => {
                                    collegeSelect.append(`<option value="${college.id}">${college.name}</option>`);
                                });
                            }
                        }
                    }
                });
            }
        }

        function handleCollegeChange() {
            const campusId = $('#campus_id').val();
            const collegeId = $('#college_id').val();
            const schoolSelect = $('#school_id');
            
            // Reset dependent dropdowns
            schoolSelect.empty().append('<option value="">All Schools</option>');
            $('#department_id').empty().append('<option value="">All Departments</option>');
            $('#program_id').empty().append('<option value="">All Programs</option>');
            $('#intake_id').empty().append('<option value="">All Intakes</option>');
            $('#group_id').empty().append('<option value="">All Groups</option>');
            
            if (campusId && collegeId) {
                $.ajax({
                    url: 'Dashboard/get_organization_structure.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            const campus = response.data.find(c => c.id === campusId);
                            if (campus) {
                                const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                                if (college && college.schools) {
                                    college.schools.forEach(school => {
                                        schoolSelect.append(`<option value="${school.id}">${school.name}</option>`);
                                    });
                                }
                            }
                        }
                    }
                });
            }
        }

        function handleSchoolChange() {
            const campusId = $('#campus_id').val();
            const collegeId = $('#college_id').val();
            const schoolId = $('#school_id').val();
            const departmentSelect = $('#department_id');
            
            // Reset dependent dropdowns
            departmentSelect.empty().append('<option value="">All Departments</option>');
            $('#program_id').empty().append('<option value="">All Programs</option>');
            $('#intake_id').empty().append('<option value="">All Intakes</option>');
            $('#group_id').empty().append('<option value="">All Groups</option>');
            
            if (campusId && collegeId && schoolId) {
                $.ajax({
                    url: 'Dashboard/get_organization_structure.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            const campus = response.data.find(c => c.id === campusId);
                            if (campus) {
                                const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                                if (college) {
                                    const school = college.schools.find(s => s.id === parseInt(schoolId));
                                    if (school && school.departments) {
                                        school.departments.forEach(department => {
                                            departmentSelect.append(`<option value="${department.id}">${department.name}</option>`);
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        function handleDepartmentChange() {
            const campusId = $('#campus_id').val();
            const collegeId = $('#college_id').val();
            const schoolId = $('#school_id').val();
            const departmentId = $('#department_id').val();
            const programSelect = $('#program_id');
            
            // Reset dependent dropdowns
            programSelect.empty().append('<option value="">All Programs</option>');
            $('#intake_id').empty().append('<option value="">All Intakes</option>');
            $('#group_id').empty().append('<option value="">All Groups</option>');
            
            if (campusId && collegeId && schoolId && departmentId) {
                $.ajax({
                    url: 'Dashboard/get_organization_structure.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            const campus = response.data.find(c => c.id === campusId);
                            if (campus) {
                                const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                                if (college) {
                                    const school = college.schools.find(s => s.id === parseInt(schoolId));
                                    if (school) {
                                        const department = school.departments.find(d => d.id === parseInt(departmentId));
                                        if (department && department.programs) {
                                            department.programs.forEach(program => {
                                                programSelect.append(`<option value="${program.id}">${program.name}</option>`);
                                            });
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        function handleProgramChange() {
            const campusId = $('#campus_id').val();
            const collegeId = $('#college_id').val();
            const schoolId = $('#school_id').val();
            const departmentId = $('#department_id').val();
            const programId = $('#program_id').val();
            const intakeSelect = $('#intake_id');
            const groupSelect = $('#group_id');
            
            // Reset dropdowns
            intakeSelect.empty().append('<option value="">All Intakes</option>');
            groupSelect.empty().append('<option value="">All Groups</option>');
            
            if (campusId && collegeId && schoolId && departmentId && programId) {
                $.ajax({
                    url: 'Dashboard/get_organization_structure.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            const campus = response.data.find(c => c.id === campusId);
                            if (campus) {
                                const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                                if (college) {
                                    const school = college.schools.find(s => s.id === parseInt(schoolId));
                                    if (school) {
                                        const department = school.departments.find(d => d.id === parseInt(departmentId));
                                        if (department) {
                                            const program = department.programs.find(p => p.id === parseInt(programId));
                                            if (program) {
                                                // Load intakes
                                                if (program.intakes) {
                                                    program.intakes.forEach(intake => {
                                                        intakeSelect.append(`<option value="${intake.id}">${intake.year}/${intake.month}</option>`);
                                                    });
                                                }
                                                
                                                // Load groups
                                                if (program.groups) {
                                                    program.groups.forEach(group => {
                                                        groupSelect.append(`<option value="${group.id}">${group.name} (${group.size})</option>`);
                                                    });
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        function handleIntakeChange() {
            const campusId = $('#campus_id').val();
            const collegeId = $('#college_id').val();
            const schoolId = $('#school_id').val();
            const departmentId = $('#department_id').val();
            const programId = $('#program_id').val();
            const intakeId = $('#intake_id').val();
            const groupSelect = $('#group_id');
            
            // Reset group dropdown
            groupSelect.empty().append('<option value="">All Groups</option>');
            
            if (campusId && collegeId && schoolId && departmentId && programId && intakeId) {
                $.ajax({
                    url: 'Dashboard/get_organization_structure.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            const campus = response.data.find(c => c.id === campusId);
                            if (campus) {
                                const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                                if (college) {
                                    const school = college.schools.find(s => s.id === parseInt(schoolId));
                                    if (school) {
                                        const department = school.departments.find(d => d.id === parseInt(departmentId));
                                        if (department) {
                                            const program = department.programs.find(p => p.id === parseInt(programId));
                                            if (program) {
                                                const intake = program.intakes.find(i => i.id === parseInt(intakeId));
                                                if (intake && intake.groups) {
                                                    intake.groups.forEach(group => {
                                                        groupSelect.append(`<option value="${group.id}">${group.name}</option>`);
                                                    });
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        function loadTimetable() {
            showLoading();
            
            // Get all current filter values
            const filters = {
                campus_id: $('#campus_id').val(),
                college_id: $('#college_id').val(),
                school_id: $('#school_id').val(),
                department_id: $('#department_id').val(),
                program_id: $('#program_id').val(),
                intake_id: $('#intake_id').val(),
                group_id: $('#group_id').val(),
                academic_year_id: $('#academic_year_id').val(),
                semester: $('#semester').val()
            };
            
            $.ajax({
                url: 'Dashboard/get_timetable.php',
                method: 'GET',
                data: filters,
                dataType: 'json',
                success: function(response) {
                    hideLoading();
                    
                    if (response.success) {
                        if (response.data && response.data.length > 0) {
                            timetableData = response.data;
                            displayTimetable(response.data);
                        } else {
                            displayNoData();
                        }
                    } else {
                        displayError(response.error || 'An error occurred while loading the timetable.');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    displayError('Failed to load timetable data. Please try again later.');
                }
            });
        }

        function displayTimetable(data) {
            const container = $('#timetableCards');
            container.empty();

            // Group sessions by their unique combination
            const groupedSessions = {};
            data.forEach(session => {
                const key = `${session.session.day}_${session.session.start_time}_${session.session.end_time}_${session.timetable.module.id}_${session.timetable.lecturer.id}`;
                if (!groupedSessions[key]) {
                    groupedSessions[key] = {
                        session: session.session,
                        timetable: session.timetable,
                        groups: []
                    };
                }
                groupedSessions[key].groups = groupedSessions[key].groups.concat(session.timetable.groups);
            });

            // Create cards for each session
            Object.values(groupedSessions).forEach((groupedSession, index) => {
                const { session, timetable, groups } = groupedSession;
                const dayClass = session.day.toLowerCase();
                
                const card = `
                    <div class="timetable-card ${dayClass}" data-session-id="${index}">
                        <div class="card-header">
                            <div class="session-time">
                                <i class="bi bi-clock"></i>
                                ${session.start_time} - ${session.end_time}
                            </div>
                            <div class="session-day">
                                <i class="bi bi-calendar"></i>
                                ${session.day}
                            </div>
                        </div>
                        
                        <div class="module-info">
                            <div class="module-code">${timetable.module.code}</div>
                            <h4 class="module-title">${timetable.module.name}</h4>
                            <div class="module-credits">
                                <i class="bi bi-book"></i>
                                ${timetable.module.credits} Credits
                            </div>
                        </div>
                        
                        <div class="lecturer-info">
                            <div class="info-icon">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="info-content">
                                <h6>${timetable.lecturer.name}</h6>
                                <p>${timetable.lecturer.email}</p>
                            </div>
                        </div>
                        
                        <div class="facility-info">
                            <div class="info-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="info-content">
                                <h6>${timetable.facility.name} (${timetable.facility.type})</h6>
                                <p>${timetable.facility.location}</p>
                            </div>
                        </div>
                        
                        <div class="groups-preview">
                            <div class="groups-count">
                                <i class="bi bi-people"></i>
                                ${groups.length} Group${groups.length !== 1 ? 's' : ''}
                            </div>
                            <button class="view-more-btn" onclick="openPanel(${index})">
                                View More
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                container.append(card);
            });
        }

        function displayNoData() {
            const container = $('#timetableCards');
            container.html(`
                <div class="no-data">
                    <i class="bi bi-calendar-x"></i>
                    <h4>No Sessions Found</h4>
                    <p>No timetable sessions match your current filters.</p>
                    <button class="btn btn-primary" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </button>
                </div>
            `);
        }

        function displayError(message) {
            const container = $('#timetableCards');
            container.html(`
                <div class="no-data">
                    <i class="bi bi-exclamation-triangle"></i>
                    <h4>Error Loading Timetable</h4>
                    <p>${message}</p>
                    <button class="btn btn-primary" onclick="loadTimetable()">
                        <i class="bi bi-arrow-clockwise"></i> Try Again
                    </button>
                </div>
            `);
        }

        function openPanel(sessionIndex) {
            // Get the grouped session data
            const data = timetableData;
            if (!data || data.length === 0) return;

            // Group sessions again (same logic as displayTimetable)
            const groupedSessions = {};
            data.forEach(session => {
                const key = `${session.session.day}_${session.session.start_time}_${session.session.end_time}_${session.timetable.module.id}_${session.timetable.lecturer.id}`;
                if (!groupedSessions[key]) {
                    groupedSessions[key] = {
                        session: session.session,
                        timetable: session.timetable,
                        groups: []
                    };
                }
                groupedSessions[key].groups = groupedSessions[key].groups.concat(session.timetable.groups);
            });

            const sessionArray = Object.values(groupedSessions);
            const session = sessionArray[sessionIndex];
            if (!session) return;

            // Update panel title
            document.getElementById('panelTitle').textContent = session.timetable.module.name;
            document.getElementById('panelSubtitle').textContent = `${session.session.day}, ${session.session.start_time} - ${session.session.end_time}`;

            // Generate panel content
            const panelContent = `
                <div class="detail-section">
                    <h6><i class="bi bi-book"></i> Module Information</h6>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Module Name</span>
                            <span class="detail-value">${session.timetable.module.name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Module Code</span>
                            <span class="detail-value">${session.timetable.module.code}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Credits</span>
                            <span class="detail-value">${session.timetable.module.credits}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Academic Year</span>
                            <span class="detail-value">${session.timetable.academic_year}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Semester</span>
                            <span class="detail-value">Semester ${session.timetable.semester}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h6><i class="bi bi-person-circle"></i> Lecturer Information</h6>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Name</span>
                            <span class="detail-value">${session.timetable.lecturer.name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">
                                <a href="mailto:${session.timetable.lecturer.email}" style="color: #667eea;">
                                    ${session.timetable.lecturer.email}
                                </a>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value">
                                <a href="tel:${session.timetable.lecturer.phone}" style="color: #667eea;">
                                    ${session.timetable.lecturer.phone}
                                </a>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h6><i class="bi bi-building"></i> Facility Information</h6>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Facility</span>
                            <span class="detail-value">${session.timetable.facility.name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Type</span>
                            <span class="detail-value">${session.timetable.facility.type}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Location</span>
                            <span class="detail-value">${session.timetable.facility.location}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Capacity</span>
                            <span class="detail-value">${session.timetable.facility.capacity} students</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h6><i class="bi bi-people"></i> Groups (${session.groups.length})</h6>
                    <div class="groups-list">
                        ${session.groups.map(group => `
                            <div class="group-card">
                                <div class="group-header">
                                    <div class="group-name">${group.name}</div>
                                    <div class="group-size">${group.size} students</div>
                                </div>
                                <div class="group-details">
                                    <div class="group-detail">
                                        <i class="bi bi-geo-alt"></i>
                                        <span>Campus: ${group.campus.name}</span>
                                    </div>
                                    <div class="group-detail">
                                        <i class="bi bi-building"></i>
                                        <span>College: ${group.college.name}</span>
                                    </div>
                                    <div class="group-detail">
                                        <i class="bi bi-bank"></i>
                                        <span>School: ${group.school.name}</span>
                                    </div>
                                    <div class="group-detail">
                                        <i class="bi bi-diagram-3"></i>
                                        <span>Department: ${group.department.name}</span>
                                    </div>
                                    <div class="group-detail">
                                        <i class="bi bi-mortarboard"></i>
                                        <span>Program: ${group.program.name} (${group.program.code})</span>
                                    </div>
                                    <div class="group-detail">
                                        <i class="bi bi-calendar-date"></i>
                                        <span>Intake: ${group.intake.year}/${group.intake.month}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;

            document.getElementById('panelContent').innerHTML = panelContent;

            // Show panel with animation
            document.getElementById('overlay').classList.add('active');
            document.getElementById('slidePanel').classList.add('active');
            document.getElementById('timetableMain').classList.add('shifted');
        }

        function closePanel() {
            document.getElementById('overlay').classList.remove('active');
            document.getElementById('slidePanel').classList.remove('active');
            document.getElementById('timetableMain').classList.remove('shifted');
        }

        
    
    // Handle reset
    function resetFilters() {
        currentStep = 0;
        $('.filter-step').removeClass('active');
        $('#campus-step').addClass('active');
        $('.step').removeClass('active completed');
        $('.step[data-step="campus"]').addClass('active');
        $('#prevBtn').hide();
        $('#nextBtn').show();
        $('#applyBtn').hide();
        $('.form-select').val('').trigger('change');
        $('#group_id').val(null).trigger('change');
        
        // Reset academic year and semester to empty values
        $('#academic_year_id').val('').trigger('change');
        $('#semester').val('').trigger('change');
        
        loadTimetable();
    }
    
    // Make resetFilters available globally
    window.resetFilters = resetFilters;
    
    // Add change event listeners for all select elements
    $('#campus_id').on('change', function() {
        handleCampusChange();
        loadTimetable();
    });
    
    $('#college_id').on('change', function() {
        handleCollegeChange();
        loadTimetable();
    });
    
    $('#school_id').on('change', function() {
        handleSchoolChange();
        loadTimetable();
    });
    
    $('#department_id').on('change', function() {
        handleDepartmentChange();
        loadTimetable();
    });
    
    $('#program_id').on('change', function() {
        handleProgramChange();
        loadTimetable();
    });
    
    $('#intake_id').on('change', function() {
        handleIntakeChange();
        loadTimetable();
    });

    // Add group change handler
    $('#group_id').on('change', function() {
        loadTimetable();
    });
});

function showLoading() {
    $('#loadingIndicator').show();
}

function hideLoading() {
    $('#loadingIndicator').hide();
}

function loadCampuses() {
    $.ajax({
        url: 'Dashboard/get_organization_structure.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const campusSelect = $('#campus_id');
                campusSelect.empty().append('<option value="">All Campuses</option>');
                response.data.forEach(campus => {
                    campusSelect.append(`<option value="${campus.id}">${campus.name}</option>`);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading campuses:', error);
        }
    });
}

function handleCampusChange() {
    const campusId = $('#campus_id').val();
    const collegeSelect = $('#college_id');
    
    // Reset dependent dropdowns
    collegeSelect.empty().append('<option value="">All Colleges</option>');
    $('#school_id').empty().append('<option value="">All Schools</option>');
    $('#department_id').empty().append('<option value="">All Departments</option>');
    $('#program_id').empty().append('<option value="">All Programs</option>');
    $('#intake_id').empty().append('<option value="">All Intakes</option>');
    
    if (campusId) {
        $.ajax({
            url: 'Dashboard/get_organization_structure.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const campus = response.data.find(c => c.id === campusId);
                    if (campus && campus.colleges) {
                        campus.colleges.forEach(college => {
                            collegeSelect.append(`<option value="${college.id}">${college.name}</option>`);
                        });
                    }
                }
            }
        });
    }
}

function handleCollegeChange() {
    const campusId = $('#campus_id').val();
    const collegeId = $('#college_id').val();
    const schoolSelect = $('#school_id');
    
    // Reset dependent dropdowns
    schoolSelect.empty().append('<option value="">All Schools</option>');
    $('#department_id').empty().append('<option value="">All Departments</option>');
    $('#program_id').empty().append('<option value="">All Programs</option>');
    $('#intake_id').empty().append('<option value="">All Intakes</option>');
    
    if (campusId && collegeId) {
        $.ajax({
            url: 'Dashboard/get_organization_structure.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const campus = response.data.find(c => c.id === campusId);
                    if (campus) {
                        const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                        if (college && college.schools) {
                            college.schools.forEach(school => {
                                schoolSelect.append(`<option value="${school.id}">${school.name}</option>`);
                            });
                        }
                    }
                }
            }
        });
    }
}

function handleSchoolChange() {
    const campusId = $('#campus_id').val();
    const collegeId = $('#college_id').val();
    const schoolId = $('#school_id').val();
    const departmentSelect = $('#department_id');
    
    // Reset dependent dropdowns
    departmentSelect.empty().append('<option value="">All Departments</option>');
    $('#program_id').empty().append('<option value="">All Programs</option>');
    $('#intake_id').empty().append('<option value="">All Intakes</option>');
    
    if (campusId && collegeId && schoolId) {
        $.ajax({
            url: 'Dashboard/get_organization_structure.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const campus = response.data.find(c => c.id === campusId);
                    if (campus) {
                        const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                        if (college) {
                            const school = college.schools.find(s => s.id === parseInt(schoolId));
                            if (school && school.departments) {
                                school.departments.forEach(department => {
                                    departmentSelect.append(`<option value="${department.id}">${department.name}</option>`);
                                });
                            }
                        }
                    }
                }
            }
        });
    }
}

function handleDepartmentChange() {
    const campusId = $('#campus_id').val();
    const collegeId = $('#college_id').val();
    const schoolId = $('#school_id').val();
    const departmentId = $('#department_id').val();
    const programSelect = $('#program_id');
    
    // Reset dependent dropdowns
    programSelect.empty().append('<option value="">All Programs</option>');
    $('#intake_id').empty().append('<option value="">All Intakes</option>');
    
    if (campusId && collegeId && schoolId && departmentId) {
        $.ajax({
            url: 'Dashboard/get_organization_structure.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const campus = response.data.find(c => c.id === campusId);
                    if (campus) {
                        const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                        if (college) {
                            const school = college.schools.find(s => s.id === parseInt(schoolId));
                            if (school) {
                                const department = school.departments.find(d => d.id === parseInt(departmentId));
                                if (department && department.programs) {
                                    department.programs.forEach(program => {
                                        programSelect.append(`<option value="${program.id}">${program.name}</option>`);
                                    });
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}

function handleProgramChange() {
    const campusId = $('#campus_id').val();
    const collegeId = $('#college_id').val();
    const schoolId = $('#school_id').val();
    const departmentId = $('#department_id').val();
    const programId = $('#program_id').val();
    const intakeSelect = $('#intake_id');
    const groupSelect = $('#group_id');
    
    // Reset dropdowns
    intakeSelect.empty().append('<option value="">All Intakes</option>');
    groupSelect.empty().append('<option value="">All Groups</option>');
    
    if (campusId && collegeId && schoolId && departmentId && programId) {
        $.ajax({
            url: 'Dashboard/get_organization_structure.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const campus = response.data.find(c => c.id === campusId);
                    if (campus) {
                        const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                        if (college) {
                            const school = college.schools.find(s => s.id === parseInt(schoolId));
                            if (school) {
                                const department = school.departments.find(d => d.id === parseInt(departmentId));
                                if (department) {
                                    const program = department.programs.find(p => p.id === parseInt(programId));
                                    if (program) {
                                        // Load intakes
                                        if (program.intakes) {
                                            program.intakes.forEach(intake => {
                                                intakeSelect.append(`<option value="${intake.id}">${intake.year}/${intake.month}</option>`);
                                            });
                                        }
                                        
                                        // Load groups
                                        if (program.groups) {
                                            program.groups.forEach(group => {
                                                groupSelect.append(`<option value="${group.id}">${group.name} (${group.size})</option>`);
                                            });
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}

function handleIntakeChange() {
    const campusId = $('#campus_id').val();
    const collegeId = $('#college_id').val();
    const schoolId = $('#school_id').val();
    const departmentId = $('#department_id').val();
    const programId = $('#program_id').val();
    const intakeId = $('#intake_id').val();
    const groupSelect = $('#group_id');
    
    // Reset group dropdown
    groupSelect.empty().append('<option value="">All Groups</option>');
    
    if (campusId && collegeId && schoolId && departmentId && programId && intakeId) {
        $.ajax({
            url: 'Dashboard/get_organization_structure.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const campus = response.data.find(c => c.id === campusId);
                    if (campus) {
                        const college = campus.colleges.find(c => c.id === parseInt(collegeId));
                        if (college) {
                            const school = college.schools.find(s => s.id === parseInt(schoolId));
                            if (school) {
                                const department = school.departments.find(d => d.id === parseInt(departmentId));
                                if (department) {
                                    const program = department.programs.find(p => p.id === parseInt(programId));
                                    if (program) {
                                        const intake = program.intakes.find(i => i.id === parseInt(intakeId));
                                        if (intake && intake.groups) {
                                            intake.groups.forEach(group => {
                                                groupSelect.append(`<option value="${group.id}">${group.name}</option>`);
                                            });
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}

function loadTimetable() {
    showLoading();
    
    // Get all current filter values
    const filters = {
        campus_id: $('#campus_id').val(),
        college_id: $('#college_id').val(),
        school_id: $('#school_id').val(),
        department_id: $('#department_id').val(),
        program_id: $('#program_id').val(),
        intake_id: $('#intake_id').val(),
        group_id: $('#group_id').val(),
        academic_year_id: $('#academic_year_id').val(),
        semester: $('#semester').val()
    };
    
    $.ajax({
        url: 'Dashboard/get_timetable.php',
        method: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            hideLoading();
            const container = $('.timetable-container');
            container.empty();

            if (response.success) {
                if (response.data && response.data.length > 0) {
                    displayTimetable(response.data);
                } else {
                    // Show no data message with current filters
                    const activeFilters = Object.entries(filters)
                        .filter(([key, value]) => value)
                        .map(([key, value]) => {
                            const select = $(`#${key}`);
                            const text = select.find('option:selected').text();
                            return `<div><strong>${key.replace('_id', '').replace(/\b\w/g, l => l.toUpperCase())}:</strong> ${text}</div>`;
                        })
                        .join('');

                    container.html(`
                        <div class="no-data-message">
                            <div class="alert alert-info">
                                <h4><i class="bi bi-info-circle"></i> No Timetable Found</h4>
                                <p>Current filters:</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-2">Organization Structure</h6>
                                        ${activeFilters}
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" onclick="resetFilters()">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                }
            } else {
                container.html(`
                    <div class="alert alert-danger">
                        <h4><i class="bi bi-exclamation-triangle"></i> Error</h4>
                        <p>${response.error || 'An error occurred while loading the timetable.'}</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            const container = $('.timetable-container');
            container.html(`
                <div class="alert alert-danger">
                    <h4><i class="bi bi-exclamation-triangle"></i> Error</h4>
                    <p>Failed to load timetable data. Please try again later.</p>
                    <small class="text-muted">Error details: ${error}</small>
                </div>
            `);
        }
    });
}

function displayTimetable(data) {
    const container = $('.timetable-container');
    container.empty();
    
    // Get selected filter values
    const campus = $('#campus_id option:selected').text();
    const college = $('#college_id option:selected').text();
    const school = $('#school_id option:selected').text();
    const department = $('#department_id option:selected').text();
    const program = $('#program_id option:selected').text();
    const intake = $('#intake_id option:selected').text();
    const academicYear = $('#academic_year_id option:selected').text();
    const semester = $('#semester option:selected').text();

    // Add organization structure and academic details header
    container.append(`
        <div class="timetable-header p-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="header-section">
                        <h4><i class="bi bi-building"></i> Organization Structure</h4>
                        <div class="header-content">
                            ${campus !== 'All Campuses' ? `<div><strong>Campus:</strong> ${campus}</div>` : ''}
                            ${college !== 'All Colleges' ? `<div><strong>College:</strong> ${college}</div>` : ''}
                            ${school !== 'All Schools' ? `<div><strong>School:</strong> ${school}</div>` : ''}
                            ${department !== 'All Departments' ? `<div><strong>Department:</strong> ${department}</div>` : ''}
                            ${program !== 'All Programs' ? `<div><strong>Program:</strong> ${program}</div>` : ''}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="header-section">
                        <h4><i class="bi bi-calendar-check"></i> Academic Details</h4>
                        <div class="header-content">
                            ${intake !== 'All Intakes' ? `<div><strong>Intake:</strong> ${intake}</div>` : ''}
                            ${academicYear !== 'All Academic Years' ? `<div><strong>Academic Year:</strong> ${academicYear}</div>` : ''}
                            ${semester !== 'All Semesters' ? `<div><strong>Semester:</strong> ${semester}</div>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);

    // Group sessions by their unique combination of day, time, module, and lecturer
    const groupedSessions = {};
    data.forEach(session => {
        const key = `${session.session.day}_${session.session.start_time}_${session.session.end_time}_${session.timetable.module.id}_${session.timetable.lecturer.id}`;
        if (!groupedSessions[key]) {
            groupedSessions[key] = {
                session: session.session,
                timetable: session.timetable,
                groups: []
            };
        }
        groupedSessions[key].groups = groupedSessions[key].groups.concat(session.timetable.groups);
    });

    // Create cards container
    const cardsContainer = $('<div class="timetable-cards"></div>');

    // Add sessions as cards
    Object.values(groupedSessions).forEach((groupedSession, index) => {
        const { session, timetable, groups } = groupedSession;
        
        // Create card for each session
        const card = $(`
            <div class="timetable-card">
                <div class="card-header">
                    <div class="session-time">
                        <i class="bi bi-clock"></i> ${session.start_time} - ${session.end_time}
                    </div>
                    <div class="session-day">
                        <i class="bi bi-calendar"></i> ${session.day}
                    </div>
                </div>
                <div class="card-body">
                    <div class="module-info">
                        <div class="module-header">
                            <div class="module-main">
                               <div class="module-period">
                                    <div class="period-item">
                                        <i class="bi bi-calendar3"></i>
                                        <span>Semester ${timetable.semester}</span>
                                    </div>
                                    <div class="period-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <span>${timetable.academic_year}</span>
                                    </div>
                                </div>
                                <div class="module-basic">
                                
                                    <div class="module-code-badge">
                                        <span class="code-label">Code</span>
                                        <span class="code-value">${timetable.module.code}</span>
                                    </div>
                                    <div class="module-title-section">
                                        <h5 class="module-title">${timetable.module.name}</h5>
                                        <div class="module-credits">
                                            <i class="bi bi-book"></i>
                                            <span>${timetable.module.credits} Credits</span>
                                        </div>
                                    </div>
                                </div>
                             
                            </div>
                        </div>
                    </div>
                    <div class="lecturer-info compact">
                        <div class="info-icon"><i class="bi bi-person-circle"></i></div>
                        <div class="info-content">
                            <div class="lecturer-details">
                                <div class="detail-item">
                                    <span class="detail-name">${timetable.lecturer.name}</span>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="bi bi-envelope"></i>
                                        <a href="mailto:${timetable.lecturer.email}" class="contact-link">
                                            ${timetable.lecturer.email}
                                        </a>
                                    </div>
                                    <div class="contact-item">
                                        <i class="bi bi-telephone"></i>
                                        <a href="tel:${timetable.lecturer.phone}" class="contact-link">
                                            ${timetable.lecturer.phone}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="facility-info compact">
                        <div class="info-icon"><i class="bi bi-building"></i></div>
                        <div class="info-content">
                            <div class="facility-details">
                                <div class="facility-header">
                                    <span class="facility-name">${timetable.facility.name}</span>
                                    <span class="facility-type">${timetable.facility.type}</span>
                                </div>
                                <div class="facility-meta">
                                    <span class="facility-location" title="${timetable.facility.location}">
                                        <i class="bi bi-geo-alt"></i> ${timetable.facility.location}
                                    </span>
                                    <span class="facility-capacity">
                                        <i class="bi bi-people"></i> ${timetable.facility.capacity}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="groups-info">
                        <div class="groups-header" data-bs-toggle="collapse" data-bs-target="#groups-${index}" aria-expanded="false">
                            <h6 class="mb-0">
                                <i class="bi bi-people"></i> Groups (${groups.length})
                                <i class="bi bi-chevron-down ms-2"></i>
                            </h6>
                        </div>
                        <div class="collapse" id="groups-${index}">
                            <div class="groups-list">
                                ${groups.map(group => `
                                    <div class="group-item">
                                        <div class="group-header">
                                            <div class="group-name">${group.name}</div>
                                            <div class="group-size">Size: ${group.size}</div>
                                        </div>
                                        <div class="group-details">
                                            <div class="detail-row">
                                                <i class="bi bi-geo-alt"></i>
                                                <span>Campus: ${group.campus.name}</span>
                                            </div>
                                            <div class="detail-row">
                                                <i class="bi bi-building"></i>
                                                <span>College: ${group.college.name}</span>
                                            </div>
                                            <div class="detail-row">
                                                <i class="bi bi-bank"></i>
                                                <span>School: ${group.school.name}</span>
                                            </div>
                                            <div class="detail-row">
                                                <i class="bi bi-diagram-3"></i>
                                                <span>Department: ${group.department.name}</span>
                                            </div>
                                            <div class="detail-row">
                                                <i class="bi bi-mortarboard"></i>
                                                <span>Program: ${group.program.name} (${group.program.code})</span>
                                            </div>
                                            <div class="detail-row">
                                                <i class="bi bi-calendar-date"></i>
                                                <span>Intake: ${group.intake.year}/${group.intake.month}</span>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        cardsContainer.append(card);
    });

    container.append(cardsContainer);

    // Update event listeners for collapse functionality
    $('.groups-header').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('bs-target');
        const icon = $(this).find('.bi-chevron-down');
        const collapseElement = $(target);
        
        // Toggle the collapse state
        if (collapseElement.hasClass('show')) {
            collapseElement.removeClass('show');
            icon.removeClass('rotate-icon');
        } else {
            // Close all other open collapses first
            $('.collapse.show').removeClass('show');
            $('.bi-chevron-down.rotate-icon').removeClass('rotate-icon');
            
            // Open the clicked one
            collapseElement.addClass('show');
            icon.addClass('rotate-icon');
        }
    });
}