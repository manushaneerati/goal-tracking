<?php
session_start();

$host = 'localhost';
$dbname = 'project';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB connection failed");
}

$data = json_decode(file_get_contents("php://input"), true);

$goal_id = $data['goal_id'];
$progress_date = $data['progress_date'];
$current_username = $_SESSION['username'] ?? 'Guest';

$stmt = $conn->prepare("DELETE FROM goal_progress WHERE goal_id = ? AND progress_date = ? AND username = ?");
$stmt->bind_param("iss", $goal_id, $progress_date, $current_username);
$stmt->execute();

echo "Removed";
?>
