# ANFORDERUNGEN: WordPress Plugin Tabellen/Ergebnisse (MVP)

**Status:** MVP-Scope finalisiert (06.05.2026)  
**Bereit für:** Release und Betrieb

---

## Executive Summary

### Produktziel MVP

**Automatisierte, verlässliche Anzeige von Tabellen und Ergebnissen aus externer API-Quelle.**

Redakteure im WordPress-Backend konfigurieren Gutenberg-Blöcke mit Liga, Saison und Team. Daten werden automatisch alle 5 Minuten synchronisiert. Bei API-Fehlern wird der letzte gültige Stand mit Hinweisbanner angezeigt.

### Zielgruppe

**Primär:** Redakteure im WordPress-Backend  
Sie konfigurieren und warten die Blöcke im Backend-Editor.

**Sekundär:** Website-Besucher  
Sie sehen aktuelle, verifizierte Daten ohne technische Hürden.

### MVP-Scope In

- ✓ Externer API-Abruf
- ✓ 5-Minuten-Caching (WordPress Transients, TTL 5 Min)
- ✓ Gutenberg-Block-Ausgabe (2 separate Blöcke: Standings, Results)
- ✓ Mehrere Ligen und Saisons (pro Block konfigurierbar)
- ✓ Fallback-Anzeige bei API-Fehlern (letzter Stand + Zuletzt-aktualisiert Hinweis)

### MVP-Scope Out

- ✗ Spielpläne (Schedules Block) – für Phase 2 geplant
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
- Fehlende Werte als "Unbekannt" dargestellt ✓
- API-Fehler: Fallback auf letzten Stand ✓

### Feature 3: Spielpläne (Phase 2)
**Status:** Out-of-MVP-Scope, für Phase 2 geplant  
**Priorität:** P2  
**Datei:** `docs/04_ANFORDERUNGEN_SPIELPLAENE.md`

**Kurzbeschreibung:**  
Block zeigt kommende Spiele chronologisch. Wird nach MVP-Release implementiert.

---

## Architekturentscheidungen (Phase 5)

### Gutenberg-Integration

**2 separate Blöcke (nicht ein universeller Block):**
- Block 1: Tabellen (Standings)
- Block 2: Ergebnisse (Results)

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
2. Subtiler Hinweis: „Zuletzt aktualisiert: [Zeit]" unter Block
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
- Textdomain: `matchball-for-sams`
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

## WordPress.org Release Anforderungen

### Pflichtartefakte

- Plugin-Hauptdatei mit vollständigem Header (Name, Version, Requires, PHP, License, Text Domain)
- `readme.txt` im WordPress.org-Format (Contributors, Tags, Stable Tag, Changelog)
- GPL-kompatible Lizenzdatei im Projekt (empfohlen: GPL-2.0-or-later)
- Vollständiger, lauffähiger Plugin-Code ohne Build-Abhängigkeiten im Release-Paket

### Compliance-Gates vor Einreichung

- Keine Obfuskation, keine versteckten externen Code-Downloads
- Keine Telemetrie ohne explizites Opt-in
- Nur sparsame, dismissible Admin-Notices
- Keine geheimen Schlüssel im Repository oder in Ausgaben/Logs
- Versionen konsistent: Plugin-Header Version == readme Stable Tag == Release-Zip Name

### WordPress.org Listing-Artefakte (SVN assets)

- Optional aber empfohlen: Banner `banner-772x250.png` + `banner-1544x500.png`
- Optional aber empfohlen: Icons `icon-128x128.png` + `icon-256x256.png`
- Optional: Screenshots `screenshot-1.png`, `screenshot-2.png`, ... passend zur Readme-Beschreibung
- Lokale Vorbereitung im Repo unter `wordpress-org-assets/` möglich, aber nicht Teil des Plugin-ZIPs

### Release-Entscheidung (Go/No-Go)

