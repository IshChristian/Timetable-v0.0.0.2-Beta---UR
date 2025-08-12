<?php
session_start();
include('connection.php');

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Check database connection
    if (!$connection) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    // Get the filter type and parent ID
    $filter_type = isset($_GET['type']) ? $_GET['type'] : '';
    $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

    switch ($filter_type) {
        case 'colleges':
            if ($parent_id > 0) {
                $query = "SELECT id, name FROM college WHERE campus_id = ? ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT id, name FROM college ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        case 'schools':
            if ($parent_id > 0) {
                $query = "SELECT id, name FROM school WHERE college_id = ? ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT id, name FROM school ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        case 'departments':
            if ($parent_id > 0) {
                $query = "SELECT id, name FROM department WHERE school_id = ? ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT id, name FROM department ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        case 'programs':
            if ($parent_id > 0) {
                $query = "SELECT id, name FROM program WHERE department_id = ? ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT id, name FROM program ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        case 'intakes':
            if ($parent_id > 0) {
                $query = "SELECT id, CONCAT(year, ' - ', month) as name FROM intake WHERE program_id = ? ORDER BY year DESC, month DESC";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT id, CONCAT(year, ' - ', month) as name FROM intake ORDER BY year DESC, month DESC";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        case 'groups':
            if ($parent_id > 0) {
                $query = "SELECT id, name FROM student_group WHERE intake_id = ? ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT sg.id, sg.name FROM student_group sg ORDER BY sg.name";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        case 'modules_by_program':
            if ($parent_id > 0) {
                $query = "SELECT id, CONCAT(code, ' - ', name) as name FROM module WHERE program_id = ? ORDER BY code";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT id, CONCAT(code, ' - ', name) as name FROM module ORDER BY code";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        case 'facilities_by_campus':
            if ($parent_id > 0) {
                $query = "SELECT id, CONCAT(name, ' (', type, ')') as name FROM facility WHERE campus_id = ? AND type IN ('classroom', 'Lecture Hall', 'Laboratory') ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
            } else {
                $query = "SELECT id, CONCAT(name, ' (', type, ')') as name FROM facility WHERE type IN ('classroom', 'Lecture Hall', 'Laboratory') ORDER BY name";
                $stmt = mysqli_prepare($connection, $query);
            }
            break;

        default:
            throw new Exception("Invalid filter type");
    }

    // Execute query
    if (isset($stmt)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        
        mysqli_stmt_close($stmt);
        
        $response['success'] = true;
        $response['data'] = $data;
        $response['message'] = count($data) . ' records found';
    } else {
        throw new Exception("Failed to prepare statement");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Filter data error: " . $e->getMessage());
}

// Close connection
if ($connection) {
    mysqli_close($connection);
}

// Return JSON response
echo json_encode($response);
?>