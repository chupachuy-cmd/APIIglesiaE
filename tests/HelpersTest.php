<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testGenerateCsrfToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = \generateCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testCsrfFieldReturnsHtml(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $html = \csrfField();
        $this->assertStringContainsString('csrf_token', $html);
        $this->assertStringContainsString('hidden', $html);
    }

    public function testFormatDateSpanish(): void
    {
        $result = \formatDateSpanish('2024-12-25');
        $this->assertEquals('25 de diciembre de 2024', $result);

        $result = \formatDateSpanish('');
        $this->assertEquals('', $result);

        $result = \formatDateSpanish('invalid');
        $this->assertEquals('invalid', $result);
    }

    public function testFormatDayNameSpanish(): void
    {
        $result = \formatDayNameSpanish('2024-12-25');
        $this->assertNotEmpty($result);
        $days = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $this->assertContains($result, $days);
    }

    public function testFormatMonthAbbrSpanish(): void
    {
        $result = \formatMonthAbbrSpanish('2024-12-25');
        $this->assertEquals('dic', $result);
    }

    public function testSanitizeFilename(): void
    {
        $result = \sanitizeFilename('Mi Archivo (1).mp3');
        $this->assertEquals('Mi-Archivo-1.mp3', $result);
    }

    public function testValidateFileUploadNoFile(): void
    {
        $result = \validateFileUpload([], ['mp3']);
        $this->assertNotNull($result);
        $this->assertStringContainsString('Error', $result);
    }

    public function testHashEquals(): void
    {
        $this->assertTrue(hash_equals('abc', 'abc'));
        $this->assertFalse(hash_equals('abc', 'def'));
    }
}
