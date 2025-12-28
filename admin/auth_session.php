<?php
// admin/auth_session.php
session_start();

// Check if the user variable exists in session
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}
?>