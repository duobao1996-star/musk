#!/usr/bin/env bash
set -euo pipefail

BASE_URL=${BASE_URL:-"http://127.0.0.1:8787"}
USERNAME=${USERNAME:-"admin"}
PASSWORD=${PASSWORD:-"Admin@12345"}

log() { echo -e "\033[1;34m[SMOKE]\033[0m $*"; }
fail() { echo -e "\033[1;31m[FAIL]\033[0m $*"; exit 1; }

require_cmd() { command -v "$1" >/dev/null 2>&1 || fail "缺少依赖命令: $1"; }
require_cmd curl
require_cmd jq

log "1) 登录获取 token"
LOGIN_RES=$(curl -s -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"$USERNAME\",\"password\":\"$PASSWORD\"}")
TOKEN=$(echo "$LOGIN_RES" | jq -r '.data.token // empty')
[[ -n "$TOKEN" ]] || { echo "$LOGIN_RES"; fail "登录失败，未获取到 token"; }

auth_get() { curl -s "$BASE_URL$1" -H "Authorization: Bearer $TOKEN"; }

log "2) /api/me"
ME=$(auth_get /api/me)
[[ $(echo "$ME" | jq -r '.code') == "200" ]] || { echo "$ME"; fail "/api/me code!=200"; }

log "3) /api/permissions/menu"
MENU=$(auth_get /api/permissions/menu)
[[ $(echo "$MENU" | jq -r '.code') == "200" ]] || { echo "$MENU"; fail "/api/permissions/menu code!=200"; }

log "4) /api/permissions?page=1&limit=10"
PERM=$(auth_get "/api/permissions?page=1&limit=10")
[[ $(echo "$PERM" | jq -r '.code') == "200" ]] || { echo "$PERM"; fail "/api/permissions code!=200"; }
[[ $(echo "$PERM" | jq -r 'has("pagination")') == "true" ]] || { echo "$PERM"; fail "/api/permissions 缺少 pagination"; }

log "5) /api/roles?page=1&limit=10"
ROLES=$(auth_get "/api/roles?page=1&limit=10")
[[ $(echo "$ROLES" | jq -r '.code') == "200" ]] || { echo "$ROLES"; fail "/api/roles code!=200"; }
[[ $(echo "$ROLES" | jq -r 'has("pagination")') == "true" ]] || { echo "$ROLES"; fail "/api/roles 缺少 pagination"; }

log "6) /api/operation-logs?page=1&limit=10"
LOGS=$(auth_get "/api/operation-logs?page=1&limit=10")
[[ $(echo "$LOGS" | jq -r '.code') == "200" ]] || { echo "$LOGS"; fail "/api/operation-logs code!=200"; }
[[ $(echo "$LOGS" | jq -r 'has("pagination")') == "true" ]] || { echo "$LOGS"; fail "/api/operation-logs 缺少 pagination"; }

log "7) /api/performance/stats"
STATS=$(auth_get /api/performance/stats)
[[ $(echo "$STATS" | jq -r '.code') == "200" ]] || { echo "$STATS"; fail "/api/performance/stats code!=200"; }

log "8) /api/performance/slow-queries?threshold=1"
SLOW=$(auth_get "/api/performance/slow-queries?threshold=1")
[[ $(echo "$SLOW" | jq -r '.code') == "200" ]] || { echo "$SLOW"; fail "/api/performance/slow-queries code!=200"; }

log "全部通过"
exit 0


