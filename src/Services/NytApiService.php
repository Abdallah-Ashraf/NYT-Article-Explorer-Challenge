<?php

namespace Services;

use Exception;

class NytApiService
{
    private string $apiKey;
    private string $baseUrl;
    private string $cacheDir;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->apiKey = $config['nyt']['api_key'];
        $this->baseUrl = $config['nyt']['base_url'];
        $this->cacheDir = __DIR__ . '/../../storage/cache';
    }

    public function searchArticles(string $query = null, int $page = 0, string $articleId = null): array
    {
        $query = urlencode(trim($query)); // Secure the input
        $page = max(0, $page ); // NYT API uses 0-based pages
        if ($page > 100) {
            $page = 100;
        }

        $url = "{$this->baseUrl}?page={$page}&api-key={$this->apiKey}";
        if($articleId)
            $url .= "&fq=uri:(\"{$articleId}\")";
        elseif ($query)
            $url .= "&q={$query}";

        try {
            // Use cURL instead of file_get_contents for better control
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $data = json_decode($response, true);

            if (!isset($data['response']['docs'])) {
                throw new Exception("Invalid API response");
            }

            // Extract pagination details
            $totalHits = $data['response']['meta']['hits'] ?? 0;
            $perPage = 10;
            $totalPages = ceil($totalHits / $perPage);

            $prevPage = ($page > 0) ? $page - 1 : null;
            $nextPage = ($page < $totalPages - 1) ? $page + 1 : null;

            return[
                'meta' => [
                    'total' => $totalHits,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => $totalPages,
                    'prev_page' => $prevPage,
                    'next_page' => $nextPage
                ],
                'articles' => $this->formatArticles($data['response'] ?? [])
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function getArticleById(string $articleUrl): ?array
    {
        $articleUrl = trim($articleUrl);

        if (!filter_var($articleUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        $searchResult = $this->searchArticles('', 0, $articleUrl);

        if(isset($searchResult['status']) && $searchResult['status'] == 'error') {
            return null;
        }

        if (count($searchResult['articles']) > 0){
            return $searchResult['articles'][0];
        }

        return null;
    }


    function formatArticles(array $data): array
    {
        $articles = [];
        if (!isset($data['docs'])) return [];

        foreach ($data['docs'] as $doc) {
            $articles[] = [
                'title' => $doc['headline']['main'] ?? 'No Title',
                'url' => $doc['web_url'] ?? '',
                'snippet' => $doc['snippet'] ?? '',
                'published_date' => $doc['pub_date'] ?? '',
                'document_type' => $doc['document_type'] ?? '',
                'id' => $doc['_id'],
            ];
        }
        return $articles;
    }
}
