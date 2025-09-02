<?php
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear any remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with success message
header('Location: /login.php?message=logged_out');
exit();
?>
