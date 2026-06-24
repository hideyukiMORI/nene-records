#!/usr/bin/env bash
#
# Dump the NeNe Records MySQL database to a timestamped, gzipped file.
#
# Usage:
#   bash scripts/backup-db.sh [BACKUP_DIR]
#
# Overridable env (defaults target the standalone compose.prod.yaml stack):
#   COMPOSE_FILE   compose file to talk to        (default: compose.prod.yaml)
#   DB_SERVICE     mysql service name in compose   (default: mysql)
#   BACKUP_DIR     where dumps are written         (default: ./backups, or $1)
#   KEEP           how many dumps to retain        (default: 14)
#
# Credentials are read from the running db container's own environment
# (MYSQL_USER / MYSQL_PASSWORD / MYSQL_DATABASE), so no secrets are handled here.
#
# Restore a dump:
#   gunzip < backups/<file>.sql.gz \
#     | docker compose -f compose.prod.yaml exec -T mysql \
#         sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-compose.prod.yaml}"
DB_SERVICE="${DB_SERVICE:-mysql}"
BACKUP_DIR="${1:-${BACKUP_DIR:-./backups}}"
KEEP="${KEEP:-14}"

mkdir -p "$BACKUP_DIR"
ts="$(date +%Y%m%d-%H%M%S)"
out="$BACKUP_DIR/nene_records-${ts}.sql.gz"

# --single-transaction: consistent dump without locking InnoDB tables.
docker compose -f "$COMPOSE_FILE" exec -T "$DB_SERVICE" sh -c \
  'exec mysqldump --single-transaction --routines --triggers --no-tablespaces \
     -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > "$out"

# Fail loudly if the dump came out empty (e.g. wrong service / creds).
if [ ! -s "$out" ]; then
  echo "backup FAILED: $out is empty" >&2
  rm -f "$out"
  exit 1
fi

echo "backup written: $out ($(du -h "$out" | cut -f1))"

# Retention: keep the newest $KEEP dumps, delete the rest.
ls -1t "$BACKUP_DIR"/nene_records-*.sql.gz 2>/dev/null | tail -n "+$((KEEP + 1))" | xargs -r rm -f
