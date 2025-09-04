<?php
require_once 'includes/layout.php';

$userId = $_SESSION['user']['id'];
$pdo = getDatabaseConnection();

// Get quiz history
$stmt = $pdo->prepare("
    SELECT qr.*, u.first_name, u.last_name, fs.name AS face_shape_name
    FROM user_quiz_results qr
    JOIN users u ON qr.user_id = u.id
    LEFT JOIN face_shapes fs ON qr.face_shape_id = fs.id
    WHERE qr.user_id = ?
    ORDER BY qr.created_at DESC
");
$stmt->execute([$userId]);
$quizHistory = $stmt->fetchAll();

// Get saved haircuts history
$stmt = $pdo->prepare("
    SELECT ush.*, h.name, h.description, h.image_url, h.maintenance_level
    FROM user_saved_haircuts ush
    JOIN haircuts h ON ush.haircut_id = h.id
    WHERE ush.user_id = ?
    ORDER BY ush.saved_at DESC
    LIMIT 20
");
$stmt->execute([$userId]);
$savedHistory = $stmt->fetchAll();

// Get recommendation history (using haircut history as recommendation history)
$stmt = $pdo->prepare("
    SELECT uhh.*, h.name, h.description, h.image_url
    FROM user_haircut_history uhh
    JOIN haircuts h ON uhh.haircut_id = h.id
    WHERE uhh.user_id = ?
    ORDER BY uhh.created_at DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$recommendationHistory = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT qr.id) as total_quizzes,
        COUNT(DISTINCT ush.id) as total_saved,
        COUNT(DISTINCT uhh.id) as total_recommendations,
        MIN(qr.created_at) as first_quiz
    FROM users u
    LEFT JOIN user_quiz_results qr ON u.id = qr.user_id
    LEFT JOIN user_saved_haircuts ush ON u.id = ush.user_id
    LEFT JOIN user_haircut_history uhh ON u.id = uhh.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - HairCut Suggester</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .history-main {
            margin-left: 280px;
            padding: 30px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .history-header {
            margin-bottom: 30px;
        }
        
        .history-header h1 {
            font-size: 2.5rem;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: var(--shadow-light);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin: 0 0 8px 0;
            color: var(--dark-color);
        }
        
        .stat-card p {
            margin: 0;
            color: var(--gray-medium);
            font-weight: 500;
        }
        
        .history-tabs {
            display: flex;
            background: white;
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-light);
        }
        
        .tab-button {
            flex: 1;
            padding: 15px 20px;
            background: transparent;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .tab-button.active {
            background: var(--primary-gradient);
            color: white;
        }
        
        .tab-button:not(.active) {
            color: var(--gray-medium);
        }
        
        .tab-button:hover:not(.active) {
            background: var(--gray-light);
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
            box-shadow: var(--shadow-light);
            overflow: hidden;
        }
        
        .section-header {
            padding: 25px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .section-header h2 {
            margin: 0;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .history-list {
            padding: 0;
        }
        
        .history-item {
            padding: 20px 25px;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-item:hover {
            background: var(--gray-light);
        }
        
        .history-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
        }
        
        .quiz-icon {
            background: var(--primary-gradient);
        }
        
        .save-icon {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }
        
        .recommendation-icon {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }
        
        .history-content {
            flex: 1;
        }
        
        .history-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0 0 5px 0;
        }
        
        .history-description {
            color: var(--gray-medium);
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        
        .history-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 12px;
            color: var(--gray-medium);
        }
        
        .history-date {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .history-badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .history-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .action-btn.primary {
            background: var(--primary-color);
            color: white;
        }
        
        .action-btn.outline {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .quiz-details {
            background: var(--gray-light);
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
        }
        
        .quiz-answers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .answer-item {
            display: flex;
            justify-content: between;
            align-items: center;
            font-size: 12px;
        }
        
        .answer-label {
            color: var(--gray-medium);
            font-weight: 500;
        }
        
        .answer-value {
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .haircut-preview {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .haircut-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .haircut-placeholder {
            width: 100%;
            height: 100%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--gray-medium);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .load-more {
            text-align: center;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .history-main {
                margin-left: 0;
                padding: 20px;
            }
            
            .stats-overview {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .history-tabs {
                flex-direction: column;
            }
            
            .history-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .history-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="history-main">
            <div class="history-header">
                <h1><i class="fas fa-history"></i> My History</h1>
                <p>Track your journey with face shape quizzes, saved haircuts, and recommendations</p>
            </div>
            
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3><?php echo $stats['total_quizzes']; ?></h3>
                    <p>Quizzes Taken</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3><?php echo $stats['total_saved']; ?></h3>
                    <p>Saved Haircuts</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-magic"></i>
                    </div>
                    <h3><?php echo $stats['total_recommendations']; ?></h3>
                    <p>Recommendations</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <h3><?php echo $stats['first_quiz'] ? date('M Y', strtotime($stats['first_quiz'])) : 'N/A'; ?></h3>
                    <p>Member Since</p>
                </div>
            </div>
            
            <div class="history-tabs">
                <button class="tab-button active" onclick="switchTab('quiz-history', this)">
                    <i class="fas fa-user-check"></i> Quiz History
                </button>
                <button class="tab-button" onclick="switchTab('saved-history', this)">
                    <i class="fas fa-heart"></i> Saved Haircuts
                </button>
                <button class="tab-button" onclick="switchTab('recommendation-history', this)">
                    <i class="fas fa-magic"></i> Recommendations
                </button>
            </div>
            
            <!-- Quiz History Tab -->
            <div id="quiz-history" class="tab-content active">
                <div class="history-section">
                    <div class="section-header">
                        <h2><i class="fas fa-user-check"></i> Face Shape Quiz History</h2>
                    </div>
                    
                    <div class="history-list">
                        <?php if (empty($quizHistory)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-check"></i>
                                <h3>No quiz history yet</h3>
                                <p>Take your first face shape quiz to get started!</p>
                                <a href="face-shape-quiz.php" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-play"></i> Take Quiz Now
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($quizHistory as $quiz): ?>
                                <div class="history-item">
                                    <div class="history-icon quiz-icon">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    
                                    <div class="history-content">
                                        <h3 class="history-title">Face Shape Quiz Result: <?php echo ucfirst($quiz['face_shape_name'] ?? ''); ?></h3>
                                        <p class="history-description">Completed face shape analysis and received personalized recommendations</p>
                                        
                                        <div class="history-meta">
                                            <span class="history-date">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($quiz['created_at'])); ?>
                                            </span>
                                            <span class="history-badge"><?php echo ucfirst($quiz['face_shape_name'] ?? ''); ?> Face</span>
                                        </div>
                                        
                                        <div class="quiz-details" style="display: none;" id="quiz-<?php echo $quiz['id']; ?>">
                                            <div class="quiz-answers">
                                                <?php 
                                                $answers = json_decode($quiz['quiz_score'] ?? '[]', true);
                                                if ($answers):
                                                    foreach ($answers as $key => $value):
                                                ?>
                                                    <div class="answer-item">
                                                        <span class="answer-label"><?php echo str_replace('_', ' ', ucfirst($key)); ?>:</span>
                                                        <span class="answer-value"><?php echo ucfirst($value); ?></span>
                                                    </div>
                                                <?php 
                                                    endforeach;
                                                endif; 
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="history-actions">
                                        <button class="action-btn outline" onclick="toggleQuizDetails(<?php echo $quiz['id']; ?>, this)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="action-btn primary" onclick="viewRecommendations('<?php echo $quiz['face_shape_name'] ?? ''; ?>')">
                                            <i class="fas fa-magic"></i> Get Recommendations
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Saved Haircuts History Tab -->
            <div id="saved-history" class="tab-content">
                <div class="history-section">
                    <div class="section-header">
                        <h2><i class="fas fa-heart"></i> Saved Haircuts History</h2>
                    </div>
                    
                    <div class="history-list">
                        <?php if (empty($savedHistory)): ?>
                            <div class="empty-state">
                                <i class="fas fa-heart"></i>
                                <h3>No saved haircuts yet</h3>
                                <p>Start browsing haircuts and save your favorites!</p>
                                <a href="browse-haircuts.php" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-search"></i> Browse Haircuts
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($savedHistory as $saved): ?>
                                <div class="history-item">
                                    <div class="haircut-preview">
                                        <?php if ($saved['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($saved['image_url']); ?>" alt="<?php echo htmlspecialchars($saved['name']); ?>">
                                        <?php else: ?>
                                            <div class="haircut-placeholder">
                                                <i class="fas fa-cut"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="history-content">
                                        <h3 class="history-title"><?php echo htmlspecialchars($saved['name']); ?></h3>
                                        <p class="history-description"><?php echo htmlspecialchars(substr($saved['description'], 0, 100)) . '...'; ?></p>
                                        
                                        <div class="history-meta">
                                            <span class="history-date">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('M j, Y', strtotime($saved['saved_at'])); ?>
                                            </span>
                                            <?php if ($saved['maintenance_level']): ?>
                                                <span class="history-badge"><?php echo ucfirst($saved['maintenance_level']); ?> Maintenance</span>
                                            <?php endif; ?>
                                            <span class="history-badge">Saved Haircut</span>
                                        </div>
                                    </div>
                                    
                                    <div class="history-actions">
                                        <button class="action-btn outline" onclick="viewHaircut(<?php echo $saved['haircut_id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="action-btn primary" onclick="unsaveHaircut(<?php echo $saved['haircut_id']; ?>, this)">
                                            <i class="fas fa-heart-broken"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recommendations History Tab -->
            <div id="recommendation-history" class="tab-content">
                <div class="history-section">
                    <div class="section-header">
                        <h2><i class="fas fa-magic"></i> Recommendations History</h2>
                    </div>
                    
                    <div class="history-list">
                        <?php if (empty($recommendationHistory)): ?>
                            <div class="empty-state">
                                <i class="fas fa-magic"></i>
                                <h3>No recommendations yet</h3>
                                <p>Take the face shape quiz to get personalized recommendations!</p>
                                <a href="face-shape-quiz.php" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-user-check"></i> Take Quiz
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recommendationHistory as $rec): ?>
                                <div class="history-item">
                                    <div class="haircut-preview">
                                        <?php if ($rec['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($rec['image_url']); ?>" alt="<?php echo htmlspecialchars($rec['name']); ?>">
                                        <?php else: ?>
                                            <div class="haircut-placeholder">
                                                <i class="fas fa-cut"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="history-content">
                                        <h3 class="history-title"><?php echo htmlspecialchars($rec['name']); ?></h3>
                                        <p class="history-description"><?php echo htmlspecialchars(substr($rec['description'], 0, 100)) . '...'; ?></p>
                                        
                                        <div class="history-meta">
                                            <span class="history-date">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('M j, Y', strtotime($rec['created_at'])); ?>
                                            </span>
                                            <?php if (!empty($rec['rating'])): ?>
                                                <span class="history-badge">Rating <?php echo (int)$rec['rating']; ?>/5</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="history-actions">
                                        <button class="action-btn outline" onclick="viewHaircut(<?php echo $rec['haircut_id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="action-btn primary" onclick="saveHaircut(<?php echo $rec['haircut_id']; ?>, this)">
                                            <i class="fas fa-heart"></i> Save
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="load-more">
                                <button class="btn btn-outline" onclick="loadMoreRecommendations()">
                                    <i class="fas fa-plus"></i> Load More
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function switchTab(tabId, btn) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked button
            if (btn) { btn.classList.add('active'); }
        }
        
        function toggleQuizDetails(quizId, btn) {
            const details = document.getElementById(`quiz-${quizId}`);
            const button = btn;
            
            if (details.style.display === 'none' || !details.style.display) {
                details.style.display = 'block';
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details';
            } else {
                details.style.display = 'none';
                button.innerHTML = '<i class="fas fa-eye"></i> View Details';
            }
        }
        
        function viewRecommendations(faceShape) {
            window.location.href = `recommendations.php?face_shape=${faceShape}`;
        }
        
        function viewHaircut(haircutId) {
            window.location.href = `haircut-details.php?id=${haircutId}`;
        }
        
        function saveHaircut(haircutId, button) {
            const icon = button.querySelector('i');
            const originalHTML = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            button.disabled = true;
            
            fetch('../api/save-haircut.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    haircut_id: haircutId,
                    action: 'save'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.innerHTML = '<i class="fas fa-check"></i> Saved!';
                    showToast('Haircut saved successfully!', 'success');
                    
                    setTimeout(() => {
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to save haircut');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.innerHTML = originalHTML;
                button.disabled = false;
                showToast('Error saving haircut. Please try again.', 'error');
            });
        }
        
        function unsaveHaircut(haircutId, button) {
            if (!confirm('Are you sure you want to remove this haircut from your saved list?')) {
                return;
            }
            
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
            button.disabled = true;
            
            fetch('../api/save-haircut.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    haircut_id: haircutId,
                    action: 'unsave'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the item from the list
                    const historyItem = button.closest('.history-item');
                    historyItem.style.opacity = '0';
                    setTimeout(() => historyItem.remove(), 300);
                    showToast('Haircut removed from saved list', 'success');
                } else {
                    throw new Error(data.message || 'Failed to remove haircut');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.innerHTML = originalHTML;
                button.disabled = false;
                showToast('Error removing haircut. Please try again.', 'error');
            });
        }
        
        function loadMoreRecommendations() {
            showToast('Loading more recommendations...', 'info');
        }
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            setTimeout(() => toast.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
