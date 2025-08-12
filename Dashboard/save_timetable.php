<?php
session_start();
include("../connection.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect input
    $academic_year_id = intval($_POST['academic_year_id']);
    $module_id = intval($_POST['module_id']);
    $lecturer_id = intval($_POST['lecturer_id']);
    $facility_id = intval($_POST['facility_id']);
    $intake_id = !empty($_POST['intake_id']) ? intval($_POST['intake_id']) : "NULL";
    $program_id = !empty($_POST['program_id']) ? intval($_POST['program_id']) : "NULL";
    $group_ids = isset($_POST['group_ids']) ? $_POST['group_ids'] : [];
    $start_time = mysqli_real_escape_string($connection, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($connection, $_POST['end_time']);
    $created_by = intval($_SESSION['id']);

    // Insert into timetable
    $sql = "INSERT INTO timetable (academic_year_id, module_id, leader_lecturer_id, facility_id, createdby, status, approvedby) 
            VALUES ($academic_year_id, $module_id, $lecturer_id, $facility_id, $created_by, 'pending', 0)";
    $result = mysqli_query($connection, $sql);

    if ($result) {
        $timetable_id = mysqli_insert_id($connection);

        // Insert into timetable_sessions
        $sql_session = "INSERT INTO timetable_sessions (timetable_id, start_time, end_time) VALUES ($timetable_id, '$start_time', '$end_time')";
        mysqli_query($connection, $sql_session);

        // Insert into timetable_groups for each group
        foreach ($group_ids as $gid) {
            $gid = intval($gid);
            mysqli_query($connection, "INSERT INTO timetable_groups (timetable_id, group_id) VALUES ($timetable_id, $gid)");
        }

        $_SESSION['success'] = "Schedule added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add schedule. " . mysqli_error($connection);
    }
    header("Location: timetable.php");
    exit();
} else {
    header("Location: timetable.php");
    exit();
}
?>