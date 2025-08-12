<?php
session_start();    
include('connection.php');

// Error handling for database connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get current system settings with error handling
$system_query = "SELECT s.*, ay.year_label 
                FROM system s 
                LEFT JOIN academic_year ay ON s.accademic_year_id = ay.id 
                LIMIT 1";
$system_result = mysqli_query($connection, $system_query);

if (!$system_result) {
    die("Error fetching system settings: " . mysqli_error($connection));
}

$system_data = mysqli_fetch_assoc($system_result);

// Set defaults if no system data found
if (!$system_data) {
    $system_data = [
        'accademic_year_id' => 1,
        'semester' => '1',
        'year_label' => 'Current Academic Year'
    ];
}

// Get academic years with error handling
$academic_years_query = "SELECT * FROM academic_year ORDER BY year_label DESC";
$academic_years_result = mysqli_query($connection, $academic_years_query);

if (!$academic_years_result) {
    die("Error fetching academic years: " . mysqli_error($connection));
}

// Get programs for module filtering with error handling
$programs_query = "SELECT p.*, d.name as department_name 
                  FROM program p 
                  LEFT JOIN department d ON p.department_id = d.id 
                  ORDER BY p.name";
$programs_result = mysqli_query($connection, $programs_query);

if (!$programs_result) {
    die("Error fetching programs: " . mysqli_error($connection));
}

// Get student groups with error handling
$groups_query = "SELECT sg.*, i.year, i.month, p.name as program_name 
                FROM student_group sg 
                LEFT JOIN intake i ON sg.intake_id = i.id 
                LEFT JOIN program p ON i.program_id = p.id 
                ORDER BY i.year DESC, i.month DESC";
$groups_result = mysqli_query($connection, $groups_query);

if (!$groups_result) {
    die("Error fetching student groups: " . mysqli_error($connection));
}

// Get facilities with error handling
$facilities_query = "SELECT f.*, c.name as campus_name 
                    FROM facility f 
                    LEFT JOIN campus c ON f.campus_id = c.id 
                    WHERE f.type IN ('classroom', 'Lecture Hall', 'Laboratory')
                    ORDER BY f.name";
$facilities_result = mysqli_query($connection, $facilities_query);

if (!$facilities_result) {
    die("Error fetching facilities: " . mysqli_error($connection));
}

// Get lecturers with error handling
$lecturers_query = "SELECT id, names, email FROM users WHERE role = 'lecturer' ORDER BY names";
$lecturers_result = mysqli_query($connection, $lecturers_query);

if (!$lecturers_result) {
    die("Error fetching lecturers: " . mysqli_error($connection));
}

// Get modules with error handling
$modules_query = "SELECT m.*, p.name as program_name, p.id as program_id 
                 FROM module m 
                 LEFT JOIN program p ON m.program_id = p.id 
                 ORDER BY m.code";
$modules_result = mysqli_query($connection, $modules_query);

if (!$modules_result) {
    die("Error fetching modules: " . mysqli_error($connection));
}

// Get all campuses with error handling
$campuses_query = "SELECT id, name FROM campus ORDER BY name";
$campuses_result = mysqli_query($connection, $campuses_query);

if (!$campuses_result) {
    die("Error fetching campuses: " . mysqli_error($connection));
}

// Get all colleges with error handling
$colleges_query = "SELECT c.id, c.name, cam.name as campus_name 
                  FROM college c 
                  LEFT JOIN campus cam ON c.campus_id = cam.id 
                  ORDER BY c.name";
$colleges_result = mysqli_query($connection, $colleges_query);

if (!$colleges_result) {
    die("Error fetching colleges: " . mysqli_error($connection));
}

// Get all schools with error handling
$schools_query = "SELECT s.id, s.name, c.name as college_name 
                 FROM school s 
                 LEFT JOIN college c ON s.college_id = c.id 
                 ORDER BY s.name";
$schools_result = mysqli_query($connection, $schools_query);

if (!$schools_result) {
    die("Error fetching schools: " . mysqli_error($connection));
}

// Get all departments with error handling
$departments_query = "SELECT d.id, d.name, s.name as school_name 
                     FROM department d 
                     LEFT JOIN school s ON d.school_id = s.id 
                     ORDER BY d.name";
$departments_result = mysqli_query($connection, $departments_query);

