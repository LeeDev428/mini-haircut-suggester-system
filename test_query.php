<?php
// Test the SQL query logic to make sure syntax is correct
$faceShape = 'round';
$hairType = 'straight';
$maintenance = 'low';
$category = 'classic';
$search = '';
$user_id = 1;

$query = "
    SELECT DISTINCT h.*,
           (SELECT COUNT(*) FROM user_saved_haircuts ush WHERE ush.haircut_id = h.id AND ush.user_id = ?) as is_saved
    FROM haircuts h
    WHERE 1=1
";

$params = [$user_id];

// Primary filter: Face Shape -> use direct haircut compatibility columns
if ($faceShape) {
    switch(strtolower($faceShape)) {
        case 'round':
            $query .= " AND h.suitable_for_round = 1";
            break;
        case 'oval':
            $query .= " AND h.suitable_for_oval = 1";
            break;
        case 'square':
            $query .= " AND h.suitable_for_square = 1";
            break;
        case 'heart':
            $query .= " AND h.suitable_for_heart = 1";
            break;
        case 'oblong':
            $query .= " AND h.suitable_for_oblong = 1";
            break;
        case 'diamond':
            $query .= " AND h.suitable_for_oblong = 1";
            break;
    }
}

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

echo "Generated SQL Query:\n";
echo $query . "\n\n";
echo "Parameters: " . implode(', ', $params) . "\n";

// Test with database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=haircut_suggester', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $haircuts = $stmt->fetchAll();
    
    echo "\n✅ Query executed successfully!\n";
    echo "Found " . count($haircuts) . " haircuts\n";
    
} catch (Exception $e) {
    echo "\n❌ Query failed: " . $e->getMessage() . "\n";
}
?>
