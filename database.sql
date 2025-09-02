-- Haircut Suggester Database Schema
CREATE DATABASE IF NOT EXISTS haircut_suggester;
USE haircut_suggester;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    hair_type ENUM('straight', 'wavy', 'curly', 'coily'),
    lifestyle ENUM('busy', 'professional', 'casual', 'fashion-forward', 'low-maintenance'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Appointments table
CREATE TABLE appointments (
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
);

-- Stylists table (for appointment booking)
CREATE TABLE stylists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialties TEXT,
    experience_years INT,
    rating DECIMAL(3,2) DEFAULT 0.00,
    is_available BOOLEAN DEFAULT TRUE,
    working_hours JSON,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Face shapes table
CREATE TABLE face_shapes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    characteristics TEXT,
    image_url VARCHAR(255),
    tips TEXT
);

-- Hair types table
CREATE TABLE hair_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    care_tips TEXT
);

-- Hair thickness table
CREATE TABLE hair_thickness (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Lifestyle preferences table
CREATE TABLE lifestyle_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    time_commitment VARCHAR(100)
);

-- Age groups table
CREATE TABLE age_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    min_age INT,
    max_age INT
);

-- Haircuts table
CREATE TABLE haircuts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    maintenance_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    suitable_for_curly BOOLEAN DEFAULT TRUE,
    suitable_for_straight BOOLEAN DEFAULT TRUE,
    suitable_for_wavy BOOLEAN DEFAULT TRUE,
    suitable_for_coily BOOLEAN DEFAULT TRUE,
    suitable_for_thin BOOLEAN DEFAULT TRUE,
    suitable_for_thick BOOLEAN DEFAULT TRUE,
    suitable_for_medium BOOLEAN DEFAULT TRUE,
    min_age INT DEFAULT 16,
    max_age INT DEFAULT 80,
    suitable_for_male BOOLEAN DEFAULT TRUE,
    suitable_for_female BOOLEAN DEFAULT TRUE,
    styling_tips TEXT,
    styling_time VARCHAR(50),
    difficulty_level ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    trend_score INT DEFAULT 5,
    professional_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Haircut recommendations (many-to-many relationship)
CREATE TABLE haircut_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    face_shape_id INT,
    haircut_id INT,
    priority_score INT DEFAULT 5,
    reason TEXT,
    FOREIGN KEY (face_shape_id) REFERENCES face_shapes(id) ON DELETE CASCADE,
    FOREIGN KEY (haircut_id) REFERENCES haircuts(id) ON DELETE CASCADE
);

-- User quiz results
CREATE TABLE user_quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    face_shape_id INT,
    hair_type_id INT,
    hair_thickness_id INT,
    lifestyle_preference_id INT,
    age_group_id INT,
    current_hair_length ENUM('very_short', 'short', 'medium', 'long', 'very_long'),
    budget_range ENUM('low', 'medium', 'high'),
    special_occasions BOOLEAN DEFAULT FALSE,
    professional_environment BOOLEAN DEFAULT FALSE,
    quiz_score JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (face_shape_id) REFERENCES face_shapes(id),
    FOREIGN KEY (hair_type_id) REFERENCES hair_types(id),
    FOREIGN KEY (hair_thickness_id) REFERENCES hair_thickness(id),
    FOREIGN KEY (lifestyle_preference_id) REFERENCES lifestyle_preferences(id),
    FOREIGN KEY (age_group_id) REFERENCES age_groups(id)
);

-- User saved haircuts
CREATE TABLE user_saved_haircuts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    haircut_id INT,
    notes TEXT,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (haircut_id) REFERENCES haircuts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_haircut (user_id, haircut_id)
);

-- User haircut history (what they tried)
CREATE TABLE user_haircut_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    haircut_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    date_tried DATE,
    would_recommend BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (haircut_id) REFERENCES haircuts(id) ON DELETE CASCADE
);

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role, first_name, last_name) 
VALUES ('admin', 'admin@haircutsuggester.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User');

-- Insert face shapes
INSERT INTO face_shapes (name, description, characteristics, image_url, tips) VALUES
('Oval', 'The ideal face shape with balanced proportions', 'Length is about 1.5 times the width, forehead slightly wider than chin, soft jawline', 'assets/images/face-shapes/oval.jpg', 'You can wear almost any hairstyle! Experiment with different lengths and styles.'),
('Round', 'Soft, curved lines with full cheeks', 'Width and length are nearly equal, soft curved jawline, full cheeks', 'assets/images/face-shapes/round.jpg', 'Add height and volume on top, avoid styles that add width to sides.'),
('Square', 'Strong, angular features', 'Width and length similar, strong angular jawline, broad forehead', 'assets/images/face-shapes/square.jpg', 'Soften angular features with layers and curves, avoid blunt cuts.'),
('Heart', 'Wide forehead, narrow chin', 'Forehead is widest part, chin is pointed or narrow, high cheekbones', 'assets/images/face-shapes/heart.jpg', 'Balance narrow chin with width at jaw level, soft layers work well.'),
('Oblong', 'Longer than it is wide', 'Length is noticeably longer than width, high forehead, long chin', 'assets/images/face-shapes/oblong.jpg', 'Add width to sides, avoid very long styles that elongate further.'),
('Diamond', 'Narrow forehead and chin, wide cheekbones', 'Widest at cheekbones, narrow forehead and chin, angular features', 'assets/images/face-shapes/diamond.jpg', 'Add width at forehead and chin, avoid styles that emphasize cheekbones.');

