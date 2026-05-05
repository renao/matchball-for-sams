# ANFORDERUNGEN: WordPress Plugin Tabellen/Ergebnisse/Spielpläne

**Status:** Anforderungsphase abgeschlossen (05.05.2026)  
**Bereit für:** Refinement-Zyklen mit KI-Agenten

---

## Executive Summary

### Produktziel MVP

**Automatisierte, verlässliche Anzeige von Tabellen, Ergebnissen und Spielplänen aus externer API-Quelle.**

Redakteure im WordPress-Backend konfigurieren Gutenberg-Blöcke mit Liga, Saison und Team. Daten werden automatisch alle 5 Minuten synchronisiert. Bei API-Fehlern wird der letzte gültige Stand mit Hinweisbanner angezeigt.

### Zielgruppe

**Primär:** Redakteure im WordPress-Backend  
Sie konfigurieren und warten die Blöcke im Backend-Editor.

**Sekundär:** Website-Besucher  
Sie sehen aktuelle, verifizierte Daten ohne technische Hürden.

### MVP-Scope In

- ✓ Externer API-Abruf
- ✓ 5-Minuten-Caching (WordPress Transients, TTL 5 Min)
- ✓ Gutenberg-Block-Ausgabe (3 separate Blöcke)
- ✓ Mehrere Ligen und Saisons (pro Block konfigurierbar)
- ✓ Fallback-Anzeige bei API-Fehlern (letzter Stand + Banner)

### MVP-Scope Out

- ✗ Eigene Spielplan-Generierung
- ✗ CSV-Import/Export
- ✗ Advanced-Analytics-Historie
- ✗ Frontend-Live-Ticker
- ✗ Echtzeit-Sekundenupdates

---

## Globale Qualitätsmetriken

| Metrik | Zielwert | Messdefinition |
|--------|----------|---|
| **Render-SLO** | ≤ 2.5 s | 95. Perzentil pro Block-Render im Frontend |
| **Fallback-SLO** | ≤ 5 s | Zeit bis sichtbares Hinweisbanner + Datenstand |
| **Sync-Erfolgsrate** | ≥ 97% pro Tag | Erfolgreiche Cron-Läufe / geplante Läufe je Quelle |
| **Datenaktualität** | ≤ 5 Minuten | TTL 5 Min, WP-Cron 5-Min-Takt |
| **Stale-Policy** | max. 24 h | Letzter gültiger Stand, danach degradierter Hinweiszustand |

---

## Features

### Feature 1: Tabellen
**Status:** Spec in Bearbeitung  
**Priorität:** P1  
**Datei:** `docs/02_ANFORDERUNGEN_TABELLEN.md`

**Kurzbeschreibung:**  
Block zeigt sortierte Tabelle (Rangfolge) nach Ligaregelwerk. Filter: Liga, Saison, Team. Automatischer Refresh ≤ 5 Min.

**AC (Übersicht):**
- Sortierung folgt Regelwerk der Datenquelle ✓
- Aktualisierung ≤ 5 Minuten ✓
- Filter Liga/Saison/Team ohne Reload ✓
- API-Fehler: letzter Stand + Banner ✓
- Leerer Zustand verständlich erklärt ✓

### Feature 2: Ergebnisse
**Status:** Spec in Bearbeitung  
**Priorität:** P1  
**Datei:** `docs/03_ANFORDERUNGEN_ERGEBNISSE.md`

**Kurzbeschreibung:**  
Block zeigt abgelaufene Spiele mit Endständen. Sortierung nach Spieltag/Datum (reproduzierbar). Fehlende Werte als „N/A".

**AC (Übersicht):**
- Datum/Paarung/Endstand konsistent zur API ✓
- Sortierung nach Spieltag/Datum reproduzierbar ✓
- Fehlende Werte als N/A dargestellt ✓
- API-Fehler: Fallback auf letzten Stand ✓

### Feature 3: Spielpläne
**Status:** Spec in Bearbeitung  
**Priorität:** P1  
**Datei:** `docs/04_ANFORDERUNGEN_SPIELPLAENE.md`

**Kurzbeschreibung:**  
Block zeigt kommende Spiele chronologisch. Datum/Zeit nach WordPress-Lokalisierung.

**AC (Übersicht):**
- Kommende Spiele chronologisch ✓
- Filter Liga/Saison/Team deterministisch ✓
- Datum/Zeit nach WordPress-Lokalisierung ✓
- API-Fehler: letzter Plan mit Banner ✓

---

## Architekturentscheidungen (Phase 5)

### Gutenberg-Integration

