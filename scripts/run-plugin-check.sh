#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_DIR="$ROOT_DIR/logs"
TEST_DATE="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="$LOG_DIR/plugin_check_$TEST_DATE.log"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

mkdir -p "$LOG_DIR"

echo "🔍 Führe plugin-check für Easy SAMS aus..."
echo "   Log: $LOG_FILE"
echo ""

docker compose exec wpcli php /var/www/html/wp-content/plugins/plugin-check/plugin-check.php \
    /var/www/html/wp-content/plugins/easy-sams \
    --no-interaction \
    --severity=warning \
    --format=json 2>&1 | tee "$LOG_FILE"

echo ""
echo -e "${GREEN}✓ plugin-check abgeschlossen!${NC}"
echo "   Ergebnisse: $LOG_FILE"
