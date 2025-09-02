-- Create user_saved_haircuts table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_saved_haircuts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    haircut_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (haircut_id) REFERENCES haircuts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_haircut (user_id, haircut_id)
);

-- Insert some sample data for testing
INSERT IGNORE INTO user_saved_haircuts (user_id, haircut_id, saved_at, notes) VALUES
(1, 1, '2024-12-01 10:00:00', 'Love this classic style'),
(1, 2, '2024-12-02 14:30:00', 'Great for formal events'),
(2, 1, '2024-12-03 09:15:00', 'Thinking about trying this'),
(2, 3, '2024-12-04 16:45:00', 'Perfect summer cut'),
(3, 2, '2024-12-05 11:20:00', 'Elegant and professional'),
(3, 4, '2024-12-06 13:10:00', 'Modern and trendy'),
(1, 3, '2024-12-07 15:30:00', 'Good for casual outings');
