#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "🚀 Starte Testumgebung für Easy SAMS mit WordPress 7.1.1..."
echo ""

# Docker Container starten
cd "$ROOT_DIR"
docker compose down 2>/dev/null || true
docker compose up -d

# Warten bis WordPress und Setup fertig sind
echo "⏳ Warte auf WordPress Setup..."
for i in {1..60}; do
    if docker compose exec wpcli wp plugin is-active easy-sams 2>/dev/null; then
        echo -e "${GREEN}✓ WordPress ist bereit und Plugin aktiviert!${NC}"
        break
    fi
    sleep 1
    if [ $i -eq 60 ]; then
        echo -e "${RED}✗ Timeout: WordPress konnte nicht gestartet werden${NC}"
        echo "   Versuche manuell: docker compose logs wpcli"
        exit 1
    fi
done

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
