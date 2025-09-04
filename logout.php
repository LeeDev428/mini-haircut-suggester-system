<?php
// Ensure session utilities and DB helpers are available (also starts session in `database.php`)
require_once __DIR__ . '/config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// If a session cookie exists, delete it
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session data on the server side
session_destroy();

// Clear any remember-me cookie if present
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 42000, '/');
    unset($_COOKIE['remember_token']);
}

// Redirect everyone (users and admins) to the main page of the app
header('Location: /haircut-suggester/index.php?message=logged_out');
exit();
?>
