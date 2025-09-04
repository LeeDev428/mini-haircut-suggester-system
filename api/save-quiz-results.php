<?php
require_once '../config/database.php';
require_once '../config/recommendation_engine.php';

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
$faceShapeName = sanitizeInput($data['face_shape']);
$answers = json_encode($data['answers']);

try {
    $pdo = getDatabaseConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Resolve face_shape_id from name (case-insensitive)
    $stmt = $pdo->prepare("SELECT id, name FROM face_shapes WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt->execute([$faceShapeName]);
    $faceShape = $stmt->fetch();
    if (!$faceShape) {
        throw new Exception('Unknown face shape');
    }

    // Optionally resolve other preference IDs from payload if present
    $hairTypeId = isset($data['hair_type_id']) ? (int)$data['hair_type_id'] : null;
    $hairThicknessId = isset($data['hair_thickness_id']) ? (int)$data['hair_thickness_id'] : null;
    $lifestyleId = isset($data['lifestyle_preference_id']) ? (int)$data['lifestyle_preference_id'] : null;
    $ageGroupId = isset($data['age_group_id']) ? (int)$data['age_group_id'] : null;
    $currentLength = isset($data['current_hair_length']) ? sanitizeInput($data['current_hair_length']) : null;
    $budgetRange = isset($data['budget_range']) ? sanitizeInput($data['budget_range']) : null;
    $specialOccasions = !empty($data['special_occasions']);
    $professionalEnv = !empty($data['professional_environment']);

    // Insert into user_quiz_results table per schema
    $stmt = $pdo->prepare("INSERT INTO user_quiz_results (
            user_id, face_shape_id, hair_type_id, hair_thickness_id, lifestyle_preference_id, age_group_id,
            current_hair_length, budget_range, special_occasions, professional_environment, quiz_score
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        (int)$faceShape['id'],
        $hairTypeId,
        $hairThicknessId,
        $lifestyleId,
        $ageGroupId,
        $currentLength,
        $budgetRange,
        $specialOccasions,
        $professionalEnv,
        $answers
    ]);

    $quizResultId = $pdo->lastInsertId();

    // Generate recommendations using the engine with available IDs
    $engine = new HaircutRecommendationEngine();
    // For gender, attempt from session user if exists
    $gender = $_SESSION['user']['gender'] ?? null;

    $recs = $engine->getRecommendations(
        (int)$faceShape['id'],
        $hairTypeId,
        $hairThicknessId,
        $lifestyleId,
        $ageGroupId,
        $gender,
        $currentLength,
        $budgetRange
    );
    
    // Commit transaction
    $pdo->commit();
    
    // Update session data (store face shape name for UI convenience)
    $_SESSION['user']['face_shape'] = $faceShape['name'];
    
    echo json_encode([
        'success' => true,
        'quiz_result_id' => $quizResultId,
        'face_shape' => $faceShape['name'],
        'recommendations' => array_map(function($r) {
            return [
                'haircut_id' => $r['id'] ?? $r['haircut_id'] ?? null,
                'name' => $r['name'] ?? null,
                'final_score' => $r['final_score'] ?? null,
                'reason' => $r['reason'] ?? null,
                'image_url' => $r['image_url'] ?? null,
            ];
        }, $recs),
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
