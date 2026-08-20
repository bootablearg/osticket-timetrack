#!/bin/bash
#
# Time Tracking plugin for osTicket — installer
#
#   ./install.sh [/path/to/osticket]
#
# Copies the plugin into include/plugins/timetrack/, installs the missing
# scp/apps.php entry point, checks the environment and tells you exactly
# what is left to do. It never touches the database: the plugin creates its
# own table the first time it boots.
#
# GNU GPL v2

set -euo pipefail

SRC="$(cd "$(dirname "$0")" && pwd)"
RED=$'\033[31m'; GRN=$'\033[32m'; YEL=$'\033[33m'; OFF=$'\033[0m'

ok()   { echo "  ${GRN}OK${OFF}    $*"; }
warn() { echo "  ${YEL}CHECK${OFF} $*"; }
die()  { echo "  ${RED}ERROR${OFF} $*" >&2; exit 1; }

# ---------------------------------------------------------------- target
ROOT="${1:-}"
if [ -z "$ROOT" ]; then
    for c in /var/www/osticket /var/www/html/osticket /var/www/html \
             /usr/share/osticket /srv/osticket; do
        [ -f "$c/bootstrap.php" ] && [ -d "$c/include/plugins" ] && { ROOT="$c"; break; }
    done
fi
[ -n "$ROOT" ] || die "Could not find osTicket. Pass the path: ./install.sh /var/www/osticket"
ROOT="${ROOT%/}"

[ -f "$ROOT/bootstrap.php" ] || die "$ROOT does not look like an osTicket install (no bootstrap.php)"
[ -d "$ROOT/include/plugins" ] || die "$ROOT/include/plugins is missing"

echo
echo "osTicket Time Tracking — installing into $ROOT"
echo

# ---------------------------------------------------------------- version
# THIS_VERSION is a literal in an installed copy ('v1.18.4') but an
# expression in a git checkout, so fall back to MAJOR_VERSION.
VERSION="$(sed -n "s/.*define( *'THIS_VERSION' *, *'\([^']*\)'.*/\1/p" "$ROOT/bootstrap.php" 2>/dev/null | head -1 || true)"
[ -n "$VERSION" ] || VERSION="$(sed -n "s/.*define( *'MAJOR_VERSION' *, *'\([^']*\)'.*/\1/p" "$ROOT/bootstrap.php" 2>/dev/null | head -1 || true)"
[ -n "$VERSION" ] && ok "osTicket $VERSION" || warn "could not read the osTicket version; continuing"
case "$VERSION" in
    v1.18*|1.18*) ;;
    "") ;;
    *) warn "this plugin was written and tested against 1.18.x — on $VERSION it may misbehave" ;;
esac

# ---------------------------------------------------------------- php
PHP=""
for c in php php8.2 php8.1 php8.3 php8.4 php7.4; do
    command -v "$c" >/dev/null 2>&1 && { PHP="$c"; break; }
done
if [ -n "$PHP" ]; then
    ok "PHP CLI: $($PHP -r 'echo PHP_VERSION;')"
else
    warn "no PHP CLI found; skipping the syntax check"
fi

# ---------------------------------------------------------------- owner
OWNER="$(stat -c '%U:%G' "$ROOT/include/plugins" 2>/dev/null || echo '')"
[ -n "$OWNER" ] && ok "files will be owned by $OWNER"

# ---------------------------------------------------------------- plugin
DEST="$ROOT/include/plugins/timetrack"
if [ -d "$DEST" ]; then
    warn "$DEST already exists — updating it"
fi
mkdir -p "$DEST"
cp -r "$SRC/plugin/." "$DEST/"
find "$DEST" -type f -exec chmod 644 {} \;
find "$DEST" -type d -exec chmod 755 {} \;
[ -n "$OWNER" ] && chown -R "$OWNER" "$DEST" 2>/dev/null || true
ok "plugin copied to $DEST"

if [ -n "$PHP" ]; then
    while read -r f; do
        $PHP -l "$f" >/dev/null || die "syntax error in $f"
    done < <(find "$DEST" -name '*.php')
    ok "all plugin files parse cleanly"
fi

# ---------------------------------------------------------------- apps.php
# osTicket builds the "Applications" tab pointing at scp/apps.php
# (include/class.nav.php) but never ships that file. Without it the tab 404s
# as soon as any plugin registers a staff app.
APPS="$ROOT/scp/apps.php"
if [ -f "$APPS" ]; then
    if grep -q "timetrack\|Application::getStaffApps" "$APPS" 2>/dev/null; then
        cp "$SRC/scp/apps.php" "$APPS"
        ok "scp/apps.php updated"
    else
        warn "$APPS already exists and is not ours — left untouched."
        warn "  The report lives at /scp/apps.php/timetrack/ and needs that file"
        warn "  to hand the request to scp/apps/dispatcher.php."
    fi
else
    cp "$SRC/scp/apps.php" "$APPS"
    chmod 644 "$APPS"
    [ -n "$OWNER" ] && chown "$OWNER" "$APPS" 2>/dev/null || true
    ok "scp/apps.php installed (osTicket references it but does not ship it)"
fi

# ---------------------------------------------------------------- web server
echo
echo "One thing to check"
warn "PHP must receive PATH_INFO: the report is served as"
warn "  /scp/apps.php/timetrack/ and the REST API as /api/<endpoint>."
warn "  Apache handles this out of the box. On nginx it depends on the vhost —"
warn "  a 'URL not supported' 400 from the API, or a blank report page, means"
warn "  it is being dropped. The block to paste is in docs/nginx.md."

echo
echo "Done. What is left, in the admin panel:"
echo "  1. Admin Panel -> Manage -> Plugins -> Add New Plugin -> Time Tracking"
echo "  2. Install it, then open it and add an instance (that is what enables it)"
echo "  3. Optional: Manage -> Ticket Queues -> your queue -> Columns"
echo "     -> add the 'Time logged' annotation"
echo
echo "The ost_timetrack table is created automatically on first boot."
echo "Optional core patch for the built-in CSV export: core-patch/apply.sh"
echo
