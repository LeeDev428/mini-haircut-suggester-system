<?php
// Sidebar component for the dashboard
require_once '../config/database.php';
requireUser();

$userId = $_SESSION['user']['id'];
$userName = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
$userFirstName = $_SESSION['user']['first_name'];

// Get user stats for sidebar
$pdo = getDatabaseConnection();
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM user_saved_haircuts WHERE user_id = ?) as total_saved,
        (SELECT COUNT(*) FROM user_quiz_results WHERE user_id = ?) as total_quizzes,
        (SELECT COUNT(*) FROM user_haircut_history WHERE user_id = ?) as total_recommendations
");
$stmt->execute([$userId, $userId, $userId]);
$userStats = $stmt->fetch() ?: ['total_saved' => 0, 'total_quizzes' => 0, 'total_recommendations' => 0];

// Determine current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-cut"></i> HairCut Suggester</h2>
        <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-info">
            <h4><?php echo htmlspecialchars($userName); ?></h4>
            <span class="user-role">Member</span>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
    
      
        <li class="menu-item <?php echo ($currentPage === 'browse-haircuts') ? 'active' : ''; ?>">
            <a href="browse-haircuts.php">
                <i class="fas fa-images"></i>
                <span>Browse Haircuts</span>
            </a>
        </li>
        <li class="menu-item <?php echo ($currentPage === 'my-history') ? 'active' : ''; ?>">
            <a href="my-history.php">
                <i class="fas fa-history"></i>
                <span>My History</span>
            </a>
        </li>
        <li class="menu-item <?php echo ($currentPage === 'booking') ? 'active' : ''; ?>">
            <a href="booking.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Book Appointment</span>
            </a>
        </li>
        <li class="menu-item <?php echo ($currentPage === 'profile') ? 'active' : ''; ?>">
            <a href="profile.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>
