# Integrations-Map: Externe APIs & Abhängigkeiten

Status: Spec-basiert finalisiert (ohne Live-Key)  
Datum: 05.05.2026  
Owner: Product Owner

## Externe Datenquelle

### Datenquelle: SAMS REST API v2.1

1. Basis-URL: https://wvv.sams-server.de/api/v2
2. Authentifizierung: Header X-Api-Key
3. Content-Type: application/hal+json
4. Paging: page und size, Standard size 20, Maximum size 100
5. Optionaler Quellenfilter: association UUID pro Datenquelle im Admin hinterlegbar

## Endpoint-Mapping für MVP

1. Saison-Liste
1. Endpoint: GET /seasons
2. Zweck: Dropdown Saison befüllen

2. Liga-Liste pro Saison
1. Endpoint: GET /leagues
2. Query: season (Pflicht), association (optional), page, size
3. Zweck: Dropdown Liga abhängig von Saison befüllen

3. Team-Liste pro Liga
1. Endpoint: GET /leagues/{uuid}/teams
2. Query: page, size
3. Zweck: Dropdown Team abhängig von Liga befüllen

4. Tabellen-Daten
1. Endpoint: GET /leagues/{uuid}/rankings
2. Query: page, size
3. Zweck: Rangliste für Tabellen-Block

5. Match-Daten für Ergebnisse
1. Endpoint: GET /league-matches
2. Query: for-season (Pflicht), for-league (Pflicht), for-team (optional), association (optional), page, size
3. Zweck: Ergebnis-Block aus Matchquelle speisen

## Feldmapping Kernobjekte

1. Saison aus SeasonDto
1. uuid
2. name
3. startDate
4. endDate
5. currentSeason

2. Liga aus LeagueDto
1. uuid
2. name
3. shortName
4. seasonUuid
5. associationUuid
6. latestResultUpdate
7. latestStructuralUpdate

3. Team aus TeamDto
1. uuid
2. name
3. shortName
4. teamNumber
5. sportsclubUuid
6. associationUuid

4. Tabelle aus LeagueRankingsEntryDto
1. rank
2. teamName
3. matchesPlayed
4. points
5. ballDifference
6. ballWins
7. ballLosses
8. setWins
9. setLosses
10. setDifference
11. setRatio
12. resultTypes (optional)

5. Matches aus LeagueMatchDto
1. uuid
2. date
3. time
4. matchNumber
5. team1Description
6. team2Description
7. results
8. location
9. matchDayUuid
10. leagueUuid
11. seasonUuid
12. associationUuid
13. verified
14. indefinitelyRescheduled
15. delayPossible

## Fehlerbehandlung & Fallback

1. 403: Auth fehlgeschlagen oder API-Key ungültig
2. 409: Ungültige Request-Daten
3. 500: Unerwarteter Serverfehler
4. Timeout > 5 Sekunden: Fallback auf letzten gültigen Stand
5. Retry: 1 Retry im nächsten regulären Sync-Zyklus
6. Stale-Policy: max. 24 Stunden

## Abhängigkeiten & Validierung

1. Vor Freigabe
1. Endpoint-Mapping dokumentiert
2. Query-Filter konsistent über alle Feature-Dateien
3. Feldmapping vollständig
4. Fehlerfälle konsistent dokumentiert

2. Offen bis API-Key
1. Live-Response-Validierung
2. Reale Rate-Limit-Messung
3. Produktionsnahe Lasttests

## Deployment & Betrieb

1. Datenquellen werden ausschließlich durch Admins verwaltet
2. Datenquellenmodell je Quelle:
1. Bezeichnung
2. Basis-URL
3. API-Key
4. optional association UUID
5. Aktiv-Status
6. Default-Quelle
3. Redakteure wählen im Block nur aktive Datenquellen per Name aus