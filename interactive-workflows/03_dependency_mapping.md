# Workflow: Dependency-Mapping (Phase 4.3)

**Dauer:** 60-90 Minuten (nach allen Feature-Deep-Dives)  
**Trigger:** Wenn alle 3 Feature-Deep-Dives abgeschlossen sind  
**Output:** Vollständige `docs/06_INTEGRATIONS_MAP.md`

---

## Ziel

Externe Abhängigkeiten und API-Details konkretisieren.

---

## Ablauf

### Schritt 1: API-Datenquellen identifizieren (15 min)

- Welche externe API liefert Tabellen-Daten?
- Welche liefert Ergebnisse?
- Welche liefert Spielpläne?
- Sind es 1, 2 oder 3 verschiedene APIs?

### Schritt 2: Pro Quelle: Endpunkte dokumentieren (45 min)

**Für jede API:**

1. **Basis-Infos:**
   - URL
   - HTTP Method (GET/POST/etc.)
   - Authentication (API-Key, Bearer, OAuth)
   - Rate-Limits (z.B. 100 req/min)
   - Response-Format (JSON/XML)

2. **Pro Feature:**
   - Endpunkt-URL
   - Erforderliche Query-Parameter (liga, saison, etc.)
   - Feldmapping (API-Feld → Plugin-Feld)
   - Fehler-Codes & Meanings

3. **Error-Handling:**
   - HTTP 4xx/5xx Codes
   - Timeouts
   - Retry-Strategie

### Schritt 3: Rate-Limits & Sync-Strategie validieren (15 min)

- Passen Rate-Limits zu 5-Minuten-Sync?
- Brauchen wir Request-Aggregation?
- Oder müssen wir Sync-Intervall erhöhen?

### Schritt 4: Fallback & Stale-Policy finalisieren (15 min)

- Stale-Grenze: 24 Stunden (bestätigen/anpassen)
- Fehlerfall-Matrix dokumentieren

---

## Output in `06_INTEGRATIONS_MAP.md`

- [ ] Alle Endpunkte dokumentiert
- [ ] Rate-Limits bekannt
- [ ] Feldmappings konkret
- [ ] Error-Szenarien geplant
- [ ] Fallback-Strategie final

---

**Durchführung:** Mit Product Owner + ggf. externem API-Team
