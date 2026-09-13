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
        echo "    Fixing $DEST (was $([ -L "$DEST" ] && echo "broken symlink" || echo "regular file"))"
        rm -f "$DEST"
        ln -s "$TARGET" "$DEST"
    else
        echo "    $DEST already OK"
    fi
done

echo "==> Syncing workbox (non-build assets)..."
# workbox-*.js lives in public/ root (not public/build/), so it needs its own symlink
for f in "$APP_DIR"/public/workbox-*.js; do
    [ -e "$f" ] || continue
    BN=$(basename "$f")
    DEST="$PUBLIC_HTML/$BN"
    if [ ! -L "$DEST" ] || [ ! -e "$DEST" ]; then
        echo "    Fixing $DEST"
        rm -f "$DEST"
        ln -s "$f" "$DEST"
    else
        echo "    $DEST already OK"
    fi
done

echo "==> Clearing Laravel cache..."
php artisan optimize:clear
php artisan cache:clear

echo "==> Done."
