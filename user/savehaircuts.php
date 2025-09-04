<?php
require_once 'includes/layout.php';

$pdo = getDatabaseConnection();
$user_id = $_SESSION['user']['id'];

// Handle AJAX requests for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'save':
                $haircut_id = intval($_POST['haircut_id']);
                
                // Check if already saved
                $check_stmt = $pdo->prepare("SELECT id FROM user_saved_haircuts WHERE user_id = ? AND haircut_id = ?");
                $check_stmt->execute([$user_id, $haircut_id]);
                
                if ($check_stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Haircut already saved']);
                } else {
                    $save_stmt = $pdo->prepare("INSERT INTO user_saved_haircuts (user_id, haircut_id, saved_at) VALUES (?, ?, NOW())");
                    $save_stmt->execute([$user_id, $haircut_id]);
                    echo json_encode(['success' => true, 'message' => 'Haircut saved successfully']);
                }
                break;
                
            case 'unsave':
                $haircut_id = intval($_POST['haircut_id']);
                $delete_stmt = $pdo->prepare("DELETE FROM user_saved_haircuts WHERE user_id = ? AND haircut_id = ?");
                $delete_stmt->execute([$user_id, $haircut_id]);
                echo json_encode(['success' => true, 'message' => 'Haircut removed from saved']);
                break;
                
            case 'update_notes':
                $haircut_id = intval($_POST['haircut_id']);
                $notes = $_POST['notes'];
                $update_stmt = $pdo->prepare("UPDATE user_saved_haircuts SET notes = ? WHERE user_id = ? AND haircut_id = ?");
                $update_stmt->execute([$notes, $user_id, $haircut_id]);
                echo json_encode(['success' => true, 'message' => 'Notes updated successfully']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Get saved haircuts with details
$saved_query = "SELECT h.*, ush.saved_at, ush.notes, ush.id as saved_id
                FROM user_saved_haircuts ush 
                JOIN haircuts h ON ush.haircut_id = h.id 
                WHERE ush.user_id = ? 
                ORDER BY ush.saved_at DESC";
$saved_stmt = $pdo->prepare($saved_query);
$saved_stmt->execute([$user_id]);
$saved_haircuts = $saved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if trending_score column exists
$column_check = $pdo->query("SHOW COLUMNS FROM haircuts LIKE 'trending_score'");
$has_trending_score = $column_check->rowCount() > 0;

// Get all haircuts for browsing/adding
$order_clause = $has_trending_score ? "ORDER BY COALESCE(h.trending_score, 50) DESC" : "ORDER BY h.id DESC";
$all_haircuts_query = "SELECT h.*, 
                       (SELECT COUNT(*) FROM user_saved_haircuts WHERE haircut_id = h.id AND user_id = ?) as is_saved
                       FROM haircuts h 
                       $order_clause";
$all_haircuts_stmt = $pdo->prepare($all_haircuts_query);
$all_haircuts_stmt->execute([$user_id]);
$all_haircuts = $all_haircuts_stmt->fetchAll(PDO::FETCH_ASSOC);

startLayout('Saved Haircuts', 'saved-haircuts');
?>

<style>
    .saved-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .page-title {
        font-size: 2.5em;
        font-weight: bold;
    background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 10px;
    }
    
    .page-subtitle {
        color: #6b7280;
        font-size: 1.1em;
    }
    
    .tabs {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .tab {
        padding: 15px 25px;
        background: none;
        border: none;
        font-size: 16px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .tab.active {
    color: var(--primary-color);
    }
    
    .tab.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 3px;
    background: var(--primary-gradient);
        border-radius: 2px;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .haircuts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }
    
    .haircut-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        position: relative;
    }
    
    .haircut-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }
    
    .haircut-image {
        height: 200px;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 48px;
        position: relative;
    }
    
    .haircut-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .save-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        color: #6b7280;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .save-btn:hover {
        background: white;
        transform: scale(1.1);
    }
    
    .save-btn.saved {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .haircut-info {
        padding: 20px;
    }
    
    .haircut-name {
        font-size: 18px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 8px;
    }
    
    .haircut-description {
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 15px;
        line-height: 1.5;
    }
    
    .haircut-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .tag {
        background: #f3f4f6;
        color: #374151;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .tag.category {
    background: var(--primary-gradient);
        color: white;
    }
    
    .saved-date {
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 10px;
    }
    
    .notes-section {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f1f3f4;
    }
    
    .notes-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 13px;
        resize: vertical;
        min-height: 60px;
    }
    
    .notes-input:focus {
        outline: none;
    border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-primary {
    background: var(--primary-gradient);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    .empty-state h3 {
        margin-bottom: 10px;
        color: #374151;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
        display: none;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
</style>

<div class="saved-container">
    <div class="page-header">
        <h1 class="page-title">💖 Saved Haircuts</h1>
        <p class="page-subtitle">Manage your favorite hairstyles and add personal notes</p>
    </div>
    
    <div class="alert alert-success" id="successAlert"></div>
    <div class="alert alert-error" id="errorAlert"></div>
    
    <div class="tabs">
    <button class="tab active" onclick="switchTab('saved', this)">
            <i class="fas fa-heart"></i> My Saved (<?php echo count($saved_haircuts); ?>)
        </button>
       
    </div>
    
    <!-- Saved Haircuts Tab -->
    <div id="saved-tab" class="tab-content active">
        <?php if (empty($saved_haircuts)): ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>No Saved Haircuts Yet</h3>
                <p>Start saving your favorite hairstyles from the browse section!</p>
                <a href="browse-haircuts.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Browse Haircuts
                </a>
            </div>
        <?php else: ?>
            <div class="haircuts-grid">
                <?php foreach ($saved_haircuts as $haircut): ?>
                <div class="haircut-card">
                    <div class="haircut-image">
                        <?php if ($haircut['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($haircut['image_url']); ?>" alt="<?php echo htmlspecialchars($haircut['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-cut"></i>
                        <?php endif; ?>
                        <button class="save-btn saved" onclick="unsaveHaircut(<?php echo $haircut['id']; ?>)" title="Remove from saved">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    <div class="haircut-info">
                        <div class="saved-date">
                            <i class="fas fa-calendar"></i> Saved on <?php echo date('M j, Y', strtotime($haircut['saved_at'])); ?>
                        </div>
                        <div class="haircut-name"><?php echo htmlspecialchars($haircut['name']); ?></div>
                        <div class="haircut-description">
                            <?php echo htmlspecialchars(substr($haircut['description'], 0, 100)) . (strlen($haircut['description']) > 100 ? '...' : ''); ?>
                        </div>
                        
                        <div class="haircut-tags">
                            <?php if ($haircut['style_category']): ?>
                                <span class="tag category"><?php echo ucfirst($haircut['style_category']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($haircut['length_category'])): ?>
                                <span class="tag"><?php echo ucfirst($haircut['length_category']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($haircut['maintenance_level'])): ?>
                                <span class="tag"><?php echo ucfirst($haircut['maintenance_level']); ?> Maintenance</span>
                            <?php endif; ?>
                        </div>
                        

                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                          
                            <button class="btn btn-danger btn-sm" onclick="unsaveHaircut(<?php echo $haircut['id']; ?>)">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Browse Haircuts Tab -->
    <div id="browse-tab" class="tab-content">
        <div class="haircuts-grid">
            <?php foreach ($all_haircuts as $haircut): ?>
            <div class="haircut-card">
                <div class="haircut-image">
                    <?php if ($haircut['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($haircut['image_url']); ?>" alt="<?php echo htmlspecialchars($haircut['name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-cut"></i>
                    <?php endif; ?>
                    <button class="save-btn <?php echo $haircut['is_saved'] ? 'saved' : ''; ?>" 
                            onclick="<?php echo $haircut['is_saved'] ? 'unsaveHaircut' : 'saveHaircut'; ?>(<?php echo $haircut['id']; ?>)"
                            title="<?php echo $haircut['is_saved'] ? 'Remove from saved' : 'Save haircut'; ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                <div class="haircut-info">
                    <div class="haircut-name"><?php echo htmlspecialchars($haircut['name']); ?></div>
                    <div class="haircut-description">
                        <?php echo htmlspecialchars(substr($haircut['description'], 0, 100)) . (strlen($haircut['description']) > 100 ? '...' : ''); ?>
                    </div>
                    
                    <div class="haircut-tags">
                        <?php if ($haircut['style_category']): ?>
                            <span class="tag category"><?php echo ucfirst($haircut['style_category']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($haircut['length_category'])): ?>
                            <span class="tag"><?php echo ucfirst($haircut['length_category']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($haircut['maintenance_level'])): ?>
                            <span class="tag"><?php echo ucfirst($haircut['maintenance_level']); ?> Maintenance</span>
                        <?php endif; ?>
                        <?php if (($haircut['trending_score'] ?? 50) > 70): ?>
                            <span class="tag" style="background: #fef3c7; color: #92400e;">🔥 Trending</span>
                        <?php endif; ?>
                    </div>
                    
                   
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tabName, btn) {
    // Remove active class from all tabs and contents
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to clicked tab and corresponding content
    if (btn) { btn.classList.add('active'); }
    document.getElementById(tabName + '-tab').classList.add('active');
}

function saveHaircut(haircutId) {
    fetch('savehaircuts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=save&haircut_id=${haircutId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Update button appearance
            const button = document.querySelector(`button[onclick*="${haircutId}"]`);
            button.classList.add('saved');
            button.setAttribute('onclick', `unsaveHaircut(${haircutId})`);
            button.setAttribute('title', 'Remove from saved');
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error saving haircut');
    });
}

function unsaveHaircut(haircutId) {
    if (confirm('Are you sure you want to remove this haircut from your saved list?')) {
        fetch('savehaircuts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=unsave&haircut_id=${haircutId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                // Reload page to update the lists
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'Error removing haircut');
        });
    }
}

function updateNotes(haircutId, notes) {
    fetch('savehaircuts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_notes&haircut_id=${haircutId}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Notes updated successfully');
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'Error updating notes');
    });
}

function showAlert(type, message) {
    const alert = document.getElementById(type + 'Alert');
    alert.textContent = message;
    alert.style.display = 'block';
    
    setTimeout(() => {
        alert.style.display = 'none';
    }, 3000);
}
</script>

<?php endLayout(); ?>
