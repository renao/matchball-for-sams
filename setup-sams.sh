#!/bin/bash

# Setup-Script für WordPress Plugin Anforderungsrahmen
set -e

BASEDIR="/home/renao/devel/sams-integration"

echo "==========================================" 
echo "Setup: Anforderungsrahmen-Struktur"
echo "Basis: $BASEDIR"
echo "==========================================="

# Ordner erstellen
mkdir -p "$BASEDIR/docs"
mkdir -p "$BASEDIR/agents"
mkdir -p "$BASEDIR/prompts"
mkdir -p "$BASEDIR/interactive-workflows"
mkdir -p "$BASEDIR/artifacts/requirements_logs"
mkdir -p "$BASEDIR/artifacts/feature_matrices"
mkdir -p "$BASEDIR/artifacts/generated_specs"

# .gitignore für artifacts
cat > "$BASEDIR/artifacts/.gitignore" << 'EOF'
*
!.gitignore
EOF

# .instructions.md
cat > "$BASEDIR/.instructions.md" << 'EOF'
# Workspace Instructions: Anforderungsrahmen für WordPress-Plugin

## Rollenmodell (5 Agenten)
1. Requirements-Interviewer
2. Spec-Validator
3. Security-Reviewer
4. Dev-Assistant
5. Test-Strategist

## Standard-Workflow
Interviewer → Validator → Security → Dev → Test

## Freigabeprozess
Ein Feature ist umsetzbar wenn:
- Alle Akzeptanzkriterien Pass
- Keine High-Risk-Items offen
- Security bestanden
- Restpunkte dokumentiert/priorisiert
- Technische Umsetzungsschätzung vorhanden

Freigabe: Product Owner (14 Tage gültig)

*Detaillierte Leitlinien siehe ANFORDERUNGEN.md*
EOF

# ANFORDERUNGEN.md (gekürzt für Brevität, full version siehe setup-script.sh)
cat > "$BASEDIR/ANFORDERUNGEN.md" << 'EOF'
# ANFORDERUNGEN: WordPress Plugin

Status: Anforderungsphase abgeschlossen, bereit für Refinement-Zyklen

## Executive Summary
- Produktziel: Automatisierte Anzeige von Tabellen, Ergebnissen, Spielplänen
- Zielgruppe: Redakteure im WordPress-Backend
- MVP-Scope: externe API, 5-Min-Cache, Gutenberg-Blöcke, Multi-Liga/Saison, Fallback
- Globale SLOs: Render ≤2.5s, Fallback ≤5s, Sync ≥97%/Tag

Siehe ANFORDERUNGEN.md im Workspace für vollständige Version.
EOF

# docs-Dateien (Templates)
cat > "$BASEDIR/docs/01_PROJEKT_BRIEF.md" << 'EOF'
# Projektbrief: WordPress-Plugin Tabellen/Ergebnisse/Spielpläne

## Vision & Ziele
Schaffung eines verlässlichen WordPress-Plugins für Tabellen, Ergebnisse und Spielpläne aus externen APIs.

## Zielgruppe
Redakteure im WordPress-Backend (Konfiguration), Website-Besucher (Anzeige).

## Erfolgskriterien
1. Funktional: Alle 3 Features nach AC umgesetzt
2. Performance: Render ≤2.5s, Fallback ≤5s
3. Zuverlässigkeit: Sync ≥97% pro Tag
4. Security: API-Keys geschützt, Admin kontrolliert
5. Wartbarkeit: Anforderungen testbar, Code dokumentiert

*Detailliert siehe /memories/session/handoff.md*
EOF

cat > "$BASEDIR/docs/02_ANFORDERUNGEN_TABELLEN.md" << 'EOF'
# Feature-Spezifikation: Tabellen

Status: In Bearbeitung

## 1. Zielbild & Nutzerkontext
[Wird gefüllt im Feature-Deep-Dive]

## 2. Fachliche Regeln & Ausnahmen
[Wird gefüllt im Feature-Deep-Dive]

## 3. Datenfelder & API-Mapping
[Wird gefüllt im Feature-Deep-Dive]

## 4. Fehlerfälle & Fallback
[Wird gefüllt im Feature-Deep-Dive]

## 5. Akzeptanzkriterien
- [ ] Sortierung folgt Regelwerk der Datenquelle
- [ ] Aktualisierung ≤ 5 Minuten
- [ ] Filter Liga/Saison/Team ohne Reload
- [ ] API-Fehler: letzter Stand + Banner
- [ ] Leerer Zustand verständlich

