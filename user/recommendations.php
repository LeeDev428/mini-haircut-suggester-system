<?php
require_once 'includes/layout.php';
require_once '../config/recommendation_engine.php';

$userId = $_SESSION['user']['id'];
$pdo = getDatabaseConnection();

// Get user's latest quiz info (face shape and preference IDs)
$stmt = $pdo->prepare("
    SELECT fs.name as face_shape, fs.id as face_shape_id,
           qr.hair_type_id, qr.hair_thickness_id, qr.lifestyle_preference_id, qr.age_group_id,
           qr.current_hair_length, qr.budget_range,
           COUNT(qr.id) as quiz_count,
           MAX(qr.created_at) as last_quiz
    FROM users u
    LEFT JOIN user_quiz_results qr ON u.id = qr.user_id
    LEFT JOIN face_shapes fs ON qr.face_shape_id = fs.id
    WHERE u.id = ?
    GROUP BY u.id, fs.id, fs.name, qr.hair_type_id, qr.hair_thickness_id, qr.lifestyle_preference_id, qr.age_group_id, qr.current_hair_length, qr.budget_range
    ORDER BY MAX(qr.created_at) DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch();

// Fetch user gender for recommendation engine
$gender = null;
$stmt = $pdo->prepare('SELECT gender FROM users WHERE id = ?');
$stmt->execute([$userId]);
$userRow = $stmt->fetch();
if ($userRow) { $gender = $userRow['gender']; }

// Get personalized recommendations using the engine
$recommendations = [];
if ($userInfo && $userInfo['face_shape_id']) {
    $engine = new HaircutRecommendationEngine();
    $recommendations = $engine->getRecommendations(
        (int)$userInfo['face_shape_id'],
        $userInfo['hair_type_id'] ? (int)$userInfo['hair_type_id'] : null,
        $userInfo['hair_thickness_id'] ? (int)$userInfo['hair_thickness_id'] : null,
        $userInfo['lifestyle_preference_id'] ? (int)$userInfo['lifestyle_preference_id'] : null,
        $userInfo['age_group_id'] ? (int)$userInfo['age_group_id'] : null,
        $gender,
        $userInfo['current_hair_length'] ?? null,
        $userInfo['budget_range'] ?? null
    );
}

// Get trending haircuts for the user's face shape (by trend_score)
$trendingHaircuts = [];
if ($userInfo && $userInfo['face_shape_id']) {
    $stmt = $pdo->prepare("
        SELECT h.*
        FROM haircut_recommendations hr
        JOIN haircuts h ON hr.haircut_id = h.id
        WHERE hr.face_shape_id = ?
        ORDER BY h.trend_score DESC
        LIMIT 6
    ");
    $stmt->execute([$userInfo['face_shape_id']]);
    $trendingHaircuts = $stmt->fetchAll();
}
?>
<?php startLayout('Get Recommendations', 'recommendations'); ?>

<style>
    .recommendations-content {
        padding: 20px 0;
    }
    
    .face-shape-info {
    background: var(--primary-gradient);
        color: white;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .face-shape-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
    }
        
        .face-shape-details h3 {
            margin: 0 0 8px 0;
            font-size: 1.5rem;
        }
        
        .face-shape-details p {
            margin: 0;
            opacity: 0.9;
        }
        
        .no-quiz-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
        }
        
        .no-quiz-card i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .no-quiz-card h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
        }
        
        .no-quiz-card p {
            color: var(--gray-medium);
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .recommendation-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            position: relative;
        }
        
        .recommendation-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .recommendation-image {
            height: 200px;
            position: relative;
            overflow: hidden;
        }
        
        .recommendation-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }
        
        .recommendation-score {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary-color);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .recommendation-content {
            padding: 20px;
        }
        
        .recommendation-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .recommendation-reason {
            color: var(--gray-medium);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .recommendation-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .recommendation-tag {
            background: var(--gray-light);
            color: var(--gray-medium);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .recommendation-tag.maintenance {
            background: var(--primary-color);
            color: white;
        }
        
        .recommendation-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid #f1f3f4;
        }
        
        .action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
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
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .view-all-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .view-all-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            text-align: center;
        }
        
        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            font-size: 1.5rem;
            margin: 0 0 5px 0;
            color: var(--dark-color);
        }
        
        .stat-card p {
            margin: 0;
            color: var(--gray-medium);
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .recommendations-main {
                margin-left: 0;
                padding: 20px;
            }
            
            .face-shape-info {
                flex-direction: column;
                text-align: center;
            }
            
            .recommendations-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

<div class="recommendations-content">
    <?php 
    renderPageHeader(
        'Get Recommendations', 
        'Personalized haircut suggestions based on your face shape and preferences',
    ); 
    ?>
    
    <?php if (!$userInfo || !$userInfo['face_shape']): ?>
                <div class="no-quiz-card">
                    <i class="fas fa-user-check"></i>
                    <h3>Take the Face Shape Quiz First!</h3>
                    <p>To get personalized haircut recommendations, you need to take our face shape quiz. It only takes 2-3 minutes and will help us suggest the perfect hairstyles for you.</p>
                  
                </div>
            <?php else: ?>
                <div class="face-shape-info">
                    <div class="face-shape-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="face-shape-details">
                        <h3>Your Face Shape: <?php echo ucfirst($userInfo['face_shape']); ?></h3>
                        <p>Based on your quiz results, we've curated personalized recommendations just for you. 
                           Last quiz taken: <?php echo date('M j, Y', strtotime($userInfo['last_quiz'])); ?></p>
                    </div>
                    <div style="margin-left: auto;">
                        <a href="face-shape-quiz.php" class="btn btn-outline">
                            <i class="fas fa-redo"></i> Retake Quiz
                        </a>
                    </div>
                </div>
                
                <div class="stats-cards">
                    <div class="stat-card">
                        <i class="fas fa-magic"></i>
                        <h3><?php echo count($recommendations); ?></h3>
                        <p>Personalized Recommendations</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line"></i>
                        <h3><?php echo $userInfo['quiz_count']; ?></h3>
                        <p>Quizzes Taken</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-fire"></i>
                        <h3><?php echo count($trendingHaircuts); ?></h3>
                        <p>Trending for Your Face Shape</p>
                    </div>
                </div>
                
                <?php if (!empty($recommendations)): ?>
                    <div class="section-header">
                        <h2><i class="fas fa-stars"></i> Your Personalized Recommendations</h2>
                        <a href="browse-haircuts.php?face_shape=<?php echo $userInfo['face_shape']; ?>" class="view-all-btn">
                            View All
                        </a>
                    </div>
                    
                    <div class="recommendations-grid">
                        <?php foreach ($recommendations as $rec): ?>
                            <div class="recommendation-card">
                                <div class="recommendation-image">
                                    <?php if ($rec['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($rec['image_url']); ?>" alt="<?php echo htmlspecialchars($rec['name']); ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-cut"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="recommendation-score">
                                        <?php echo max(0, min(100, (int)round($rec['final_score']))); ?>% Match
                                    </div>
                                </div>
                                
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title"><?php echo htmlspecialchars($rec['name']); ?></h3>
                                    <p class="recommendation-reason"><?php echo htmlspecialchars($rec['reason'] ?? 'Recommended for your face shape and preferences'); ?></p>
                                    
                                    <div class="recommendation-tags">
                                        <span class="recommendation-tag maintenance">
                                            <?php echo ucfirst($rec['maintenance_level']); ?> Maintenance
                                        </span>
                                        <?php if (!empty($rec['style_category'])): ?>
                                            <span class="recommendation-tag">
                                                <?php echo ucfirst($rec['style_category']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="recommendation-actions">
                                        <button class="action-btn primary" onclick="viewDetails(<?php echo (int)($rec['id'] ?? $rec['haircut_id']); ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="action-btn outline" onclick="saveHaircut(<?php echo (int)($rec['id'] ?? $rec['haircut_id']); ?>, this)">
                                            <i class="fas fa-heart"></i> Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($trendingHaircuts)): ?>
                    <div class="section-header">
                        <h2><i class="fas fa-fire"></i> Trending for <?php echo ucfirst($userInfo['face_shape']); ?> Face Shapes</h2>
                        <a href="browse-haircuts.php?face_shape=<?php echo $userInfo['face_shape']; ?>&sort=popularity" class="view-all-btn">
                            View All Trending
                        </a>
                    </div>
                    
                    <div class="recommendations-grid">
                        <?php foreach ($trendingHaircuts as $haircut): ?>
                            <div class="recommendation-card">
                                <div class="recommendation-image">
                                    <?php if ($haircut['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($haircut['image_url']); ?>" alt="<?php echo htmlspecialchars($haircut['name']); ?>">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-cut"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="recommendation-score">
                                        <?php echo (int)$haircut['trend_score']; ?> Trend
                                    </div>
                                </div>
                                
                                <div class="recommendation-content">
                                    <h3 class="recommendation-title"><?php echo htmlspecialchars($haircut['name']); ?></h3>
                                    <p class="recommendation-reason"><?php echo htmlspecialchars(substr($haircut['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="recommendation-tags">
                                        <span class="recommendation-tag maintenance">
                                            <?php echo ucfirst($haircut['maintenance_level']); ?> Maintenance
                                        </span>
                                        <?php if (!empty($haircut['style_category'])): ?>
                                            <span class="recommendation-tag">
                                                <?php echo ucfirst($haircut['style_category']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="recommendation-actions">
                                        <button class="action-btn primary" onclick="viewDetails(<?php echo $haircut['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="action-btn outline" onclick="saveHaircut(<?php echo $haircut['id']; ?>, this)">
                                            <i class="fas fa-heart"></i> Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 40px;">
                    <a href="browse-haircuts.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-search"></i> Browse All Haircuts
                    </a>
                </div>
            <?php endif; ?>

    <script>
        function viewDetails(haircutId) {
            window.location.href = `haircut-details.php?id=${haircutId}`;
        }
        
        function saveHaircut(haircutId, button) {
            const icon = button.querySelector('i');
            const text = button.querySelector('span') || button;
            
            // Show loading state
            icon.className = 'fas fa-spinner fa-spin';
            text.textContent = ' Saving...';
            button.disabled = true;
            
            // Make API call
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
                    icon.className = 'fas fa-check';
                    text.textContent = ' Saved!';
                    showToast('Haircut saved successfully!', 'success');
                } else {
                    throw new Error(data.message || 'Failed to save haircut');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                icon.className = 'fas fa-heart';
                text.textContent = ' Save';
                showToast('Error saving haircut. Please try again.', 'error');
            })
            .finally(() => {
                button.disabled = false;
                setTimeout(() => {
                    if (icon.className === 'fas fa-check') {
                        icon.className = 'fas fa-heart';
                        text.textContent = ' Save';
                    }
                }, 2000);
            });
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
</div>

<?php endLayout(); ?>
