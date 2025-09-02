<?php
require_once 'includes/layout.php';

$userId = $_SESSION['user']['id'];
$pdo = getDatabaseConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $dateOfBirth = sanitizeInput($_POST['date_of_birth'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $hairType = sanitizeInput($_POST['hair_type'] ?? '');
    $lifestyle = sanitizeInput($_POST['lifestyle'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already taken by another user";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    date_of_birth = ?, 
                    gender = ?, 
                    hair_type = ?, 
                    lifestyle = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $firstName, 
                $lastName, 
                $email, 
                $phone, 
                $dateOfBirth ?: null, 
                $gender ?: null, 
                $hairType ?: null, 
                $lifestyle ?: null, 
                $userId
            ]);
            
            // Update session data
            $_SESSION['user']['first_name'] = $firstName;
            $_SESSION['user']['last_name'] = $lastName;
            $_SESSION['user']['email'] = $email;
            
            $success = "Profile updated successfully!";
        } catch (Exception $e) {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../login.php');
    exit();
}

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM user_saved_haircuts WHERE user_id = ?) as saved_haircuts,
        (SELECT COUNT(*) FROM user_quiz_results WHERE user_id = ?) as quizzes_taken,
        (SELECT COUNT(*) FROM appointments WHERE user_id = ?) as total_appointments,
        (SELECT COUNT(*) FROM user_haircut_history WHERE user_id = ?) as recommendations
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$stats = $stmt->fetch();

startLayout('Profile Settings', 'profile');
?>

<style>
    .profile-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
        margin-top: 20px;
    }
    
    .profile-sidebar {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        height: fit-content;
        position: sticky;
        top: 20px;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: bold;
        margin: 0 auto 20px;
        position: relative;
    }
    
    .avatar-edit {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 36px;
        height: 36px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .profile-name {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .profile-name h2 {
        margin: 0 0 5px 0;
        color: #2d3748;
    }
    
    .profile-name p {
        margin: 0;
        color: #718096;
    }
    
    .profile-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 25px;
    }
    
    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f7fafc;
        border-radius: 10px;
    }
    
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 12px;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .profile-form {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .form-section {
        margin-bottom: 40px;
    }
    
    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .section-title h3 {
        margin: 0;
        color: #2d3748;
    }
    
    .section-title i {
        color: #667eea;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
    }
    
    .required {
        color: #ef4444;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .help-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 5px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 14px 28px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
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
    
    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .profile-stats {
            grid-template-columns: repeat(4, 1fr);
        }
    }
</style>

<div class="profile-container">
    <?php renderPageHeader('Profile Settings', 'Manage your personal information and preferences'); ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-grid">
        <!-- Profile Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                <div class="avatar-edit">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            
            <div class="profile-name">
                <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            
            <div class="profile-stats">
              
                <div class="stat-item">
                    <div class="stat-number">Sep 2025</div>
                    <div class="stat-label">Member Since</div>
                </div>
            </div>
        </div>
        
        <!-- Profile Form -->
        <div class="profile-form">
            <form method="POST" id="profileForm">
                <!-- Personal Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i>
                        <h3>Personal Information</h3>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   placeholder="+1 (555) 123-4567">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" 
                                   value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                            <div class="help-text">This helps us provide age-appropriate recommendations</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                <option value="prefer_not_to_say" <?php echo ($user['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Hair & Style Preferences -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-cut"></i>
                        <h3>Hair & Style Preferences</h3>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="hair_type">Hair Type</label>
                            <select id="hair_type" name="hair_type">
                                <option value="">Select Hair Type</option>
                                <option value="straight" <?php echo ($user['hair_type'] ?? '') === 'straight' ? 'selected' : ''; ?>>Straight</option>
                                <option value="wavy" <?php echo ($user['hair_type'] ?? '') === 'wavy' ? 'selected' : ''; ?>>Wavy</option>
                                <option value="curly" <?php echo ($user['hair_type'] ?? '') === 'curly' ? 'selected' : ''; ?>>Curly</option>
                                <option value="coily" <?php echo ($user['hair_type'] ?? '') === 'coily' ? 'selected' : ''; ?>>Coily</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="lifestyle">Lifestyle</label>
                            <select id="lifestyle" name="lifestyle">
                                <option value="">Select Lifestyle</option>
                                <option value="professional" <?php echo ($user['lifestyle'] ?? '') === 'professional' ? 'selected' : ''; ?>>Professional</option>
                                <option value="casual" <?php echo ($user['lifestyle'] ?? '') === 'casual' ? 'selected' : ''; ?>>Casual</option>
                                <option value="active" <?php echo ($user['lifestyle'] ?? '') === 'active' ? 'selected' : ''; ?>>Active/Sports</option>
                                <option value="creative" <?php echo ($user['lifestyle'] ?? '') === 'creative' ? 'selected' : ''; ?>>Creative/Artistic</option>
                                <option value="student" <?php echo ($user['lifestyle'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (!firstName || !lastName || !email) {
            e.preventDefault();
            showToast('Please fill in all required fields', 'error');
            return;
        }
        
        if (!isValidEmail(email)) {
            e.preventDefault();
            showToast('Please enter a valid email address', 'error');
            return;
        }
    });
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
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

<?php endLayout(); ?>
