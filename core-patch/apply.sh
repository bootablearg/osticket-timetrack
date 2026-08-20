#!/bin/bash
#
# Optional core patch — exposes the plugin's time in osTicket's own CSV
# export of the ticket queue (the "Export" link under a ticket listing).
#
#   ./apply.sh [/path/to/osticket]           apply
#   ./apply.sh --check [/path/to/osticket]   report status only
#   ./apply.sh --revert [/path/to/osticket]  undo
#
# THE PLUGIN DOES NOT NEED THIS. Its own report and CSV work without it.
# Only apply it if you want the built-in queue export to carry the time
# columns as well — and remember to re-run it after every osTicket upgrade.
#
# The REST API needs no patch: osTicket's API validator only logs unknown
# fields, it does not reject them.
#
# GNU GPL v2

set -euo pipefail

SRC="$(cd "$(dirname "$0")" && pwd)"
PATCH="$SRC/timetrack-core.patch"
MODE="apply"

case "${1:-}" in
    --check)  MODE="check";  shift ;;
    --revert) MODE="revert"; shift ;;
esac

ROOT="${1:-}"
if [ -z "$ROOT" ]; then
    for c in /var/www/osticket /var/www/html/osticket /var/www/html \
             /usr/share/osticket /srv/osticket; do
        [ -f "$c/bootstrap.php" ] && { ROOT="$c"; break; }
    done
fi
[ -n "$ROOT" ] || { echo "Pass the osTicket path: $0 /var/www/osticket" >&2; exit 1; }
ROOT="${ROOT%/}"
[ -f "$ROOT/bootstrap.php" ] || { echo "$ROOT is not an osTicket install" >&2; exit 1; }

FILES="include/class.queue.php include/class.export.php"
cd "$ROOT"

applied() { patch -p1 --dry-run --reverse --force < "$PATCH" >/dev/null 2>&1; }

if [ "$MODE" = "check" ]; then
    if applied; then echo "APPLIED"; exit 0
    else echo "NOT APPLIED"; exit 1; fi
fi

if [ "$MODE" = "revert" ]; then
    applied || { echo "Nothing to revert."; exit 0; }
    patch -p1 --reverse < "$PATCH"
    echo "Reverted."
    exit 0
fi

if applied; then
    echo "Already applied."
    exit 0
fi

if ! patch -p1 --dry-run < "$PATCH" >/dev/null 2>&1; then
    echo "This patch does not apply cleanly to your osTicket version." >&2
    echo "It is a 7-line change; apply it by hand:" >&2
    echo >&2
    cat "$PATCH" >&2
    exit 1
fi

BACKUP="$SRC/backup-$(date +%Y%m%d%H%M%S)"
mkdir -p "$BACKUP"
for f in $FILES; do cp -p "$f" "$BACKUP/$(basename "$f")"; done
echo "Backup: $BACKUP"

patch -p1 < "$PATCH"

PHP=""
for c in php php8.2 php8.1 php8.3 php8.4; do
    command -v "$c" >/dev/null 2>&1 && { PHP="$c"; break; }
done
if [ -n "$PHP" ]; then
    for f in $FILES; do
        $PHP -l "$f" >/dev/null || { echo "Syntax error in $f — restore from $BACKUP" >&2; exit 1; }
    done
fi

OWNER="$(stat -c '%U:%G' include/class.queue.php 2>/dev/null || echo '')"
[ -n "$OWNER" ] && chown "$OWNER" $FILES 2>/dev/null || true

echo "Applied and verified. Re-run this after every osTicket upgrade."
