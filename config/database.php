<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
class Database {
    private $host = "localhost";
    private $database = "haircut_suggester";
    private $username = "root";
    private $password = "";
    public $connection;

    public function getConnection() {
        $this->connection = null;
        try {
            $this->connection = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->connection;
    }
}

// Database connection function
function getDatabaseConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

function isAdmin() {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    // Prefer session role check; users table uses a 'role' enum (admin|user)
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function isUser() {
    if (!isset($_SESSION['user'])) {
        return false;
    }
    // Regular user role
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'user';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /haircut-suggester/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /haircut-suggester/user/dashboard.php');
        exit();
    }
}

function requireUser() {
    requireLogin();
    if (!isUser()) {
        header('Location: /haircut-suggester/admin/dashboard.php');
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path;
}

// Error handling
function showError($message) {
    return '<div class="alert alert-error">' . htmlspecialchars($message) . '</div>';
}

function showSuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}
?>
