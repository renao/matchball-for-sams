#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="easy-sams"
VERSION="$(awk -F': ' '/^ \* Version:/ {print $2; exit}' "$ROOT_DIR/easy-sams.php" | tr -d '[:space:]')"
OUT_DIR="$ROOT_DIR/artifacts/releases"
STAGING_DIR="$OUT_DIR/${PLUGIN_SLUG}"
ZIP_PATH="$OUT_DIR/${PLUGIN_SLUG}-${VERSION}.zip"
DISTIGNORE_FILE="$ROOT_DIR/.distignore"

if [[ -z "$VERSION" ]]; then
  echo "Could not read plugin version from easy-sams.php"
  exit 1
fi

mkdir -p "$OUT_DIR"
rm -rf "$STAGING_DIR"
mkdir -p "$STAGING_DIR"

rsync -a --delete \
  --exclude-from="$DISTIGNORE_FILE" \
  "$ROOT_DIR/" "$STAGING_DIR/"

rm -f "$ZIP_PATH"
(
  cd "$OUT_DIR"
  zip -rq "$(basename "$ZIP_PATH")" "$PLUGIN_SLUG"
)

rm -rf "$STAGING_DIR"

echo "Created release artifact: $ZIP_PATH"
