#!/bin/bash
set -e

APP_DIR="/home/bimbelgr/apps/BimbelGracia"
PUBLIC_HTML="/home/bimbelgr/public_html"

echo "==> Installing deps..."
cd "$APP_DIR" && npm ci

echo "==> Building assets..."
npm run build

echo "==> Syncing public_html..."
# sw.js lives at public/sw.js (root, not inside build/)
# manifest.json is written manually to public/manifest.json (NOT inside build/)

# Clean up Vite-generated PWA manifest (NOT build/manifest.json — that is the
# Laravel Vite manifest needed by @vite() directive in Blade templates).
rm -f "$APP_DIR/public/build/manifest.webmanifest"

for f in sw.js manifest.json; do
    # Always use public/ root for sw.js and manifest.json.
    DEST="$PUBLIC_HTML/$f"

    if [ ! -L "$DEST" ] || [ ! -e "$DEST" ]; then
        echo "    Fixing $DEST (was $([ -L "$DEST" ] && echo "broken symlink" || echo "regular file"))"
        rm -f "$DEST"
        ln -s "$APP_DIR/public/$f" "$DEST"
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
