<?php

namespace App\Services;

use App\Models\Error;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class RemoteSshLogFetcher
{
    /**
     * @throws \RuntimeException
     */
    public function fetchTail(
        string $host,
        int $port,
        string $username,
        ?string $password,
        ?string $privateKeyPath,
        ?string $privateKeyPassphrase,
        string $logPath,
        int $tailLines,
        int $timeoutSeconds = 30,
        ?string $logSource = null,
    ): string {
        $ssh = new SSH2($host, $port, $timeoutSeconds);
        $ssh->setTimeout($timeoutSeconds);

        $authenticated = false;
        if ($privateKeyPath !== null && $privateKeyPath !== '' && is_readable($privateKeyPath)) {
            $keyMaterial = file_get_contents($privateKeyPath);
            if ($keyMaterial === false) {
                throw new \RuntimeException('Não foi possível ler a chave: '.$privateKeyPath);
            }
            $key = $privateKeyPassphrase !== null && $privateKeyPassphrase !== ''
                ? PublicKeyLoader::load($keyMaterial, $privateKeyPassphrase)
                : PublicKeyLoader::load($keyMaterial);
            $authenticated = $ssh->login($username, $key);
        } elseif ($password !== null && $password !== '') {
            $authenticated = $ssh->login($username, $password);
        }

        if (! $authenticated) {
            throw new \RuntimeException('Falha na autenticação SSH para '.$username.'@'.$host.' (use senha ou chave privada legível).');
        }

        $n = max(1, $tailLines);
        if ($logSource === Error::LOG_SOURCE_APPLICATION) {
            $remoteCmd = $this->buildApplicationTailCommand($logPath, $n);
        } else {
            $remoteCmd = sprintf('tail -n %d %s', $n, escapeshellarg($logPath));
        }

        $output = $ssh->exec($remoteCmd);
        if ($output === false) {
            throw new \RuntimeException('Comando remoto falhou.');
        }

        return (string) $output;
    }

    /**
     * Aceita arquivo (ex.: .../laravel.log) ou diretório (.../storage/logs).
     * No diretório: usa o laravel-*.log mais recente por data no nome; senão laravel.log.
     */
    private function buildApplicationTailCommand(string $logPath, int $n): string
    {
        $q = escapeshellarg($logPath);
        $inner = 'if [ -f '.$q.' ]; then '
            .'tail -n '.$n.' '.$q.'; '
            .'elif [ -d '.$q.' ]; then '
            .'f=$(ls -1t '.$q.'/laravel-*.log 2>/dev/null | head -1); '
            .'if [ -n "$f" ] && [ -f "$f" ]; then tail -n '.$n.' "$f"; '
            .'elif [ -f '.$q.'/laravel.log ]; then tail -n '.$n.' '.$q.'/laravel.log; '
            .'else echo "autofix: nenhum laravel.log em diretório '.$q.'" 1>&2; exit 1; '
            .'fi; '
            .'else echo "autofix: caminho não é arquivo nem diretório: '.$q.'" 1>&2; exit 1; fi';

        return 'sh -lc '.escapeshellarg($inner);
    }
}
