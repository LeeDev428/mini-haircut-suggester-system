<?php
require_once 'database.php';

class HaircutRecommendationEngine {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Get haircut recommendations based on user preferences
     */
    public function getRecommendations($faceShapeId, $hairTypeId, $hairThicknessId, $lifestyleId, $ageGroupId, $gender, $currentLength = null, $budget = null) {
        // Get base recommendations from face shape
        $recommendations = $this->getBaseRecommendations($faceShapeId);
        
        // Filter by hair type compatibility
        $recommendations = $this->filterByHairType($recommendations, $hairTypeId);
        
        // Filter by hair thickness
        $recommendations = $this->filterByHairThickness($recommendations, $hairThicknessId);
        
        // Filter by lifestyle/maintenance level
        $recommendations = $this->filterByLifestyle($recommendations, $lifestyleId);
        
        // Filter by age appropriateness
        $recommendations = $this->filterByAge($recommendations, $ageGroupId);
        
        // Filter by gender
        $recommendations = $this->filterByGender($recommendations, $gender);
        
        // Apply current hair length considerations
        if ($currentLength) {
            $recommendations = $this->considerCurrentLength($recommendations, $currentLength);
        }
        
        // Apply budget considerations
        if ($budget) {
            $recommendations = $this->filterByBudget($recommendations, $budget);
        }
        
        // Sort by combined score
        usort($recommendations, function($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });
        
        return array_slice($recommendations, 0, 6); // Return top 6 recommendations
    }
    
    private function getBaseRecommendations($faceShapeId) {
        $query = "SELECT h.*, hr.priority_score, hr.reason, fs.name as face_shape_name
                 FROM haircut_recommendations hr
                 JOIN haircuts h ON hr.haircut_id = h.id
                 JOIN face_shapes fs ON hr.face_shape_id = fs.id
                 WHERE hr.face_shape_id = :face_shape_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':face_shape_id', $faceShapeId);
        $stmt->execute();
        
        $recommendations = $stmt->fetchAll();
        
        // Initialize scores
        foreach ($recommendations as &$rec) {
            $rec['final_score'] = $rec['priority_score'];
            $rec['score_breakdown'] = [
                'face_shape' => $rec['priority_score'],
                'hair_type' => 0,
                'thickness' => 0,
                'lifestyle' => 0,
                'age' => 0,
                'gender' => 0,
                'trend' => $rec['trend_score']
            ];
        }
        
