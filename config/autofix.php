<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Janela de deduplicação (horas)
    |--------------------------------------------------------------------------
    |
    | Erros com a mesma impressão digital no mesmo servidor dentro desta janela
    | incrementam occurrence_count em vez de criar novo registro.
    |
    */
    'dedup_window_hours' => (int) env('AUTOFIX_DEDUP_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Origem do log (servidor web vs aplicação Laravel)
    |--------------------------------------------------------------------------
    */
    'valid_log_sources' => ['server', 'application'],

    'log_source_labels' => [
        'server' => 'Servidor (nginx/apache)',
        'application' => 'Aplicação (Laravel)',
    ],

    /*
    | Filtro por linha ao coletar logs (SSH / agent). Uma regex por origem.
    |
    | application: só nível ERROR — texto Monolog ([data] canal.ERROR:) ou JSON
    | (level_name ERROR / level 400 do Monolog).
    */
    'line_filter_patterns' => [
        'server' => '/error|crit|alert|emerg|fatal/i',
        'application' => '/\[[^\]]+\]\s+[\w.-]+\.ERROR:|"level_name"\s*:\s*"(ERROR|error)"|"level"\s*:\s*400\b/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Regras de detecção (ordem importa: primeira correspondência vence)
    |--------------------------------------------------------------------------
    |
    | match: substring procurada na mensagem
    | case_insensitive: padrão true (usa comparação sem diferenciar maiúsculas)
    |
    */
    'rules' => [
        [
            'match' => 'permission denied',
            'solution' => 'chmod -R 775 storage bootstrap/cache (e verifique dono do processo web)',
        ],
        [
            'match' => 'SQLSTATE',
            'solution' => 'Verifique conexão com banco (.env: DB_*) e se o serviço do banco está ativo',
        ],
        [
            'match' => 'Target class',
            'solution' => 'composer dump-autoload; confira binding no service provider e namespaces PSR-4',
        ],
        [
            'match' => 'Class not found',
            'solution' => 'composer install && composer dump-autoload',
        ],
        [
            'match' => 'Connection refused',
            'solution' => 'Serviço indisponível (DB, Redis, fila): confira host/porta e firewall',
        ],
        [
            'match' => '502 Bad Gateway',
            'solution' => 'PHP-FPM / upstream: verifique socket/porta, timeouts e logs do backend',
        ],
        [
            'match' => '504 Gateway Time-out',
            'solution' => 'Aumente timeouts (nginx/proxy) ou otimize o endpoint; verifique PHP max_execution_time',
        ],
        [
            'match' => 'Allowed memory size',
            'solution' => 'Aumente memory_limit no PHP ou corrija vazamento / uso excessivo de memória',
        ],
        [
            'match' => 'file_put_contents',
            'solution' => 'Permissões de escrita no caminho alvo (storage, cache, uploads)',
        ],
        [
            'match' => 'Vite manifest not found',
            'solution' => 'Execute npm run build (ou npm run dev) e publique public/build',
        ],
        [
            'match' => 'No such file or directory',
            'solution' => 'Caminho ou symlink ausente; confira deploy e paths relativos ao docroot',
        ],
    ],

    'fallback_solution' => 'Análise manual necessária',

    /*
    |--------------------------------------------------------------------------
    | Coleta remota via SSH (alternativa ao agent.sh no servidor)
    |--------------------------------------------------------------------------
    |
    | O comando `php artisan autofix:pull-remote-logs` conecta neste host,
    | executa `tail` no log e grava erros no mesmo fluxo da API.
    |
    | laravel_log_path: pode ser o arquivo (…/laravel.log) ou o diretório
    | storage/logs (usa o laravel-*.log mais recente por data de modificação).
    |
    | Preferível chave privada (AUTOFIX_SSH_PRIVATE_KEY_PATH) em vez de senha.
    |
    */
    'remote' => [
        'host' => env('AUTOFIX_SSH_HOST'),
        'port' => (int) env('AUTOFIX_SSH_PORT', 22),
        'username' => env('AUTOFIX_SSH_USER'),
        'password' => env('AUTOFIX_SSH_PASSWORD'),
        'private_key_path' => env('AUTOFIX_SSH_PRIVATE_KEY_PATH'),
        'private_key_passphrase' => env('AUTOFIX_SSH_PRIVATE_KEY_PASSPHRASE'),
        'log_path' => env('AUTOFIX_SSH_LOG_PATH', '/var/log/nginx/error.log'),
        'laravel_log_path' => env('AUTOFIX_SSH_LARAVEL_LOG_PATH'),
        'server_name' => env('AUTOFIX_SSH_SERVER_NAME'),
        'tail_lines' => (int) env('AUTOFIX_SSH_TAIL_LINES', 200),
        'timeout' => (int) env('AUTOFIX_SSH_TIMEOUT', 30),
    ],

];
