# Feature-Spezifikation: Tabellen

**Status:** In Bearbeitung  
**Letzter Update:** 05.05.2026  
**Owner:** Product Owner

---

## 1. Zielbild & Nutzerkontext

### Nutzer & Kontext

**Nutzer:** Redakteur im WordPress-Backend

**Workflow:**
1. Redakteur erstellt oder bearbeitet eine WordPress-Seite
2. Im Gutenberg-Editor klickt er „Block hinzufügen" und wählt „Tabellen"
3. Block-Konfiguration öffnet sich mit Feldern: Datenquelle, Saison, Liga, Team (optional)
4. Redakteur wählt Datenquelle + Saison + Liga; ggf. filtern nach Team
5. Block zeigt Tabelle (Rangfolge) mit aktuellem Stand
6. Tabelle aktualisiert sich automatisch alle 5 Minuten
7. Bei API-Fehler: Letzter gültiger Stand mit Hinweisbanner statt Fehler

### Nutzen

- Redakteur: Keine manuellen Updates nötig, einfache Konfiguration
- Besucher: Aktuelle, verifizierte Rangfolge ohne technische Hürden

---

## 2. Fachliche Regeln & Ausnahmen

### Verbindliche Regeln

- **Sortierung:** Folgt dem Regelwerk der Datenquelle (z.B. Punkte absteigend, bei Gleichstand: Tordifferenz)
- **Keine Eigenberechnung:** Plugin übernimmt Daten 1:1, keine Korrektur/Anpassung
- **Leere Tabelle:** Wird mit verständlicher Meldung angezeigt (z.B. „Keine Daten für diese Liga/Saison")
- **Filter-Kombinationen:** Müssen eindeutig sein (Liga + Saison definieren die Tabelle)

### Ausnahmen

- Wenn API unerwartete Feldwerte liefert → Fallback auf letzten gültigen Stand
- Wenn API länger als 5 Sekunden nicht antwortet → sofort Fallback

---

## 3. Datenfelder & API-Mapping

Endpoint für Tabellen

1. GET /leagues/{leagueUuid}/rankings
2. Header: X-Api-Key
3. Query: page, size
4. Upstream-Filterkette: Datenquelle -> Saison -> Liga -> optional Team

Pflichtfelder aus LeagueRankingsEntryDto

1. rank -> Rang
2. teamName -> Team
3. matchesPlayed -> Spiele
4. points -> Punkte
5. ballWins + ballLosses -> Erzielte/Kassierte Bälle als Paar (z.B. 812:744)
6. ballDifference -> Differenz in Klammern (z.B. +68)

Erweiterte Felder (optional für Darstellung)

1. setWins
2. setLosses
3. setDifference
4. setRatio
5. ballWins
6. ballLosses
7. ballDifference
8. ballRatio
9. resultTypes

Regel zur Sortierung

1. Rang kommt aus dem Feld rank der Quelle
2. Keine lokale Ersatzsortierung im Plugin
3. Fuehrende Statistik in der Darstellung bleibt Punkte (points)

---

## 4. Fehlerfälle & Fallback

| Fehlerfall | Fallback-Verhalten | Reaktionszeit |
|---|---|---|
| **API nicht erreichbar** | Letzter gültiger Stand + Banner | ≤ 5 s |
| **API Timeout (> 5 s)** | Fallback automatisch | ≤ 5 s |
| **Ungültige Filter-Kombination** | Fehlermeldung: „Diese Liga/Saison existiert nicht" | – |
| **Keine Daten vorhanden** | Verständliche Meldung: „Tabelle für Liga [X] noch nicht verfügbar" | – |
| **API antwortet mit Fehler (4xx/5xx)** | Letzter Stand + Banner mit Fehler-Kurzursache | ≤ 5 s |

Bei HTTP 403 (Authfehler), 409 (ungültige Request-Daten) und 500 (Serverfehler) gilt: Fallback auf den letzten gültigen Stand gemäß [docs/06_INTEGRATIONS_MAP.md](docs/06_INTEGRATIONS_MAP.md).

### Fallback-Banner-Beispiel

```
⚠️ Daten von [12:30 Uhr] – Live-Update fehlgeschlagen. 
Nächster Versuch in 5 Minuten.
```

### Stale-Policy

- Letzter gültiger Stand wird maximal **24 Stunden** ohne Warnung angezeigt
- Nach 24 Stunden: Warnung wird noch deutlicher oder Daten werden nicht mehr angezeigt

---

## 5. Akzeptanzkriterien (AC)

### AC1: Sortierung nach Regelwerk
**Beschreibung:** Tabelle ist nach dem Regelwerk der Datenquelle sortiert  
**Messung:** Manuell prüfen oder Unit-Test  
**Pass-Kriterium:** Rang-Spalte zeigt 1, 2, 3, ... oder Punkte absteigend  
**Testfall:** Tabelle mit bekanntem Datensatz, Vergleich gegen API-Quelle

### AC2: Aktualisierung ≤ 5 Minuten
**Beschreibung:** Anzeige aktualisiert sich spätestens alle 5 Minuten  
**Messung:** WP-Cron Logs + Transient TTL prüfen  
**Pass-Kriterium:** TTL = 5 Min, Cron-Lauf erfolgreich  
**Testfall:** Cron triggern, Transient auf TTL 5 Min prüfen, nach 4:59 noch gültig, nach 5:01 aktualisiert

### AC3: Filter ohne Reload
**Beschreibung:** Filter Liga/Saison/Team greifen ohne Seiten-Reload  
**Messung:** Frontend-Test mit Ajax/JS-Monitoring  
**Pass-Kriterium:** Tabelle wechselt, keine Seite lädt neu  
**Testfall:** Filter ändern → Assert Tabelleninhalt ändert sich, URL bleibt gleich (oder nur Query-String)

### AC4: API-Fehler zeigt Fallback
**Beschreibung:** Bei API-Fehler wird letzter gültiger Stand + Banner angezeigt (≤ 5 s)  
**Messung:** Frontend + Error-Logs prüfen  
**Pass-Kriterium:** Banner sichtbar, Daten vorhanden, Reaktionszeit ≤ 5 s  
**Testfall:** API blockieren/deaktivieren → Fallback-Banner erscheint

### AC5: Leerer Zustand verständlich
**Beschreibung:** Wenn keine Daten existieren, wird verständliche Meldung angezeigt  
**Messung:** Frontend-Sichtprüfung  
**Pass-Kriterium:** Text ist klar (z.B. „Keine Daten für diese Liga")  
**Testfall:** Filter-Kombination wählen, die keine Daten hat → Meldung prüfen

---

## 6. Nicht-Ziele (MVP)

- ✗ Keine Echtzeit-Sekundenupdates (5-Min-Takt ist Standard)
- ✗ Keine CSV-Export-Funktion
- ✗ Keine Inline-Bearbeitung von Daten durch Redakteur
- ✗ Keine Custom-Spalten oder Berechnung von Zwischenwerten
- ✗ Keine Analytics oder Reporting

---

## Glossar (Feature-spezifisch)

| Begriff | Definition |
|---------|-----------|
| Transient | WordPress-Caching-Mechanismus mit TTL (automatischer Ablauf) |
| WP-Cron | WordPress Pseudo-Cron (triggert auf Seiten-Anfrage, nicht echtes Cron) |
| TTL | Time To Live – Gültigkeitsdauer des Cache |
| API-Fehler | HTTP 4xx/5xx oder Timeout |
| Fallback | Fallback auf letzten gültigen Stand aus Cache |

---

**Nächster Schritt:** Feature-Deep-Dive durchführen, konkrete API-Details klären
