<?php
session_start();
include("connection.php");
header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
$type = trim($_POST['type'] ?? '');
$capacity = intval($_POST['capacity'] ?? 0);
$site = intval($_POST['site'] ?? 0);
$id = intval($_POST['id'] ?? 0);

if (!$name || !$type || $capacity <= 0 || $site <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Check duplicate
$checkSql = "SELECT id FROM facility WHERE name = ? AND site = ? AND id != ?";
$stmt = $connection->prepare($checkSql);
$stmt->bind_param("sii", $name, $site, $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Facility with same name and site already exists.']);
    exit;
}

if ($id > 0) {
    // Update
    $sql = "UPDATE facility SET name = ?, type = ?, capacity = ?, site = ? WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ssiii", $name, $type, $capacity, $site, $id);
} else {
    // Insert
    $sql = "INSERT INTO facility (name, type, capacity, site) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ssii", $name, $type, $capacity, $site);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
