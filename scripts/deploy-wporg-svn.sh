#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="matchball-for-sams"
SVN_URL=""
SVN_WORK_DIR="$ROOT_DIR/artifacts/wporg-svn"
RELEASE_ZIP=""
ASSETS_DIR="$ROOT_DIR/wordpress-org-assets"
COMMIT_MESSAGE=""
DO_COMMIT=false
SYNC_ASSETS=true
SVN_USERNAME="${WPORG_SVN_USERNAME:-}"
SVN_PASSWORD="${WPORG_SVN_PASSWORD:-}"
NON_INTERACTIVE="${WPORG_SVN_NON_INTERACTIVE:-false}"

usage() {
  cat <<EOF
Usage:
  ./scripts/deploy-wporg-svn.sh [options]

Options:
  --slug <slug>             WordPress.org plugin slug (default: matchball-for-sams)
  --svn-url <url>           Full SVN URL (default: https://plugins.svn.wordpress.org/<slug>)
  --work-dir <path>         Local SVN working copy path (default: artifacts/wporg-svn/<slug>)
  --zip <path>              Release zip path (default: artifacts/releases/<slug>-<version>.zip)
  --assets-dir <path>       WordPress.org assets source dir (default: wordpress-org-assets)
  --no-assets               Do not sync assets directory
  --username <name>         SVN username (or use WPORG_SVN_USERNAME)
  --password <pass>         SVN password (or use WPORG_SVN_PASSWORD)
  --non-interactive         Disable prompts for CI pipelines
  --commit                  Perform svn commit (default: dry-run only)
  --message <text>          SVN commit message (default: Release <version>)
  -h, --help                Show this help

Behavior:
  1) Ensures release zip exists (builds it if missing)
  2) Checks out or updates the plugin SVN working copy
  3) Syncs extracted plugin files into trunk/
  4) Syncs same release into tags/<version>/
  5) Optionally syncs WordPress.org assets into assets/
  6) Shows svn status and optionally commits

Pipeline example:
  WPORG_SVN_USERNAME=youruser WPORG_SVN_PASSWORD=yourpass \
  ./scripts/deploy-wporg-svn.sh --non-interactive --commit --message "Release <version>"
EOF
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1"
    exit 1
  fi
}

VERSION_FROM_PLUGIN="$(awk -F': ' '/^ \* Version:/ {print $2; exit}' "$ROOT_DIR/matchball-for-sams.php" | tr -d '[:space:]')"
if [[ -z "$VERSION_FROM_PLUGIN" ]]; then
  echo "Could not read plugin version from matchball-for-sams.php"
  exit 1
fi

while [[ $# -gt 0 ]]; do
  case "$1" in
    --slug)
      PLUGIN_SLUG="$2"
      shift 2
      ;;
    --svn-url)
      SVN_URL="$2"
      shift 2
      ;;
    --work-dir)
      SVN_WORK_DIR="$2"
      shift 2
      ;;
    --zip)
      RELEASE_ZIP="$2"
      shift 2
      ;;
    --assets-dir)
      ASSETS_DIR="$2"
      shift 2
      ;;
    --no-assets)
      SYNC_ASSETS=false
      shift
      ;;
    --username)
      SVN_USERNAME="$2"
      shift 2
      ;;
    --password)
      SVN_PASSWORD="$2"
      shift 2
      ;;
    --non-interactive)
      NON_INTERACTIVE=true
      shift
      ;;
    --commit)
      DO_COMMIT=true
      shift
      ;;
    --message)
      COMMIT_MESSAGE="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1"
      usage
      exit 1
      ;;
  esac
done

if [[ -z "$SVN_URL" ]]; then
  SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"
fi

if [[ "$SVN_WORK_DIR" == "$ROOT_DIR/artifacts/wporg-svn" ]]; then
  SVN_WORK_DIR="$SVN_WORK_DIR/$PLUGIN_SLUG"
fi

if [[ -z "$RELEASE_ZIP" ]]; then
  RELEASE_ZIP="$ROOT_DIR/artifacts/releases/${PLUGIN_SLUG}-${VERSION_FROM_PLUGIN}.zip"
fi

