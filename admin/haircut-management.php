<?php
require_once 'includes/layout.php';

$pdo = getDatabaseConnection();

// Function to handle file upload
function handleImageUpload($inputName, $existingImage = null) {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return $existingImage; // Return existing image if no new upload
    }
    
    $file = $_FILES[$inputName];
    $uploadDir = '../uploads/haircuts/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.');
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('File size too large. Please upload images smaller than 5MB.');
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('haircut_') . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Delete old image if exists and is not the same
        if ($existingImage && $existingImage !== $filepath && file_exists($existingImage)) {
            unlink($existingImage);
        }
        return $filepath;
    } else {
        throw new Exception('Failed to upload image.');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create') {
                $image_url = handleImageUpload('image_upload');
                
                $stmt = $pdo->prepare("
                    INSERT INTO haircuts (name, description, image_url, suitable_for_straight, 
                    suitable_for_wavy, suitable_for_curly, suitable_for_coily, 
                    suitable_for_round, suitable_for_oval, suitable_for_square, 
                    suitable_for_heart, suitable_for_oblong, maintenance_level, 
                     style_category, price_range_min, price_range_max, 
                    trending_score) 
                    VALUES (?, ?,  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $image_url,
                    isset($_POST['suitable_for_straight']) ? 1 : 0,
                    isset($_POST['suitable_for_wavy']) ? 1 : 0,
                    isset($_POST['suitable_for_curly']) ? 1 : 0,
                    isset($_POST['suitable_for_coily']) ? 1 : 0,
                    isset($_POST['suitable_for_round']) ? 1 : 0,
                    isset($_POST['suitable_for_oval']) ? 1 : 0,
                    isset($_POST['suitable_for_square']) ? 1 : 0,
                    isset($_POST['suitable_for_heart']) ? 1 : 0,
                    isset($_POST['suitable_for_oblong']) ? 1 : 0,
                    $_POST['maintenance_level'],
               
                    $_POST['style_category'],
                    floatval($_POST['price_range_min']),
                    floatval($_POST['price_range_max']),
                    intval($_POST['trending_score'])
                ]);
                
                $message = "Haircut created successfully!";
                $messageType = "success";
            } elseif ($_POST['action'] === 'update') {
                $existing_haircut = null;
                if (isset($_POST['haircut_id'])) {
                    $stmt = $pdo->prepare("SELECT image_url FROM haircuts WHERE id = ?");
                    $stmt->execute([intval($_POST['haircut_id'])]);
                    $existing_haircut = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                $image_url = handleImageUpload('image_upload', $existing_haircut['image_url'] ?? null);
                
                $stmt = $pdo->prepare("
                    UPDATE haircuts SET 
                    name = ?, description = ?, image_url = ?, 
                    suitable_for_straight = ?, suitable_for_wavy = ?, suitable_for_curly = ?, suitable_for_coily = ?,
                    suitable_for_round = ?, suitable_for_oval = ?, suitable_for_square = ?, suitable_for_heart = ?, suitable_for_oblong = ?,
                    maintenance_level = ?,  style_category = ?, 
                    price_range_min = ?, price_range_max = ?, trending_score = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $image_url,
                    isset($_POST['suitable_for_straight']) ? 1 : 0,
                    isset($_POST['suitable_for_wavy']) ? 1 : 0,
                    isset($_POST['suitable_for_curly']) ? 1 : 0,
                    isset($_POST['suitable_for_coily']) ? 1 : 0,
                    isset($_POST['suitable_for_round']) ? 1 : 0,
                    isset($_POST['suitable_for_oval']) ? 1 : 0,
                    isset($_POST['suitable_for_square']) ? 1 : 0,
                    isset($_POST['suitable_for_heart']) ? 1 : 0,
                    isset($_POST['suitable_for_oblong']) ? 1 : 0,
                    $_POST['maintenance_level'],
                
                    $_POST['style_category'],
                    floatval($_POST['price_range_min']),
                    floatval($_POST['price_range_max']),
                    intval($_POST['trending_score']),
                    intval($_POST['haircut_id'])
                ]);
                
                $message = "Haircut updated successfully!";
                $messageType = "success";
            } elseif ($_POST['action'] === 'delete') {
                // Get image path before deleting
                $stmt = $pdo->prepare("SELECT image_url FROM haircuts WHERE id = ?");
                $stmt->execute([intval($_POST['haircut_id'])]);
                $haircut = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("DELETE FROM haircuts WHERE id = ?");
                $stmt->execute([intval($_POST['haircut_id'])]);
                
                // Delete image file if exists
                if ($haircut['image_url'] && file_exists($haircut['image_url'])) {
                    unlink($haircut['image_url']);
                }
                
                $message = "Haircut deleted successfully!";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get all haircuts for listing
$haircuts_stmt = $pdo->query("SELECT * FROM haircuts ORDER BY created_at DESC");
$haircuts = $haircuts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get haircut for editing
$editing_haircut = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM haircuts WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editing_haircut = $stmt->fetch(PDO::FETCH_ASSOC);
}

