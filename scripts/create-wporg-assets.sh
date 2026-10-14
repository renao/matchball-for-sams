#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/wordpress-org-assets"
SOURCE="${1:-$OUT_DIR/icon-source.png}"

if command -v magick >/dev/null 2>&1; then
  IM_BIN=(magick)
else
  IM_BIN=(convert)
fi

if [[ ! -f "$SOURCE" ]]; then
  echo "Icon source not found: $SOURCE" >&2
  echo "Provide a square PNG (at least 256x256) as wordpress-org-assets/icon-source.png or pass a path." >&2
  exit 1
fi

"${IM_BIN[@]}" "$SOURCE" -resize 128x128 PNG32:"$OUT_DIR/icon-128x128.png"
"${IM_BIN[@]}" "$SOURCE" -resize 256x256 PNG32:"$OUT_DIR/icon-256x256.png"

cat > "$OUT_DIR/asset-notes.txt" <<'EOF'
WordPress.org assets for this plugin.

Included:
- icon-source.png: original square artwork (source only, not uploaded to wp.org)
- icon-128x128.png / icon-256x256.png: generated from icon-source.png
  via scripts/create-wporg-assets.sh

Banners:
- intentionally omitted (banners are optional on wordpress.org)

Screenshots (optional, add manually):
- screenshot-1.png, screenshot-2.png, ...
- captions come from the numbered list in the "== Screenshots ==" section of readme.txt

Deploy:
- scripts/deploy-wporg-svn.sh syncs this folder to the assets directory in the
  WordPress.org SVN repository
EOF

echo "Generated plugin icons in: $OUT_DIR"
