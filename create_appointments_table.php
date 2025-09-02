<?php
require_once 'config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Check if appointments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'appointments'");
    if ($stmt->rowCount() == 0) {
        echo "Creating appointments table...\n";
        
        $sql = "CREATE TABLE appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            service_type ENUM('consultation', 'haircut', 'styling', 'coloring', 'treatment') NOT NULL,
            preferred_stylist VARCHAR(100),
            notes TEXT,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            phone VARCHAR(20),
            emergency_contact VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_datetime (appointment_date, appointment_time)
        )";
        
        $pdo->exec($sql);
        echo "Appointments table created successfully.\n";
    } else {
        echo "Appointments table already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
