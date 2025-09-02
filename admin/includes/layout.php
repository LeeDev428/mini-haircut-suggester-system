<?php
require_once '../config/database.php';

// Just call the requireAdmin function from database.php
requireAdmin();

$adminId = $_SESSION['user']['id'];
$adminName = $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'];
$adminFirstName = $_SESSION['user']['first_name'];

// Get admin stats
$pdo = getDatabaseConnection();
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_appointments,
        (SELECT COUNT(*) FROM appointments WHERE status = 'confirmed') as confirmed_appointments,
        (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_users,
        (SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()) as today_appointments
");
$stmt->execute();
$adminStats = $stmt->fetch() ?: ['pending_appointments' => 0, 'confirmed_appointments' => 0, 'new_users' => 0, 'today_appointments' => 0];

function startAdminLayout($pageTitle = 'Admin - HairCut Suggester', $currentPage = '') {
    global $adminName, $adminFirstName, $adminStats;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --admin-primary: #dc3545;
            --admin-secondary: #6c757d;
            --admin-success: #28a745;
            --admin-warning: #ffc107;
            --admin-info: #17a2b8;
        }
        
        .admin-sidebar {
            background: linear-gradient(135deg, var(--admin-primary), #e74c3c);
        }
        
        .admin-sidebar .sidebar-header {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .admin-badge {
            background: var(--admin-warning);
            color: #000;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--admin-primary);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--admin-primary);
        }
        
        .calendar-day.has-appointments {
            position: relative;
        }
        
        .appointment-count {
            position: absolute;
            top: 2px;
            right: 2px;
            background: var(--admin-primary);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        /* Status badges */
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn-outline {
            background: white;
            border: 1px solid var(--admin-primary);
            color: var(--admin-primary);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .btn-outline:hover {
            background: var(--admin-primary);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Admin Sidebar -->
        <nav class="sidebar admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel <span class="admin-badge">ADMIN</span></h2>
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-user">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($adminName); ?></h4>
                    <span class="user-role">Administrator</span>
                </div>
            </div>

            <ul class="sidebar-menu">

               <li class="menu-item">
                    <a href="../user/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="menu-item <?php echo ($currentPage === 'manage-appointments') ? 'active' : ''; ?>">
                    <a href="manage-appointments.php">
                        <i class="fas fa-calendar-check"></i>
                        <span>Appointments</span>
                        <?php if ($adminStats['confirmed_appointments'] > 0): ?>
                            <span class="badge" style="
                                background: #b71c1c;
                                color: #fff;
                                border-radius: 5%;
                                margin-left: 12px;
                                padding: 0;
                                font-size: 12px;
                                font-weight: bold;
                                display: inline-flex;
                                min-width: 28px;
                                height: 28px;
                                align-items: center;
                                justify-content: center;
                                text-align: center;
                            "><?php echo $adminStats['confirmed_appointments']; ?></span>
                        <?php endif; ?>
                     
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($currentPage === 'manage-users') ? 'active' : ''; ?>">
                    <a href="manage-users.php">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($currentPage === 'haircuts') ? 'active' : ''; ?>">
                    <a href="haircut-management.php">
                        <i class="fas fa-cut"></i>
                        <span>Haircut Management</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($currentPage === 'profile') ? 'active' : ''; ?>">
                    <a href="profile.php">
                        <i class="fas fa-user-cog"></i>
                        <span>My Profile</span>
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

function endAdminLayout() {
?>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
<?php
}

function renderAdminPageHeader($title, $subtitle = '', $actionButton = null) {
    global $adminFirstName;
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
