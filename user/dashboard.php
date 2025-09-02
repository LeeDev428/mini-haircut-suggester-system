<?php
require_once 'includes/layout.php';

$pdo = getDatabaseConnection();
$user_id = $_SESSION['user']['id'];

// Get user's appointment statistics
$appointment_stats_query = "SELECT 
    (SELECT COUNT(*) FROM appointments WHERE user_id = :user_id) as total_appointments,
    (SELECT COUNT(*) FROM appointments WHERE user_id = :user_id AND status = 'confirmed') as confirmed_appointments,
    (SELECT COUNT(*) FROM appointments WHERE user_id = :user_id AND status = 'completed') as completed_appointments,
    (SELECT COUNT(*) FROM appointments WHERE user_id = :user_id AND appointment_date >= CURDATE()) as upcoming_appointments";
$appointment_stats_stmt = $pdo->prepare($appointment_stats_query);
$appointment_stats_stmt->bindParam(':user_id', $user_id);
$appointment_stats_stmt->execute();
$appointment_stats = $appointment_stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get user's other statistics
$other_stats_query = "SELECT 
    (SELECT COUNT(*) FROM user_saved_haircuts WHERE user_id = :user_id) as saved_haircuts,
    (SELECT COUNT(*) FROM user_quiz_results WHERE user_id = :user_id) as quizzes_taken,
    (SELECT COUNT(*) FROM user_haircut_history WHERE user_id = :user_id) as recommendations
";
$other_stats_stmt = $pdo->prepare($other_stats_query);
$other_stats_stmt->bindParam(':user_id', $user_id);
$other_stats_stmt->execute();
$other_stats = $other_stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent appointments (mini history)
$recent_appointments_query = "SELECT a.*, s.name as stylist_name 
                             FROM appointments a 
                             LEFT JOIN stylists s ON a.preferred_stylist = s.name
                             WHERE a.user_id = :user_id 
                             ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                             LIMIT 5";
$recent_appointments_stmt = $pdo->prepare($recent_appointments_query);
$recent_appointments_stmt->bindParam(':user_id', $user_id);
$recent_appointments_stmt->execute();
$recent_appointments = $recent_appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Get recent quiz results
$recent_quiz_query = "SELECT qr.*, fs.name as face_shape
                     FROM user_quiz_results qr 
                     LEFT JOIN face_shapes fs ON qr.face_shape_id = fs.id
                     WHERE qr.user_id = :user_id 
                     ORDER BY qr.created_at DESC LIMIT 1";
$recent_quiz_stmt = $pdo->prepare($recent_quiz_query);
$recent_quiz_stmt->bindParam(':user_id', $user_id);
$recent_quiz_stmt->execute();
$recent_quiz = $recent_quiz_stmt->fetch(PDO::FETCH_ASSOC);

startLayout('Dashboard', 'dashboard');
?>