-- Insert hair types
INSERT INTO hair_types (name, description, care_tips) VALUES
('Straight', 'Hair that lies flat against the scalp with minimal natural texture', 'Use lightweight products, avoid over-washing, regular trims prevent split ends'),
('Wavy', 'Hair with natural S-shaped waves and body', 'Use curl-enhancing products, scrunch while drying, avoid brushing when dry'),
('Curly', 'Hair with defined spirals and curls', 'Use leave-in conditioners, diffuse when drying, avoid sulfates and silicones'),
('Coily', 'Tightly coiled or kinky hair with lots of texture', 'Deep condition regularly, use oil-based products, gentle detangling when wet');

-- Insert hair thickness
INSERT INTO hair_thickness (name, description) VALUES
('Fine', 'Individual strands are thin, hair may lack volume'),
('Medium', 'Average strand thickness, most common hair type'),
('Thick', 'Individual strands are wide, hair has natural volume');

-- Insert lifestyle preferences
INSERT INTO lifestyle_preferences (name, description, time_commitment) VALUES
('Low Maintenance', 'Quick and easy styling, minimal time investment', '5-10 minutes daily'),
('Medium Maintenance', 'Some styling required, moderate time investment', '15-30 minutes daily'),
('High Maintenance', 'Requires regular styling and professional upkeep', '30+ minutes daily');

-- Insert age groups
INSERT INTO age_groups (name, min_age, max_age) VALUES
('Teen', 13, 19),
('Young Adult', 20, 29),
('Adult', 30, 49),
('Mature Adult', 50, 65),
('Senior', 66, 100);

-- Insert haircuts
INSERT INTO haircuts (name, description, image_url, maintenance_level, suitable_for_curly, suitable_for_straight, suitable_for_wavy, suitable_for_coily, suitable_for_thin, suitable_for_thick, suitable_for_medium, min_age, max_age, suitable_for_male, suitable_for_female, styling_tips, styling_time, difficulty_level, trend_score, professional_required) VALUES
('Classic Bob', 'Timeless bob cut that hits at chin length', 'assets/images/haircuts/classic-bob.jpg', 'medium', FALSE, TRUE, TRUE, FALSE, FALSE, TRUE, TRUE, 16, 65, FALSE, TRUE, 'Use a round brush when blow-drying for smooth finish. Apply heat protectant before styling.', '15-20 minutes', 'medium', 8, FALSE),
('Long Layers', 'Layered cut that adds movement and volume', 'assets/images/haircuts/long-layers.jpg', 'low', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 16, 80, FALSE, TRUE, 'Scrunch with mousse for natural texture. Air dry for effortless look.', '10-15 minutes', 'easy', 9, FALSE),
('Pixie Cut', 'Short, chic cut that frames the face', 'assets/images/haircuts/pixie.jpg', 'medium', FALSE, TRUE, TRUE, FALSE, TRUE, FALSE, TRUE, 18, 60, FALSE, TRUE, 'Use texturizing paste for definition. Style with fingers for tousled look.', '5-10 minutes', 'easy', 7, TRUE),
('Long Bob (Lob)', 'Longer version of bob, hits at collarbone', 'assets/images/haircuts/lob.jpg', 'low', FALSE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, 16, 70, FALSE, TRUE, 'Air dry for natural look or blow dry with round brush for polish.', '10-20 minutes', 'easy', 9, FALSE),
('Side-Swept Bangs', 'Angled bangs that sweep to one side', 'assets/images/haircuts/side-bangs.jpg', 'medium', FALSE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, 16, 65, FALSE, TRUE, 'Use a small round brush to sweep bangs. Light hairspray to hold.', '10-15 minutes', 'medium', 8, FALSE),
('Shag Cut', 'Layered cut with feathered texture throughout', 'assets/images/haircuts/shag.jpg', 'low', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 16, 50, TRUE, TRUE, 'Scrunch with sea salt spray for texture. Tousle with fingers.', '10-15 minutes', 'easy', 9, FALSE),
('Blunt Cut', 'Straight across cut with no layers', 'assets/images/haircuts/blunt.jpg', 'low', FALSE, TRUE, FALSE, FALSE, FALSE, TRUE, TRUE, 16, 80, FALSE, TRUE, 'Blow dry straight with paddle brush. Use smoothing serum.', '15-20 minutes', 'medium', 6, FALSE),
('Curly Bob', 'Bob cut specifically designed for curly hair', 'assets/images/haircuts/curly-bob.jpg', 'medium', TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, 16, 65, FALSE, TRUE, 'Use curl cream and diffuse dry. Scrunch for definition.', '15-25 minutes', 'medium', 8, TRUE),
('Buzz Cut', 'Very short cut, uniform length all over', 'assets/images/haircuts/buzz-cut.jpg', 'low', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 16, 80, TRUE, FALSE, 'Minimal styling needed. Use scalp moisturizer.', '2-5 minutes', 'easy', 7, TRUE),
('Undercut', 'Short sides with longer hair on top', 'assets/images/haircuts/undercut.jpg', 'high', FALSE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, 16, 45, TRUE, FALSE, 'Style top with pomade or gel. Regular maintenance required.', '10-15 minutes', 'medium', 9, TRUE),
('Wolf Cut', 'Modern shag with choppy layers', 'assets/images/haircuts/wolf-cut.jpg', 'low', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, 16, 35, TRUE, TRUE, 'Scrunch with texturizing spray. Embrace the messy look.', '5-10 minutes', 'easy', 10, FALSE),
('Curtain Bangs', 'Face-framing bangs parted in the middle', 'assets/images/haircuts/curtain-bangs.jpg', 'medium', FALSE, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE, 16, 60, FALSE, TRUE, 'Blow dry bangs forward, then sweep to sides with round brush.', '10-15 minutes', 'medium', 9, FALSE);

