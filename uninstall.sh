#!/bin/bash
#
# Time Tracking plugin for osTicket — uninstaller
#
#   ./uninstall.sh [/path/to/osticket]
#
# Removes the plugin files and reverts the optional core patch.
# It does NOT drop ost_timetrack: your logged time stays where it is.
#
# GNU GPL v2

set -euo pipefail
SRC="$(cd "$(dirname "$0")" && pwd)"

ROOT="${1:-}"
if [ -z "$ROOT" ]; then
    for c in /var/www/osticket /var/www/html/osticket /var/www/html \
             /usr/share/osticket /srv/osticket; do
        [ -f "$c/bootstrap.php" ] && { ROOT="$c"; break; }
    done
fi
[ -n "$ROOT" ] || { echo "Pass the osTicket path: $0 /var/www/osticket" >&2; exit 1; }
ROOT="${ROOT%/}"

echo "Before removing the files, uninstall the plugin from"
echo "Admin Panel -> Manage -> Plugins, or osTicket will keep a dangling"
echo "registry row it cannot delete afterwards."
read -r -p "Done that? [y/N] " a
[ "$a" = "y" ] || [ "$a" = "Y" ] || { echo "Aborted."; exit 1; }

if [ -x "$SRC/core-patch/apply.sh" ]; then
    "$SRC/core-patch/apply.sh" --revert "$ROOT" || true
fi

rm -rf "$ROOT/include/plugins/timetrack"
echo "Plugin files removed."

if [ -f "$ROOT/scp/apps.php" ] && grep -q "Application::getStaffApps" "$ROOT/scp/apps.php" 2>/dev/null; then
    echo
    echo "$ROOT/scp/apps.php was installed by this plugin and is left in place:"
    echo "other plugins that register staff apps need it. Delete it by hand if"
    echo "you are sure nothing else uses it."
fi

echo
echo "Your time entries are still in the ost_timetrack table. To drop them:"
echo "  DROP TABLE ost_timetrack;"
