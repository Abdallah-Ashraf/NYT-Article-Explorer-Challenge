<?php

namespace Services;

require_once __DIR__ . '/../Database/Database.php';

use Database\Database;
use PDO;

class RateLimiter
{
    private static int $maxRequests = 5;
    private static int $windowSeconds = 300; // 5 minutes
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function checkRateLimit(string $identifier): bool
    {
        $currentTime = time();
        $windowStartTime = date('Y-m-d H:i:s', $currentTime - self::$windowSeconds);

        // Fetch the current rate limit record
        $stmt = $this->db->prepare("SELECT request_count, last_request FROM rate_limits WHERE token = ? OR ip_address = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $rateData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rateData) {
            $elapsedTime = strtotime($rateData['last_request']);

            if ($elapsedTime < strtotime($windowStartTime)) {
                // Reset the counter if outside the time window
                $this->resetRateLimit($identifier);
                return true;
            }

            if ($rateData['request_count'] >= self::$maxRequests) {
                return false;
            }

            // Increment request count
            $stmt = $this->db->prepare("UPDATE rate_limits SET request_count = request_count + 1, last_request = CURRENT_TIMESTAMP WHERE token = ? OR ip_address = ?");
            $stmt->execute([$identifier, $identifier]);
        } else {
            // Create a new rate limit record
            $stmt = $this->db->prepare("INSERT INTO rate_limits (ip_address, token, request_count) VALUES (?, ?, 1)");
            $stmt->execute([$identifier, $identifier]);
        }

        return true;
    }

    public function getRetryAfter(string $identifier): int
    {
        $stmt = $this->db->prepare("SELECT last_request FROM rate_limits WHERE token = ? OR ip_address = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $rateData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rateData) {
            $elapsedTime = time() - strtotime($rateData['last_request']);
            return max(0, self::$windowSeconds - $elapsedTime);
        }

        return 0;
    }

    private function resetRateLimit(string $identifier): void
    {
        $stmt = $this->db->prepare("UPDATE rate_limits SET request_count = 1, last_request = CURRENT_TIMESTAMP WHERE token = ? OR ip_address = ?");
        $stmt->execute([$identifier, $identifier]);
    }
}
