<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=haircut_suggester', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Current haircuts table structure:\n";
    $result = $pdo->query('DESCRIBE haircuts');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
