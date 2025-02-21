<?php

namespace Services;

require_once __DIR__ . '/../Database/Database.php';

use Database\Database;
use PDO;
use InvalidArgumentException;

class FavoriteService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function addFavorite(int $userId, string $articleId, string $articleTitle, string $articleUrl): bool
    {
        //  Validate and sanitize input
        $articleId = trim($articleId);
        $articleTitle = trim(htmlspecialchars($articleTitle, ENT_QUOTES, 'UTF-8')); // XSS Protection
        $articleUrl = filter_var($articleUrl, FILTER_VALIDATE_URL);

        if (empty($articleId) || empty($articleTitle) || !$articleUrl) {
            throw new InvalidArgumentException("Invalid input");
        }

        // Check for existing favorite
        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND article_id = ?");
        $checkStmt->execute([$userId, $articleId]);
        $exists = $checkStmt->fetchColumn() > 0;

        if ($exists) {
            // Duplicate detected, return false without throwing an exception
            return false;
        }

        // Insert new favorite
        $stmt = $this->db->prepare("INSERT INTO favorites (user_id, article_id, article_title, article_url) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $articleId, $articleTitle, $articleUrl]);
    }

    public function getFavorites(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteFavorite(int $userId, string $favoriteId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM favorites WHERE article_id = ? AND user_id = ?");
        return $stmt->execute([$favoriteId, $userId]);
    }


    // Override database connection
    public function setDatabaseConnection($db)
    {
        $this->db = $db;
    }
}
