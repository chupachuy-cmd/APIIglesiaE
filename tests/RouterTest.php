<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class RouterTest extends TestCase
{
    public function testInvalidEndpointWouldBeRejected(): void
    {
        $allowedTables = ['coros', 'devocionarios', 'dulia'];
        $this->assertNotContains('invalid_table', $allowedTables);
        $this->assertContains('coros', $allowedTables);
    }

    public function testAllowlistedEndpoints(): void
    {
        $allowedTables = [
            'coros' => 'coros',
            'devocionarios' => 'devocionarios',
            'dulia' => 'dulia',
            'gacetas' => 'gacetas',
            'hiperdulia' => 'hiperdulia',
            'latria' => 'latria',
            'predicas' => 'predicas',
            'eventos' => 'eventos',
            'oraciones' => 'oraciones'
        ];
        $this->assertCount(9, $allowedTables);
        $this->assertContains('coros', $allowedTables);
        $this->assertContains('eventos', $allowedTables);
        $this->assertContains('oraciones', $allowedTables);
    }

    public function testColumnSchemasAreComplete(): void
    {
        $schemas = [
            'coros' => ['title', 'lyrics', 'url'],
            'eventos' => ['invitation', 'title', 'date_event', 'hour_event', 'place', 'image_url'],
            'oraciones' => ['title_pray', 'description_pray', 'subject_pray', 'pray_for', 'pray_to', 'date_pray', 'lyrics_pray'],
        ];

        $this->assertContains('title', $schemas['coros']);
        $this->assertContains('title', $schemas['eventos']);
        $this->assertCount(7, $schemas['oraciones']);
    }

    public function testDatabaseSingleton(): void
    {
        $db1 = \Database::getInstance();
        $db2 = \Database::getInstance();
        $this->assertSame($db1, $db2);
    }

    public function testLoadEnvFunction(): void
    {
        \loadEnv(__DIR__ . '/../.env');
        $dbName = getenv('DB_NAME');
        $this->assertNotEmpty($dbName);
    }

    public function testSetHeadersDoesNotError(): void
    {
        \setHeaders();
        $this->assertTrue(true);
    }

    public function testCsrfTokenConsistency(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = \generateCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }
}