if [[ -z "$COMMIT_MESSAGE" ]]; then
  COMMIT_MESSAGE="Release ${VERSION_FROM_PLUGIN}"
fi

require_cmd svn
require_cmd rsync
require_cmd unzip

if [[ ! -f "$RELEASE_ZIP" ]]; then
  echo "Release zip not found, building: $RELEASE_ZIP"
  "$ROOT_DIR/scripts/create-release-zip.sh"
fi

if [[ ! -f "$RELEASE_ZIP" ]]; then
  echo "Release zip still missing: $RELEASE_ZIP"
  exit 1
fi

SVN_ARGS=()
if [[ -n "$SVN_USERNAME" ]]; then
  SVN_ARGS+=(--username "$SVN_USERNAME")
fi

if [[ -n "$SVN_PASSWORD" ]]; then
  SVN_ARGS+=(--password "$SVN_PASSWORD")
fi

if [[ "$NON_INTERACTIVE" == "true" ]]; then
  SVN_ARGS+=(--non-interactive '--trust-server-cert-failures=unknown-ca,cn-mismatch,expired,not-yet-valid,other')
fi

TMP_DIR="$(mktemp -d)"
EXTRACT_DIR="$TMP_DIR/extracted"
trap 'rm -rf "$TMP_DIR"' EXIT

mkdir -p "$EXTRACT_DIR"
unzip -q "$RELEASE_ZIP" -d "$EXTRACT_DIR"

PLUGIN_DIR_IN_ZIP="$EXTRACT_DIR/$PLUGIN_SLUG"
if [[ ! -d "$PLUGIN_DIR_IN_ZIP" ]]; then
  echo "Expected folder $PLUGIN_SLUG not found inside zip"
  exit 1
fi

if [[ -d "$SVN_WORK_DIR/.svn" ]]; then
  echo "Updating existing SVN working copy: $SVN_WORK_DIR"
  svn "${SVN_ARGS[@]}" update "$SVN_WORK_DIR"
else
  mkdir -p "$(dirname "$SVN_WORK_DIR")"
  echo "Checking out SVN repo: $SVN_URL"
  svn "${SVN_ARGS[@]}" checkout "$SVN_URL" "$SVN_WORK_DIR"
fi

mkdir -p "$SVN_WORK_DIR/trunk" "$SVN_WORK_DIR/tags" "$SVN_WORK_DIR/assets"

echo "Sync trunk/ from release zip"
rsync -a --delete --exclude '.svn/' "$PLUGIN_DIR_IN_ZIP/" "$SVN_WORK_DIR/trunk/"

echo "Sync tags/${VERSION_FROM_PLUGIN}/ from release zip"
mkdir -p "$SVN_WORK_DIR/tags/$VERSION_FROM_PLUGIN"
rsync -a --delete --exclude '.svn/' "$PLUGIN_DIR_IN_ZIP/" "$SVN_WORK_DIR/tags/$VERSION_FROM_PLUGIN/"

if [[ "$SYNC_ASSETS" == true ]]; then
  if [[ -d "$ASSETS_DIR" ]]; then
    echo "Sync assets/ from: $ASSETS_DIR"
    rsync -a --delete --exclude '.svn/' "$ASSETS_DIR/" "$SVN_WORK_DIR/assets/"
  else
    echo "Assets dir not found, skipping: $ASSETS_DIR"
  fi
else
  echo "Asset sync disabled (--no-assets)"
fi

cd "$SVN_WORK_DIR"

while IFS= read -r line; do
  [[ -z "$line" ]] && continue
  status="${line:0:1}"
  path="${line:8}"

  if [[ "$status" == "?" ]]; then
    svn "${SVN_ARGS[@]}" add --parents "$path"
  elif [[ "$status" == "!" ]]; then
    svn "${SVN_ARGS[@]}" delete "$path"
  fi
done < <(svn "${SVN_ARGS[@]}" status)

echo
echo "SVN status summary:"
svn "${SVN_ARGS[@]}" status

if [[ "$DO_COMMIT" == true ]]; then
  echo
  echo "Committing to SVN"
  svn "${SVN_ARGS[@]}" commit -m "$COMMIT_MESSAGE"
else
  echo
  echo "Dry-run complete. No commit performed."
  echo "To commit, run again with --commit"
fi
