<?php
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

function startLayout($pageTitle = 'HairCut Suggester', $currentPage = '') {
    global $userName, $userFirstName, $userStats;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - HairCut Suggester</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
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
            
               
                <li class="menu-item <?php echo ($currentPage === 'browse') ? 'active' : ''; ?>">
                    <a href="browse-haircuts.php">
                        <i class="fas fa-images"></i>
                        <span>Browse Haircuts</span>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage === 'history') ? 'active' : ''; ?>">
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

                <li class="menu-item <?php echo ($currentPage === 'saved-haircuts') ? 'active' : ''; ?>">
                    <a href="savehaircuts.php">
                        <i class="fas fa-heart"></i>
                        <span>Saved Haircuts</span>
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

        <!-- Main Content -->
        <main class="main-content">
<?php
}

function endLayout() {
?>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
<?php
}

function renderPageHeader($title, $subtitle = '', $actionButton = null) {
    global $userFirstName;
?>
    <header class="content-header">
        <div class="header-left">
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <?php if ($subtitle): ?>
                <p><?php echo htmlspecialchars($subtitle); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($actionButton): ?>
            <div class="header-right">
                <?php echo $actionButton; ?>
            </div>
        <?php endif; ?>
    </header>
<?php
}
?>
