#!/usr/bin/env bash
# NeNe Records — security assessment harness (authorized, isolated instances only).
# Boots a throwaway instance, seeds data, runs the attack battery, asserts results, tears down.
# Never run against production (*.nene-records.com / *.ayane.co.jp).
set -uo pipefail

PROJECT=nene-records-sectest
APP_PORT=18092
DB_PORT=13318
B="http://localhost:${APP_PORT}"
JWT='sectest-jwt-secret-32chars-minimum!!'   # throwaway test secret — no production meaning
PW='SecTest-Passw0rd-2026!'
WORK="$(mktemp -d)"
pass=0; fail=0

repo_root() { git rev-parse --show-toplevel 2>/dev/null || pwd; }
cd "$(repo_root)" || exit 1

cleanup() { docker compose -p "$PROJECT" down -v >/dev/null 2>&1; rm -rf "$WORK"; }
trap cleanup EXIT

check() { # name expected actual
  if [ "$2" = "$3" ]; then echo "  ✅ $1 ($3)"; pass=$((pass+1));
  else echo "  ❌ $1 (expected $2, got $3)"; fail=$((fail+1)); fi
}
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }

echo "== boot isolated instance =="
NENE_RECORDS_PORT=$APP_PORT NENE_RECORDS_MYSQL_PORT=$DB_PORT APP_DEBUG=false \
  NENE2_LOCAL_JWT_SECRET="$JWT" NENE2_MACHINE_API_KEY='sectest-machine-key' \
  docker compose -p "$PROJECT" up -d --build app mysql >/dev/null 2>&1
for i in $(seq 1 40); do [ "$(code "$B/health")" = "200" ] && break; sleep 3; done

echo "== seed orgs / admins / data =="
for slug in default ayane; do
  docker compose -p "$PROJECT" exec -T \
    -e NENE_INSTALL_ADMIN_EMAIL="admin-${slug}@sectest.local" -e NENE_INSTALL_ADMIN_PASSWORD="$PW" \
    -e NENE_INSTALL_ORG_SLUG="$slug" app php tools/install.php >/dev/null 2>&1
done
# resolved org is driven by ORG_SLUG from the repo .env (compose auto-loads it).
RESOLVED=$(docker compose -p "$PROJECT" exec -T app sh -c 'echo $ORG_SLUG' | tr -d '\r')
curl -s -c "$WORK/match.txt"  -o /dev/null -X POST "$B/api/v1/auth/login" -H 'Content-Type: application/json' -H 'X-Requested-With: 1' -d "{\"email\":\"admin-${RESOLVED}@sectest.local\",\"password\":\"$PW\"}"
OTHER=default; [ "$RESOLVED" = default ] && OTHER=ayane
curl -s -c "$WORK/other.txt" -o /dev/null -X POST "$B/api/v1/auth/login" -H 'Content-Type: application/json' -H 'X-Requested-With: 1' -d "{\"email\":\"admin-${OTHER}@sectest.local\",\"password\":\"$PW\"}"
curl -s -b "$WORK/match.txt" -o /dev/null -X POST "$B/api/v1/webhooks" -H 'Content-Type: application/json' -H 'X-Requested-With: 1' \
  -d '{"url":"https://x.example/h","events":["entity.created"],"secret":"whsec_TEST","is_active":true}'

echo "== F-01: unauthenticated admin GET must be 401 =="
for p in /api/v1/webhooks /api/v1/entities /api/v1/entities/export /api/v1/text-fields /api/v1/notification-channels; do
  check "unauth GET $p" 401 "$(code "$B$p")"
done
check "public GET /api/v1/public/settings stays open" 200 "$(code "$B/api/v1/public/settings")"
check "GET /health stays open" 200 "$(code "$B/health")"

echo "== F-02: cross-tenant must be 403 =="
check "other-org cookie -> resolved org webhooks" 403 "$(code -b "$WORK/other.txt" "$B/api/v1/webhooks")"
JWT_OTHER=$(grep -oE 'nene_session[[:space:]]+ey[A-Za-z0-9._-]+' "$WORK/other.txt" | awk '{print $2}')
check "other-org JWT bearer replay" 403 "$(code -H "Authorization: Bearer $JWT_OTHER" "$B/api/v1/webhooks")"
check "same-org admin still works" 200 "$(code -b "$WORK/match.txt" "$B/api/v1/webhooks")"

echo "== CSRF / superadmin / JWT =="
check "cookie POST w/o X-Requested-With" 403 "$(code -b "$WORK/match.txt" -X POST "$B/api/v1/webhooks" -H 'Content-Type: application/json' -d '{}')"
check "non-superadmin -> superadmin route" 403 "$(code -b "$WORK/match.txt" "$B/api/v1/superadmin/organizations")"
check "unauth -> superadmin route" 401 "$(code "$B/api/v1/superadmin/organizations")"
NONE="$(printf '{"typ":"JWT","alg":"none"}' | base64 -w0 | tr '+/' '-_' | tr -d '=').$(printf '{"sub":"a","exp":9999999999}' | base64 -w0 | tr '+/' '-_' | tr -d '=')."
check "JWT alg:none" 401 "$(code -H "Authorization: Bearer $NONE" "$B/api/v1/webhooks")"

echo "== F-03: version-disclosure headers absent =="
hdr=$(curl -s -D - -o /dev/null "$B/health")
echo "$hdr" | grep -qi '^X-Powered-By:' && { echo "  ❌ X-Powered-By present"; fail=$((fail+1)); } || { echo "  ✅ X-Powered-By absent"; pass=$((pass+1)); }
echo "$hdr" | grep -qiE '^Server: Apache/[0-9]' && { echo "  ❌ Server discloses version"; fail=$((fail+1)); } || { echo "  ✅ Server version suppressed"; pass=$((pass+1)); }

echo
echo "== RESULT: $pass passed, $fail failed =="
[ "$fail" -eq 0 ]
