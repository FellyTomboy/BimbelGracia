#!/bin/bash
set -e

APP_DIR="/home/bimbelgr/apps/BimbelGracia"
PUBLIC_HTML="/home/bimbelgr/public_html"

echo "==> Installing deps..."
cd "$APP_DIR" && npm ci

echo "==> Building assets..."
npm run build

echo "==> Syncing public_html..."
for f in sw.js manifest.webmanifest; do
    TARGET="$APP_DIR/public/$f"
    DEST="$PUBLIC_HTML/$f"

    if [ ! -L "$DEST" ] || [ ! -e "$DEST" ]; then
        # Not a valid symlink (broken or regular file) — recreate symlink
        echo "    Fixing $DEST (was $([ -L "$DEST" ] && echo "broken symlink" || echo "regular file"))"
        rm -f "$DEST"
        ln -s "$TARGET" "$DEST"
    else
        # Valid symlink — just ensure it points to correct target
        echo "    $DEST already OK"
    fi
done

echo "==> Clearing Laravel cache..."
php artisan optimize:clear
php artisan cache:clear

echo "==> Done."
