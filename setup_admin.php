<?php
require_once 'config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Check if is_admin column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($stmt->rowCount() == 0) {
        echo "Adding is_admin column to users table...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT 0");
        echo "Column added successfully.\n";
    } else {
        echo "is_admin column already exists.\n";
    }
    
    // Create admin user if doesn't exist
    $adminEmail = 'admin@haircut.com';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    
    if (!$stmt->fetch()) {
        echo "Creating admin user...\n";
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, first_name, last_name, email, password, is_admin, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        
        $stmt->execute(['admin', 'Admin', 'User', $adminEmail, $hashedPassword]);
        echo "Admin user created:\n";
        echo "Email: admin@haircut.com\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin user already exists.\n";
        // Make sure existing admin user has admin privileges
        $pdo->prepare("UPDATE users SET is_admin = 1 WHERE email = ?")->execute([$adminEmail]);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