<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .stat-icon.appointments { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-icon.saved { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-icon.quizzes { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stat-icon.rating { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: #2d3748;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #718096;
        font-size: 14px;
        font-weight: 500;
    }
    
    .stat-change {
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .stat-change.positive {
        background: #d4edda;
        color: #155724;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .content-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .section-title {
        margin: 0;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 18px;
        font-weight: 600;
    }
    
    .appointment-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .appointment-item:last-child {
        border-bottom: none;
    }
    
    .appointment-date {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 12px;
        border-radius: 10px;
        text-align: center;
        min-width: 60px;
    }
    
    .appointment-date .day {
        font-size: 18px;
        font-weight: bold;
        line-height: 1;
    }
    
    .appointment-date .month {
        font-size: 11px;
        opacity: 0.8;
        text-transform: uppercase;
    }
    
    .appointment-info h5 {
        margin: 0 0 5px 0;
        color: #2d3748;
        font-weight: 600;
    }
    
    .appointment-info p {
        margin: 0;
        font-size: 13px;
        color: #718096;
    }
    
    .appointment-status {
        margin-left: auto;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .appointment-status.confirmed {
        background: #d4edda;
        color: #155724;
    }
    
    .appointment-status.completed {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .appointment-status.cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    
    .haircut-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .haircut-card {
        background: #f8f9fa;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s ease;
        cursor: pointer;
    }
    
    .haircut-card:hover {
        transform: scale(1.05);
    }
    
    .haircut-image {
        height: 100px;
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 24px;
    }
    
    .haircut-name {
        padding: 10px;
        font-size: 12px;
        font-weight: 600;
        color: #495057;
        text-align: center;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .quick-action {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 25px;
        text-decoration: none;
        color: #495057;
        text-align: center;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    
    .quick-action:hover {
        border-color: #667eea;
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        color: #667eea;
        text-decoration: none;
    }
    
    .quick-action i {
        font-size: 36px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .quick-action h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }
    
    .quick-action p {
        margin: 0;
        font-size: 13px;
        color: #718096;
    }
    
    .no-data {
        text-align: center;
        padding: 40px 20px;
        color: #718096;
    }
    
    .no-data i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard-container">
    <?php renderPageHeader('Welcome back, ' . htmlspecialchars($_SESSION['user']['first_name']) . '! 👋', 'Ready to discover your perfect hairstyle?'); ?>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-number"><?php echo $appointment_stats['total_appointments']; ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
                <div class="stat-icon appointments">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
          
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-number"><?php echo $other_stats['saved_haircuts']; ?></div>
                    <div class="stat-label">Saved Haircuts</div>
                </div>
                <div class="stat-icon saved">
                    <i class="fas fa-heart"></i>
                </div>
            </div>
          
        </div>
        
    
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="booking.php" class="quick-action">
            <i class="fas fa-calendar-plus"></i>
            <h3>Book Appointment</h3>
            <p>Schedule your next haircut session</p>
        </a>
        <a href="browse-haircuts.php" class="quick-action">
            <i class="fas fa-search"></i>
            <h3>Browse Styles</h3>
            <p>Discover new hairstyles for you</p>
        </a>
    
        <a href="my-history.php" class="quick-action">
            <i class="fas fa-history"></i>
            <h3>View History</h3>
            <p>See your past appointments & styles</p>
        </a>
    </div>
    
    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Recent Appointments (Mini History) -->
        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-calendar"></i>
                    Recent Appointments
                </h3>
                <a href="my-history.php" style="color: #667eea; text-decoration: none; font-size: 14px;">View All</a>
            </div>
            
            <?php if (empty($recent_appointments)): ?>
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <p>No appointments yet</p>
                    <a href="booking.php" style="color: #667eea; text-decoration: none;">Book your first appointment</a>
                </div>
            <?php else: ?>
                <?php foreach ($recent_appointments as $appointment): ?>
                <div class="appointment-item">
                    <div class="appointment-date">
                        <div class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></div>
                        <div class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></div>
                    </div>
                    <div class="appointment-info">
                        <h5><?php echo ucfirst($appointment['service_type']); ?></h5>
                        <p><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></p>
                        <?php if ($appointment['stylist_name']): ?>
                            <p>with <?php echo htmlspecialchars($appointment['stylist_name']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="appointment-status <?php echo $appointment['status']; ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Saved Haircuts -->
        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-heart"></i>
                    Saved Haircuts
                </h3>
                <a href="browse-haircuts.php" style="color: #667eea; text-decoration: none; font-size: 14px;">Browse More</a>
            </div>
            
            <?php if (empty($saved_haircuts)): ?>
                <div class="no-data">
                    <i class="fas fa-heart"></i>
                    <p>No saved haircuts yet</p>
                    <a href="browse-haircuts.php" style="color: #667eea; text-decoration: none;">Discover styles to save</a>
                </div>
            <?php else: ?>
                <div class="haircut-grid">
                    <?php foreach ($saved_haircuts as $haircut): ?>
                    <div class="haircut-card" onclick="window.location.href='haircut-details.php?id=<?php echo $haircut['id']; ?>'">
                        <div class="haircut-image">
                            <?php if ($haircut['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($haircut['image_url']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-cut"></i>
                            <?php endif; ?>
                        </div>
                        <div class="haircut-name">
                            <?php echo htmlspecialchars($haircut['name']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endLayout(); ?>
