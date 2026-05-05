# Feature-Spezifikation: Ergebnisse

**Status:** In Bearbeitung  
**Letzter Update:** 05.05.2026  
**Owner:** Product Owner

---

## 1. Zielbild & Nutzerkontext

**Nutzer:** Redakteur im WordPress-Backend

**Workflow:**
1. Redakteur erstellt Block „Ergebnisse"
2. Konfiguriert: Datenquelle, Saison, Liga, Team (optional)
3. Block zeigt abgelaufene Spiele mit Endständen
4. Redakteur wählt Datenquelle + Saison + Liga; ggf. filtern nach Team
5. Automatischer Refresh ≤ 5 Minuten

**Nutzen:**
- Besucher sehen Historie vergangener Spiele
- Vollständiger Überblick über bisherige Saisonegg

---

## 2. Fachliche Regeln & Ausnahmen

- **Ergebnisse = Abgelaufene Spiele:** Nur Spiele mit Endstand anzeigen
- **Sortierung:** Reproduzierbar (z.B. nach Spieltag DESC oder Datum DESC)
- **Fehlende Werte:** Zeige „N/A" statt leeres Feld (z.B. wenn Torschützen nicht erfasst)
- **Keine Eigenberechnung:** Daten 1:1 von API

---

## 3. Datenfelder & API-Mapping

Endpoint für Ergebnisse

1. GET /league-matches
2. Header: X-Api-Key
3. Query: for-season (Pflicht), for-league (Pflicht), for-team (optional), association (optional), page, size

Pflichtfelder aus LeagueMatchDto

1. uuid -> Match-ID
2. date -> Datum
3. time -> Zeit
4. team1Description -> Heimteam
5. team2Description -> Auswärtsteam
6. matchNumber -> Spielnummer
7. leagueUuid -> Liga-Referenz
8. matchDayUuid -> Spieltag-Referenz

Ergebnisfelder

1. results.setPoints -> primärer Endstand
2. results.ballPoints -> Fallback-Endstand
3. results.winner -> optional
4. results.winnerName -> optional

Optionale Zusatzfelder

1. verified
2. location.name
3. indefinitelyRescheduled

Sortier- und Sichtregeln

1. Standardansicht: vergangene Spiele, Datum absteigend
2. Option Zukünftige Spiele anzeigen: standardmäßig aus
3. Bei gemischter Anzeige: rein chronologisch

---

## 4. Fehlerfälle & Fallback

| Fehlerfall | Fallback |
|---|---|
| API nicht erreichbar | Letzter gültiger Stand + Banner |
| Keine Ergebnisse für Filter | Verständliche Meldung: „Noch keine Ergebnisse" |
| Fehlende Einzelwerte (Torschützen) | „N/A" anzeigen |

Bei HTTP 403 (Authfehler), 409 (ungültige Request-Daten) und 500 (Serverfehler) gilt: Fallback auf den letzten gültigen Stand gemäß [docs/06_INTEGRATIONS_MAP.md](docs/06_INTEGRATIONS_MAP.md).

---

## 5. Akzeptanzkriterien

### AC1: Daten konsistent zur API
**Datum, Paarung, Endstand entsprechen exakt der API-Quelle**

### AC2: Sortierung reproduzierbar
**Ergebnisse sind nach Spieltag/Datum konsistent sortiert (z.B. immer neueste oben)**

### AC3: Fehlende Werte als N/A
**Wenn ein Feld in API leer ist → „N/A" zeigen, nicht leeres Feld**

### AC4: Fallback bei API-Fehler
**Bei Fehler → letzter gültiger Stand mit Banner**

---

## 6. Nicht-Ziele

- ✗ Keine Torschützen-Details/Statistiken
- ✗ Keine Spielberichte
- ✗ Kein Export

---

**Nächster Schritt:** Feature-Deep-Dive durchführen
