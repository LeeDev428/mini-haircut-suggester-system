<?php
require_once 'includes/layout.php';

$pdo = getDatabaseConnection();

// Get user's recent quiz results
$user_id = $_SESSION['user']['id'];
$recent_query = "SELECT qr.*, fs.name as face_shape
                FROM user_quiz_results qr 
                LEFT JOIN face_shapes fs ON qr.face_shape_id = fs.id
                WHERE qr.user_id = :user_id 
                ORDER BY qr.created_at DESC LIMIT 1";
$recent_stmt = $pdo->prepare($recent_query);
$recent_stmt->bindParam(':user_id', $user_id);
$recent_stmt->execute();
$recent_quiz = $recent_stmt->fetch(PDO::FETCH_ASSOC);

// Get saved haircuts
$saved_query = "SELECT h.*, ush.saved_at 
                FROM user_saved_haircuts ush 
                JOIN haircuts h ON ush.haircut_id = h.id 
                WHERE ush.user_id = :user_id 
                ORDER BY ush.saved_at DESC LIMIT 6";
$saved_stmt = $pdo->prepare($saved_query);
$saved_stmt->bindParam(':user_id', $user_id);
$saved_stmt->execute();
$saved_haircuts = $saved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total counts
$counts_query = "SELECT 
    (SELECT COUNT(*) FROM user_saved_haircuts WHERE user_id = :user_id) as total_saved,
    (SELECT COUNT(*) FROM user_quiz_results WHERE user_id = :user_id) as total_quizzes,
    (SELECT COUNT(*) FROM user_haircut_history WHERE user_id = :user_id) as total_recommendations";
$counts_stmt = $pdo->prepare($counts_query);
$counts_stmt->bindParam(':user_id', $user_id);
$counts_stmt->execute();
$counts = $counts_stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php startLayout('Dashboard', 'dashboard'); ?>
            <!-- Header -->
            <header class="content-header">
                <div class="header-left">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user']['first_name']); ?>! 👋</h1>
                    <p>Ready to discover your perfect hairstyle?</p>
                </div>
                
            </header>

          

          

<script src="../assets/js/dashboard.js"></script>

<?php endLayout(); ?>
