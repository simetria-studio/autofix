<?php

namespace App\Services;

use App\Models\Error;

class ErrorIngestionService
{
    public function __construct(
        private readonly ErrorSolutionDetector $detector
    ) {}

    /**
     * @return array{status: string, id: int, solution: string, deduplicated: bool, occurrence_count: int, log_source: string}
     */
    public function ingest(string $message, ?string $serverName, string $logSource = Error::LOG_SOURCE_SERVER): array
    {
        $logSource = $this->normalizeLogSource($logSource);

        $solution = $this->detector->detect($message);
        $fingerprint = hash('sha256', (string) $serverName."\n".$logSource."\n".$message);

        $windowHours = max(1, (int) config('autofix.dedup_window_hours', 24));
        $existing = Error::query()
            ->where('fingerprint', $fingerprint)
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->first();

        if ($existing !== null) {
            $existing->increment('occurrence_count');
            $existing->refresh();

            return [
                'status' => 'ok',
                'id' => $existing->id,
                'solution' => $existing->solution,
                'deduplicated' => true,
                'occurrence_count' => $existing->occurrence_count,
                'log_source' => $existing->log_source ?? $logSource,
            ];
        }

        $error = Error::create([
            'fingerprint' => $fingerprint,
            'server_name' => $serverName,
            'log_source' => $logSource,
            'message' => $message,
            'solution' => $solution,
            'occurrence_count' => 1,
        ]);

        return [
            'status' => 'ok',
            'id' => $error->id,
            'solution' => $solution,
            'deduplicated' => false,
            'occurrence_count' => 1,
            'log_source' => $logSource,
        ];
    }

    private function normalizeLogSource(string $logSource): string
    {
        $allowed = config('autofix.valid_log_sources', ['server', 'application']);

        return in_array($logSource, $allowed, true) ? $logSource : Error::LOG_SOURCE_SERVER;
    }
}