startAdminLayout('Haircut Management', 'haircuts');
?>

<style>
    .management-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .form-section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
    }
    
    .form-group input[type="file"] {
        padding: 0;
        border: 2px dashed #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
        transition: border-color 0.2s ease;
        cursor: pointer;
    }
    
    .form-group input[type="file"]:hover {
        border-color: #667eea;
        background: #f0f4ff;
    }
    
    .form-group input[type="file"]:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .current-image {
        margin-top: 10px;
        padding: 10px;
        background: #f9fafb;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }
    
    .current-image img {
        max-width: 100px;
        max-height: 100px;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-group textarea {
        height: 100px;
        resize: vertical;
    }
    
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .checkbox-item input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    
    .btn {
        padding: 12px 24px;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .btn-outline {
        background: white;
        border: 2px solid #e5e7eb;
        color: #374151;
    }
    
    .haircuts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .haircut-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease;
    }
    
    .haircut-card:hover {
        transform: translateY(-5px);
    }
    
    .haircut-image {
        height: 200px;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 48px;
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
    
    .haircut-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
        font-size: 12px;
    }
    
    .detail-item {
        background: #f9fafb;
        padding: 8px;
        border-radius: 6px;
    }
    
    .detail-label {
        font-weight: 600;
        color: #374151;
    }
    
    .detail-value {
        color: #6b7280;
    }
    
    .haircut-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 12px;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
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
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .section-title {
        margin: 0;
        color: #111827;
        font-size: 24px;
        font-weight: 600;
    }
</style>

<div class="management-container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 2.5em; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 10px;">Haircut Management</h1>
        <p style="color: #6b7280; font-size: 1.1em;">Add, edit, and manage all haircuts</p>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Haircut Form -->
    <div class="form-section">
        <div class="section-header">
            <h2 class="section-title">
                <?php echo $editing_haircut ? 'Edit Haircut' : 'Add New Haircut'; ?>
            </h2>
            <?php if ($editing_haircut): ?>
                <a href="haircut-management.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel Edit
                </a>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?php echo $editing_haircut ? 'update' : 'create'; ?>">
            <?php if ($editing_haircut): ?>
                <input type="hidden" name="haircut_id" value="<?php echo $editing_haircut['id']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Haircut Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo $editing_haircut ? htmlspecialchars($editing_haircut['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="image_upload">Haircut Image</label>
                    <input type="file" id="image_upload" name="image_upload" accept="image/*" 
                           style="padding: 12px; border: 2px dashed #e5e7eb; border-radius: 8px; background: #f9fafb;">
                    <?php if ($editing_haircut && $editing_haircut['image_url']): ?>
                        <div style="margin-top: 10px;">
                            <small style="color: #6b7280;">Current image:</small><br>
                            <img src="<?php echo htmlspecialchars($editing_haircut['image_url']); ?>" 
                                 style="max-width: 100px; max-height: 100px; border-radius: 6px; border: 1px solid #e5e7eb;">
                        </div>
                    <?php endif; ?>
                    <small style="color: #6b7280; display: block; margin-top: 5px;">
                        Supported formats: JPG, PNG, GIF, WebP (max 5MB)
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="maintenance_level">Maintenance Level *</label>
                    <select id="maintenance_level" name="maintenance_level" required>
                        <option value="">Select Level</option>
                        <option value="low" <?php echo ($editing_haircut && $editing_haircut['maintenance_level'] === 'low') ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo ($editing_haircut && $editing_haircut['maintenance_level'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo ($editing_haircut && $editing_haircut['maintenance_level'] === 'high') ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                
              
                <div class="form-group">
                    <label for="style_category">Style Category</label>
                    <select id="style_category" name="style_category">
                        <option value="">Select Style</option>
                        <option value="classic" <?php echo ($editing_haircut && $editing_haircut['style_category'] === 'classic') ? 'selected' : ''; ?>>Classic</option>
                        <option value="modern" <?php echo ($editing_haircut && $editing_haircut['style_category'] === 'modern') ? 'selected' : ''; ?>>Modern</option>
                        <option value="trendy" <?php echo ($editing_haircut && $editing_haircut['style_category'] === 'trendy') ? 'selected' : ''; ?>>Trendy</option>
                        <option value="casual" <?php echo ($editing_haircut && $editing_haircut['style_category'] === 'casual') ? 'selected' : ''; ?>>Casual</option>
                        <option value="formal" <?php echo ($editing_haircut && $editing_haircut['style_category'] === 'formal') ? 'selected' : ''; ?>>Formal</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="trending_score">Trending Score (0-100)</label>
                    <input type="number" id="trending_score" name="trending_score" min="0" max="100" 
                           value="<?php echo $editing_haircut ? $editing_haircut['trending_score'] : '50'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="price_range_min">Min Price (₱)</label>
                    <input type="number" id="price_range_min" name="price_range_min" step="0.01" min="0" 
                           value="<?php echo $editing_haircut ? $editing_haircut['price_range_min'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="price_range_max">Max Price (₱)</label>
                    <input type="number" id="price_range_max" name="price_range_max" step="0.01" min="0" 
                           value="<?php echo $editing_haircut ? $editing_haircut['price_range_max'] : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo $editing_haircut ? htmlspecialchars($editing_haircut['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Hair Type Compatibility</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_straight" name="suitable_for_straight" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_straight']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_straight">Straight Hair</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_wavy" name="suitable_for_wavy" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_wavy']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_wavy">Wavy Hair</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_curly" name="suitable_for_curly" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_curly']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_curly">Curly Hair</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_coily" name="suitable_for_coily" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_coily']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_coily">Coily Hair</label>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Face Shape Compatibility</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_round" name="suitable_for_round" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_round']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_round">Round Face</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_oval" name="suitable_for_oval" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_oval']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_oval">Oval Face</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_square" name="suitable_for_square" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_square']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_square">Square Face</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_heart" name="suitable_for_heart" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_heart']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_heart">Heart Face</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="suitable_for_oblong" name="suitable_for_oblong" 
                               <?php echo ($editing_haircut && $editing_haircut['suitable_for_oblong']) ? 'checked' : ''; ?>>
                        <label for="suitable_for_oblong">Oblong Face</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                <?php echo $editing_haircut ? 'Update Haircut' : 'Create Haircut'; ?>
            </button>
        </form>
    </div>
    
    <!-- Haircuts List -->
    <div class="form-section">
        <div class="section-header">
            <h2 class="section-title">All Haircuts (<?php echo count($haircuts); ?>)</h2>
        </div>
        
        <div class="haircuts-grid">
            <?php foreach ($haircuts as $haircut): ?>
            <div class="haircut-card">
                <div class="haircut-image">
                    <?php if ($haircut['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($haircut['image_url']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-cut"></i>
                    <?php endif; ?>
                </div>
                <div class="haircut-info">
                    <div class="haircut-name"><?php echo htmlspecialchars($haircut['name']); ?></div>
                    <div class="haircut-description">
                        <?php echo htmlspecialchars(substr($haircut['description'], 0, 100)) . (strlen($haircut['description']) > 100 ? '...' : ''); ?>
                    </div>
                    
                    <div class="haircut-details">
                     
                        <div class="detail-item">
                            <div class="detail-label">Maintenance</div>
                            <div class="detail-value"><?php echo ucfirst($haircut['maintenance_level'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Style</div>
                            <div class="detail-value"><?php echo ucfirst($haircut['style_category'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Trending</div>
                            <div class="detail-value"><?php echo ($haircut['trending_score'] ?? 50); ?>/100</div>
                        </div>
                    </div>
                    
                    <div class="haircut-actions">
                        <a href="?edit=<?php echo $haircut['id']; ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this haircut?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="haircut_id" value="<?php echo $haircut['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php endAdminLayout(); ?>
