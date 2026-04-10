<?php

declare(strict_types=1);

if (! function_exists('detectarSolucao')) {
    /**
     * Sugere uma ação com base em trechos conhecidos da mensagem de erro.
     */
    function detectarSolucao(string $mensagem): string
    {
        if (str_contains($mensagem, 'Permission denied')) {
            return 'chmod -R 775 storage';
        }

        if (str_contains($mensagem, 'SQLSTATE')) {
            return 'Verifique conexão com banco (.env)';
        }

        if (str_contains($mensagem, 'Class not found')) {
            return 'composer install';
        }

        return 'Análise manual necessária';
    }
}
