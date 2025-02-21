<?php

namespace Middleware;

require_once __DIR__ . '/../Services/AuthService.php';

use Services\AuthService;

class AuthMiddleware {

    public static function authenticate(): void
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Missing token']);
            exit;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $authService = new AuthService(); // Initialize AuthService
        $decoded = $authService->validateToken($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized: Invalid token']);
            exit;
        }

        // Store user ID and username in global context
        $_SERVER['user_id'] = $decoded['sub'];
        $_SERVER['username'] = $decoded['username'];
    }
}
