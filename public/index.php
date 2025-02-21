<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Services/FavoriteService.php';
require_once __DIR__ . '/../src/Services/NytApiService.php';
require_once __DIR__ . '/../src/Services/Logger.php';
require_once __DIR__ . '/../src/Services/RateLimiter.php';

use Services\AuthService;
use Middleware\AuthMiddleware;
use Services\FavoriteService;
use Services\NytApiService;
use Services\Logger;
use Services\RateLimiter;



// Allow CORS
header("Access-Control-Allow-Origin: *"); // Allow all domains (or specify your frontend domain)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Allowed methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allowed headers
header("Access-Control-Allow-Credentials: true");



// Handle preflight (OPTIONS request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// main request info
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$ipAddress = $_SERVER['REMOTE_ADDR'];
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// logger variables
$startTime = microtime(true);
$responseData = ['message' => 'Invalid request']; // Default response
$statusCode = 404; // Default status code

// creating services instance
$authService = new AuthService();
$favoriteService = new FavoriteService();
$rateLimiter = new RateLimiter();
// Start request logging
Logger::logRequest();

// Extract JWT token from Authorization header
$jwtToken = str_replace('Bearer ', '', $authHeader);
$identifier = $jwtToken ?: $ipAddress; // Use token if available, otherwise use IP
// Apply rate limiting
if (!$rateLimiter->checkRateLimit($identifier)) {
    $statusCode = 429;
    header('Retry-After: ' . $rateLimiter->getRetryAfter($identifier));
    $responseData = [
        'error' => 'Too many requests. Please try again later.',
        'retry_after' => $rateLimiter->getRetryAfter($identifier)
    ];
}else{
    // Handle requests
    switch (true) {
        case $requestUri === '/register' && $requestMethod === 'POST':
            try{
                $data = json_decode(file_get_contents('php://input'), true);
                if ($authService->register($data['username'], $data['password'])) {
                    $statusCode = 200;
                    $responseData = ['message' => 'User registered successfully'];
                } else {
                    $statusCode = 409; // Conflict for duplicates
                    $responseData = ["error" => "User already registered"];
                }
            } catch (PDOException $e) {
                $statusCode = 500;
                $responseData = ["error" => "Database error: " . $e->getMessage()];
            }
            break;

        case $requestUri === '/login' && $requestMethod === 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $token = $authService->login($data['username'], $data['password']);
            if ($token) {
                $statusCode = 200;
                $responseData = ['token' => $token];
            } else {
                $statusCode = 401;
                $responseData = ['error' => 'Invalid credentials'];
            }
            break;

        case $requestUri === '/validate' && $requestMethod === 'GET':
            $headers = getallheaders();
            if (!isset($headers['Authorization'])) {
                $statusCode = 401;
                $responseData = ['error' => 'Token required'];
                exit;
            }
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            $userData = $authService->validateToken($token);

            if ($userData) {
                $statusCode = 200;
                $responseData = ['user' => $userData];
            } else {
                $statusCode = 401;
                $responseData = ['error' => 'Invalid or expired token'];
            }
            break;
        case str_starts_with($requestUri, '/articles/search') && $requestMethod === 'GET':
            $query = $_GET['q'] ?? '';
            $page = $_GET['page'] ?? 0;

            $articleService = new NytApiService();
            $result = $articleService->searchArticles($query, (int)$page);
            $statusCode = 200;
            $responseData = $result;
            break;
        case str_starts_with($requestUri, '/articles?id=')&& $requestMethod === 'GET':
            if (!isset($_GET['id']) || empty($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_URL)) {
                $statusCode = 400;
                $responseData = ['error' => 'Valid article id is required'];
                exit;
            }
            $articleUrl = $_GET['id'];

            $articleService = new NytApiService();
            $article = $articleService->getArticleById($articleUrl);

            if ($article) {
                $statusCode = 200;
                $responseData = ['status' => 'success', 'article' => $article];
            } else {
                $statusCode = 404;
                $responseData = ['status' => 'error', 'message' => 'Article not found'];
            }
            break;
        case $requestUri === '/favorites' :

            //  Secure all endpoints below this line
            AuthMiddleware::authenticate();
            $userId = $_SERVER['user_id'];

            switch (true) {
                case $requestMethod === 'GET':
                    $favorites = $favoriteService->getFavorites($userId);
                    $statusCode = 200;
                    $responseData = ["favorites" => $favorites];
                    break;
                case $requestMethod === 'POST':
                    $data = json_decode(file_get_contents("php://input"), true);
                    if (isset($data['article_id'], $data['article_title'], $data['article_url'])) {
                        try{
                            $success = $favoriteService->addFavorite($userId, $data['article_id'], $data['article_title'], $data['article_url']);
                            if ($success) {
                                $statusCode = 200;
                                $responseData = ["message" => "Article added to favorites"];
                            } else {
                                $statusCode = 409; // Conflict for duplicates
                                $responseData = ["error" => "Article already in favorites"];
                            }

                        }catch (InvalidArgumentException $e) {
                            $statusCode = 400;
                            $responseData = ["error" => $e->getMessage()];
                        } catch (PDOException $e) {
                            $statusCode = 500;
                            $responseData = ["error" => "Database error: " . $e->getMessage()];
                        }
                    } else {
                        $statusCode = 400;
                        $responseData = ["error" => "Missing required fields"];
                    }
                    break;
                case $requestMethod === 'DELETE':
                    $data = json_decode(file_get_contents("php://input"), true);
                    if (isset($data['article_id'])) {
                        $favoriteId = $data['article_id'];
                        $success = $favoriteService->deleteFavorite($userId, $favoriteId);

                        if ($success) {
                            $statusCode = 200;
                            $responseData = ["message" => "Favorite deleted"];
                        } else {
                            $statusCode = 404;
                            $responseData = ["error" => "Favorite not found"];
                        }
                    } else {
                        $statusCode = 400;
                        $responseData = ["error" => "Missing required Article id"];
                    }
                    break;
                default:
                    $statusCode = 400;
                    $responseData = ['error' => 'Invalid request'];
            }
            break;

        default:
            $statusCode = 404;
            $responseData = ['error' => 'Invalid request'];
    }

}

// End request and log response
$executionTime = microtime(true) - $startTime;
http_response_code($statusCode);
$responseJson = json_encode($responseData);
// logging Response
Logger::logResponse($statusCode, $responseJson, $executionTime);
// return response
echo $responseJson;
