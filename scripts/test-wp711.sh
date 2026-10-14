#!/usr/bin/env bash
set -euo pipefail

# Farbcodes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_DIR="$ROOT_DIR/logs"
TEST_DATE="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="$LOG_DIR/test_wp711_$TEST_DATE.log"

# Log-Verzeichnis erstellen
mkdir -p "$LOG_DIR"

# Header
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN} Matchball - Standings & Tables for SAMS - WordPress 7.1.3 Test Suite${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Docker Umgebungsvariablen laden
if [ -f "$ROOT_DIR/.env" ]; then
    echo -e "${YELLOW}✓ Lade Umgebungsvariablen aus .env${NC}"
    set -a
    # shellcheck disable=SC1091
    source "$ROOT_DIR/.env"
    set +a
fi

# Schritt 1: Docker Umgebungen starten
echo -e "${YELLOW}🐳 Starte Docker Container...${NC}"
cd "$ROOT_DIR"
docker compose down 2>/dev/null || true
docker compose up -d --build

# Warten bis WordPress und Setup fertig sind
echo -e "${YELLOW}⏳ Warte auf WordPress Setup (ca. 60s)...${NC}"
for i in {1..60}; do
    if docker compose exec wpcli wp core is-installed >/dev/null 2>&1 \
       && docker compose exec wpcli wp plugin is-active matchball-for-sams >/dev/null 2>&1; then
        echo -e "${GREEN}✓ WordPress ist bereit und Plugin aktiviert!${NC}"
        break
    fi
    sleep 1
    if [ "$i" -eq 60 ]; then
        echo -e "${RED}✗ Timeout: WordPress/Plugin Setup fehlgeschlagen${NC}"
        echo "   Versuche manuell: docker compose logs wpcli"
        exit 1
    fi
done

# Schritt 2: WordPress Version prüfen
echo -e "${YELLOW}📋 Prüfe WordPress Version...${NC}"
WP_VERSION=$(docker compose exec wpcli wp core version)
echo -e "   Installierte Version: ${GREEN}$WP_VERSION${NC}"
if [[ "$WP_VERSION" == "7.1.3" ]]; then
    echo -e "${GREEN}✓ Richtige WordPress Version!${NC}"
else
    echo -e "${RED}✗ Falsche WordPress Version: $WP_VERSION (erwartet 7.1.3)${NC}"
fi

# Schritt 3: Plugin Check ausführen
echo -e "${YELLOW}🔍 Führe plugin-check aus...${NC}"
PLUGIN_CHECK_LOG="$LOG_DIR/plugin_check_$TEST_DATE.log"
docker compose exec wpcli wp plugin check matchball-for-sams \
    --severity=warning 2>&1 | tee "$PLUGIN_CHECK_LOG" || true

# Schritt 4: PHP Fehler prüfen
echo -e "${YELLOW}🐞 Prüfe PHP Fehler...${NC}"
ERROR_LOG="$LOG_DIR/php_errors_$TEST_DATE.log"
docker compose exec wpcli cat /var/www/html/wp-content/debug.log 2>/dev/null | grep -i "matchball" > "$ERROR_LOG" || true
if [ -s "$ERROR_LOG" ]; then
    echo -e "${RED}✗ PHP Fehler gefunden:${NC}"
    cat "$ERROR_LOG"
else
    echo -e "${GREEN}✓ Keine PHP Fehler gefunden${NC}"
fi

# Schritt 5: Plugin Funktionalität testen
echo -e "${YELLOW}🧪 Teste Plugin Funktionalität...${NC}"
# REST API testen (via HTTP, da wp rest in aktuellen WP-CLI-Versionen nicht mehr enthalten ist)
echo -e "   - Teste REST API Endpunkte..."
if curl -sS -m 10 "http://localhost:${WP_PORT:-8080}/?rest_route=/matchball/v1" 2>/dev/null | grep -q 'matchball'; then
    echo -e "${GREEN}✓ REST API Endpunkte registriert${NC}"
else
    echo -e "${RED}✗ REST API Endpunkte nicht gefunden${NC}"
fi

# Block-Registrierung testen
echo -e "   - Teste Block-Registrierung..."
REGISTERED_BLOCKS=$(docker compose exec wpcli wp eval 'echo implode(",", array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered()));' 2>/dev/null || echo "")
if echo "$REGISTERED_BLOCKS" | grep -q 'matchball/standings' && echo "$REGISTERED_BLOCKS" | grep -q 'matchball/results'; then
    echo -e "${GREEN}✓ Blöcke matchball/standings und matchball/results registriert${NC}"
else
    echo -e "${RED}✗ Blöcke nicht registriert${NC}"
fi

# Plugin Version prüfen
echo -e "   - Teste Plugin Version..."
PLUGIN_VERSION=$(docker compose exec wpcli wp plugin get matchball-for-sams --field=version 2>/dev/null || echo "")
if [ -n "$PLUGIN_VERSION" ]; then
    echo -e "   Plugin Version: ${GREEN}$PLUGIN_VERSION${NC}"
else
    echo -e "${RED}✗ Plugin Version konnte nicht gelesen werden${NC}"
fi

# Schritt 6: Docker Logs speichern
echo -e "${YELLOW}📝 Speichere Docker Logs...${NC}"
docker compose logs > "$LOG_DIR/docker_$TEST_DATE.log" 2>&1 || true

# Schritt 7: Zusammenfassung
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN} Test Zusammenfassung${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "   WordPress Version: $WP_VERSION"
echo -e "   Plugin Version:    $PLUGIN_VERSION"
echo -e "   Log Datei:        $LOG_FILE"
echo -e "   Plugin Check:     $PLUGIN_CHECK_LOG"
echo -e "   PHP Fehler:       $ERROR_LOG"
echo -e "   Docker Logs:      $LOG_DIR/docker_$TEST_DATE.log"
echo -e ""
echo -e "   Alle Logs sind in: ${GREEN}$LOG_DIR/${NC}"
echo -e "${GREEN}========================================${NC}"
