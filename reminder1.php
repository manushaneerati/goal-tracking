<?php
// Include your database connection and mail sending libraries
include('db_connection.php');  // Ensure this file contains the database connection
include('mailer.php');  // This file should have a function to send emails (like PHPMailer)

function sendGoalReminderEmail($userEmail, $userName, $goalText) {
    $subject = "Reminder: You have an outstanding goal!";
    $message = "Hello $userName,\n\nThis is a reminder that you have a goal you haven't interacted with recently:\n\n$goalText\n\nPlease make sure to track your progress!";

    // Send email using PHP mail() or PHPMailer
    mail($userEmail, $subject, $message);  // Or use PHPMailer to send a more robust email
}

function checkAndSendReminders() {
    global $conn; // Assume $conn is the database connection

    // Query to get all users and their goals that have not been updated in the last X days
    $query = "
        SELECT u.email, u.username, g.goal_text, g.start_date, g.selected_date 
        FROM users u
        INNER JOIN goals g ON u.id = g.user_id
        WHERE DATEDIFF(CURRENT_DATE, g.selected_date) > 7";  // Check if the user hasn't selected or updated the goal in the last 7 days
    
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        // If a user has not interacted with their goal in the past X days, send them a reminder
        sendGoalReminderEmail($row['email'], $row['username'], $row['goal_text']);
    }
}

// Run the check and send reminders
checkAndSendReminders();
?>
