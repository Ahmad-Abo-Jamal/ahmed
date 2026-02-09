-- Add reading_time and word_count columns to articles
ALTER TABLE articles
ADD COLUMN word_count INT DEFAULT 0,
ADD COLUMN reading_time INT DEFAULT 0; -- in minutes

-- Optionally, populate existing rows (approximate)
UPDATE articles SET word_count = CHAR_LENGTH(content) - CHAR_LENGTH(REPLACE(content, ' ', '')) + 1;
UPDATE articles SET reading_time = CEIL(word_count / 200.0);
