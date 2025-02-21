<?php

namespace Services;

class Logger
{
    private static string $logFile = __DIR__ . '/../../logs/api.log';

    public static function log(string $type, string $message, array $context = []): void
    {
        self::ensureLogDirectoryExists();
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$type] $message";

        if (!empty($context)) {
            $logEntry .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        file_put_contents(self::$logFile, $logEntry . PHP_EOL, FILE_APPEND);
    }

    public static function logRequest(): void
    {
        $request = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'query_params' => $_GET,
            'body' => file_get_contents('php://input'),
            'headers' => getallheaders(),
        ];

        self::log('REQUEST', 'Incoming API request', $request);
    }

    public static function logResponse(int $statusCode, string $responseBody, float $executionTime): void
    {
        $response = [
            'status_code' => $statusCode,
            'response' => $responseBody,
            'execution_time' => number_format($executionTime, 4) . 's',
        ];

        self::log('RESPONSE', 'API response sent', $response);
    }


    private static function ensureLogDirectoryExists(): void
    {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }
}
