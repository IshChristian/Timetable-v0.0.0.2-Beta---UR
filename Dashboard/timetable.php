<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("connection.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Get current user's campus
$current_user_id = $_SESSION['id'];
$user_query = mysqli_query($connection, "SELECT role, campus FROM users WHERE id = $current_user_id");
$current_user = mysqli_fetch_assoc($user_query);
$campus_id = $current_user['campus'];

// Fetch academic years
$academic_years = [];
$academic_years_query = "SELECT id, year_label FROM academic_year ORDER BY year_label DESC";
$academic_years_result = mysqli_query($connection, $academic_years_query);
if (!$academic_years_result) {
    die("Error fetching academic years: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($academic_years_result)) {
    $academic_years[] = $row;
}

// Fetch modules
$modules = [];
$modules_query = "SELECT m.id, m.code, m.name FROM module m ORDER BY m.code";
$modules_result = mysqli_query($connection, $modules_query);
if (!$modules_result) {
    die("Error fetching modules: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($modules_result)) {
    $modules[] = $row;
}

// Fetch lecturers
$lecturers = [];
$lecturers_query = "SELECT id, names FROM users WHERE role = 'lecturer' ORDER BY names";
$lecturers_result = mysqli_query($connection, $lecturers_query);
if (!$lecturers_result) {
    die("Error fetching lecturers: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($lecturers_result)) {
    $lecturers[] = $row;
}

// Fetch facilities
$facilities = [];
$facilities_query = "SELECT id, name FROM facility WHERE type IN ('classroom', 'Lecture Hall', 'Laboratory') ORDER BY name";
$facilities_result = mysqli_query($connection, $facilities_query);
if (!$facilities_result) {
    die("Error fetching facilities: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($facilities_result)) {
    $facilities[] = $row;
}

// Fetch intakes
$intakes = [];
$intakes_query = "SELECT i.id, i.year, i.month, p.name as program_name FROM intake i LEFT JOIN program p ON i.program_id = p.id ORDER BY i.year DESC, i.month DESC";
$intakes_result = mysqli_query($connection, $intakes_query);
if (!$intakes_result) {
    die("Error fetching intakes: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($intakes_result)) {
    $intakes[] = $row;
}

// Fetch programs (only those with intakes)
$programs = [];
$programs_query = "SELECT DISTINCT p.id, p.name FROM program p INNER JOIN intake i ON i.program_id = p.id ORDER BY p.name";
$programs_result = mysqli_query($connection, $programs_query);
if (!$programs_result) {
    die("Error fetching programs: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($programs_result)) {
    $programs[] = $row;
}

// Fetch groups
$groups = [];
$groups_query = "SELECT sg.id, sg.name, i.year, i.month, p.name as program_name FROM student_group sg LEFT JOIN intake i ON sg.intake_id = i.id LEFT JOIN program p ON i.program_id = p.id ORDER BY i.year DESC, i.month DESC";
$groups_result = mysqli_query($connection, $groups_query);
if (!$groups_result) {
    die("Error fetching groups: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($groups_result)) {
    $groups[] = $row;
}

// Fetch all scheduled classes from the database
$schedules = [];
$schedule_query = "
    SELECT 
        t.id,
        m.name AS subject,
        m.code AS module_code,
        f.name AS room,
        ts.start_time,
        ts.end_time,
        ts.day,
        ts.date,
        g.name AS group_name,
        u.names AS teacher
    FROM timetable t
    INNER JOIN module m ON t.module_id = m.id
    INNER JOIN facility f ON t.facility_id = f.id
    INNER JOIN users u ON t.leader_lecturer_id = u.id
    INNER JOIN timetable_sessions ts ON ts.timetable_id = t.id
    INNER JOIN timetable_groups tg ON tg.timetable_id = t.id
    INNER JOIN student_group g ON tg.group_id = g.id
    ORDER BY FIELD(ts.day, 'Monday','Tuesday','Wednesday','Thursday','Friday'), ts.start_time
";
$schedule_result = mysqli_query($connection, $schedule_query);
if (!$schedule_result) {
    die("Error fetching schedules: " . mysqli_error($connection));
}
while ($row = mysqli_fetch_assoc($schedule_result)) {
    // Format time slot for easier filtering
    $row['time'] = substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5);
    $row['group'] = $row['group_name'];
    $schedules[] = $row;
}

// Get unique subjects, groups, and rooms for filters
$subjects = [];
$groupsList = [];
$rooms = [];
foreach ($schedules as $s) {
    $subjects[$s['subject']] = true;
    $groupsList[$s['group_name']] = true;
    $rooms[$s['room']] = true;
}

// Get week start from GET or default to current week (Monday)
$week_start = isset($_GET['week_start']) ? $_GET['week_start'] : null;
if ($week_start && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week_start)) {
    $monday = new DateTime($week_start);
} else {
    $monday = new DateTime();
    $monday->modify('Monday this week');
}
$week_dates = [];
for ($i = 0; $i < 5; $i++) {
    $date = clone $monday;
    $date->modify("+$i days");
    $week_dates[] = [
        'date' => $date->format('Y-m-d'),
        'day' => $date->format('l')
    ];
}
$week_label = $week_dates[0]['date'] . " to " . $week_dates[4]['date'];

// Group schedules by day and time for the selected week
$schedulesByDayTime = [];
foreach ($schedules as $s) {
    foreach ($week_dates as $wd) {
        if ($s['day'] === $wd['day']) {
            $schedulesByDayTime[$s['time']][$wd['day']][] = $s;
        }
    }
}
// Get all unique time slots for the week
$timeSlotsForWeek = array_keys($schedulesByDayTime);
sort($timeSlotsForWeek);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>UR-TIMETABLE - Timetable</title>
  <link href="assets/img/icon1.png" rel="icon" />
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
  <link href="assets/css/style.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .time-slot {
        min-height: 80px;
    }
    .class-card {
        transition: all 0.2s ease;
    }
    .class-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .modal {
        backdrop-filter: blur(4px);
    }
    @media (max-width: 900px) {
        .timetable-table thead {
            display: none;
        }
        .timetable-table, .timetable-table tbody, .timetable-table tr, .timetable-table td {
            display: block;
            width: 100%;
        }
        .timetable-table tr {
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f3f4f6;
        }
        .timetable-table td {
            text-align: left;
            padding-left: 50%;
            position: relative;
            min-height: 60px;
            border: none;
            border-bottom: 1px solid #e5e7eb;
        }
        .timetable-table td:before {
            position: absolute;
            left: 1rem;
            top: 1rem;
            width: 45%;
            white-space: nowrap;
            font-weight: 600;
            color: #2563eb;
            content: attr(data-label);
        }
    }

    .timetable-table th, .timetable-table td {
        border-right: 1px solid #e0e7ff;
    }
    .timetable-table th:last-child, .timetable-table td:last-child {
        border-right: none;
    }
    .timetable-table tr:last-child td {
        border-bottom: none;
    }
    .time-slot {
        min-width: 110px;
        background: linear-gradient(90deg, #f0f7ff 60%, #fff 100%);
        font-weight: 600;
        color: #2563eb;
        border-right: 1px solid #e0e7ff;
    }
    .class-card {
        background: linear-gradient(135deg, #e0e7ff 60%, #fff 100%);
        border-left: 4px solid #2563eb;
        box-shadow: 0 2px 8px rgba(37,99,235,0.07);
        margin-bottom: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        transition: box-shadow 0.2s, transform 0.2s;
        cursor: pointer;
        position: relative;
    }
    .class-card:hover {
        box-shadow: 0 6px 24px rgba(37,99,235,0.18);
        transform: translateY(-2px) scale(1.03);
        z-index: 2;
    }
    .class-card .subject {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }
    .class-card .group-badge {
        background: #2563eb;
        color: #fff;
        font-size: 0.75rem;
        padding: 0.15rem 0.6rem;
        border-radius: 9999px;
        margin-left: 0.5rem;
        font-weight: 500;
        letter-spacing: 0.02em;
    }
    .class-card .meta {
        font-size: 0.85rem;
        color: #475569;
        margin-bottom: 0.15rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .class-card .meta i {
        color: #2563eb;
        font-size: 0.95em;
    }
  </style>
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
</head>
<body>
  <?php include("./includes/header.php"); ?>
  <?php include("./includes/menu.php"); ?>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Timetable</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Timetable</li>
        </ol>
      </nav>
    </div>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-calendar-alt text-blue-600 text-2xl"></i>
                    <h1 class="text-2xl font-bold text-gray-900">Student Timetable</h1>
                </div>
                <button onclick="openNewScheduleModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                    <i class="fas fa-plus"></i>
                    <span>New Schedule</span>
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-wrap items-center gap-4">
            <form method="get" class="flex items-center gap-2">
                <label for="week_start" class="text-sm text-gray-600">Select Week (Monday):</label>
                <input type="date" id="week_start" name="week_start" value="<?= htmlspecialchars($monday->format('Y-m-d')) ?>" class="border border-gray-300 rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">Show</button>
                <a href="timetable.php" class="ml-2 text-blue-600 hover:underline text-sm">This Week</a>
            </form>
            <span class="ml-4 text-gray-700 text-sm">
                Showing week: <b><?= htmlspecialchars($week_label) ?></b>
            </span>
            <button onclick="downloadTimetable()" class="ml-auto bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm flex items-center gap-2">
                <i class="fas fa-download"></i> Download Timetable
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-filter text-gray-500"></i>
                    <span class="font-medium text-gray-700">Filters:</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-600">Subject:</label>
                    <select id="subjectFilter" onchange="applyFilters()" class="border border-gray-300 rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Subjects</option>
                        <?php foreach (array_keys($subjects) as $subject): ?>
                            <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-600">Group:</label>
                    <select id="groupFilter" onchange="applyFilters()" class="border border-gray-300 rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Groups</option>
                        <?php foreach (array_keys($groupsList) as $group): ?>
                            <option value="<?= htmlspecialchars($group) ?>"><?= htmlspecialchars($group) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-600">Room:</label>
                    <select id="roomFilter" onchange="applyFilters()" class="border border-gray-300 rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Rooms</option>
                        <?php foreach (array_keys($rooms) as $room): ?>
                            <option value="<?= htmlspecialchars($room) ?>"><?= htmlspecialchars($room) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button onclick="clearFilters()" type="button" class="text-blue-600 hover:text-blue-800 text-sm flex items-center space-x-1">
                    <i class="fas fa-times"></i>
                    <span>Clear Filters</span>
                </button>
            </div>
        </div>

        <!-- Timetable for selected/custom week -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-blue-100">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] timetable-table" id="timetableTable">
                    <thead class="bg-gradient-to-r from-blue-100 to-blue-200">
                        <tr>
                            <th class="px-4 py-4 text-left text-base font-bold text-blue-900 border-b border-blue-200">Time</th>
                            <?php foreach ($week_dates as $wd): ?>
                                <th class="px-4 py-4 text-left text-base font-bold text-blue-900 border-b border-blue-200">
                                    <?= htmlspecialchars($wd['day']) ?><br>
                                    <span class="text-xs text-gray-500"><?= htmlspecialchars($wd['date']) ?></span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="timetableBodyWeek">
                        <?php foreach ($timeSlotsForWeek as $timeSlot): ?>
                        <tr class="border-b hover:bg-blue-50 transition">
                            <td class="px-4 py-3 time-slot align-middle" data-label="Time"><?= htmlspecialchars($timeSlot) ?></td>
                            <?php foreach ($week_dates as $wd): ?>
                            <td class="px-4 py-3 align-top" data-label="<?= htmlspecialchars($wd['day']) ?>">
                                <?php 
                                $classesForSlot = [];
                                if (isset($schedulesByDayTime[$timeSlot][$wd['day']])) {
                                    $classesForSlot = $schedulesByDayTime[$timeSlot][$wd['day']];
                                }
                                
                                foreach ($classesForSlot as $classForSlot): 
                                ?>
                                <div class="class-card" onclick="viewClassDetails('<?= $classForSlot['id'] ?>')">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="subject"><?= htmlspecialchars($classForSlot['subject']) ?></span>
                                        <span class="group-badge"><?= htmlspecialchars($classForSlot['group']) ?></span>
                                    </div>
                   
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire('Success', "<?= addslashes($_SESSION['success']); ?>", 'success');
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire('Error', "<?= addslashes($_SESSION['error']); ?>", 'error');
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

    <script>
        // Sample data
        let schedules = <?php echo json_encode($schedules); ?>;
        let filteredSchedules = [...schedules];

        // Dynamically get all unique time slots from the data
        const timeSlots = Array.from(new Set(schedules.map(s => s.time))).sort();
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        function applyFilters() {
            const subjectFilter = document.getElementById('subjectFilter').value;
            const groupFilter = document.getElementById('groupFilter').value;
            const roomFilter = document.getElementById('roomFilter').value;

            filteredSchedules = schedules.filter(schedule => {
                return (!subjectFilter || schedule.subject === subjectFilter) &&
                       (!groupFilter || schedule.group === groupFilter) &&
                       (!roomFilter || schedule.room === roomFilter);
            });

            renderFilteredTimetable();
        }

        function renderFilteredTimetable() {
            // Hide all class cards first
            const allCards = document.querySelectorAll('.class-card');
            allCards.forEach(card => {
                card.style.display = 'none';
            });

            // Show only filtered cards
            filteredSchedules.forEach(schedule => {
                const cards = document.querySelectorAll(`[data-schedule-id="${schedule.id}"]`);
                cards.forEach(card => {
                    card.style.display = 'block';
                });
            });
        }

        function clearFilters() {
            document.getElementById('subjectFilter').value = '';
            document.getElementById('groupFilter').value = '';
            document.getElementById('roomFilter').value = '';
            
            // Show all class cards
            const allCards = document.querySelectorAll('.class-card');
            allCards.forEach(card => {
                card.style.display = 'block';
            });
            
            filteredSchedules = [...schedules];
        }

        function viewClassDetails(scheduleId) {
            const schedule = schedules.find(s => s.id === scheduleId);
            if (!schedule) return;

            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">${schedule.subject}</h4>
                            <p class="text-sm text-gray-600">Code: ${schedule.module_code || 'N/A'}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-users text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Group</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.group}</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-user-tie text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Teacher</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.teacher || 'N/A'}</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-door-open text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Room</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.room}</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-calendar text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Day</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.day}</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3 col-span-2">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-clock text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Time</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.time}</p>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewModal').classList.add('flex');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        function openNewScheduleModal() {
            document.getElementById('newScheduleModal').classList.remove('hidden');
            document.getElementById('newScheduleModal').classList.add('flex');
        }

        function closeNewScheduleModal() {
            document.getElementById('newScheduleModal').classList.add('hidden');
            document.getElementById('newScheduleModal').classList.remove('flex');
            document.getElementById('newScheduleForm').reset();
        }

        function addNewSchedule(event) {
            event.preventDefault();

            // Collect form data
            const academic_year_id = document.getElementById('newAcademicYearId').value;
            const module_id = document.getElementById('newModuleId').value;
            const lecturer_id = document.getElementById('newLeaderLecturerId').value;
            const facility_id = document.getElementById('newFacilityId').value;
            const intake_id = document.getElementById('newIntakeId').value;
            const program_id = document.getElementById('newProgramId').value;
            const groupSelect = document.getElementById('newGroupIds');
            const group_ids = Array.from(groupSelect.selectedOptions).map(opt => opt.value);
            const day = document.getElementById('newDay').value;
            const start_time = document.getElementById('newStartTime').value;
            const end_time = document.getElementById('newEndTime').value;

            // Basic validation
            if (!academic_year_id || !module_id || !lecturer_id || !facility_id || !start_time || !end_time || !day || group_ids.length === 0) {
                Swal.fire('Error', 'Please fill all required fields.', 'error');
                return;
            }
            if (start_time >= end_time) {
                Swal.fire('Error', 'End time must be after start time.', 'error');
                return;
            }

            // Prepare data for POST
            const formData = new FormData();
            formData.append('academic_year_id', academic_year_id);
            formData.append('module_id', module_id);
            formData.append('lecturer_id', lecturer_id);
            formData.append('facility_id', facility_id);
            formData.append('intake_id', intake_id);
            formData.append('program_id', program_id);
            formData.append('day', day);
            formData.append('start_time', start_time);
            formData.append('end_time', end_time);
            group_ids.forEach(gid => formData.append('group_ids[]', gid));

            // Send AJAX request to backend
            fetch('save_timetable.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(resp => {
                console.log('Raw response:', resp);
                let data;
                try { 
                    data = JSON.parse(resp); 
                } catch (e) { 
                    console.error('JSON parse error:', e);
                    console.error('Response was:', resp);
                    data = null; 
                }
                if (data && data.success) {
                    Swal.fire('Success', data.success, 'success').then(() => {
                        location.reload();
                    });
                } else if (data && data.error) {
                    console.error('Schedule error:', data.error);
                    Swal.fire('Error', data.error, 'error');
                } else {
                    console.error('Unknown response:', resp);
                    Swal.fire('Error', 'An error occurred while saving the schedule.', 'error');
                }
            })
            .catch(err => {
                console.error('AJAX error:', err);
                Swal.fire('Error', 'Network error occurred.', 'error');
            });
        }

        function downloadTimetable() {
            // Create a simple CSV download of the timetable
            let csvContent = "Day,Time,Subject,Room,Group\n";
            
            schedules.forEach(schedule => {
                csvContent += `"${schedule.day}","${schedule.time}","${schedule.subject}","${schedule.room}","${schedule.group}"\n`;
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "timetable.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Add data attributes to class cards for filtering
        document.addEventListener('DOMContentLoaded', function() {
            const classCards = document.querySelectorAll('.class-card');
            classCards.forEach(card => {
                const scheduleId = card.getAttribute('onclick').match(/'([^']+)'/)[1];
                card.setAttribute('data-schedule-id', scheduleId);
            });
        });

        // Close modals when clicking outside
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });

        document.getElementById('newScheduleModal').addEventListener('click', function(e) {
            if (e.target === this) closeNewScheduleModal();
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewModal();
                closeNewScheduleModal();
            }
        });
    </script>
  </main>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

                                    <div class="meta">
                                        <i class="fas fa-door-open"></i>
                                        <span><?= htmlspecialchars($classForSlot['room']) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-md w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Class Details</h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <div id="modalContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- New Schedule Modal -->
    <div id="newScheduleModal" class="modal fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-md w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">New Schedule</h3>
                <button onclick="closeNewScheduleModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="newScheduleForm" onsubmit="addNewSchedule(event)">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                            <select name="academic_year_id" id="newAcademicYearId" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Academic Year</option>
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?= htmlspecialchars($year['id']) ?>">
                                        <?= htmlspecialchars($year['year_label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                            <select name="module_id" id="newModuleId" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Module</option>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?= htmlspecialchars($module['id']) ?>">
                                        <?= htmlspecialchars($module['code']) ?> - <?= htmlspecialchars($module['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lecturer</label>
                            <select name="lecturer_id" id="newLeaderLecturerId" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Lecturer</option>
                                <?php foreach ($lecturers as $lecturer): ?>
                                    <option value="<?= htmlspecialchars($lecturer['id']) ?>">
                                        <?= htmlspecialchars($lecturer['names']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Facility</label>
                            <select name="facility_id" id="newFacilityId" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Facility</option>
                                <?php foreach ($facilities as $facility): ?>
                                    <option value="<?= htmlspecialchars($facility['id']) ?>">
                                        <?= htmlspecialchars($facility['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Intake</label>
                            <select name="intake_id" id="newIntakeId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Intake (optional)</option>
                                <?php foreach ($intakes as $intake): ?>
                                    <option value="<?= htmlspecialchars($intake['id']) ?>">
                                        <?= htmlspecialchars($intake['program_name']) ?> - <?= htmlspecialchars($intake['month']) ?>/<?= htmlspecialchars($intake['year']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                            <select name="program_id" id="newProgramId" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Program (optional)</option>
                                <?php foreach ($programs as $program): ?>
                                    <option value="<?= htmlspecialchars($program['id']) ?>">
                                        <?= htmlspecialchars($program['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Groups</label>
                            <select name="group_ids[]" id="newGroupIds" multiple required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= htmlspecialchars($group['id']) ?>">
                                        <?= htmlspecialchars($group['name']) ?> (<?= htmlspecialchars($group['program_name']) ?> - <?= htmlspecialchars($group['month']) ?>/<?= htmlspecialchars($group['year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-gray-500">Hold Ctrl (Windows) or Cmd (Mac) to select multiple groups.</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                            <select name="day" id="newDay" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                            </select>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                                <input type="time" name="start_time" id="newStartTime" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                <input type="time" name="end_time" id="newEndTime" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeNewScheduleModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                            Add Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <script>
        alert("<?= addslashes($_SESSION['success']); ?>");
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <script>
        alert("<?= addslashes($_SESSION['error']); ?>");
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

    <script>
        // Sample data
        let schedules = <?php echo json_encode($schedules); ?>;
        let filteredSchedules = [...schedules];

        // Dynamically get all unique time slots from the data
        const timeSlots = Array.from(new Set(schedules.map(s => s.time))).sort();
        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        function renderTimetable() {
            const tbody = document.getElementById('timetableBody');
            tbody.innerHTML = '';

            timeSlots.forEach(timeSlot => {
                const row = document.createElement('tr');
                row.className = 'border-b hover:bg-blue-50 transition';

                // Time column
                const timeCell = document.createElement('td');
                timeCell.className = 'px-4 py-3 time-slot align-middle';
                timeCell.textContent = timeSlot;
                timeCell.setAttribute('data-label', 'Time');
                row.appendChild(timeCell);

                days.forEach(day => {
                    const dayCell = document.createElement('td');
                    dayCell.className = 'px-4 py-3 align-top';
                    dayCell.setAttribute('data-label', day);

                    // Find all classes for this day and time slot (may be multiple groups)
                    const classes = filteredSchedules.filter(s =>
                        s.day === day && s.time === timeSlot
                    );

                    if (classes.length > 0) {
                        dayCell.innerHTML = classes.map(classForSlot => `
                            <div class="class-card" onclick="viewClassDetails('${classForSlot.id}')">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="subject">${classForSlot.subject}</span>
                                    <span class="group-badge">${classForSlot.group}</span>
                                </div>
                                
                                <div class="meta">
                                    <i class="fas fa-door-open"></i>
                                    <span>${classForSlot.room}</span>
                                </div>
                            </div>
                        `).join('');
                    }

                    row.appendChild(dayCell);
                });

                tbody.appendChild(row);
            });
        }

        function applyFilters() {
            const subjectFilter = document.getElementById('subjectFilter').value;
            const groupFilter = document.getElementById('groupFilter').value;
            const roomFilter = document.getElementById('roomFilter').value;

            filteredSchedules = schedules.filter(schedule => {
                return (!subjectFilter || schedule.subject === subjectFilter) &&
                       (!groupFilter || schedule.group === groupFilter) &&
                       (!roomFilter || schedule.room === roomFilter);
            });

            renderTimetable();
        }

        function clearFilters() {
            document.getElementById('subjectFilter').value = '';
            document.getElementById('groupFilter').value = '';
            document.getElementById('roomFilter').value = '';
            filteredSchedules = [...schedules];
            renderTimetable();
        }

        function viewClassDetails(scheduleId) {
            const schedule = schedules.find(s => s.id === scheduleId);
            if (!schedule) return;

            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">${schedule.subject}</h4>
                            <p class="text-sm text-gray-600">Group ${schedule.group}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-user-tie text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Teacher</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.teacher}</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-door-open text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Room</span>
                            </div>
                            <p class="text-sm text-gray-900">Room ${schedule.room}</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-calendar text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Day</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.day}</p>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <i class="fas fa-clock text-gray-500"></i>
                                <span class="text-sm font-medium text-gray-700">Time</span>
                            </div>
                            <p class="text-sm text-gray-900">${schedule.time}</p>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewModal').classList.add('flex');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        function openNewScheduleModal() {
            document.getElementById('newScheduleModal').classList.remove('hidden');
            document.getElementById('newScheduleModal').classList.add('flex');
        }

        function closeNewScheduleModal() {
            document.getElementById('newScheduleModal').classList.add('hidden');
            document.getElementById('newScheduleModal').classList.remove('flex');
            document.getElementById('newScheduleForm').reset();
        }

        function addNewSchedule(event) {
            event.preventDefault();

            // Collect form data
            const academic_year_id = document.getElementById('newAcademicYearId').value;
            const module_id = document.getElementById('newModuleId').value;
            const lecturer_id = document.getElementById('newLeaderLecturerId').value;
            const facility_id = document.getElementById('newFacilityId').value;
            const intake_id = document.getElementById('newIntakeId').value;
            const program_id = document.getElementById('newProgramId').value;
            const groupSelect = document.getElementById('newGroupIds');
            const group_ids = Array.from(groupSelect.selectedOptions).map(opt => opt.value);
            const start_time = document.getElementById('newStartTime').value;
            const end_time = document.getElementById('newEndTime').value;
            const schedule_date = document.getElementById('newScheduleDate').value;
            const day = document.getElementById('newDay').value;

            // Basic validation
            if (!academic_year_id || !module_id || !lecturer_id || !facility_id || !start_time || !end_time || group_ids.length === 0) {
                Swal.fire('Error', 'Please fill all required fields.', 'error');
                return;
            }
            if (start_time >= end_time) {
                Swal.fire('Error', 'End time must be after start time.', 'error');
                return;
            }

            // Prepare data for POST
            const formData = new FormData();
            formData.append('academic_year_id', academic_year_id);
            formData.append('module_id', module_id);
            formData.append('lecturer_id', lecturer_id);
            formData.append('facility_id', facility_id);
            formData.append('intake_id', intake_id);
            formData.append('program_id', program_id);
            formData.append('start_time', start_time);
            formData.append('end_time', end_time);
            group_ids.forEach(gid => formData.append('group_ids[]', gid));
            formData.append('schedule_date', schedule_date);
            formData.append('day', day);

            // Send AJAX request to backend
            fetch('save_timetable.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(resp => {
                console.log('Raw response:', resp); // Add this line
                let data;
                try { data = JSON.parse(resp); } catch (e) { data = null; }
                if (data && data.success) {
                    Swal.fire('Success', data.success, 'success').then(() => {
                        location.reload();
                    });
                } else if (data && data.error) {
                    console.error('Schedule error:', data.error);
                    Swal.fire('Error', data.error, 'error');
                } else {
                    console.error('Unknown response:', resp);
                    Swal.fire('Error', 'Unknown error occurred.', 'error');
                }
            })
            .catch(err => {
                console.error('AJAX error:', err);
                Swal.fire('Error', 'AJAX request failed.', 'error');
            });
        }

        // Initialize the timetable
        renderTimetable();

        // Close modals when clicking outside
        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });

        document.getElementById('newScheduleModal').addEventListener('click', function(e) {
            if (e.target === this) closeNewScheduleModal();
        });
    </script>
  </main>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

