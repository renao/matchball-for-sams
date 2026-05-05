# Feature-Spezifikation: Spielpläne

**Status:** In Bearbeitung  
**Letzter Update:** 05.05.2026  
**Owner:** Product Owner

---

## 1. Zielbild & Nutzerkontext

**Nutzer:** Redakteur im WordPress-Backend

**Workflow:**
1. Redakteur erstellt Block „Spielpläne"
2. Konfiguriert: Datenquelle, Saison, Liga, Team (optional)
3. Block zeigt kommende Spiele chronologisch
4. Datum/Zeit nach WordPress-Lokalisierung
5. Automatischer Refresh ≤ 5 Minuten

**Nutzen:**
- Besucher sehen zukünftige Termine
- Können sich Spiele vormerken

---

## 2. Fachliche Regeln & Ausnahmen

- **Spielpläne = Zukünftige Spiele:** Nur kommende/aktuelle Spiele anzeigen
- **Sortierung:** Chronologisch (früheste zuerst)
- **Keine Generierung:** Spielplan wird nicht vom Plugin erstellt, nur angezeigt
- **Keine Ressourcen-Konflikt-Erkennung**

---

## 3. Datenfelder & API-Mapping

Endpoint für Spielpläne

1. GET /league-matches
2. Header: X-Api-Key
3. Query: for-season (Pflicht), for-league (Pflicht), for-team (optional), association (optional), page, size

Pflichtfelder aus LeagueMatchDto

1. uuid -> Match-ID
2. date -> Datum
3. time -> Zeit
4. team1Description -> Heimteam
5. team2Description -> Auswärtsteam
6. leagueUuid -> Liga-Referenz
7. matchDayUuid -> Spieltag-Referenz

Optionale Felder

1. location.name -> Ort
2. matchNumber
3. verified
4. indefinitelyRescheduled
5. delayPossible

Sortier- und Sichtregeln

1. Standardansicht: alle zukünftigen Spiele
2. Option Vergangene Spiele einblenden: optional
3. Bei gleichem Zeitstempel: stabile API-Reihenfolge übernehmen

---

## 4. Fehlerfälle & Fallback

| Fehlerfall | Fallback |
|---|---|
| API nicht erreichbar | Letzter gültiger Plan + Banner |
| Keine kommenden Spiele | Verständliche Meldung: „Keine weiteren Spiele geplant" |

Bei HTTP 403 (Authfehler), 409 (ungültige Request-Daten) und 500 (Serverfehler) gilt: Fallback auf den letzten gültigen Stand gemäß [docs/06_INTEGRATIONS_MAP.md](docs/06_INTEGRATIONS_MAP.md).

---

## 5. Akzeptanzkriterien

### AC1: Chronologische Sortierung
**Kommende Spiele sind von früheste zu später sortiert**

### AC2: Filter deterministisch
**Filter Liga/Saison/Team greifen reproduzierbar und konsistent**

### AC3: WordPress-Lokalisierung
**Datum/Zeit folgen der WordPress-Einstellung (Spracheinheit, Datumsformat)**

### AC4: Fallback bei Ausfall
**Bei API-Fehler → letzter gültiger Plan mit Hinweisbanner**

---

## 6. Nicht-Ziele

- ✗ Keine Spielplan-Generierung durch Plugin
- ✗ Keine Ressourcen-Konflikt-Analyse
- ✗ Keine Änderungen durch Redakteur (Spielplan kommt von API)

---

**Nächster Schritt:** Feature-Deep-Dive durchführen
