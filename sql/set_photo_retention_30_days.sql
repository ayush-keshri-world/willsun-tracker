-- Run once in phpMyAdmin to apply the 30-day policy to existing retained photos.
UPDATE visits
SET photo_expires_at = DATE_ADD(visited_at, INTERVAL 30 DAY)
WHERE photo_path IS NOT NULL;
