<?php
$to = "manusha.neerati123@gmail.com";  // Replace with your email address
$subject = "Test Email from PHP";
$message = "This is a test email to check if mail() works.";
$headers = "From: no-reply@yourdomain.com\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully to $to";
} else {
    echo "Failed to send email";
}
?>
