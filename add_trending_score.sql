-- Add missing trending_score column to haircuts table
ALTER TABLE haircuts ADD COLUMN IF NOT EXISTS trending_score INT DEFAULT 50;

-- Update some sample data for trending scores
UPDATE haircuts SET trending_score = 85 WHERE id = 1;
UPDATE haircuts SET trending_score = 75 WHERE id = 2;
UPDATE haircuts SET trending_score = 90 WHERE id = 3;
UPDATE haircuts SET trending_score = 65 WHERE id = 4;
UPDATE haircuts SET trending_score = 70 WHERE id = 5;
