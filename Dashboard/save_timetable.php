<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect input
    $academic_year_id = filter_input(INPUT_POST, 'academic_year_id', FILTER_VALIDATE_INT);
    $module_id = filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
    $lecturer_id = filter_input(INPUT_POST, 'lecturer_id', FILTER_VALIDATE_INT);
    $facility_id = filter_input(INPUT_POST, 'facility_id', FILTER_VALIDATE_INT);
    $intake_id = filter_input(INPUT_POST, 'intake_id', FILTER_VALIDATE_INT);
    $program_id = filter_input(INPUT_POST, 'program_id', FILTER_VALIDATE_INT);
    $group_ids = filter_input(INPUT_POST, 'group_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $start_time = mysqli_real_escape_string($connection, filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING));
    $end_time = mysqli_real_escape_string($connection, filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING));
    $day = mysqli_real_escape_string($connection, filter_input(INPUT_POST, 'day', FILTER_SANITIZE_STRING));
    $created_by = intval($_SESSION['id']);

    // Check for errors
    if ($academic_year_id === false || $module_id === false || $lecturer_id === false || $facility_id === false || $intake_id === false || $program_id === false || $group_ids === false || $start_time === false || $end_time === false || $day === false) {
        $_SESSION['error'] = "Invalid input. Please check your inputs and try again.";
        header("Location: timetable.php");
        exit();
    }

    // Insert into timetable
    $sql = "INSERT INTO timetable (academic_year_id, module_id, leader_lecturer_id, facility_id, createdby, status, approvedby) 
            VALUES (?, ?, ?, ?, ?, 'pending', 0)";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, 'iiiiii', $academic_year_id, $module_id, $lecturer_id, $facility_id, $created_by);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $timetable_id = mysqli_insert_id($connection);

        // Insert into timetable_sessions
        $sql_session = "INSERT INTO timetable_sessions (timetable_id, start_time, end_time, `day`) VALUES (?, ?, ?, ?)";
        $stmt_session = mysqli_prepare($connection, $sql_session);
        mysqli_stmt_bind_param($stmt_session, 'isss', $timetable_id, $start_time, $end_time, $day);
        mysqli_stmt_execute($stmt_session);

        // Insert into timetable_groups for each group
        foreach ($group_ids as $gid) {
            $gid = intval($gid);
            $sql_group = "INSERT INTO timetable_groups (timetable_id, group_id) VALUES (?, ?)";
            $stmt_group = mysqli_prepare($connection, $sql_group);
            mysqli_stmt_bind_param($stmt_group, 'ii', $timetable_id, $gid);
            mysqli_stmt_execute($stmt_group);
        }

        $_SESSION['success'] = "Schedule added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add schedule. " . mysqli_error($connection);
    }
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($stmt_session);
    mysqli_stmt_close($stmt_group);
    header("Location: timetable.php");
    exit();
} else {
    header("Location: timetable.php");
    exit();
}
?>
