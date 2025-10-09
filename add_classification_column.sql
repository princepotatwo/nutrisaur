-- Add classification column to programs table
-- This column will store the WHO classification for targeted notifications

ALTER TABLE programs ADD COLUMN classification VARCHAR(100) NULL;

-- Add index for better performance when filtering by classification
CREATE INDEX idx_programs_classification ON programs(classification);

-- Update existing records to have NULL classification (they target all classifications)
UPDATE programs SET classification = NULL WHERE classification IS NULL;