        return $recommendations;
    }
    
    private function filterByHairType($recommendations, $hairTypeId) {
        // Get hair type info
        $query = "SELECT name FROM hair_types WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $hairTypeId);
        $stmt->execute();
        $hairType = $stmt->fetch();
        
        if (!$hairType) return $recommendations;
        
        $hairTypeName = strtolower($hairType['name']);
        
        foreach ($recommendations as &$rec) {
            $score = 0;
            $compatible = false;
            
            switch ($hairTypeName) {
                case 'straight':
                    if ($rec['suitable_for_straight']) {
                        $score = 10;
                        $compatible = true;
                    }
                    break;
                case 'wavy':
                    if ($rec['suitable_for_wavy']) {
                        $score = 10;
                        $compatible = true;
                    }
                    break;
                case 'curly':
                    if ($rec['suitable_for_curly']) {
                        $score = 10;
                        $compatible = true;
                    }
                    break;
                case 'coily':
                    if ($rec['suitable_for_coily']) {
                        $score = 10;
                        $compatible = true;
                    }
                    break;
            }
            
            if (!$compatible) {
                $score = -5; // Penalty for incompatible hair types
            }
            
            $rec['score_breakdown']['hair_type'] = $score;
            $rec['final_score'] += $score;
        }
        
        // Remove incompatible haircuts (negative scores indicate incompatibility)
        return array_filter($recommendations, function($rec) {
            return $rec['score_breakdown']['hair_type'] >= 0;
        });
    }
    
    private function filterByHairThickness($recommendations, $hairThicknessId) {
        // Get hair thickness info
        $query = "SELECT name FROM hair_thickness WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $hairThicknessId);
        $stmt->execute();
        $thickness = $stmt->fetch();
        
        if (!$thickness) return $recommendations;
        
        $thicknessName = strtolower($thickness['name']);
        
        foreach ($recommendations as &$rec) {
            $score = 0;
            
            switch ($thicknessName) {
                case 'fine':
                    if ($rec['suitable_for_thin']) {
                        $score = 8;
                    } else {
                        $score = -3; // Penalty for unsuitable thickness
                    }
                    break;
                case 'medium':
                    if ($rec['suitable_for_medium']) {
                        $score = 8;
                    } else {
                        $score = -3;
                    }
                    break;
                case 'thick':
                    if ($rec['suitable_for_thick']) {
                        $score = 8;
                    } else {
                        $score = -3;
                    }
                    break;
            }
            
            $rec['score_breakdown']['thickness'] = $score;
            $rec['final_score'] += $score;
        }
        
        return $recommendations;
    }
    
    private function filterByLifestyle($recommendations, $lifestyleId) {
        // Get lifestyle preference
        $query = "SELECT name FROM lifestyle_preferences WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $lifestyleId);
        $stmt->execute();
        $lifestyle = $stmt->fetch();
        
        if (!$lifestyle) return $recommendations;
        
        $lifestyleName = strtolower($lifestyle['name']);
        
        foreach ($recommendations as &$rec) {
            $score = 0;
            $maintenanceLevel = $rec['maintenance_level'];
            
            switch ($lifestyleName) {
                case 'low maintenance':
                    if ($maintenanceLevel == 'low') {
                        $score = 10;
                    } elseif ($maintenanceLevel == 'medium') {
                        $score = 5;
                    } else {
                        $score = -5; // High maintenance doesn't fit low maintenance lifestyle
                    }
                    break;
                case 'medium maintenance':
                    if ($maintenanceLevel == 'medium') {
                        $score = 10;
                    } elseif ($maintenanceLevel == 'low' || $maintenanceLevel == 'high') {
                        $score = 7;
                    }
                    break;
                case 'high maintenance':
                    if ($maintenanceLevel == 'high') {
                        $score = 10;
                    } elseif ($maintenanceLevel == 'medium') {
                        $score = 8;
                    } else {
                        $score = 5; // Low maintenance might be too simple
                    }
                    break;
            }
            
            $rec['score_breakdown']['lifestyle'] = $score;
            $rec['final_score'] += $score;
        }
        
        return $recommendations;
    }
    
    private function filterByAge($recommendations, $ageGroupId) {
        // Get age group
        $query = "SELECT min_age, max_age FROM age_groups WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $ageGroupId);
        $stmt->execute();
        $ageGroup = $stmt->fetch();
        
        if (!$ageGroup) return $recommendations;
        
        $userMinAge = $ageGroup['min_age'];
        $userMaxAge = $ageGroup['max_age'];
        
        foreach ($recommendations as &$rec) {
            $score = 0;
            $haircutMinAge = $rec['min_age'];
            $haircutMaxAge = $rec['max_age'];
            
            // Check if user age range overlaps with haircut age range
            if ($userMaxAge >= $haircutMinAge && $userMinAge <= $haircutMaxAge) {
                // Age appropriate
                $score = 8;
                
                // Bonus for perfect age match
                if ($userMinAge >= $haircutMinAge && $userMaxAge <= $haircutMaxAge) {
                    $score = 10;
                }
            } else {
                // Age inappropriate
                $score = -8;
            }
            
            $rec['score_breakdown']['age'] = $score;
            $rec['final_score'] += $score;
        }
        
        return $recommendations;
    }
    
    private function filterByGender($recommendations, $gender) {
        foreach ($recommendations as &$rec) {
            $score = 0;
            
            if ($gender == 'male' && $rec['suitable_for_male']) {
                $score = 8;
            } elseif ($gender == 'female' && $rec['suitable_for_female']) {
                $score = 8;
            } elseif ($gender == 'other') {
                // For non-binary, consider both male and female suitable cuts
                if ($rec['suitable_for_male'] || $rec['suitable_for_female']) {
                    $score = 8;
                }
            } else {
                $score = -10; // Strong penalty for gender incompatible cuts
            }
            
            $rec['score_breakdown']['gender'] = $score;
            $rec['final_score'] += $score;
        }
        
        return array_filter($recommendations, function($rec) {
            return $rec['score_breakdown']['gender'] >= 0;
        });
    }
    
    private function considerCurrentLength($recommendations, $currentLength) {
        // Add logic for current hair length considerations
        foreach ($recommendations as &$rec) {
            $score = 0;
            $haircutName = strtolower($rec['name']);
            
            // Basic length compatibility logic
            switch ($currentLength) {
                case 'very_short':
                    if (strpos($haircutName, 'pixie') !== false || strpos($haircutName, 'buzz') !== false) {
                        $score = 5; // Easy transition
                    } elseif (strpos($haircutName, 'bob') !== false) {
                        $score = 3; // Requires growth
                    } else {
                        $score = 1; // Significant growth needed
                    }
                    break;
                case 'short':
                    if (strpos($haircutName, 'bob') !== false || strpos($haircutName, 'pixie') !== false) {
                        $score = 5;
                    } else {
                        $score = 3;
                    }
                    break;
                case 'medium':
                    if (strpos($haircutName, 'lob') !== false || strpos($haircutName, 'bob') !== false) {
                        $score = 5;
                    } else {
                        $score = 4;
                    }
                    break;
                case 'long':
                case 'very_long':
                    if (strpos($haircutName, 'long') !== false || strpos($haircutName, 'layer') !== false) {
                        $score = 5;
                    } else {
                        $score = 2; // Requires cutting
                    }
                    break;
            }
            
            $rec['final_score'] += $score;
        }
        
        return $recommendations;
    }
    
    private function filterByBudget($recommendations, $budget) {
        foreach ($recommendations as &$rec) {
            $score = 0;
            $professionalRequired = $rec['professional_required'];
            $maintenanceLevel = $rec['maintenance_level'];
            
            switch ($budget) {
                case 'low':
                    if (!$professionalRequired && $maintenanceLevel == 'low') {
                        $score = 8;
                    } elseif (!$professionalRequired && $maintenanceLevel == 'medium') {
                        $score = 5;
                    } else {
                        $score = -5; // Too expensive
                    }
                    break;
                case 'medium':
                    if ($maintenanceLevel != 'high') {
                        $score = 7;
                    } else {
                        $score = 3;
                    }
                    break;
                case 'high':
                    $score = 8; // Can afford anything
                    if ($professionalRequired) {
                        $score = 10; // Bonus for professional cuts
                    }
                    break;
            }
            
            $rec['final_score'] += $score;
        }
        
        return $recommendations;
    }
    
    /**
     * Get detailed explanation for a recommendation
     */
    public function getRecommendationExplanation($haircut, $userPreferences) {
        $explanation = [];
        
        // Face shape compatibility
        $explanation[] = "This style is recommended for " . $userPreferences['face_shape'] . " face shapes because " . $haircut['reason'];
        
        // Hair type compatibility
        $hairType = $userPreferences['hair_type'];
        $explanation[] = "Perfect for " . $hairType . " hair texture";
        
        // Lifestyle match
        $lifestyle = $userPreferences['lifestyle'];
        $explanation[] = "Matches your " . strtolower($lifestyle) . " lifestyle with " . $haircut['maintenance_level'] . " maintenance requirements";
        
        // Styling info
        if ($haircut['styling_tips']) {
            $explanation[] = "Styling tip: " . $haircut['styling_tips'];
        }
        
        return $explanation;
    }
}
?>
