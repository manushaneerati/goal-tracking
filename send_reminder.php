<?php
// Enable full error reporting for debugging on localhost
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');  // Set your correct timezone here

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Database connection settings
$host = 'localhost';
$dbname = 'project';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only run this script after 1:00 PM (13:00 hours)
$currentHour = (int)date('H'); // Gets current hour in 24-hour format (0-23)
if ($currentHour < 13) {
    exit("It's not time to send reminders yet. Current time: " . date('H:i'));
}

$today = date('Y-m-d');

// Get all distinct usernames with goals
$usernames = [];
$result = $conn->query("SELECT DISTINCT username FROM goals");
while ($row = $result->fetch_assoc()) {
    $usernames[] = $row['username'];
}

// Loop through each user to check if progress is made today
foreach ($usernames as $user) {
    $stmt = $conn->prepare("
        SELECT gp.id 
        FROM goal_progress gp 
        JOIN goals g ON gp.goal_id = g.id
        WHERE g.username = ? AND DATE(gp.date_completed) = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $user, $today);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        // Get user's email address
        $email_stmt = $conn->prepare("SELECT email FROM users WHERE username = ?");
        $email_stmt->bind_param("s", $user);
        $email_stmt->execute();
        $email_res = $email_stmt->get_result();

        if ($email_res->num_rows > 0) {
            $email_row = $email_res->fetch_assoc();
            $to = $email_row['email'];

            // Send email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Enable SMTP debug output for troubleshooting
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = 'html';

                //Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';        // Gmail SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'neeratimanusha3@gmail.com';      // Your Gmail address
                $mail->Password = 'xflpyeiutukxkvtp';        // Your Gmail app password
                $mail->SMTPSecure = 'tls';               // Encryption
                $mail->Port = 587;

                //Recipients
                $mail->setFrom('neeratimanusha3@gmail.com', 'Goal Tracker Team');
                $mail->addAddress($to, $user);

                // Content
                $mail->isHTML(false);
                $mail->Subject = "Reminder: Don't forget to update your goal today!";
                $mail->Body = "Hi $user,\n\nYou haven't updated your goal progress today. Keep your streak alive by marking your progress on the Goal Tracker app.\n\nBest regards,\nGoal Tracker Team";

                $mail->send();
                error_log("Reminder email sent successfully to $to");
            } catch (Exception $e) {
                error_log("Message could not be sent to $to. Mailer Error: {$mail->ErrorInfo}");
            }
        }
        $email_stmt->close();
    }
    $stmt->close();
}

$conn->close();
