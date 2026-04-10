<?php

namespace App\Services;

class ErrorSolutionDetector
{
    /**
     * @return array<int, array{match: string, solution: string, case_insensitive?: bool}>
     */
    public function rules(): array
    {
        return config('autofix.rules', []);
    }

    public function detect(string $mensagem): string
    {
        foreach ($this->rules() as $rule) {
            $match = $rule['match'];
            $caseInsensitive = $rule['case_insensitive'] ?? true;

            if ($caseInsensitive) {
                if (stripos($mensagem, $match) !== false) {
                    return $rule['solution'];
                }
            } elseif (str_contains($mensagem, $match)) {
                return $rule['solution'];
            }
        }

        return (string) config('autofix.fallback_solution', 'Análise manual necessária');
    }
}
