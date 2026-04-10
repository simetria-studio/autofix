<?php

declare(strict_types=1);

use App\Services\ErrorSolutionDetector;

if (! function_exists('detectarSolucao')) {
    /**
     * Sugere uma ação com base em regras configuradas (config/autofix.php).
     */
    function detectarSolucao(string $mensagem): string
    {
        return app(ErrorSolutionDetector::class)->detect($mensagem);
    }
}