-- Insert haircut recommendations (face shape to haircut mapping)
INSERT INTO haircut_recommendations (face_shape_id, haircut_id, priority_score, reason) VALUES
-- Oval face (suits most styles)
(1, 1, 9, 'Classic bob enhances natural balance'), 
(1, 2, 9, 'Long layers add movement without disrupting proportions'),
(1, 3, 8, 'Pixie cut highlights facial symmetry'), 
(1, 4, 9, 'Lob is universally flattering'),
(1, 6, 8, 'Shag adds texture while maintaining balance'),
(1, 11, 9, 'Wolf cut complements oval proportions'),
-- Round face (elongating styles)
(2, 4, 9, 'Lob creates vertical lines to elongate'), 
(2, 2, 8, 'Long layers add length and movement'),
(2, 5, 9, 'Side-swept bangs create asymmetry'), 
(2, 12, 8, 'Curtain bangs frame face vertically'),
(2, 6, 7, 'Shag adds height and texture'),
-- Square face (softening styles)
(3, 2, 9, 'Long layers soften angular jawline'), 
(3, 6, 8, 'Shag adds soft texture'),
(3, 8, 7, 'Curly bob softens harsh angles'), 
(3, 12, 9, 'Curtain bangs soften forehead'),
(3, 11, 8, 'Wolf cut breaks up angular lines'),
-- Heart face (balancing styles)
(4, 1, 8, 'Classic bob adds width at jawline'), 
(4, 4, 9, 'Lob balances narrow chin'),
(4, 8, 8, 'Curly bob adds volume at jaw level'), 
(4, 7, 7, 'Blunt cut adds weight at bottom'),
(4, 2, 7, 'Long layers balance proportions'),
-- Oblong face (width-adding styles)
(5, 1, 9, 'Classic bob adds horizontal lines'), 
(5, 5, 8, 'Side-swept bangs shorten forehead'),
(5, 7, 8, 'Blunt cut adds width'), 
(5, 8, 7, 'Curly bob adds volume to sides'),
(5, 12, 8, 'Curtain bangs reduce face length'),
-- Diamond face (width at forehead and chin)
(6, 5, 9, 'Side-swept bangs add forehead width'), 
(6, 12, 8, 'Curtain bangs balance cheekbones'),
(6, 2, 7, 'Long layers add movement'), 
(6, 6, 8, 'Shag softens angular features'),
(6, 1, 7, 'Classic bob adds chin width');

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'HairCut Suggester', 'Website name'),
('site_description', 'Find your perfect hairstyle with our intelligent recommendation system', 'Website description'),
('admin_email', 'admin@haircutsuggester.com', 'Admin contact email'),
('max_upload_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_image_types', 'jpg,jpeg,png,gif', 'Allowed image file extensions'),
('quiz_passing_score', '70', 'Minimum score to pass face shape quiz'),
('recommendations_per_page', '6', 'Number of haircut recommendations to show per page');