## 6. Nicht-Ziele
- Keine Echtzeit-Updates
- Keine CSV-Export
- Keine Analytics
EOF

cat > "$BASEDIR/docs/03_ANFORDERUNGEN_ERGEBNISSE.md" << 'EOF'
# Feature-Spezifikation: Ergebnisse

Status: In Bearbeitung

[Analog zu 02_ANFORDERUNGEN_TABELLEN.md]
EOF

cat > "$BASEDIR/docs/04_ANFORDERUNGEN_SPIELPLAENE.md" << 'EOF'
# Feature-Spezifikation: Spielpläne

Status: In Bearbeitung

[Analog zu 02_ANFORDERUNGEN_TABELLEN.md]
EOF

cat > "$BASEDIR/docs/05_GLOSSAR.md" << 'EOF'
# Glossar & Datenfelder

| Begriff | Definition |
|---------|-----------|
| Liga | Organisatorischer Zusammenschluss von Teams |
| Saison | Zeitraum (z.B. 2025/26) |
| Team | Einzelner Verein/Mannschaft |
| Transient | WP-Caching mit TTL |
| WP-Cron | WordPress Pseudo-Cron |

[Wird erweitert während Refinement-Zyklen]
EOF

cat > "$BASEDIR/docs/06_INTEGRATIONS_MAP.md" << 'EOF'
# Integrations-Map: Externe APIs

Status: In Bearbeitung – wird im Feature-Deep-Dive gefüllt

## Externe Datenquellen
- Tabellen-Endpunkt: TBD
- Ergebnisse-Endpunkt: TBD
- Spielpläne-Endpunkt: TBD

[Detailliert nach API-Discovery]
EOF

# Agenten-Dateien
cat > "$BASEDIR/agents/.agent.md" << 'EOF'
# Agent-Hub: Anforderungsrahmen-Workflow

## Rollen & Ablauf

Interviewer → Validator → Security → Dev → Test

Freigabe: Product Owner (14 Tage gültig)

Siehe einzelne Agent-Dateien für Details.
EOF

for agent in requirements-interviewer spec-validator security-reviewer dev-assistant test-strategist; do
  cat > "$BASEDIR/agents/${agent}.md" << "EOF"
# ${agent}

Input: [TBD]
Output: [TBD]

[Wird gefüllt mit konkreten Workflows]
EOF
done

# Prompt-Templates
for template in clarify-requirements-template feature-decomposition acceptance-criteria-builder risk-assessment; do
  cat > "$BASEDIR/prompts/${template}.md" << "EOF"
# ${template}

[Wiederverwendbarer Prompt für KI-Agenten]

[Wird gefüllt mit konkretenbsp-Prompts]
EOF
done

# Workflows
cat > "$BASEDIR/interactive-workflows/01_kickoff_workshop.md" << 'EOF'
# Workflow: Kickoff-Workshop

Dauer: ~2 Stunden
Output: Initiale Feature-Rohentwürfe

[Details siehe /memories/session/handoff.md]
EOF

cat > "$BASEDIR/interactive-workflows/02_feature_deep_dive.md" << 'EOF'
# Workflow: Feature-Deep-Dive

Dauer: 3-4 Stunden pro Feature
Reihenfolge: Tabellen → Ergebnisse → Spielpläne

[Details siehe /memories/session/handoff.md]
EOF

cat > "$BASEDIR/interactive-workflows/03_dependency_mapping.md" << 'EOF'
# Workflow: Dependency-Mapping

Ziel: API-Details klären
Output: 06_INTEGRATIONS_MAP.md

[Wird nach Feature-Deep-Dives durchgeführt]
EOF

cat > "$BASEDIR/interactive-workflows/04_iteration_checklist.md" << 'EOF'
# Checklist: Iterations-Qualitätsgates

Pre-Deep-Dive-Gate
Exit-Deep-Dive-Gate
Pre-Freigabe-Gate
Freigabe-Gate

[Details siehe /memories/session/handoff.md]
EOF

echo ""
echo "=========================================="
echo "✓ Struktur erstellt!"
echo "=========================================="
echo ""
echo "Dateien erstellt in: $BASEDIR"
echo ""
echo "Nächste Schritte:"
echo "1. Inhalte in /docs befüllen (deep-dive durchführen)"
echo "2. Agenten-Workflows verbinden"
echo "3. Kickoff-Workshop starten"
echo ""