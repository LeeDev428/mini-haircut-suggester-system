<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=haircut_suggester', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding missing price columns to haircuts table...\n";
    
    // Add price_range_min column
    $pdo->exec("ALTER TABLE haircuts ADD COLUMN price_range_min DECIMAL(10,2) DEFAULT 0.00 AFTER trending_score");
    echo "✓ Added price_range_min column\n";
    
    // Add price_range_max column  
    $pdo->exec("ALTER TABLE haircuts ADD COLUMN price_range_max DECIMAL(10,2) DEFAULT 0.00 AFTER price_range_min");
    echo "✓ Added price_range_max column\n";
    
    // Check if length_category exists and remove it
    $result = $pdo->query("SHOW COLUMNS FROM haircuts LIKE 'length_category'");
    if ($result->rowCount() > 0) {
        $pdo->exec("ALTER TABLE haircuts DROP COLUMN length_category");
        echo "✓ Removed length_category column\n";
    } else {
        echo "✓ length_category column doesn't exist (already removed)\n";
    }
    
    echo "\nUpdated table structure:\n";
    $result = $pdo->query('DESCRIBE haircuts');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['Field'], ['price_range_min', 'price_range_max'])) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ") ← NEW\n";
        } else {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
    
    echo "\n✅ All columns updated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
