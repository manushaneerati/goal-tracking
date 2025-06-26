<?php
$servername = "localhost";
$username = "root"; // default username for XAMPP is "root"
$password = ""; // default password for XAMPP is empty
$dbname = "goal_tracker"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
