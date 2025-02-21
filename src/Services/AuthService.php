<?php

namespace Services;

require_once __DIR__ . "/../Database/Database.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Database\Database;
use PDO;

class AuthService
{
    private string $secretKey;
    private int $jwtExpiration;
    private PDO $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->secretKey = $config['jwt']['secret'];
        $this->jwtExpiration = $config['jwt']['expiration'];
        $this->db = Database::connect();
    }

    public function register(string $username, string $password): bool
    {
        // Check for existing user
        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        $exists = $checkStmt->fetchColumn() > 0;

        if ($exists) {
            // Duplicate detected, return false without throwing an exception
            return false;
        }
        // register
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        return $stmt->execute([$username, $hashedPassword]);
    }

    public function login(string $username, string $password): ?string
    {
        $stmt = $this->db->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $this->generateToken($user['id'], $username);
        }
        return null;
    }

    private function generateToken(int $userId, string $username): string
    {
        $payload = [
            'iss' => "NYT API Explorer",
            'sub' => $userId,
            'username' => $username,
            'iat' => time(),
            'exp' => time() + $this->jwtExpiration
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validateToken(string $token): ?array
    {
        try {
            return (array) JWT::decode($token, new Key($this->secretKey, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
