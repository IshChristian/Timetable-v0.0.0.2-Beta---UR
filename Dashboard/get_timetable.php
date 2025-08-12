<?php
session_start();
include('connection.php');

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'timetable' => []
];

try {
    // Check database connection
    if (!$connection) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    $stmt = null; // Initialize $stmt to avoid undefined variable warning

    // Get filter parameters
    $academic_year_id = isset($_POST['academic_year_id']) ? intval($_POST['academic_year_id']) : null;
    $semester = isset($_POST['semester']) ? intval($_POST['semester']) : null;
    $campus_id = isset($_POST['campus_id']) ? intval($_POST['campus_id']) : null;
    $college_id = isset($_POST['college_id']) ? intval($_POST['college_id']) : null;
    $school_id = isset($_POST['school_id']) ? intval($_POST['school_id']) : null;
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : null;
    $intake_id = isset($_POST['intake_id']) ? intval($_POST['intake_id']) : null;
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : null;

    // Build the query
    $query = "
        SELECT 
            t.id,
            t.day,
            t.start_time,
            t.end_time,
            m.code as module_code,
            m.name as module_name,
            u.names as lecturer_name,
            f.name as facility_name,
            f.type as facility_type,
            sg.name as group_name,
            p.name as program_name,
            c.name as campus_name
        FROM timetable t
        LEFT JOIN module m ON t.module_id = m.id
        LEFT JOIN users u ON t.lecturer_id = u.id
        LEFT JOIN facility f ON t.facility_id = f.id
        LEFT JOIN timetable_groups tg ON t.id = tg.timetable_id
        LEFT JOIN student_group sg ON tg.group_id = sg.id
        LEFT JOIN intake i ON sg.intake_id = i.id
        LEFT JOIN program p ON i.program_id = p.id
        LEFT JOIN department d ON p.department_id = d.id
        LEFT JOIN school s ON d.school_id = s.id
        LEFT JOIN college col ON s.college_id = col.id
        LEFT JOIN campus c ON col.campus_id = c.id
        WHERE 1=1
    ";

    $params = [];
    $types = "";

    // Add filter conditions
    if ($academic_year_id) {
        $query .= " AND t.academic_year_id = ?";
        $params[] = $academic_year_id;
        $types .= "i";
    }

    if ($semester) {
        $query .= " AND t.semester = ?";
        $params[] = $semester;
        $types .= "i";
    }

    if ($campus_id) {
        $query .= " AND c.id = ?";
        $params[] = $campus_id;
        $types .= "i";
    }

    if ($college_id) {
        $query .= " AND col.id = ?";
        $params[] = $college_id;
        $types .= "i";
    }

    if ($school_id) {
        $query .= " AND s.id = ?";
        $params[] = $school_id;
        $types .= "i";
    }

    if ($department_id) {
        $query .= " AND d.id = ?";
        $params[] = $department_id;
        $types .= "i";
    }

    if ($program_id) {
        $query .= " AND p.id = ?";
        $params[] = $program_id;
        $types .= "i";
    }

    if ($intake_id) {
        $query .= " AND i.id = ?";
        $params[] = $intake_id;
        $types .= "i";
    }

    if ($group_id) {
        $query .= " AND sg.id = ?";
        $params[] = $group_id;
        $types .= "i";
    }

    $query .= " ORDER BY 
        FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
        t.start_time
    ";

    // Prepare and execute query
    if (!empty($params)) {
        $stmt = mysqli_prepare($connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            throw new Exception("Failed to prepare statement: " . mysqli_error($connection));
        }
    } else {
        $result = mysqli_query($connection, $query);
        if (!$result) {
            throw new Exception("Query failed: " . mysqli_error($connection));
        }
    }
    // Fetch results
    $timetable = [];
    if (isset($result) && $result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $timetable[] = [
                'id' => $row['id'],
                'day' => $row['day'],
                'start_time' => date('H:i', strtotime($row['start_time'])),
                'end_time' => date('H:i', strtotime($row['end_time'])),
                'module_code' => $row['module_code'],
                'module_name' => $row['module_name'],
                'lecturer_name' => $row['lecturer_name'],
                'facility_name' => $row['facility_name'],
                'facility_type' => $row['facility_type'],
                'group_name' => $row['group_name'],
                'program_name' => $row['program_name'],
                'campus_name' => $row['campus_name']
            ];
        }
    }
    }

    $response['success'] = true;
    $response['timetable'] = $timetable;
    $response['message'] = count($timetable) . ' sessions found';

    // Clean up
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Timetable fetch error: " . $e->getMessage());
}

// Close connection
if ($connection) {
    mysqli_close($connection);
}

// Return JSON response
echo json_encode($response);
?>