1. Readme-Validator ohne Fehler bestanden
2. Manuelle MVP-Abnahme vollständig bestanden
3. Cron-Sync in Produktiv-ähnlicher Umgebung verifiziert
4. Security-Selbstcheck ohne offene High-Risk-Befunde
5. Release-Paket lokal installiert und Smoke-Test bestanden

### WordPress.org Einreichungs-Checkliste

#### Vor der ersten Einreichung

1. Plugin-Version in `matchball-for-sams.php` prüfen (`Version: 3.0.0`)
2. `Stable tag` in `readme.txt` auf dieselbe Version prüfen
3. `Contributors` in `readme.txt` mit echtem WordPress.org-Username prüfen
4. `readme.txt` durch den offiziellen Readme-Validator laufen lassen
5. Release-ZIP mit `./scripts/create-release-zip.sh` neu erzeugen
6. ZIP lokal in einer sauberen WordPress-Instanz installieren und aktivieren
7. Standings-Block und Results-Block einmal vollständig durchtesten
8. Admin-Seite für Quellen speichern, API-Key-Handling und Cron-Verhalten prüfen

#### Nach Plugin-Freigabe durch WordPress.org

1. Von WordPress.org zugewiesenen Plugin-Slug dokumentieren
2. SVN-Repository auschecken (`trunk`, `assets`, `tags`)
3. Inhalt des Release-ZIPs nach `trunk/` kopieren
4. WordPress.org-Assets aus `wordpress-org-assets/` nach `assets/` kopieren
5. Optional Screenshots ergänzen und mit `readme.txt` abgleichen
6. `trunk/readme.txt` und Plugin-Hauptdatei final gegen Version prüfen
7. Ersten stabilen Release zusätzlich unter `tags/3.0.0/` ablegen
8. SVN-Dry-Run über `./scripts/deploy-wporg-svn.sh` ausführen und `svn status` prüfen
9. SVN-Commit über `./scripts/deploy-wporg-svn.sh --commit --message "Release 3.0.0"` durchführen

#### CI/Pipeline (optional)

1. SVN-Zugangsdaten als Secrets setzen: `WPORG_SVN_USERNAME`, `WPORG_SVN_PASSWORD`
2. Nicht-interaktiven Lauf nutzen: `./scripts/deploy-wporg-svn.sh --non-interactive --commit --message "Release <version>"`
3. Optional ohne Asset-Sync: `./scripts/deploy-wporg-svn.sh --non-interactive --no-assets --commit`

#### Vor jedem Folge-Release

1. Version in Plugin-Header erhöhen
2. `Stable tag` in `readme.txt` erhöhen
3. `== Changelog ==` und `== Upgrade Notice ==` aktualisieren
4. Release-ZIP neu bauen und lokal installieren
5. Änderungen in `trunk/` und neuem `tags/<version>/` veröffentlichen

---

## Nächste Schritte

### MVP ist abgeschlossen
- ✅ Standings Block implementiert und getestet
- ✅ Results Block implementiert und getestet
- ✅ 5-Minuten-Cron-Sync aktiv
- ✅ API-Key-Verschlüsselung implementiert
- ✅ Abnahme-Checkliste bereit

### Betrieb & Release
- Cron-Funktion auf Produktivserver testen (siehe [CRON_TESTING.md](CRON_TESTING.md))
- Manuelle Abnahme durchlaufen (siehe [MVP_ABNAHME_CHECKLISTE.md](MVP_ABNAHME_CHECKLISTE.md))
- Readme-Validator ausführen und Stable-Tag-Version abgleichen
- Release-ZIP bauen und lokal per WordPress-Upload testen
- Freigabe an WordPress Plugin-Verzeichnis (Einreichung)

### Phase 2: Geplante Features (nach MVP-Release)
1. **Spielpläne-Block** (matchball/schedules)
2. **i18n/Internationalisierung** (Deutsch/Englisch)
3. **Performance-Optimierungen** (bei Bedarf)
4. **Advanced Testing** (Unit/Integration Tests)

---

**Zuletzt aktualisiert:** 06.05.2026, 17:00 UTC  
**MVP-Status:** ✅ Ready for Release
