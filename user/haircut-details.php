<?php
require_once 'includes/layout.php';

$haircutId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$haircutId) {
    header('Location: browse-haircuts.php');
    exit;
}

$pdo = getDatabaseConnection();

// Get haircut details
$stmt = $pdo->prepare("
    SELECT h.*, fs.name as shape_name, fs.description as face_shape_description,
           (SELECT COUNT(*) FROM user_saved_haircuts WHERE haircut_id = h.id) as total_saves,
           (SELECT COUNT(*) FROM user_haircut_history WHERE haircut_id = h.id) as total_recommendations,
           (SELECT id FROM user_saved_haircuts WHERE user_id = ? AND haircut_id = h.id) as is_saved
    FROM haircuts h
    LEFT JOIN face_shapes fs ON h.suitable_face_shapes LIKE CONCAT('%', fs.id, '%')
    WHERE h.id = ?
");
$stmt->execute([$_SESSION['user']['id'], $haircutId]);
$haircut = $stmt->fetch();

if (!$haircut) {
    header('Location: browse-haircuts.php');
    exit;
}

// Get related haircuts
$stmt = $pdo->prepare("
    SELECT h.id, h.name, h.image_url, h.category, h.length
    FROM haircuts h
    WHERE h.id != ? AND (h.category = ? OR h.length = ?)
    ORDER BY RAND()
    LIMIT 6
");
$stmt->execute([$haircutId, $haircut['category'], $haircut['length']]);
$relatedHaircuts = $stmt->fetchAll();

// Get suitable face shapes
$faceShapeIds = explode(',', $haircut['suitable_face_shapes']);
$faceShapeIds = array_filter(array_map('trim', $faceShapeIds));

$faceShapes = [];
if (!empty($faceShapeIds)) {
    $placeholders = implode(',', array_fill(0, count($faceShapeIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM face_shapes WHERE id IN ($placeholders)");
    $stmt->execute($faceShapeIds);
    $faceShapes = $stmt->fetchAll();
}

$pageTitle = $haircut['name'];
$currentPage = 'browse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - HairCut Suggester</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .details-main {
            margin-left: 280px;
            padding: 30px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .details-header {
            margin-bottom: 30px;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            color: var(--gray-medium);
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .details-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .haircut-showcase {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
            text-align: center;
        }
        
        .showcase-image {
            width: 100%;
            max-width: 400px;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-medium);
        }
        
        .image-placeholder {
            width: 100%;
            max-width: 400px;
            height: 400px;
            background: var(--primary-gradient);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            margin: 0 auto 20px;
            box-shadow: var(--shadow-medium);
        }
        
        .showcase-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .haircut-info {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
        }
        
        .info-header {
            margin-bottom: 25px;
        }
        
        .haircut-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        .haircut-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .meta-tag {
            padding: 6px 12px;
            background: var(--gray-light);
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-dark);
            text-transform: uppercase;
        }
        
        .meta-tag.category {
            background: var(--primary-color);
            color: white;
        }
        
        .haircut-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: var(--gray-light);
            border-radius: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--gray-medium);
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .description {
            line-height: 1.6;
            color: var(--gray-dark);
            margin-bottom: 20px;
        }
        
        .face-shapes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .face-shape-card {
            background: var(--gray-light);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .face-shape-card.suitable {
            background: #ecfdf5;
            border-color: #10b981;
        }
        
        .face-shape-card h4 {
            margin: 0 0 8px 0;
            color: var(--dark-color);
            font-size: 14px;
            font-weight: 600;
        }
        
        .face-shape-card p {
            margin: 0;
            font-size: 12px;
            color: var(--gray-medium);
        }
        
        .compatibility-note {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #10b981;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .related-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-light);
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .related-card {
            background: var(--gray-light);
            border-radius: 15px;
            overflow: hidden;
            transition: var(--transition);
            text-decoration: none;
            color: inherit;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .related-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .related-image-placeholder {
            width: 100%;
            height: 150px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        
        .related-info {
            padding: 15px;
        }
        
        .related-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .related-meta {
            font-size: 12px;
            color: var(--gray-medium);
        }
        
        @media (max-width: 1024px) {
            .details-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .details-main {
                margin-left: 0;
                padding: 20px;
            }
            
            .haircut-title {
                font-size: 2rem;
            }
            
            .haircut-stats {
                grid-template-columns: 1fr;
            }
            
            .face-shapes-grid {
                grid-template-columns: 1fr;
            }
            
            .related-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="details-main">
            <div class="details-header">
                <div class="breadcrumb">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="browse-haircuts.php">Browse Haircuts</a>
                    <i class="fas fa-chevron-right"></i>
                    <span><?php echo htmlspecialchars($haircut['name']); ?></span>
                </div>
            </div>
            
            <div class="details-content">
                <div class="haircut-showcase">
                    <?php if ($haircut['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($haircut['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($haircut['name']); ?>" 
                             class="showcase-image">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <i class="fas fa-cut"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="showcase-actions">
                        <button class="btn <?php echo $haircut['is_saved'] ? 'btn-primary' : 'btn-outline'; ?>" 
                                onclick="toggleSave(<?php echo $haircut['id']; ?>)" 
                                id="saveBtn">
                            <i class="fas fa-heart"></i>
                            <?php echo $haircut['is_saved'] ? 'Saved' : 'Save Style'; ?>
                        </button>
                        
                        <button class="btn btn-outline" onclick="shareHaircut()">
                            <i class="fas fa-share"></i> Share
                        </button>
                    </div>
                </div>
                
                <div class="haircut-info">
                    <div class="info-header">
                        <h1 class="haircut-title"><?php echo htmlspecialchars($haircut['name']); ?></h1>
                        
                        <div class="haircut-meta">
                            <span class="meta-tag category"><?php echo ucfirst($haircut['category']); ?></span>
                            <span class="meta-tag"><?php echo ucfirst($haircut['length']); ?> Length</span>
                            <span class="meta-tag"><?php echo ucfirst($haircut['maintenance_level']); ?> Maintenance</span>
                            <?php if ($haircut['gender']): ?>
                                <span class="meta-tag"><?php echo ucfirst($haircut['gender']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="haircut-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $haircut['total_saves']; ?></span>
                                <div class="stat-label">Saves</div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $haircut['total_recommendations']; ?></span>
                                <div class="stat-label">Recommendations</div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo rand(40, 95); ?>%</span>
                                <div class="stat-label">Satisfaction</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </div>
                        <div class="description">
                            <?php echo nl2br(htmlspecialchars($haircut['description'])); ?>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <div class="section-title">
                            <i class="fas fa-user-check"></i> Suitable Face Shapes
                        </div>
                        
                        <?php if (!empty($faceShapes)): ?>
                            <div class="face-shapes-grid">
                                <?php foreach ($faceShapes as $shape): ?>
                                    <div class="face-shape-card suitable">
                                        <h4><?php echo ucfirst($shape['shape_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($shape['description']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($_SESSION['user']['face_shape'] && in_array($_SESSION['user']['face_shape'], array_column($faceShapes, 'shape_name'))): ?>
                                <div class="compatibility-note">
                                    <i class="fas fa-check-circle"></i>
                                    Perfect! This haircut is compatible with your <?php echo ucfirst($_SESSION['user']['face_shape']); ?> face shape.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="description">This haircut is suitable for most face shapes and can be adapted to fit your features.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($haircut['styling_tips']): ?>
                        <div class="info-section">
                            <div class="section-title">
                                <i class="fas fa-lightbulb"></i> Styling Tips
                            </div>
                            <div class="description">
                                <?php echo nl2br(htmlspecialchars($haircut['styling_tips'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($relatedHaircuts)): ?>
                <div class="related-section">
                    <div class="section-title">
                        <i class="fas fa-magic"></i> You Might Also Like
                    </div>
                    
                    <div class="related-grid">
                        <?php foreach ($relatedHaircuts as $related): ?>
                            <a href="haircut-details.php?id=<?php echo $related['id']; ?>" class="related-card">
                                <?php if ($related['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>" 
                                         class="related-image">
                                <?php else: ?>
                                    <div class="related-image-placeholder">
                                        <i class="fas fa-cut"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="related-info">
                                    <div class="related-name"><?php echo htmlspecialchars($related['name']); ?></div>
                                    <div class="related-meta">
                                        <?php echo ucfirst($related['category']); ?> • <?php echo ucfirst($related['length']); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function toggleSave(haircutId) {
            const btn = document.getElementById('saveBtn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;
            
            fetch('../api/save-haircut.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `haircut_id=${haircutId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'saved') {
                        btn.innerHTML = '<i class="fas fa-heart"></i> Saved';
                        btn.className = 'btn btn-primary';
                        showToast('Haircut saved to your collection!', 'success');
                    } else {
                        btn.innerHTML = '<i class="fas fa-heart"></i> Save Style';
                        btn.className = 'btn btn-outline';
                        showToast('Haircut removed from your collection', 'info');
                    }
                } else {
                    btn.innerHTML = originalText;
                    showToast(data.message || 'Error occurred', 'error');
                }
            })
            .catch(error => {
                btn.innerHTML = originalText;
                showToast('Network error occurred', 'error');
            })
            .finally(() => {
                btn.disabled = false;
            });
        }
        
        function shareHaircut() {
            const url = window.location.href;
            const title = '<?php echo addslashes($haircut['name']); ?>';
            
            if (navigator.share) {
                navigator.share({
                    title: title + ' - HairCut Suggester',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Link copied to clipboard!', 'success');
                });
            }
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
