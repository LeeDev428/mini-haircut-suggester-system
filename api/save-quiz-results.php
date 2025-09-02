<?php
require_once '../config/database.php';

// Enable CORS for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['face_shape']) || !isset($data['answers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data provided']);
    exit();
}

$userId = $_SESSION['user']['id'];
$faceShape = sanitizeInput($data['face_shape']);
$answers = json_encode($data['answers']);

try {
    $pdo = getDatabaseConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert quiz result
    $stmt = $pdo->prepare("
        INSERT INTO quiz_results (user_id, face_shape, answers, taken_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $faceShape, $answers]);
    
    $quizResultId = $pdo->lastInsertId();
    
    // Update user's face shape in users table
    $stmt = $pdo->prepare("UPDATE users SET face_shape = ? WHERE id = ?");
    $stmt->execute([$faceShape, $userId]);
    
    // Get recommendations based on face shape
    $recommendations = getRecommendations($userId, $faceShape);
    
    // Save recommendations to database
    $stmt = $pdo->prepare("
        INSERT INTO recommendations (user_id, haircut_id, score, reason, created_at) 
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE score = VALUES(score), reason = VALUES(reason), created_at = NOW()
    ");
    
    foreach ($recommendations as $rec) {
        $stmt->execute([
            $userId,
            $rec['haircut_id'],
            $rec['score'],
            $rec['reason']
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Update session data
    $_SESSION['user']['face_shape'] = $faceShape;
    
    echo json_encode([
        'success' => true,
        'quiz_result_id' => $quizResultId,
        'face_shape' => $faceShape,
        'recommendations_count' => count($recommendations),
        'message' => 'Quiz results saved successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollback();
    
    error_log("Error saving quiz results: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save quiz results'
    ]);
}

function getRecommendations($userId, $faceShape) {
    $pdo = getDatabaseConnection();
    
    // Get user preferences
    $stmt = $pdo->prepare("SELECT hair_type, lifestyle, age, gender FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Get haircuts suitable for this face shape
    $stmt = $pdo->prepare("
        SELECT h.*, GROUP_CONCAT(DISTINCT fs.face_shape) as suitable_faces
        FROM haircuts h
        LEFT JOIN haircut_face_shapes hfs ON h.id = hfs.haircut_id
        LEFT JOIN face_shapes fs ON hfs.face_shape_id = fs.id
        WHERE h.is_active = 1
        AND (fs.name = ? OR fs.name IS NULL)
        GROUP BY h.id
        ORDER BY h.popularity_score DESC
        LIMIT 20
    ");
    $stmt->execute([$faceShape]);
    $haircuts = $stmt->fetchAll();
    
    $recommendations = [];
    
    foreach ($haircuts as $haircut) {
        $score = calculateRecommendationScore($haircut, $user, $faceShape);
        $reason = generateRecommendationReason($haircut, $user, $faceShape);
        
        if ($score > 0) {
            $recommendations[] = [
                'haircut_id' => $haircut['id'],
                'score' => $score,
                'reason' => $reason,
                'haircut' => $haircut
            ];
        }
    }
    
    // Sort by score
    usort($recommendations, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return array_slice($recommendations, 0, 10); // Return top 10
}

function calculateRecommendationScore($haircut, $user, $faceShape) {
    $score = 50; // Base score
    
    // Face shape compatibility (highest weight)
    if (strpos($haircut['suitable_faces'], $faceShape) !== false) {
        $score += 30;
    }
    
    // Hair type compatibility
    if ($user['hair_type'] && $haircut['suitable_hair_types']) {
        $suitableTypes = explode(',', $haircut['suitable_hair_types']);
        if (in_array($user['hair_type'], $suitableTypes)) {
            $score += 15;
        }
    }
    
    // Lifestyle compatibility
    if ($user['lifestyle'] && $haircut['maintenance_level']) {
        $maintenanceMap = [
            'low' => ['busy', 'active', 'casual'],
            'medium' => ['professional', 'social', 'balanced'],
            'high' => ['fashion-forward', 'glamorous', 'experimental']
        ];
        
        if (isset($maintenanceMap[$haircut['maintenance_level']]) && 
            in_array($user['lifestyle'], $maintenanceMap[$haircut['maintenance_level']])) {
            $score += 10;
        }
    }
    
    // Age appropriateness
    if ($user['age']) {
        $age = intval($user['age']);
        if ($age >= 18 && $age <= 25 && $haircut['style_category'] === 'trendy') {
            $score += 8;
        } elseif ($age >= 26 && $age <= 40 && $haircut['style_category'] === 'professional') {
            $score += 8;
        } elseif ($age >= 41 && $haircut['style_category'] === 'classic') {
            $score += 8;
        }
    }
    
    // Gender compatibility
    if ($user['gender'] && $haircut['gender'] !== 'unisex') {
        if ($user['gender'] === $haircut['gender']) {
            $score += 5;
        } else {
            $score -= 10;
        }
    }
    
    // Popularity bonus
    if ($haircut['popularity_score'] > 80) {
        $score += 5;
    }
    
    return max(0, min(100, $score));
}

function generateRecommendationReason($haircut, $user, $faceShape) {
    $reasons = [];
    
    // Face shape reason
    if (strpos($haircut['suitable_faces'], $faceShape) !== false) {
        $faceShapeReasons = [
            'oval' => 'complements your balanced facial proportions',
            'round' => 'creates length and elongates your face',
            'square' => 'softens your angular features',
            'heart' => 'balances your forehead and jawline',
            'diamond' => 'highlights your beautiful cheekbones',
            'oblong' => 'adds width and breaks up face length'
        ];
        
        if (isset($faceShapeReasons[$faceShape])) {
            $reasons[] = $faceShapeReasons[$faceShape];
        }
    }
    
    // Hair type reason
    if ($user['hair_type'] && $haircut['suitable_hair_types']) {
        $suitableTypes = explode(',', $haircut['suitable_hair_types']);
        if (in_array($user['hair_type'], $suitableTypes)) {
            $reasons[] = "works well with your " . $user['hair_type'] . " hair";
        }
    }
    
    // Lifestyle reason
    if ($user['lifestyle'] && $haircut['maintenance_level']) {
        $lifestyleReasons = [
            'low' => 'fits your busy lifestyle with easy maintenance',
            'medium' => 'perfect balance of style and practicality',
            'high' => 'makes a bold statement for your fashion-forward lifestyle'
        ];
        
        if (isset($lifestyleReasons[$haircut['maintenance_level']])) {
            $reasons[] = $lifestyleReasons[$haircut['maintenance_level']];
        }
    }
    
    if (empty($reasons)) {
        $reasons[] = 'selected based on your unique features';
    }
    
    return "This style " . implode(" and ", $reasons) . ".";
}
?>
