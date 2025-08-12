<?php
include('./connection.php');
header('Content-Type: application/json');

// Check connection
if (!isset($connection) || !$connection) {
    echo json_encode(['success' => false, 'error' => 'Database connection not established.']);
    exit;
}
if (!extension_loaded('mysqli')) {
    echo json_encode(['success' => false, 'error' => 'MySQLi extension not loaded.']);
    exit;
}

try {
    $whereConditions = [];
    $params = [];
    $types = '';

    if (!empty($_GET['academic_year_id'])) {
        $whereConditions[] = "t.academic_year_id = ?";
        $params[] = $_GET['academic_year_id'];
        $types .= 'i';
    }
    if (!empty($_GET['semester'])) {
        $whereConditions[] = "t.semester = ?";
        $params[] = $_GET['semester'];
        $types .= 's';
    }
    if (!empty($_GET['group_id'])) {
        $whereConditions[] = "tg.group_id = ?";
        $params[] = $_GET['group_id'];
        $types .= 'i';
    }
    if (!empty($_GET['campus_id'])) {
        $whereConditions[] = "c.id = ?";
        $params[] = $_GET['campus_id'];
        $types .= 'i';
    }
    if (!empty($_GET['college_id'])) {
        $whereConditions[] = "co.id = ?";
        $params[] = $_GET['college_id'];
        $types .= 'i';
    }
    if (!empty($_GET['school_id'])) {
        $whereConditions[] = "s.id = ?";
        $params[] = $_GET['school_id'];
        $types .= 'i';
    }
    if (!empty($_GET['department_id'])) {
        $whereConditions[] = "d.id = ?";
        $params[] = $_GET['department_id'];
        $types .= 'i';
    }
    if (!empty($_GET['program_id'])) {
        $whereConditions[] = "p.id = ?";
        $params[] = $_GET['program_id'];
        $types .= 'i';
    }
    if (!empty($_GET['intake_id'])) {
        $whereConditions[] = "i.id = ?";
        $params[] = $_GET['intake_id'];
        $types .= 'i';
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $query = "
        SELECT 
            ts.day AS Day,
            CONCAT(ts.start_time, ' - ', ts.end_time) AS Time,
            m.code AS module_code,
            m.name AS module_name,
            u.names AS lecturer,
            c.name AS campus,
            co.name AS College,
            s.name AS School,
            d.name AS Department,
            p.name AS Program,
            sg.name AS `Group`,
            CONCAT(i.year, '-', LPAD(i.month, 2, '0')) AS Intake,
            f.name AS Facility,
            f.type AS facility_type
        FROM timetable t
        JOIN module m ON t.module_id = m.id
        JOIN timetable_sessions ts ON ts.timetable_id = t.id
        LEFT JOIN timetable_lecturers tl ON tl.timetable_id = t.id
        LEFT JOIN users u ON tl.lect_id = u.id
        LEFT JOIN facility f ON t.facility_id = f.id
        LEFT JOIN campus c ON f.campus_id = c.id
        LEFT JOIN college co ON co.id = (
            SELECT college_id 
            FROM school s2
            JOIN department d2 ON s2.id = d2.school_id
            JOIN program p2 ON p2.department_id = d2.id
            WHERE p2.id = m.program_id
            LIMIT 1
        )
        LEFT JOIN department d ON d.id = (
            SELECT department_id 
            FROM program 
            WHERE id = m.program_id
        )
        LEFT JOIN school s ON s.id = d.school_id
        LEFT JOIN program p ON p.id = m.program_id
        LEFT JOIN timetable_groups tg ON tg.timetable_id = t.id
        LEFT JOIN student_group sg ON sg.id = tg.group_id
        LEFT JOIN intake i ON i.id = sg.intake_id
        $whereClause
        ORDER BY ts.day, ts.start_time
    ";

    if (!empty($params)) {
        $stmt = mysqli_prepare($connection, $query);
        if ($stmt === false) {
            throw new Exception('MySQL prepare error: ' . mysqli_error($connection));
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result === false) {
            throw new Exception('MySQL get_result error: ' . mysqli_stmt_error($stmt));
        }
    } else {
        $result = mysqli_query($connection, $query);
        if ($result === false) {
            throw new Exception('MySQL query error: ' . mysqli_error($connection));
        }
    }

    $timetable = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $timetable[] = $row;
    }

    echo json_encode([
        'success' => true,
        'timetable' => $timetable,
        'count' => count($timetable)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>