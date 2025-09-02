<?php
require_once 'includes/layout.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $appointmentId = (int)$input['appointment_id'];
    $status = $input['status'];
    $cancellationReason = $input['cancellation_reason'] ?? null;
    
    // Validate status
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit();
    }
    
    try {
        $pdo = getDatabaseConnection();
        
        if ($status === 'cancelled' && $cancellationReason) {
            // Update with cancellation reason
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, cancellation_reason = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $cancellationReason, $appointmentId]);
        } else {
            // Regular status update
            $stmt = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$status, $appointmentId]);
        }
        
        echo json_encode(['success' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
