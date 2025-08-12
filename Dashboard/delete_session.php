<?php
session_start();
include('connection.php');

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Check database connection
    if (!$connection) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not authenticated");
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['session_id'])) {
        throw new Exception("Session ID is required");
    }

    $session_id = intval($input['session_id']);
    
    if ($session_id <= 0) {
        throw new Exception("Invalid session ID");
    }

    // Check if session exists and get details
    $check_query = "SELECT id, created_by FROM timetable WHERE id = ?";
    $check_stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $session_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        throw new Exception("Session not found");
    }
    
    $session_data = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);

    // Optional: Check if user has permission to delete (uncomment if needed)
    /*
    if ($session_data['created_by'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
        throw new Exception("You don't have permission to delete this session");
    }
    */

    // Begin transaction
    mysqli_autocommit($connection, false);

    try {
        // Delete group associations first
        $delete_groups_query = "DELETE FROM timetable_groups WHERE timetable_id = ?";
        $delete_groups_stmt = mysqli_prepare($connection, $delete_groups_query);
        mysqli_stmt_bind_param($delete_groups_stmt, "i", $session_id);
        
        if (!mysqli_stmt_execute($delete_groups_stmt)) {
            throw new Exception("Failed to delete group associations: " . mysqli_stmt_error($delete_groups_stmt));
        }
        mysqli_stmt_close($delete_groups_stmt);

        // Delete the main timetable entry
        $delete_timetable_query = "DELETE FROM timetable WHERE id = ?";
        $delete_timetable_stmt = mysqli_prepare($connection, $delete_timetable_query);
        mysqli_stmt_bind_param($delete_timetable_stmt, "i", $session_id);
        
        if (!mysqli_stmt_execute($delete_timetable_stmt)) {
            throw new Exception("Failed to delete timetable session: " . mysqli_stmt_error($delete_timetable_stmt));
        }
        
        $affected_rows = mysqli_stmt_affected_rows($delete_timetable_stmt);
        mysqli_stmt_close($delete_timetable_stmt);

        if ($affected_rows === 0) {
            throw new Exception("No session was deleted. Session may not exist.");
        }

        // Commit transaction
        mysqli_commit($connection);

        // Log the deletion (optional)
        error_log("Session deleted: ID $session_id by user " . $_SESSION['user_id']);

        $response['success'] = true;
        $response['message'] = "Session deleted successfully";

    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($connection);
        throw $e;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Session deletion error: " . $e->getMessage());
}

// Restore autocommit
mysqli_autocommit($connection, true);

// Close connection
if ($connection) {
    mysqli_close($connection);
}

// Return JSON response
echo json_encode($response);
?>