<?php

// Create connection with error handling
$connection = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    $_ENV['DB_TIMETABLE-v2'] ?? 'timetable-v2',
);

// Check connection
if ($connection->connect_error) {
    error_log("Connection failed: " . $connection->connect_error);
    throw new Exception("Database connection failed: " . $connection->connect_error);
}

// Set charset to ensure proper encoding
$connection->set_charset("utf8mb4");
?>