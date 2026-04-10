#!/usr/bin/env bash
#
# Envia linhas de log para a API Autofix (JSON seguro via jq).
# Requisitos: curl, jq
#
set -u

LOG_FILE="${AUTOFIX_LOG_FILE:-/var/log/nginx/error.log}"
API_URL="${AUTOFIX_API_URL:-https://autofix.blucore.dev.br/api/errors}"
SERVER_NAME="${AUTOFIX_SERVER_NAME:-$(hostname -f 2>/dev/null || hostname)}"
# server = nginx/apache/syslog | application = Laravel (ex.: storage/logs/laravel.log)
LOG_SOURCE="${AUTOFIX_LOG_SOURCE:-server}"

if ! command -v jq &>/dev/null; then
  echo "autofix: instale jq para montar JSON com segurança (apt install jq / yum install jq)" >&2
  exit 1
fi

tail -F "$LOG_FILE" 2>/dev/null | while IFS= read -r line || [[ -n "$line" ]]; do
  [[ -z "$line" ]] && continue

  # Servidor: nginx/syslog. Aplicação: só linha com nível .ERROR: (Monolog / Laravel)
  if [[ "$LOG_SOURCE" == "application" ]]; then
    if ! echo "$line" | grep -qE '\[[^]]+\][[:space:]]+[^[:space:]]+\.ERROR:|"level_name"[[:space:]]*:[[:space:]]*"(ERROR|error)"|"level"[[:space:]]*:[[:space:]]*400\b'; then
      continue
    fi
  else
    if ! echo "$line" | grep -qiE 'error|crit|alert|emerg|fatal'; then
      continue
    fi
  fi

  payload="$(jq -n --arg msg "$line" --arg sn "$SERVER_NAME" --arg ls "$LOG_SOURCE" '{message:$msg, server_name:$sn, log_source:$ls}')" || continue

  curl -sS -X POST "$API_URL" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$payload" \
    --connect-timeout 5 \
    --max-time 25 \
    -o /dev/null || true
done
