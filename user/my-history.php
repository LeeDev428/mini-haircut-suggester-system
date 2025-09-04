<?php
require_once 'includes/layout.php';

$userId = $_SESSION['user']['id'];
$pdo = getDatabaseConnection();

// Get user's appointment history
$appointments = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, s.name as stylist_name, s.rating as stylist_rating 
        FROM appointments a 
        LEFT JOIN stylists s ON a.preferred_stylist = s.name
        WHERE a.user_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$userId]);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist yet, initialize empty array
    $appointments = [];
}

// Get user's haircut history
$stmt = $pdo->prepare("
    SELECT h.*, uhh.rating, uhh.review, uhh.date_tried, uhh.would_recommend, uhh.created_at
    FROM user_haircut_history uhh 
    JOIN haircuts h ON uhh.haircut_id = h.id 
    WHERE uhh.user_id = ? 
    ORDER BY uhh.created_at DESC
");
$stmt->execute([$userId]);
$haircutHistory = $stmt->fetchAll();

// Get saved haircuts
$stmt = $pdo->prepare("
    SELECT h.*, ush.saved_at 
    FROM user_saved_haircuts ush 
    JOIN haircuts h ON ush.haircut_id = h.id 
    WHERE ush.user_id = ? 
    ORDER BY ush.saved_at DESC
");
$stmt->execute([$userId]);
$savedHaircuts = $stmt->fetchAll();

// Get quiz history
$stmt = $pdo->prepare("
    SELECT qr.*, fs.name as face_shape_name
    FROM user_quiz_results qr 
    LEFT JOIN face_shapes fs ON qr.face_shape_id = fs.id
    WHERE qr.user_id = ? 
    ORDER BY qr.created_at DESC
");
$stmt->execute([$userId]);
$quizHistory = $stmt->fetchAll();
?>

<?php startLayout('My History', 'history'); ?>

<style>
    .history-content {
        padding: 20px 0;
    }
    
    .history-tabs {
        display: flex;
        background: white;
        border-radius: 15px;
        padding: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .tab-button {
        flex: 1;
        padding: 12px 20px;
        background: none;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
        color: var(--gray-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .tab-button.active {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 2px 10px rgba(74, 144, 226, 0.3);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .history-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .section-header {
        display: flex;
        align-items: center;
        justify-content: between;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: var(--dark-color);
    }
    
    .section-count {
        background: var(--primary-color);
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        margin-left: auto;
    }
    
    .history-grid {
        display: grid;
        gap: 20px;
    }
    
    .history-item {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.2s ease;
        background: #fafbfc;
    }
    
    .history-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-color);
    }
    
    .appointment-item {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 15px;
        align-items: center;
    }
    
    .appointment-icon {
        width: 50px;
        height: 50px;
        background: var(--primary-gradient);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }
    
    .appointment-details h4 {
        margin: 0 0 5px 0;
        color: var(--dark-color);
        font-size: 16px;
    }
    
    .appointment-meta {
        font-size: 14px;
        color: var(--gray-medium);
        line-height: 1.4;
    }
    
    .appointment-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }
    
    .appointment-status.pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .appointment-status.confirmed {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .appointment-status.completed {
        background: #d4edda;
        color: #155724;
    }
    
    .appointment-status.cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    
    .haircut-item {
        display: grid;
        grid-template-columns: 80px 1fr auto;
        gap: 15px;
        align-items: center;
    }
    
    .haircut-image {
        width: 80px;
        height: 80px;
        border-radius: 10px;
        background: var(--gray-light);
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gray-medium);
        font-size: 24px;
    }
    
    .haircut-info h4 {
        margin: 0 0 5px 0;
        color: var(--dark-color);
    }
    
    .haircut-info p {
        margin: 0;
        color: var(--gray-medium);
        font-size: 14px;
        line-height: 1.4;
    }
    
    .haircut-rating {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 5px;
    }
    
    .rating-stars {
        color: #ffc107;
    }
    
    .haircut-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .quiz-item {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 15px;
        align-items: center;
    }
    
    .quiz-icon {
        width: 50px;
        height: 50px;
    background: var(--primary-gradient);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }
    
    .quiz-results {
        display: flex;
        gap: 15px;
        margin-top: 8px;
    }
    
    .quiz-result-tag {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray-medium);
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .empty-state h3 {
        margin: 0 0 10px 0;
        color: var(--dark-color);
    }
    
    .empty-state p {
        margin: 0 0 20px 0;
        max-width: 300px;
        margin-left: auto;
        margin-right: auto;
    }
    
    @media (max-width: 768px) {
        .history-tabs {
            flex-direction: column;
            gap: 8px;
        }
        
        .tab-button {
            justify-content: flex-start;
        }
        
        .appointment-item,
        .haircut-item,
        .quiz-item {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        .appointment-actions,
        .haircut-actions {
            flex-direction: row;
            justify-content: center;
        }
    }
</style>

<div class="history-content">
    <?php 
    renderPageHeader(
        'My History', 
        'View your appointment history, saved haircuts, and quiz results',
    ); 
    ?>
   
    
    <!-- Appointments Tab -->
    <div id="appointments" class="tab-content active">
        <div class="history-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Appointment History
                </h3>
                <span class="section-count" style="background: var(--primary-gradient); color: #fff; border: none;"><?php echo count($appointments); ?></span>
            </div>
            
            <?php if (empty($appointments)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar"></i>
                    <h3>No Appointments Yet</h3>
                    <p>You haven't booked any appointments yet. Schedule your first appointment with one of our professional stylists!</p>
                    <a href="booking.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Book Your First Appointment
                    </a>
                </div>
            <?php else: ?>
                <div class="history-grid">
                    <?php foreach ($appointments as $appointment): ?>
                        <div class="history-item">
                            <div class="appointment-item">
                                <div class="appointment-icon" style="background: var(--primary-gradient); color: #fff; border: none;">
                                    <i class="fas fa-cut" ></i>
                                </div>
                                <div class="appointment-details">
                                    <h4><?php echo ucfirst($appointment['service_type']); ?></h4>
                                    <div class="appointment-meta">
                                        <div><strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></div>
                                        <div><strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></div>
                                        <?php if ($appointment['stylist_name']): ?>
                                            <div><strong>Stylist:</strong> <?php echo htmlspecialchars($appointment['stylist_name']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($appointment['notes']): ?>
                                            <div><strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="appointment-status <?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Saved Haircuts Tab -->
    <div id="haircuts" class="tab-content">
        <div class="history-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-heart"></i>
                    Saved Haircuts
                </h3>
                <span class="section-count" style="background: var(--primary-gradient); color: #fff; border: none;"><?php echo count($savedHaircuts); ?></span>
            </div>
            
            <?php if (empty($savedHaircuts)): ?>
                <div class="empty-state">
                    <i class="fas fa-heart"></i>
                    <h3>No Saved Haircuts</h3>
                    <p>Start saving haircuts you love to build your personal collection!</p>
                    <a href="browse-haircuts.php" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Browse Haircuts
                    </a>
                </div>
            <?php else: ?>
                <div class="history-grid">
                    <?php foreach ($savedHaircuts as $haircut): ?>
                        <div class="history-item">
                            <div class="haircut-item">
                                <div class="haircut-image" style="background-image: url('<?php echo $haircut['image_url'] ? htmlspecialchars($haircut['image_url']) : ''; ?>')">
                                    <?php if (!$haircut['image_url']): ?>
                                        <i class="fas fa-cut"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="haircut-info">
                                    <h4><?php echo htmlspecialchars($haircut['name']); ?></h4>
                                    <p><?php echo htmlspecialchars(substr($haircut['description'], 0, 100)); ?>...</p>
                                    <small style="color: var(--gray-medium);">
                                        Saved on <?php echo date('M j, Y', strtotime($haircut['saved_at'])); ?>
                                    </small>
                                </div>
                                <div class="haircut-actions">
                                    <a href="haircut-details.php?id=<?php echo $haircut['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </a>
                                    <a href="booking.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-calendar"></i>
                                        Book
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Viewed Styles Tab -->
    <div id="viewed" class="tab-content">
        <div class="history-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-eye"></i>
                    Recently Viewed
                </h3>
                <span class="section-count"><?php echo count($haircutHistory); ?></span>
            </div>
            
            <?php if (empty($haircutHistory)): ?>
                <div class="empty-state">
                    <i class="fas fa-eye"></i>
                    <h3>No Viewing History</h3>
                    <p>Your recently viewed haircuts will appear here as you browse our gallery.</p>
                    <a href="browse-haircuts.php" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Start Browsing
                    </a>
                </div>
            <?php else: ?>
                <div class="history-grid">
                    <?php foreach ($haircutHistory as $haircut): ?>
                        <div class="history-item">
                            <div class="haircut-item">
                                <div class="haircut-image" style="background-image: url('<?php echo $haircut['image_url'] ? htmlspecialchars($haircut['image_url']) : ''; ?>')">
                                    <?php if (!$haircut['image_url']): ?>
                                        <i class="fas fa-cut"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="haircut-info">
                                    <h4><?php echo htmlspecialchars($haircut['name']); ?></h4>
                                    <p><?php echo htmlspecialchars(substr($haircut['description'], 0, 100)); ?>...</p>
                                    <?php if ($haircut['rating']): ?>
                                        <div class="haircut-rating">
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= $haircut['rating'] ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span><?php echo $haircut['rating']; ?>/5</span>
                                        </div>
                                    <?php endif; ?>
                                    <small style="color: var(--gray-medium);">
                                        <?php if ($haircut['date_tried']): ?>
                                            Tried on <?php echo date('M j, Y', strtotime($haircut['date_tried'])); ?>
                                        <?php else: ?>
                                            Added on <?php echo date('M j, Y', strtotime($haircut['created_at'])); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="haircut-actions">
                                    <a href="haircut-details.php?id=<?php echo $haircut['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                        View Again
                                    </a>
                                    <a href="booking.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-calendar"></i>
                                        Book
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quizzes Tab -->
    <div id="quizzes" class="tab-content">
        <div class="history-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-clipboard-check"></i>
                    Quiz Results
                </h3>
                <span class="section-count"><?php echo count($quizHistory); ?></span>
            </div>
            
            <?php if (empty($quizHistory)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>No Quiz Results</h3>
                    <p>Take our personalized quiz to get tailored haircut recommendations based on your face shape and preferences.</p>
                    <a href="recommendations.php" class="btn btn-primary">
                        <i class="fas fa-play"></i>
                        Take Quiz
                    </a>
                </div>
            <?php else: ?>
                <div class="history-grid">
                    <?php foreach ($quizHistory as $quiz): ?>
                        <div class="history-item">
                            <div class="quiz-item">
                                <div class="quiz-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div class="quiz-details">
                                    <h4>Face Shape Analysis</h4>
                                    <div class="appointment-meta">
                                        <div><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($quiz['created_at'])); ?></div>
                                        <div class="quiz-results">
                                            <?php if ($quiz['face_shape_name']): ?>
                                                <span class="quiz-result-tag">
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($quiz['face_shape_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($quiz['hair_type']): ?>
                                                <span class="quiz-result-tag">
                                                    <i class="fas fa-wave-square"></i> <?php echo htmlspecialchars($quiz['hair_type']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($quiz['lifestyle']): ?>
                                                <span class="quiz-result-tag">
                                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($quiz['lifestyle']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="haircut-actions">
                                    <a href="recommendations.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-magic"></i>
                                        View Recommendations
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showTab(tabName, btn) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    if (btn) { btn.classList.add('active'); }
}
</script>

<?php endLayout(); ?>
