<?php
session_start();

if (isset($_POST['selected_date'])) {
    $_SESSION['selected_date'] = $_POST['selected_date']; // Store selected date in session
}
?>