**3 separate Blöcke (nicht ein universeller Block):**
- Block 1: Tabellen
- Block 2: Ergebnisse
- Block 3: Spielpläne

**Block-Attribute (konfigurierbar pro Instanz):**
- Liga (Pflicht)
- Saison (Pflicht)
- Team (optional)

**Vorteil:** Klare Trennung, einfacheres Testing, unabhängige Konfiguration.

### Datenhaltung

**WordPress Transients pro Quelle/Liga/Saison:**
- TTL: 5 Minuten
- Key-Schema: `sams_table_{quelle}_{liga}_{saison}_{team}`
- Automatische Cleanup beim Ablauf

**Keine eigenen Plugin-Tabellen** (zu komplex für MVP).

### Synchronisation

**WP-Cron im 5-Minuten-Takt:**
- Trigger: `sams_sync_cron` Hook
- Pro Quelle/Liga/Saison ein separater Cron-Lauf
- Bei Fehler: 1 Retry nach kurzer Wartezeit (kein exponentieller Backoff im MVP)
- Log: Letzter erfolgreicher Sync + Fehler-Kurzursache

### Fehlerbehandlung & Fallback

**Bei API-Fehler:**
1. Letzter gültiger Stand aus Transient wird gezeigt
2. Hinweisbanner: „Daten von [Zeit] – Live-Update fehlgeschlagen"
3. Fallback-Reaktionszeit: ≤ 5 Sekunden
4. Stale-Grenze: max. 24 Stunden, danach degradierter Hinweiszustand

### Sicherheit (Phase 5)

**API-Key-Handhabung:**
- Ablage: WordPress Options API (verschlüsselt/gehärtet)
- Eingabe: Maskiertes Feld im Admin
- Capability: Nur `manage_options` (Admin-Zugriff)
- Logs: Kein Klartext-Secret in Error-Logs
- Encryption: at-rest mit WP-Key-Material (WP_CONTENT_KEY falls vorhanden)

**Input-Validierung:**
- Filter Liga/Saison/Team: Sanitizing + Whitelist-Validierung
- API-Response: Validierung vor Speicherung

### Internationalisierung (i18n)

**WordPress i18n (Textdomain + Übersetzungsdateien):**
- Textdomain: `sams-integration-ki`
- UI-Texte: Alle englisch + Deutsch im MVP
- Datum/Zeit: Nach `wp_get_locale()` (automatisch)
- Datenbezeichnungen: TBD nach API-Discovery

---

## Governance & Prozess

### Informationsquellen (Priorität)

1. **Führende Quelle:** `/docs/02-04_ANFORDERUNGEN_*.md` (Feature-Spezifikationen)
2. **Übersicht:** `ANFORDERUNGEN.md` (diese Datei)
3. **Agenten-Leitfaden:** `.instructions.md`

### Update-Regel

- **Normal:** Ad-hoc nach Feature-Deep-Dive-Zyklen
- **Trigger:** Bei Scope-, API-, Security-, AC-Änderung
- **Versionierung:** Feature-Spezifikationen historisiert, alte Versionen in `/artifacts/`

### Verantwortlichkeiten

- **Pflege:** Product Owner
- **Interviews:** Requirements-Interviewer Agent
- **Validierung:** Spec-Validator Agent
- **Security-Review:** Security-Reviewer Agent

---

## Nächste Schritte (Roadmap)

### Phase: Feature-Deep-Dives (parallel oder sequenziell)

1. **Tabellen-Deep-Dive** (90-120 Min)
   - Konkrete Feldliste + API-Endpunkt klären
   - Sortier-Regeln finalisieren
   - Freigabe & Validator-Report

2. **Ergebnisse-Deep-Dive** (90-120 Min)
   - Analog zu Tabellen

3. **Spielpläne-Deep-Dive** (90-120 Min)
   - Analog zu Tabellen

### Phase: Dependency-Mapping (nach allen Deep-Dives)

- API-Endpunkte konkret dokumentieren
- Rate-Limits & Retry-Strategie finalisieren
- `docs/06_INTEGRATIONS_MAP.md` befüllen

### Phase: Freigabe & Übergabe

- Product Owner-Freigabe für jedes Feature
- Übergabe an Implementierungs-Team
- Kickoff: 10-Minuten-Readiness-Check

---

## Kontakte & Eskalation

- **Product Owner:** Projektverantwortlicher
- **Escalation:** Bei Gate-Fehlern nach 2 Rework-Loops

---

**Zuletzt aktualisiert:** 05.05.2026, 09:00 UTC  
**Nächste Revalidierung:** Bei Scope-, API-, Security-, AC-Änderung oder nach 14-Tage-Freigabe-Ablauf
