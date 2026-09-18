#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT_DIR="$ROOT_DIR/wordpress-org-assets"
BG_TOP="#F4F1E8"
BG_BOTTOM="#D9E5D6"
TEXT="#14342B"
ACCENT="#C96C3A"

if command -v magick >/dev/null 2>&1; then
  IM_BIN=(magick)
else
  IM_BIN=(convert)
fi

FONT="Helvetica"
if ! "${IM_BIN[@]}" -list font 2>/dev/null | grep -q "Font: ${FONT}$"; then
  FONT="Arial"
fi
if ! "${IM_BIN[@]}" -list font 2>/dev/null | grep -q "Font: ${FONT}$"; then
  FONT="Times-Roman"
fi

mkdir -p "$OUT_DIR"

make_banner() {
  local width="$1"
  local height="$2"
  local output="$3"

  "${IM_BIN[@]}" -size "${width}x${height}" gradient:"$BG_TOP-$BG_BOTTOM" \
    -fill "$ACCENT" -draw "rectangle 0,$((height-18)) $width,$height" \
    -fill "$TEXT" -font "$FONT" -weight 700 -pointsize $((height/6)) \
    -gravity northwest -annotate +40+34 "Easy SAMS" \
    -font "$FONT" -weight 400 -pointsize $((height/12)) \
    -gravity northwest -annotate +42+$((height/2)) "WordPress blocks for standings and results" \
    -font "$FONT" -weight 400 -pointsize $((height/14)) \
    -gravity southeast -annotate +36+28 "Standings  Results  API Sync" \
    "$output"
}

make_icon() {
  local size="$1"
  local output="$2"
  local radius=$((size/7))
  local stroke=$((size/32))

  "${IM_BIN[@]}" -size "${size}x${size}" xc:none \
    -fill "$BG_TOP" -stroke "$TEXT" -strokewidth "$stroke" \
    -draw "roundrectangle 8,8 $((size-8)),$((size-8)) $radius,$radius" \
    -fill "$ACCENT" -stroke none \
    -draw "rectangle $((size/6)),$((size/2)) $((size*5/6)),$((size*5/8))" \
    -draw "rectangle $((size/4)),$((size*11/32)) $((size*3/4)),$((size*15/32))" \
    -draw "rectangle $((size*11/32)),$((size/5)) $((size*21/32)),$((size*5/16))" \
    -fill "$TEXT" -font "$FONT" -weight 700 -pointsize $((size/4)) \
    -gravity south -annotate +0+16 "SI" \
    "$output"
}

make_banner 772 250 "$OUT_DIR/banner-772x250.png"
make_banner 1544 500 "$OUT_DIR/banner-1544x500.png"
make_icon 128 "$OUT_DIR/icon-128x128.png"
make_icon 256 "$OUT_DIR/icon-256x256.png"

cat > "$OUT_DIR/asset-notes.txt" <<'EOF'
WordPress.org asset files generated locally.

Included:
- banner-772x250.png
- banner-1544x500.png
- icon-128x128.png
- icon-256x256.png

Optional next step:
- add real screenshots as screenshot-1.png, screenshot-2.png, ...
- upload these files to the top-level assets directory in the WordPress.org SVN repository
EOF

echo "Generated WordPress.org assets in: $OUT_DIR"
