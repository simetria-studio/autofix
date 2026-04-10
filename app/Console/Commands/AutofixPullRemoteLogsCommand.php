<?php

namespace App\Console\Commands;

use App\Models\Error;
use App\Services\ErrorIngestionService;
use App\Services\RemoteSshLogFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AutofixPullRemoteLogsCommand extends Command
{
    protected $signature = 'autofix:pull-remote-logs
                            {--host= : Sobrescreve AUTOFIX_SSH_HOST}
                            {--user= : Sobrescreve AUTOFIX_SSH_USER}
                            {--password= : Sobrescreve AUTOFIX_SSH_PASSWORD (cuidado: fica no histórico do terminal)}
                            {--key= : Caminho da chave privada (sobrescreve AUTOFIX_SSH_PRIVATE_KEY_PATH)}
                            {--log= : Caminho do log de servidor no remoto (nginx/apache)}
                            {--laravel-log= : Caminho do laravel.log no remoto}
                            {--source= : server, application ou both}
                            {--lines= : Quantidade de linhas do tail -n}
                            {--server-name= : Rótulo gravado em server_name no banco}
                            {--fresh : Limpa cache de linhas já vistas (após esvaziar a tabela ou para reimportar)}';

    protected $description = 'Conecta por SSH, lê tail de log(s) remoto(s) e registra erros (servidor e/ou Laravel)';

    public function handle(RemoteSshLogFetcher $fetcher, ErrorIngestionService $ingestion): int
    {
        $host = $this->option('host') ?: config('autofix.remote.host');
        $user = $this->option('user') ?: config('autofix.remote.username');
        $password = $this->option('password') ?: config('autofix.remote.password');
        $keyPath = $this->option('key') ?: config('autofix.remote.private_key_path');
        $serverLogPath = $this->option('log') ?: config('autofix.remote.log_path');
        $laravelLogPath = $this->option('laravel-log') ?: config('autofix.remote.laravel_log_path');
        $tailLines = (int) ($this->option('lines') ?: config('autofix.remote.tail_lines', 200));
        $serverName = $this->option('server-name') ?: config('autofix.remote.server_name') ?: $host;
        $port = (int) config('autofix.remote.port', 22);
        $timeout = (int) config('autofix.remote.timeout', 30);

        if ($host === null || $host === '' || $user === null || $user === '') {
            $this->error('Defina AUTOFIX_SSH_HOST e AUTOFIX_SSH_USER no .env ou use --host e --user.');

            return self::FAILURE;
        }

        $hasKey = $keyPath !== null && $keyPath !== '' && is_readable($keyPath);
        $hasPassword = $password !== null && $password !== '';

        if (! $hasKey && ! $hasPassword) {
            $this->error('Informe senha (AUTOFIX_SSH_PASSWORD ou --password) ou chave legível (AUTOFIX_SSH_PRIVATE_KEY_PATH ou --key).');

            return self::FAILURE;
        }

        $mode = strtolower((string) ($this->option('source') ?: ''));
        if ($mode === '') {
            $mode = ($laravelLogPath !== null && $laravelLogPath !== '') ? 'both' : 'server';
        }

        $jobs = [];
        if (in_array($mode, ['server', 'both'], true)) {
            $jobs[] = ['path' => (string) $serverLogPath, 'log_source' => Error::LOG_SOURCE_SERVER];
        }
        if (in_array($mode, ['application', 'both'], true)) {
            if ($laravelLogPath === null || $laravelLogPath === '') {
                if ($mode === 'application') {
                    $this->error('Defina AUTOFIX_SSH_LARAVEL_LOG_PATH ou use --laravel-log para origem application.');

                    return self::FAILURE;
                }
                $this->warn('AUTOFIX_SSH_LARAVEL_LOG_PATH vazio — pulando log da aplicação.');
            } else {
                $jobs[] = ['path' => (string) $laravelLogPath, 'log_source' => Error::LOG_SOURCE_APPLICATION];
            }
        }

        if ($jobs === []) {
            $this->error('Nenhuma origem para coletar. Use --source=server|application|both.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            foreach ($jobs as $job) {
                $key = 'autofix.ssh.line_hashes.'.hash('sha256', $host.'|'.$user.'|'.$job['path'].'|'.$job['log_source']);
                Cache::forget($key);
            }
            $this->warn('Cache de linhas SSH limpo (--fresh); todas as linhas que baterem no filtro serão tratadas como novas.');
        }

        $totalIngested = 0;
        $totalSkipped = 0;

        foreach ($jobs as $job) {
            try {
                $output = $fetcher->fetchTail(
                    $host,
                    $port,
                    $user,
                    $hasPassword ? (string) $password : null,
                    $hasKey ? (string) $keyPath : null,
                    config('autofix.remote.private_key_passphrase'),
                    $job['path'],
                    max(1, $tailLines),
                    max(5, $timeout),
                    $job['log_source'],
                );
            } catch (\Throwable $e) {
                $this->error('[ '.$job['log_source'].' ] '.$e->getMessage());

                return self::FAILURE;
            }

            [$ingested, $skipped, $nonEmptyLines, $matchedFilter] = $this->processTailOutput(
                $output,
                $host,
                $user,
                $job['path'],
                $job['log_source'],
                $serverName,
                $ingestion
            );
            $totalIngested += $ingested;
            $totalSkipped += $skipped;

            $this->info(sprintf(
                '[ %s ] %s — novas: %d, já vistas: %d',
                $job['log_source'],
                $job['path'],
                $ingested,
                $skipped
            ));

            if ($this->output->isVerbose()) {
                $this->line(sprintf('         ↳ linhas no tail (úteis): %d; passaram no filtro: %d', $nonEmptyLines, $matchedFilter));
            }

            if ($job['log_source'] === Error::LOG_SOURCE_APPLICATION && $nonEmptyLines > 0 && $matchedFilter === 0) {
                $this->warn('         Nenhuma linha bateu no filtro de ERROR (texto [data] canal.ERROR: ou JSON level 400 / level_name ERROR). Ajuste config/autofix.php se o formato do log for outro.');
            }
        }

        $this->newLine();
        $this->info(sprintf('Total — novas linhas registradas: %d, já vistas: %d', $totalIngested, $totalSkipped));

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int} ingested, skipped, nonEmptyLines, matchedFilter
     */
    private function processTailOutput(
        string $output,
        string $host,
        string $user,
        string $logPath,
        string $logSource,
        string $serverName,
        ErrorIngestionService $ingestion,
    ): array {
        $pattern = config('autofix.line_filter_patterns.'.$logSource)
            ?? config('autofix.line_filter_patterns.server', '/error/i');

        $lines = preg_split("/\r\n|\n|\r/", $output) ?: [];
        $cacheKey = 'autofix.ssh.line_hashes.'.hash('sha256', $host.'|'.$user.'|'.$logPath.'|'.$logSource);
        /** @var array<string, bool> $prevHashes */
        $prevHashes = Cache::get($cacheKey, []);
        if (! is_array($prevHashes)) {
            $prevHashes = [];
        }

        $currentHashes = [];
        $ingested = 0;
        $skipped = 0;
        $nonEmptyLines = 0;
        $matchedFilter = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if ($this->isShellToolNoiseLine($line)) {
                continue;
            }
            $nonEmptyLines++;
            if (@preg_match($pattern, $line) !== 1) {
                continue;
            }

            $matchedFilter++;
            $h = hash('sha256', $line);
            $currentHashes[$h] = true;

            if (isset($prevHashes[$h])) {
                $skipped++;

                continue;
            }

            $ingestion->ingest($line, $serverName, $logSource);
            $ingested++;
        }

        Cache::put($cacheKey, $currentHashes, now()->addHours(72));

        return [$ingested, $skipped, $nonEmptyLines, $matchedFilter];
    }

    private function isShellToolNoiseLine(string $line): bool
    {
        return str_starts_with($line, 'tail: ')
            || str_starts_with($line, 'ls: ')
            || str_starts_with($line, 'autofix:');
    }
}
