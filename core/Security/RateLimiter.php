<?php
declare(strict_types=1);

namespace Syncro\Security;

use Syncro\Models\Database;
use PDO;

class RateLimiter
{
    /**
     * Checks if the identifier has exceeded the max requests within the given decay minutes.
     * Implements a highly-optimized fixed window counter backed by MySQL.
     * 
     * @param string $identifier The unique identifier (IP address, API token)
     * @param int $maxRequests Maximum allowed requests per window
     * @param int $decayMinutes Time window in minutes
     * @return bool True if allowed, False if limit exceeded
     */
    public static function check(string $identifier, int $maxRequests = 60, int $decayMinutes = 1): bool
    {
        $db = Database::getConnection();
        $now = time();
        $windowStart = $now - ($now % ($decayMinutes * 60)); // Aligns to the fixed window

        try {
            // Upsert the rate limit counter using raw PDO for maximum performance
            // IF the window_start matches, increment hits. Otherwise, reset hits to 1 and update window_start.
            $sql = "
                INSERT INTO rate_limits (identifier, hits, window_start) 
                VALUES (:id1, 1, :win1)
                ON DUPLICATE KEY UPDATE 
                    hits = IF(window_start = :win2, hits + 1, 1),
                    window_start = IF(window_start = :win3, window_start, :win4)
            ";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'id1' => $identifier,
                'win1' => $windowStart,
                'win2' => $windowStart,
                'win3' => $windowStart,
                'win4' => $windowStart
            ]);

            // Now fetch the current hits to determine if we should throttle
            $stmt = $db->prepare("SELECT hits FROM rate_limits WHERE identifier = ? AND window_start = ?");
            $stmt->execute([$identifier, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && (int)$result['hits'] > $maxRequests) {
                return false; // Limit exceeded
            }

            return true; // Allowed

        } catch (\PDOException $e) {
            // Fail open: if rate limit table throws error (e.g. not migrated yet), don't block traffic
            // but log the issue.
            error_log("RateLimiter Error: " . $e->getMessage());
            return true; 
        }
    }

    /**
     * Periodically clean up old rate limits (can be called by a cron job)
     */
    public static function cleanup(int $olderThanMinutes = 60): void
    {
        $db = Database::getConnection();
        $threshold = time() - ($olderThanMinutes * 60);
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE window_start < ?");
        $stmt->execute([$threshold]);
    }
}
