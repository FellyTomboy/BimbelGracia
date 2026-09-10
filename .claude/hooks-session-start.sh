#!/bin/bash
# SessionStart: Tampilkan learned solutions dari memory
MEMORY_DIR="$HOME/.claude/projects/-home-bimbelgr-apps-BimbelGracia/memory"

if [ -d "$MEMORY_DIR" ]; then
  echo ""
  echo "=== Learned Solutions (from past sessions) ==="

  # Cari file memory yang berisi bug fix / error resolution
  COUNT=0
  for f in "$MEMORY_DIR"/*.md; do
    [ -f "$f" ] || continue
    # Skip MEMORY.md index
    [ "$(basename "$f")" = "MEMORY.md" ] && continue

    # Cek apakah ini file bug fix/error
    if grep -q -E "type: bug|type: error|type: fix" "$f" 2>/dev/null; then
      COUNT=$((COUNT + 1))
      echo ""
      echo "File: $(basename "$f")"
      # Tampilkan deskripsi + resolusi singkat
      DESC=$(grep -A1 "^description:" "$f" 2>/dev/null | tail -1 | sed 's/^  //')
      echo "  -> $DESC"
    fi
  done

  if [ $COUNT -eq 0 ]; then
    echo "  (belum ada bug fix tercatat)"
  fi
  echo ""
fi
