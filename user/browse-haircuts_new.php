<?php
require_once 'includes/layout.php';

// Get filter parameters
$faceShape = isset($_GET['face_shape']) ? sanitizeInput($_GET['face_shape']) : '';
$hairType = isset($_GET['hair_type']) ? sanitizeInput($_GET['hair_type']) : '';
$maintenance = isset($_GET['maintenance']) ? sanitizeInput($_GET['maintenance']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Get haircuts with filters
$pdo = getDatabaseConnection();

$query = "
    SELECT DISTINCT h.*
    FROM haircuts h
";

$params = [];

// Primary filter: Face Shape mapping via haircut_recommendations
if ($faceShape) {
    $query .= "\n    INNER JOIN haircut_recommendations hr ON hr.haircut_id = h.id\n    INNER JOIN face_shapes fs ON fs.id = hr.face_shape_id AND LOWER(fs.name) = LOWER(?)\n";
    $params[] = $faceShape;
}

// Begin WHERE for additional optional filters
$query .= "\n    WHERE 1=1\n";

if ($hairType) {
    switch($hairType) {
        case 'straight':
            $query .= " AND h.suitable_for_straight = 1";
            break;
        case 'wavy':
            $query .= " AND h.suitable_for_wavy = 1";
            break;
        case 'curly':
            $query .= " AND h.suitable_for_curly = 1";
            break;
        case 'coily':
            $query .= " AND h.suitable_for_coily = 1";
            break;
    }
}

if ($maintenance) {
    $query .= " AND h.maintenance_level = ?";
    $params[] = $maintenance;
}

if ($category) {
    $query .= " AND h.style_category = ?";
    $params[] = $category;
}

if ($search) {
    $query .= " AND (h.name LIKE ? OR h.description LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY h.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$haircuts = $stmt->fetchAll();

// Get filter options
try {
    $faceShapes = $pdo->query("SELECT name FROM face_shapes ORDER BY name")?->fetchAll(PDO::FETCH_COLUMN);
    if (!$faceShapes) { $faceShapes = ['oval', 'round', 'square', 'heart', 'diamond', 'oblong']; }
} catch (Throwable $e) {
    $faceShapes = ['oval', 'round', 'square', 'heart', 'diamond', 'oblong'];
}
$hairTypes = ['straight', 'wavy', 'curly', 'coily'];
$maintenanceLevels = ['low', 'medium', 'high'];
$categories = ['classic', 'trendy', 'professional', 'casual', 'formal'];

startLayout('Browse Haircuts', 'browse-haircuts');
?>

<style>
    .browse-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .filters-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .filters-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .filters-header h3 {
        margin: 0;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .clear-filters {
        color: var(--primary-color);
        text-decoration: none;
        font-size: 14px;
        padding: 8px 15px;
        border-radius: 8px;
        border: 1px solid var(--primary-color);
        transition: all 0.2s ease;
    }
    
    .clear-filters:hover {
        background: var(--primary-color);
        color: white;
        text-decoration: none;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-group label {
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .filter-group select,
    .filter-group input {
        padding: 10px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }
    
    .filter-group select:focus,
    .filter-group input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
    }
    
    .filter-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: var(--primary-gradient);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3);
    }
    
    .btn-outline {
        background: white;
        border: 1px solid #e5e7eb;
        color: #374151;
    }
    
    .btn-outline:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .results-count {
        color: #6b7280;
        font-size: 16px;
    }
    
    .view-toggle {
        display: flex;
        gap: 10px;
    }
    
    .view-btn {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        background: white;
        color: #6b7280;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .view-btn.active,
    .view-btn:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .haircuts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
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
        transform: translateY(-8px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }
    
    .haircut-image {
        position: relative;
        height: 240px;
        overflow: hidden;
        background: #f8f9fa;
    }
    
    .haircut-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .haircut-card:hover .haircut-image img {
        transform: scale(1.05);
    }
    
    .image-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f1f3f4 0%, #e8eaed 100%);
        color: #9aa0a6;
        font-size: 48px;
    }
    
    .haircut-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .haircut-card:hover .haircut-overlay {
        opacity: 1;
    }
    
    .overlay-actions {
        display: flex;
        gap: 15px;
    }
    
    .overlay-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        color: #374151;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    
    .overlay-btn:hover {
        background: white;
        transform: scale(1.1);
    }
    
    .save-btn.saved {
        background: #ef4444;
        color: white;
    }
    
    .haircut-content {
        padding: 20px;
    }
    
    .haircut-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .haircut-header h3 {
        margin: 0;
        color: #1f2937;
        font-size: 18px;
        font-weight: 700;
    }
    
    .haircut-price {
        background: var(--primary-color);
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .haircut-description {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 15px;
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
        font-size: 12px;
        font-weight: 500;
    }
    
    .haircut-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 12px;
    }
    
    .no-results {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .no-results i {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
    }
    
    .no-results h3 {
        color: #374151;
        margin-bottom: 10px;
    }
    
    .no-results p {
        color: #6b7280;
        margin-bottom: 20px;
    }
    
    @media (max-width: 1024px) {
        .haircuts-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .browse-container {
            padding: 15px;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .haircuts-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .results-header {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }
        
        .filter-actions {
            justify-content: stretch;
        }
        
        .filter-actions .btn {
            flex: 1;
            justify-content: center;
        }
    }
</style>

<div class="browse-container">
    <?php renderPageHeader('Browse Haircuts', 'Discover the perfect hairstyle for you'); ?>
    
    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-header">
            <h3>
                <i class="fas fa-filter"></i>
                Filter Haircuts
            </h3>
            <a href="browse-haircuts_new.php" class="clear-filters">
                <i class="fas fa-times"></i>
                Clear All Filters
            </a>
        </div>
        
    <form method="GET" action="browse-haircuts_new.php">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="face_shape">Face Shape</label>
                    <select name="face_shape" id="face_shape">
                        <option value="">All Face Shapes</option>
                        <?php foreach ($faceShapes as $shape): ?>
                            <option value="<?php echo $shape; ?>" <?php echo strtolower($faceShape) === strtolower($shape) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($shape); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="hair_type">Hair Type</label>
                    <select name="hair_type" id="hair_type">
                        <option value="">All Hair Types</option>
                        <?php foreach ($hairTypes as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $hairType === $type ? 'selected' : ''; ?>>
                                <?php echo ucfirst($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="maintenance">Maintenance Level</label>
                    <select name="maintenance" id="maintenance">
                        <option value="">All Levels</option>
                        <?php foreach ($maintenanceLevels as $level): ?>
                            <option value="<?php echo $level; ?>" <?php echo $maintenance === $level ? 'selected' : ''; ?>>
                                <?php echo ucfirst($level); ?> Maintenance
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category">Style Category</label>
                    <select name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" placeholder="Search haircuts..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Apply Filters
                </button>
                <a href="browse-haircuts_new.php" class="btn btn-outline">
                    <i class="fas fa-refresh"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Results Header -->
    <div class="results-header">
        <div class="results-count">
            <strong><?php echo count($haircuts); ?></strong> haircuts found
        </div>
        <div class="view-toggle">
            <button class="view-btn active" onclick="setGridView('grid', this)">
                <i class="fas fa-th"></i>
            </button>
            <button class="view-btn" onclick="setGridView('list', this)">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>
    
    <!-- Haircuts Grid -->
    <?php if (empty($haircuts)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>No haircuts found</h3>
            <p>Try adjusting your filters or search terms to find more results.</p>
            <a href="browse-haircuts_new.php" class="btn btn-primary">
                <i class="fas fa-refresh"></i> Show All Haircuts
            </a>
        </div>
    <?php else: ?>
        <div class="haircuts-grid" id="haircutsGrid">
            <?php foreach ($haircuts as $haircut): ?>
                <div class="haircut-card" data-haircut-id="<?php echo $haircut['id']; ?>">
                    <div class="haircut-image">
                        <?php if ($haircut['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($haircut['image_url']); ?>" alt="<?php echo htmlspecialchars($haircut['name']); ?>">
                        <?php else: ?>
                            <div class="image-placeholder">
                                <i class="fas fa-cut"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="haircut-overlay">
                            <div class="overlay-actions">
                                <button class="overlay-btn save-btn" onclick="toggleSave(<?php echo $haircut['id']; ?>, this)" title="Save Style">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <button class="overlay-btn" onclick="viewDetails(<?php echo $haircut['id']; ?>)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="overlay-btn" onclick="bookAppointment()" title="Book Appointment">
                                    <i class="fas fa-calendar"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="haircut-content">
                        <div class="haircut-header">
                            <h3><?php echo htmlspecialchars($haircut['name']); ?></h3>
                            <?php if (isset($haircut['price'])): ?>
                                <div class="haircut-price">$<?php echo number_format($haircut['price'], 2); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="haircut-description">
                            <?php echo htmlspecialchars(substr($haircut['description'], 0, 120)) . (strlen($haircut['description']) > 120 ? '...' : ''); ?>
                        </div>
                        
                        <div class="haircut-tags">
                            <?php if ($haircut['maintenance_level']): ?>
                                <span class="tag"><?php echo ucfirst($haircut['maintenance_level']); ?> Maintenance</span>
                            <?php endif; ?>
                            <?php if ($haircut['style_category']): ?>
                                <span class="tag"><?php echo ucfirst($haircut['style_category']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="haircut-actions">
                            <button class="btn btn-primary btn-sm" onclick="viewDetails(<?php echo $haircut['id']; ?>)">
                                <i class="fas fa-eye"></i>
                                View Details
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="toggleSave(<?php echo $haircut['id']; ?>, this)">
                                <i class="fas fa-heart"></i>
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function setGridView(view, btn) {
        const grid = document.getElementById('haircutsGrid');
        const buttons = document.querySelectorAll('.view-btn');
        
        buttons.forEach(btn => btn.classList.remove('active'));
        if (btn) { btn.classList.add('active'); }
        
        if (view === 'list') {
            grid.style.gridTemplateColumns = '1fr';
        } else {
            grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
        }
    }
    
    function toggleSave(haircutId, button) {
        fetch('../api/save-haircut.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                haircut_id: haircutId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.toggle('saved');
                const icon = button.querySelector('i');
                if (button.classList.contains('saved')) {
                    icon.className = 'fas fa-heart';
                    showToast('Haircut saved!', 'success');
                } else {
                    icon.className = 'far fa-heart';
                    showToast('Haircut removed from saved', 'info');
                }
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Network error occurred', 'error');
        });
    }
    
    function viewDetails(haircutId) {
        window.location.href = `haircut-details.php?id=${haircutId}`;
    }
    
    function bookAppointment() {
        window.location.href = 'booking.php';
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
    
    // Auto-submit form when filters change
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
</script>

<?php endLayout(); ?>
