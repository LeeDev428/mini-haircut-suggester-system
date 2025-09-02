<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireUser();

$userId = $_SESSION['user']['id'];

// Handle JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$haircutId = null;
if ($data && isset($data['haircut_id'])) {
    $haircutId = filter_var($data['haircut_id'], FILTER_VALIDATE_INT);
} else {
    // Fallback for form data
    $haircutId = filter_input(INPUT_POST, 'haircut_id', FILTER_VALIDATE_INT);
}

if (!$haircutId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid haircut ID']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    
    // Check if haircut exists
    $stmt = $pdo->prepare("SELECT id FROM haircuts WHERE id = ?");
    $stmt->execute([$haircutId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Haircut not found']);
        exit;
    }
    
    // Check if already saved
    $stmt = $pdo->prepare("SELECT id FROM user_saved_haircuts WHERE user_id = ? AND haircut_id = ?");
    $stmt->execute([$userId, $haircutId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove from saved
        $stmt = $pdo->prepare("DELETE FROM user_saved_haircuts WHERE user_id = ? AND haircut_id = ?");
        $stmt->execute([$userId, $haircutId]);
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Haircut removed from saved styles'
        ]);
    } else {
        // Add to saved
        $stmt = $pdo->prepare("INSERT INTO user_saved_haircuts (user_id, haircut_id) VALUES (?, ?)");
        $stmt->execute([$userId, $haircutId]);
        
        echo json_encode([
            'success' => true,
            'action' => 'saved',
            'message' => 'Haircut saved to your collection'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
