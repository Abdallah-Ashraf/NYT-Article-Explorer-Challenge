<?php

namespace tests;

require_once __DIR__ . '/../src/Services/FavoriteService.php';

use PHPUnit\Framework\TestCase;
use Services\FavoriteService;
use PDO;
use PDOStatement;

class FavoriteServiceTest extends TestCase
{
    private $mockDb;
    private $favoriteService;

    protected function setUp(): void
    {
        // Mock the PDO object
        $this->mockDb = $this->createMock(PDO::class);
        // Inject the mock into FavoriteService
        $this->favoriteService = new FavoriteService();
        $this->favoriteService->setDatabaseConnection($this->mockDb); // Override database connection
    }


    public function testGetFavorites()
    {
        $userId = 1;
        $expectedData = [
            ['article_id' => 'nyt://article/123', 'article_title' => 'Test 1', 'article_url' => 'https://nyt.com/1'],
            ['article_id' => 'nyt://article/456', 'article_title' => 'Test 2', 'article_url' => 'https://nyt.com/2']
        ];

        // Mock the SELECT query
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('fetchAll')->willReturn($expectedData);

        $this->mockDb->method('prepare')->willReturn($mockStmt);
        $mockStmt->method('execute')->willReturn(true);

        $favorites = $this->favoriteService->getFavorites($userId);

        $this->assertEquals($expectedData, $favorites);
    }


    public function testDeleteFavoriteSuccess()
    {
        $userId = 1;
        $articleId = "nyt://article/123456";

        // Mock the DELETE query
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);

        $this->mockDb->method('prepare')->willReturn($mockStmt);

        $result = $this->favoriteService->deleteFavorite($userId, $articleId);

        $this->assertTrue($result);
    }

    public function testDeleteFavoriteFailure()
    {
        $userId = 1;
        $articleId = "nyt://article/123456";

        // Mock the DELETE query failure
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->method('execute')->willReturn(false);

        $this->mockDb->method('prepare')->willReturn($mockStmt);

        $result = $this->favoriteService->deleteFavorite($userId, $articleId);

        $this->assertFalse($result);
    }

}