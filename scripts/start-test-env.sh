#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

echo "🚀 Starte Testumgebung für Matchball - Standings & Tables for SAMS mit WordPress 7.1.1..."
echo ""

# Docker Container starten
cd "$ROOT_DIR"
docker compose down 2>/dev/null || true
docker compose up -d

# Warten bis WordPress und Setup fertig sind
echo "⏳ Warte auf WordPress Setup..."
for i in {1..60}; do
    if docker compose exec wpcli wp plugin is-active matchball 2>/dev/null; then
        echo -e "${GREEN}✓ WordPress ist bereit und Plugin aktiviert!${NC}"
        break
    fi
    sleep 1
    if [ "$i" -eq 60 ]; then
        echo -e "${RED}✗ Timeout: WordPress konnte nicht gestartet werden${NC}"
        echo "   Versuche manuell: docker compose logs wpcli"
        exit 1
    fi
done

# Sicherstellen, dass der wpcli-Healthcheck erfolgreich ist
if ! docker compose exec wpcli wp core is-installed >/dev/null 2>&1; then
    echo -e "${RED}✗ WordPress-Installation nicht erreichbar${NC}"
    exit 1
fi

# URL anzeigen
echo ""
echo "🌐 WordPress ist verfügbar unter: http://localhost:8080"
echo "🔑 Admin-Zugang:"
echo "   Benutzername: test"
echo "   Passwort:    test"
echo ""
echo "📝 Führe folgende Befehle aus, um Tests durchzuführen:"
echo "   ./scripts/test-wp711.sh          # Vollständiger Test"
echo "   ./scripts/run-plugin-check.sh    # Nur plugin-check"
echo ""
