# Glossar & Datenfelder-Referenz

**Version:** 1.0  
**Datum:** 05.05.2026

---

## Fachbegriffe (Domäne: Sport/Liga)

| Begriff | Definition | Beispiel |
|---------|-----------|---------|
| **Liga** | Organisatorischer Zusammenschluss von Teams/Vereinen | "Bundesliga", "Champions League" |
| **Saison** | Zeitraum eines Spieljahres | "2025/26", "2024/25" |
| **Team** | Einzelner Verein oder Mannschaft | "FC Bayern", "Real Madrid" |
| **Spieltag** | Numbering/Ordnungsnummer der Spielrunde | 1, 2, 3, ..., 34 |
| **Ergebnis** | Abgelaufenes Spiel mit Endstand | "Bayern 3:2 Dortmund" |
| **Spielplan** | Kommende/zukünftige Spiele (Fixture List) | Termine für Spieltag 10-12 |
| **Tabelle** | Rangfolge der Teams nach Ligaregelwerk | Platz 1-18 mit Punkten |
| **Heimteam** | Team, das Heimspiel hat | "Bayern" (wenn Heimspiel) |
| **Auswärtsteam** | Team, das auswärts spielt | "Dortmund" (wenn Auswärtsspiel) |
| **Endstand** | Finales Spielergebnis | "3:2" |
| **Punkte** | Bewertung nach Liga-Regelwerk | Typisch: 3 Punkte für Sieg, 1 für Unentschieden |

---

## WordPress-Integrationsbegriffe

| Begriff | Definition | Im Plugin |
|---------|-----------|----------|
| **Gutenberg-Block** | Editierbares Element in WordPress 5.8+ | Unser Plugin = 2 Blöcke |
| **Block-Attribut** | Konfigurierbare Eigenschaft eines Blocks | Liga, Saison, Team (Konfiguration) |
| **Transient** | WordPress-Caching mit TTL (automatischer Ablauf) | Cache für Tabellen/Ergebnisse |
| **WP-Cron** | WordPress Pseudo-Cron-Job (triggert auf Seiten-Anfrage) | Sync-Trigger alle 5 Min |
| **i18n** | Internationalisierung (Mehrsprachigkeit) | UI-Texte in Deutsch + Englisch |
| **Option** | WordPress-Konfigurationswert in Datenbank | API-Keys, Settings |
| **Hook** | Andockpunkt für Plugin-Code (Action/Filter) | `sams_sync_cron` Hook |
| **Capability** | WordPress-Berechtigung | `manage_options` (Admin-only) |
| **Sanitizing** | Sicherheitsmaßnahme: Eingaben reinigen | Filter-Werte validieren |

---

## Technische Begriffe

| Begriff | Definition | Im MVP |
|---------|-----------|--------|
| **API** | Application Programming Interface – Schnittstelle zur Datenquelle | Externe Liga-API |
| **HTTP** | Hypertext Transfer Protocol | API-Kommunikation |
| **TTL** | Time To Live – Cache-Gültigkeitsdauer | 5 Minuten |
| **Transient-Key** | Eindeutiger Schlüssel für Cache | z.B. `sams_table_bundesliga_2025_bayern` |
| **Fallback** | Rückfall auf Backup-Daten | Letzter gültiger Stand aus Cache |
| **Stale Data** | Veraltete Daten (aber besser als keine Daten) | Bis max. 24 Stunden alt |
| **Rate-Limiting** | Limitierung von API-Requests pro Zeit | TBD nach API-Discovery |
| **Retry** | Wiederholung bei Fehler | 1 Retry nach kurzer Wartezeit |
| **Timeout** | Maximale Wartezeit für Response | 5 Sekunden |

---

## Datenfeld-Referenz

### Tabelle-Felder (verbindlich)

- `liga` (String): Liga-Name/ID
- `saison` (String): Saison-Jahr, z.B. "2025/26"
- `rang` (Integer): Platzierung 1-N
- `team` (String): Team-Name
- `spiele` (Integer): Anzahl gespielte Spiele
- `punkte` (Integer): Punkte nach Liga-Regelwerk
- *(weitere TBD nach API-Discovery)*

### Ergebnisse-Felder (verbindlich)

- `liga` (String): Liga
- `saison` (String): Saison
- `spieltag` (Integer): Spieltag-Nummer
- `datum` (Date): Spieldatum
- `zeit` (Time): Spielzeit
- `heimteam` (String): Heimteam-Name
- `auswärtsteam` (String): Auswärtsteam-Name
- `endstand` (String): z.B. "3:2"
- *(weitere TBD nach API-Discovery)*

### Spielplan-Felder (historisch, aktuell außer Scope)

- `liga` (String): Liga
- `saison` (String): Saison
- `spieltag` (Integer): Spieltag-Nummer
- `datum` (Date): Geplantes Spieldatum
- `zeit` (Time): Geplante Spielzeit
- `heimteam` (String): Heimteam
- `auswärtsteam` (String): Auswärtsteam
- *(weitere TBD nach API-Discovery)*

---

## Abkürzungen

| Kürzel | Bedeutung | Kontext |
|--------|-----------|---------|
| **MVP** | Minimal Viable Product | Diese Phase |
| **AC** | Akzeptanzkriterium | Testbarkeit |
| **TTL** | Time To Live | Caching |
| **SLO** | Service Level Objective | Performance-Ziel |
| **API** | Application Programming Interface | Externe Quelle |
| **WP** | WordPress | CMS-Plattform |
| **UI** | User Interface | Benutzeroberfläche |
| **UX** | User Experience | Nutzererlebnis |
| **i18n** | Internationalization (i + 18 Zeichen + n) | Mehrsprachigkeit |
| **CRUD** | Create, Read, Update, Delete | Datenbank-Operationen |

---

## Status-Werte

| Status | Bedeutung | Nächster Schritt |
|--------|-----------|---|
| **Entwurf** | Feature wird noch geschärft | Feature-Deep-Dive |
| **In Bearbeitung** | Deep-Dive läuft | Interview durchführen |
| **Validiert** | Validator hat geprüft | Security-Review |
| **Freigegeben** | Product Owner hat freigegeben | Implementierung starten |
| **Implementiert** | Feature ist deployed | Testing & Launch |

---

**Wird während Refinement-Zyklen erweitert mit konkreten API-Feldnamen und Wertebereichen**
