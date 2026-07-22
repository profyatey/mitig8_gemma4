<?php
// Sample Database To match Project Code
$host = "localhost";
$dbname = "flood_monitoring";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set UTF-8 character set
$conn->set_charset("utf8mb4");
?>
