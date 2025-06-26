<?php
session_start();

header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'project';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

if (!isset($_GET['goal_id'])) {
    echo json_encode(['error' => 'Missing goal_id']);
    exit();
}

$goal_id = intval($_GET['goal_id']);
$current_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

// Verify ownership of goal
$stmt = $conn->prepare("SELECT start_date FROM goals WHERE id = ? AND username = ?");
$stmt->bind_param("is", $goal_id, $current_username);
$stmt->execute();
$stmt->bind_result($start_date);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Goal not found or access denied']);
    exit();
}
$stmt->close();

// Get checked days from goal_progress table
$stmt = $conn->prepare("SELECT progress_date FROM goal_progress WHERE goal_id = ?");
$stmt->bind_param("i", $goal_id);
$stmt->execute();
$result = $stmt->get_result();

$checked_days = [];
while ($row = $result->fetch_assoc()) {
    $checked_days[] = $row['progress_date'];
}

echo json_encode(['start_date' => $start_date, 'checked_days' => $checked_days]);
