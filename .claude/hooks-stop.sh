#!/bin/bash
# Stop: Catat bug fix ke memory + auto-commit

MEMORY_DIR="$HOME/.claude/projects/-home-bimbelgr-apps-BimbelGracia/memory"
mkdir -p "$MEMORY_DIR"

# 1. Tampilkan file yang berubah
echo "=== Changed files ==="
git status --short
echo ""

# 2. Deteksi apakah ada keyword fix/bug/error dalam diff
HAS_FIX=$(git diff 2>/dev/null | grep -cE "^(\+|\-).*(fix|bug|error|patch|Fix|Bug|Error|Patch)" || echo "0")

if [ "$HAS_FIX" -gt 0 ] 2>/dev/null; then
  echo "=== Bug fix detected — saving to memory ==="

  # Buat nama file unik
  TIMESTAMP=$(date +%Y%m%d_%H%M%S)

  # Ambil ringkasan dari diff
  DIFF_FILES=$(git diff --stat 2>/dev/null | tail -5)
  DIFF_EXCERPT=$(git diff 2>/dev/null | grep -E "^(\+|\-).*(fix|bug|error)" | head -15)

  # Simpan sebagai memory
  cat > "$MEMORY_DIR/bug_fix_$TIMESTAMP.md" << MEMEOF
---
name: bug_fix_$TIMESTAMP
description: Bug fix recorded automatically
metadata:
  type: bug
---

## Bug/Error

Tanggal: $(date '+%Y-%m-%d %H:%M')

## Files Changed
$DIFF_FILES

## Fix Excerpt
$DIFF_EXCERPT

## Root Cause
(Lihat git diff lengkap untuk detail: \`git log --oneline -10\`)

## Resolution
Resolved in session $(date '+%Y-%m-%d %H:%M')

---
MEMEOF

  echo "Saved: $MEMORY_DIR/bug_fix_$TIMESTAMP.md"
else
  echo "(no explicit fix keywords found in diff — skipping memory save)"
fi

# 3. Auto-commit
echo ""
echo "=== Committing ==="
git add -A && git commit -m "$(date '+%Y-%m-%d %H:%M') - updated via Claude" || echo "Nothing to commit"
