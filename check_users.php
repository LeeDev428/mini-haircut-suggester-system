<?php
require_once 'config/database.php';
$pdo = getDatabaseConnection();
$stmt = $pdo->query('SELECT id, username, email, is_admin FROM users');
echo "Existing users:\n";
while ($row = $stmt->fetch()) {
    echo 'ID: ' . $row['id'] . ', Username: ' . $row['username'] . ', Email: ' . $row['email'] . ', Admin: ' . ($row['is_admin'] ? 'Yes' : 'No') . "\n";
}

// Update existing admin user
$pdo->prepare("UPDATE users SET is_admin = 1 WHERE username = 'admin' OR email = 'admin@haircut.com'")->execute();
echo "\nAdmin privileges updated for existing admin user.\n";
?>
