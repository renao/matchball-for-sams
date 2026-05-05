# Workflow: Feature-Deep-Dive (Phase 4.2)

**Dauer:** 3-4 Stunden pro Feature (insgesamt 1-2 Tage für alle 3)  
**Teilnehmer:** Product Owner, Requirements-Interviewer, Spec-Validator, Security-Reviewer  
**Output:** Freigabereife Feature-Spezifikation

---

## Standard-Reihenfolge

1. Feature 1: Tabellen (90-120 min)
2. Feature 2: Ergebnisse (90-120 min)
3. Feature 3: Spielpläne (90-120 min)

(Können auch sequenziell an verschiedenen Tagen sein)

---

## Ablauf pro Feature

### Phase 1: Delta-Review (15 min)

**Ziel:** Kickoff-Rohentwurf nochmal durchgehen, offene Punkte identifizieren

- Rohentwurf nochmal lesen
- Was hat sich seit Kickoff ergibt?
- Was braucht Klärung?

### Phase 2: Frageblöcke durcharbeiten (90 min)

**Requirements-Interviewer stellt Fragen, Product Owner antwortet detailliert**

**Block-Reihenfolge:**

1. **Zielbild & Nutzerkontext** (15 min)
   - Nochmal Anwendungsfall konkretisieren
   - Redakteur-Workflow detaillieren

2. **Fachliche Regeln & Ausnahmen** (15 min)
   - Verbindliche vs. optionale Logik
   - Randfälle durchdenken

3. **Datenfelder & API-Mapping** (20 min)
   - Konkrete Feldliste finalisieren
   - API-Endpunkt + Feldmapping klären
   - Ggf. mit externen API-Docs abstimmen

4. **Fehlerfälle & Fallback** (15 min)
   - Was alles kann schiefgehen?
   - Fallback-Verhalten pro Fehlerfall
   - Stale-Policy finalisieren

5. **Akzeptanzkriterien – MESSBAR formulieren** (15 min)
   - Jedes AC muss testbar sein
   - Anti-Pattern-Checks durchführen
   - Messwerte spezifizieren

6. **Nicht-Ziele** (10 min)
   - Klar abgrenzen, was NICHT im MVP ist

### Phase 3: Konfliktauflösung (20 min)

**Validator prüft Specifications:**

- Alle Blöcke vollständig gefüllt?
- Konflikte zwischen Anforderungen?
- Sind AC testbar?

**Bei Konflikten:**
- Entscheidungsmatrix erstellen (Pro/Con je Variante)
- Product Owner trifft finale Entscheidung

### Phase 4: Security-Check (10 min)

**Security-Reviewer:**

- API-Key-Handling ausreichend?
- Admin-Zugriff kontrolliert?
- Input-Validierung geplant?

### Phase 5: Abnahme (15 min)

**Validator-Checkpoint:**

- [ ] Alle 6 Pflichtblöcke vorhanden?
- [ ] Keine High-Risk-Items offen?
- [ ] AC alle testbar?
- [ ] Security bestanden?

**Wenn Ja:** Spezifikation ist **freigabebereit**  
**Wenn Nein:** Rücksprung in betroffenen Block (max. 2 Loops)

---

## Exit-Kriterien

Ein Feature darf den Deep-Dive verlassen WENN:

- ✓ Alle 6 Pflichtblöcke sind detailliert gefüllt
- ✓ Keine High-Risk-Items (offen)
- ✓ AC sind alle messbar und testbar
- ✓ Security-Check bestanden
- ✓ Spec ist versioniert und in Git commit

---

## Wenn Gate fehlschlägt

→ Rücksprung in den betroffenen Frageblock  
→ Agent notiert: „Fehler in Block X, Grund: Y"  
→ Max. 2 Loops, dann Owner-Eskalation erforderlich

---

**Durchführung:** Mit Interviewer, Validator, Security Agents