if (!$departments_result) {
    die("Error fetching departments: " . mysqli_error($connection));
}

// Get all intakes with error handling
$intakes_query = "SELECT i.id, i.year, i.month, p.name as program_name 
                 FROM intake i 
                 LEFT JOIN program p ON i.program_id = p.id 
                 ORDER BY i.year DESC, i.month DESC";
$intakes_result = mysqli_query($connection, $intakes_query);

if (!$intakes_result) {
    die("Error fetching intakes: " . mysqli_error($connection));
}

$semesters = ['1', '2', '3'];

// Function to safely echo values
function safe_echo($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Function to check if value is selected
function is_selected($value, $compare) {
    return ($value == $compare) ? 'selected' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management - UR</title>
    
    <!-- Favicons -->
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">
    
    <!-- Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Load Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Load Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/timetable.css">

    <!-- Template Main CSS File -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php
    // Check if includes exist before including
    $header_file = "./includes/header.php";
    $menu_file = "./includes/menu.php";
    
    if (file_exists($header_file)) {
        include($header_file);
    } else {
        echo "<!-- Header file not found -->";
    }
    
    if (file_exists($menu_file)) {
        include($menu_file);
    } else {
        echo "<!-- Menu file not found -->";
    }
    ?>

    <main id="main" class="main">
        <div class="main-container">
            <!-- Header -->
            <div class="page-header">
                <h1>
                    <div class="header-icon">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    UR Timetable Management
                </h1>
                <div class="date-nav">
                    <button type="button" onclick="previousWeek()" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span id="currentDate">Today</span>
                    <button type="button" onclick="nextWeek()" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="view-tab" data-bs-toggle="tab" data-bs-target="#view-pane" type="button" role="tab" aria-controls="view-pane" aria-selected="true">
                        <i class="bi bi-eye"></i> View Timetable
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule-pane" type="button" role="tab" aria-controls="schedule-pane" aria-selected="false">
                        <i class="bi bi-plus-circle"></i> Schedule Class
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="mainTabContent">
                <!-- View Timetable Tab -->
                <div class="tab-pane fade show active" id="view-pane" role="tabpanel" aria-labelledby="view-tab">
                    <!-- Filters -->
                    <div class="filters-container">
                        <div class="filter-title">
                            <i class="bi bi-funnel"></i>
                            Filter Timetable
                        </div>

                        <form id="filterForm">
                            <!-- Academic Period Filters -->
                            <div class="academic-period-filters">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="filter-group">
                                            <label for="view_academic_year_id"><i class="bi bi-calendar2-week"></i> Academic Year</label>
                                            <select class="form-select" id="view_academic_year_id" name="academic_year_id">
                                                <option value="">All Academic Years</option>
                                                <?php 
                                                if (mysqli_num_rows($academic_years_result) > 0) {
                                                    mysqli_data_seek($academic_years_result, 0);
                                                    while ($year = mysqli_fetch_assoc($academic_years_result)): 
                                                ?>
                                                    <option value="<?php echo safe_echo($year['id']); ?>" 
                                                        <?php echo is_selected($year['id'], $system_data['accademic_year_id']); ?>>
                                                        <?php echo safe_echo($year['year_label']); ?>
                                                    </option>
                                                <?php 
                                                    endwhile; 
                                                } else {
                                                    echo '<option value="">No academic years available</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="filter-group">
                                            <label for="view_semester"><i class="bi bi-calendar3"></i> Semester</label>
                                            <select class="form-select" id="view_semester" name="semester">
                                                <option value="">All Semesters</option>
                                                <?php foreach ($semesters as $sem): ?>
                                                    <option value="<?php echo safe_echo($sem); ?>" 
                                                        <?php echo is_selected($sem, $system_data['semester']); ?>>
                                                        Semester <?php echo safe_echo($sem); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step Indicator -->
                            <div class="step-indicator">
                                <div class="step active" data-step="campus">
                                    <div class="step-icon"><i class="bi bi-geo-alt"></i></div>
                                    <div class="step-label">Campus</div>
                                </div>
                                <div class="step" data-step="college">
                                    <div class="step-icon"><i class="bi bi-building"></i></div>
                                    <div class="step-label">College</div>
                                </div>
                                <div class="step" data-step="school">
                                    <div class="step-icon"><i class="bi bi-bank"></i></div>
                                    <div class="step-label">School</div>
                                </div>
                                <div class="step" data-step="department">
                                    <div class="step-icon"><i class="bi bi-diagram-3"></i></div>
                                    <div class="step-label">Department</div>
                                </div>
                                <div class="step" data-step="program">
                                    <div class="step-icon"><i class="bi bi-mortarboard"></i></div>
                                    <div class="step-label">Program</div>
                                </div>
                                <div class="step" data-step="intake">
                                    <div class="step-icon"><i class="bi bi-calendar"></i></div>
                                    <div class="step-label">Intake</div>
                                </div>
                                <div class="step" data-step="group">
                                    <div class="step-icon"><i class="bi bi-people"></i></div>
                                    <div class="step-label">Group</div>
                                </div>
                            </div>

                            <!-- Filter Steps -->
                            <div class="filter-steps">
                                <div class="filter-step active" id="campus-step">
                                    <div class="filter-group">
                                        <label for="campus_id"><i class="bi bi-geo-alt"></i> Select Campus</label>
                                        <select class="form-select" id="campus_id" name="campus_id">
                                            <option value="">All Campuses</option>
                                            <?php 
                                            if (mysqli_num_rows($campuses_result) > 0) {
                                                mysqli_data_seek($campuses_result, 0);
                                                while ($campus = mysqli_fetch_assoc($campuses_result)): 
                                            ?>
                                                <option value="<?php echo safe_echo($campus['id']); ?>">
                                                    <?php echo safe_echo($campus['name']); ?>
                                                </option>
                                            <?php 
                                                endwhile; 
                                            } else {
                                                echo '<option value="">No campuses available</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-step" id="college-step">
                                    <div class="filter-group">
                                        <label for="college_id"><i class="bi bi-building"></i> Select College</label>
                                        <select class="form-select" id="college_id" name="college_id">
                                            <option value="">All Colleges</option>
                                            <?php 
                                            if (mysqli_num_rows($colleges_result) > 0) {
                                                mysqli_data_seek($colleges_result, 0);
                                                while ($college = mysqli_fetch_assoc($colleges_result)): 
                                            ?>
                                                <option value="<?php echo safe_echo($college['id']); ?>">
                                                    <?php echo safe_echo($college['name']); ?>
                                                </option>
                                            <?php 
                                                endwhile; 
                                            } else {
                                                echo '<option value="">No college available</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-step" id="school-step">
                                    <div class="filter-group">
                                        <label for="school_id"><i class="bi bi-bank"></i> Select School</label>
                                        <select class="form-select" id="school_id" name="school_id">
                                            <option value="">All Schools</option>
                                            <?php 
                                            if (mysqli_num_rows($schools_result) > 0) {
                                                mysqli_data_seek($schools_result, 0);
                                                while ($school = mysqli_fetch_assoc($schools_result)): 
                                            ?>
                                                <option value="<?php echo safe_echo($school['id']); ?>">
                                                    <?php echo safe_echo($school['name']); ?>
                                                </option>
                                            <?php 
                                                endwhile; 
                                            } else {
                                                echo '<option value="">No school available</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-step" id="department-step">
                                    <div class="filter-group">
                                        <label for="department_id"><i class="bi bi-diagram-3"></i> Select Department</label>
                                        <select class="form-select" id="department_id" name="department_id">
                                            <option value="">All Departments</option>
                                            <?php 
                                            if (mysqli_num_rows($departments_result) > 0) {
                                                mysqli_data_seek($departments_result, 0);
                                                while ($department = mysqli_fetch_assoc($departments_result)): 
                                            ?>
                                                <option value="<?php echo safe_echo($department['id']); ?>">
                                                    <?php echo safe_echo($department['name']); ?>
                                                </option>
                                            <?php 
                                                endwhile; 
                                            } else {
                                                echo '<option value="">No departments available</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-step" id="program-step">
                                    <div class="filter-group">
                                        <label for="program_id"><i class="bi bi-mortarboard"></i> Select Program</label>
                                        <select class="form-select" id="program_id" name="program_id">
                                            <option value="">All Programs</option>
                                            <?php 
                                            if (mysqli_num_rows($programs_result) > 0) {
                                                mysqli_data_seek($programs_result, 0);
                                                while ($program = mysqli_fetch_assoc($programs_result)): 
                                            ?>
                                                <option value="<?php echo safe_echo($program['id']); ?>">
                                                    <?php echo safe_echo($program['name']); ?>
                                                </option>
                                            <?php 
                                                endwhile; 
                                            } else {
                                                echo '<option value="">No program available</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-step" id="intake-step">
                                    <div class="filter-group">
                                        <label for="intake_id"><i class="bi bi-calendar"></i> Select Intake</label>
                                        <select class="form-select" id="intake_id" name="intake_id">
                                            <option value="">All Intakes</option>
                                            <?php 
                                            if (mysqli_num_rows($intakes_result) > 0) {
                                                mysqli_data_seek($intakes_result, 0);
                                                while ($intake = mysqli_fetch_assoc($intakes_result)): 
                                                    $intake_label = safe_echo($intake['program_name']) . ' - ' . safe_echo($intake['month']) . '/' . safe_echo($intake['year']);
                                            ?>
                                                <option value="<?php echo safe_echo($intake['id']); ?>">
                                                    <?php echo $intake_label; ?>
                                                </option>
                                            <?php 
                                                endwhile; 
                                            } else {
                                                echo '<option value="">No intakes available</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="filter-step" id="group-step">
                                    <div class="filter-group">
                                        <label for="group_id"><i class="bi bi-people"></i> Select Group</label>
                                        <select class="form-select" id="group_id" name="group_id">
                                            <option value="">All Groups</option>
                                            <?php 
                                            if (mysqli_num_rows($groups_result) > 0) {
                                                mysqli_data_seek($groups_result, 0);
                                                while ($group = mysqli_fetch_assoc($groups_result)): 
                                            ?>
                                                <option value="<?php echo safe_echo($group['id']); ?>">
                                                    <?php echo safe_echo($group['name']); ?>
                                                </option>
                                            <?php 
                                                endwhile; 
                                            } else {
                                                echo '<option value="">No groups available</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="filter-actions">
                                <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                                    <i class="bi bi-arrow-left"></i> Previous
                                </button>
                                <button type="button" class="btn btn-primary" id="nextBtn">
                                    Next <i class="bi bi-arrow-right"></i>
                                </button>
                                <button type="submit" class="btn btn-success" id="applyBtn" style="display: none;">
                                    <i class="bi bi-search"></i> Apply Filters
                                </button>
                                <button type="button" class="btn btn-danger" onclick="resetFilters()">
                                    <i class="bi bi-x-circle"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Timetable Wrapper -->
                    <div class="timetable-wrapper">
                        <div class="timetable-main" id="timetableMain">
                            <div class="timetable-cards" id="timetableCards">
                                <!-- Loading State -->
                                <div class="loading" id="loadingIndicator">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p>Loading timetable...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Class Tab -->
                <div class="tab-pane fade" id="schedule-pane" role="tabpanel" aria-labelledby="schedule-tab">
                    <div class="schedule-form">
                        <h5 class="mb-4">
                            <i class="bi bi-plus-circle"></i> Schedule New Class
                        </h5>
                        <form id="timetableForm" method="POST" action="save_timetable.php">
                            <div class="mb-3" style="display: none;">
                                <label for="academicYear" class="form-label">Academic Year</label>
                                <select class="form-select" id="academicYear" name="academic_year_id" required disabled>
                                    <?php 
                                    if (mysqli_num_rows($academic_years_result) > 0) {
                                        mysqli_data_seek($academic_years_result, 0);
                                        while ($year = mysqli_fetch_assoc($academic_years_result)): 
                                    ?>
                                        <option value="<?php echo safe_echo($year['id']); ?>" 
                                            <?php echo is_selected($year['id'], $system_data['accademic_year_id']); ?>>
                                            <?php echo safe_echo($year['year_label']); ?>
                                        </option>
                                    <?php 
                                        endwhile; 
                                    }
                                    ?>
                                </select>
                                <input type="hidden" name="academic_year_id" value="<?php echo safe_echo($system_data['accademic_year_id']); ?>">
                            </div>

                            <div class="mb-3" style="display: none;">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester" required disabled>
                                    <option value="1" <?php echo is_selected('1', $system_data['semester']); ?>>Semester 1</option>
                                    <option value="2" <?php echo is_selected('2', $system_data['semester']); ?>>Semester 2</option>
                                    <option value="3" <?php echo is_selected('3', $system_data['semester']); ?>>Semester 3</option>
                                </select>
                                <input type="hidden" name="semester" value="<?php echo safe_echo($system_data['semester']); ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-clock"></i> Schedule
                                </label>
                                <div id="scheduleContainer" class="schedule-container">
                                    <div class="session-entry">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-4">
                                                <select class="form-select session-day" name="sessions[0][day]" required>
                                                    <option value="">Select Day</option>
                                                    <option value="Monday">Monday</option>
                                                    <option value="Tuesday">Tuesday</option>
                                                    <option value="Wednesday">Wednesday</option>
                                                    <option value="Thursday">Thursday</option>
                                                    <option value="Friday">Friday</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="time" class="form-control session-start" name="sessions[0][start_time]" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="time" class="form-control session-end" name="sessions[0][end_time]" required>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-success add-session w-100">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="studentGroups" class="form-label">
                                    <i class="bi bi-people"></i> Student Groups
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="selectedGroupsDisplay" readonly placeholder="Select groups">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#groupsModal">
                                        Select Groups
                                    </button>
                                </div>
                                <div id="selectedGroups" class="selected-groups"></div>
                            </div>

                            <div class="mb-3">
                                <label for="facility" class="form-label">
                                    <i class="bi bi-geo-alt"></i> Facility
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="selectedFacilityDisplay" readonly placeholder="Select facility">
                                    <button type="button" class="btn btn-primary" id="facilityButton" data-bs-toggle="modal" data-bs-target="#facilityModal" disabled>
                                        Select Facility
                                    </button>
                                </div>
                                <input type="hidden" id="facility" name="facility_id" required>
                            </div>

                            <div class="mb-3">
                                <label for="module" class="form-label">
                                    <i class="bi bi-book"></i> Module
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="selectedModuleDisplay" readonly placeholder="Select module">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#moduleModal">
                                        Select Module
                                    </button>
                                </div>
                                <input type="hidden" id="module" name="module_id" required>
                            </div>

                            <div class="mb-3">
                                <label for="lecturer" class="form-label">
                                    <i class="bi bi-person"></i> Lecturer
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="selectedLecturerDisplay" readonly placeholder="Select lecturer">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lecturerModal">
                                        Select Lecturer
                                    </button>
                                </div>
                                <input type="hidden" id="lecturer" name="lecturer_id" required>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i> Schedule Class
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide Panel -->
        <div class="slide-panel" id="slidePanel">
            <div class="panel-header">
                <h3 class="panel-title" id="panelTitle">Session Details</h3>
                <p class="panel-subtitle" id="panelSubtitle">Detailed information</p>
                <button class="close-panel btn btn-sm btn-outline-secondary" onclick="closePanel()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="panel-content" id="panelContent">
                <!-- Content will be populated dynamically -->
            </div>
        </div>

        <!-- Overlay -->
        <div class="overlay" id="overlay" onclick="closePanel()"></div>

        <?php
        // Include modals with error handling
        $modals = [
            'module_selector.php',
            'lecturer_selector.php', 
            'facility_selector.php',
            'group_selector.php'
        ];
        
        foreach ($modals as $modal) {
            if (file_exists($modal)) {
                include($modal);
            } else {
                echo "<!-- Modal file $modal not found -->";
            }
        }
        ?>
    </main>

    <!-- Scripts -->
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Timetable Management System loaded');
            
            // Initialize date display
            updateCurrentDate();
            
            // Initialize filter functionality if the script is available
            if (typeof initializeFilters === 'function') {
                initializeFilters();
            }
            
            // Initialize form validation
            initializeFormValidation();
        });

        function updateCurrentDate() {
            const today = new Date();
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            document.getElementById('currentDate').textContent = today.toLocaleDateString('en-US', options);
        }

        function previousWeek() {
            console.log('Previous week clicked');
            // Implementation depends on your timetable.js
        }

        function nextWeek() {
            console.log('Next week clicked');
            // Implementation depends on your timetable.js
        }

        function resetFilters() {
            console.log('Reset filters clicked');
            document.getElementById('filterForm').reset();
            // Reset any dynamic content
            const steps = document.querySelectorAll('.filter-step');
            steps.forEach((step, index) => {
                if (index === 0) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }

        function closePanel() {
            const panel = document.getElementById('slidePanel');
            const overlay = document.getElementById('overlay');
            
            if (panel) panel.style.display = 'none';
            if (overlay) overlay.style.display = 'none';
        }

        function initializeFormValidation() {
            const form = document.getElementById('timetableForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Basic validation
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    // Validate time inputs
                    const startTimes = form.querySelectorAll('.session-start');
                    const endTimes = form.querySelectorAll('.session-end');
                    
                    for (let i = 0; i < startTimes.length; i++) {
                        const start = startTimes[i].value;
                        const end = endTimes[i].value;
                        
                        if (start && end && start >= end) {
                            alert('End time must be after start time');
                            isValid = false;
                            break;
                        }
                    }
                    
                    if (isValid) {
                        // Submit form
                        this.submit();
                    }
                });
            }
        }

        // Add session functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-session') || e.target.closest('.add-session')) {
                e.preventDefault();
                addNewSession();
            }
            
            if (e.target.classList.contains('remove-session') || e.target.closest('.remove-session')) {
                e.preventDefault();
                removeSession(e.target.closest('.session-entry'));
            }
        });

        function addNewSession() {
            const container = document.getElementById('scheduleContainer');
            const sessionCount = container.querySelectorAll('.session-entry').length;
            
            const newSession = document.createElement('div');
            newSession.className = 'session-entry';
            newSession.innerHTML = `
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <select class="form-select session-day" name="sessions[${sessionCount}][day]" required>
                            <option value="">Select Day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="time" class="form-control session-start" name="sessions[${sessionCount}][start_time]" required>
                    </div>
                    <div class="col-md-3">
                        <input type="time" class="form-control session-end" name="sessions[${sessionCount}][end_time]" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-session w-100">
                            <i class="fas fa-minus"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(newSession);
        }

        function removeSession(sessionElement) {
            const container = document.getElementById('scheduleContainer');
            if (container.querySelectorAll('.session-entry').length > 1) {
                sessionElement.remove();
                // Update session indices
                updateSessionIndices();
            } else {
                alert('At least one session is required');
            }
        }

        function updateSessionIndices() {
            const sessions = document.querySelectorAll('.session-entry');
            sessions.forEach((session, index) => {
                const inputs = session.querySelectorAll('select, input');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    if (name && name.includes('sessions[')) {
                        const newName = name.replace(/sessions\[\d+\]/, `sessions[${index}]`);
                        input.setAttribute('name', newName);
                    }
                });
            });
        }

        // Error handling for missing scripts
        window.addEventListener('error', function(e) {
            console.warn('Script error:', e.message);
        });

        // Fallback functions if external scripts are missing
        if (typeof previousWeek !== 'function') {
            window.previousWeek = function() {
                console.log('Previous week functionality not implemented');
            };
        }

        if (typeof nextWeek !== 'function') {
            window.nextWeek = function() {
                console.log('Next week functionality not implemented');
            };
        }

        if (typeof resetFilters !== 'function') {
            window.resetFilters = function() {
                document.getElementById('filterForm').reset();
                console.log('Filters reset');
            };
        }
    </script>

    <!-- Load external timetable script if available -->
    <script>
        // Try to load the timetable.js file
        const timetableScript = document.createElement('script');
        timetableScript.src = 'assets/js/timetable.js';
        timetableScript.onerror = function() {
            console.warn('timetable.js not found, using fallback functions');
        };
        document.head.appendChild(timetableScript);
    </script>

    <!-- Vendor JS Files -->
    <script src="assets/vendor/apexcharts/apexcharts.min.js" onerror="console.warn('ApexCharts not found')"></script>
    <script src="assets/vendor/chart.js/chart.umd.js" onerror="console.warn('Chart.js not found')"></script>
    <script src="assets/vendor/echarts/echarts.min.js" onerror="console.warn('ECharts not found')"></script>
    <script src="assets/vendor/quill/quill.min.js" onerror="console.warn('Quill not found')"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js" onerror="console.warn('Simple DataTables not found')"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js" onerror="console.warn('TinyMCE not found')"></script>
    <script src="assets/vendor/php-email-form/validate.js" onerror="console.warn('Email form validator not found')"></script>

    <!-- Template Main JS File -->
    <script src="assets/js/main.js" onerror="console.warn('main.js not found')"></script>

</body>
</html>

