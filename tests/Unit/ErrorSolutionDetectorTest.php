<?php

namespace Tests\Unit;

use App\Services\ErrorSolutionDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorSolutionDetectorTest extends TestCase
{
    #[DataProvider('messagesProvider')]
    public function test_detects_known_patterns(string $message, string $substring): void
    {
        $detector = new ErrorSolutionDetector;

        $solution = $detector->detect($message);

        $this->assertStringContainsStringIgnoringCase($substring, $solution);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function messagesProvider(): array
    {
        return [
            'permission' => ['open() failed: Permission denied', 'chmod'],
            'sql' => ['SQLSTATE[HY000] connection', 'banco'],
            'class' => ['PHP Fatal error: Class not found in /app/Models/Foo.php', 'composer'],
            'fallback' => ['algo totalmente desconhecido xyz123', 'manual'],
        ];
    }
}
