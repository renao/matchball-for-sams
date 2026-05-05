# Checklist: Iterations-Qualitätsgates

**Wann:** Nach jedem Feature-Deep-Dive  
**Wer:** Validator + Security-Reviewer + Product Owner

---

## Pre-Deep-Dive-Gate

Bevor Deep-Dive startet:

- [ ] Feature-Rohentwurf existiert (aus Kickoff)?
- [ ] Alle Stakeholder sind informiert?
- [ ] Blockadiert ein äußeres Risiko (z.B. API-Unklarheit)?

**Wenn Nein zu einem:** Deep-Dive verschieben bis Blockade gelöst

---

## Exit-Deep-Dive-Gate

Nach Phase 4 (Abnahme), vor Freigabe:

### Vollständigkeits-Checkliste

- [ ] Zielbild vollständig? (Nutzer, Workflow, Nutzen)
- [ ] Regeln dokumentiert? (Was ist verbindlich?)
- [ ] Datenfelder definiert? (Feldliste + API-Mapping)
- [ ] Fehlerfälle geplant? (Fallback für jeden Fall)
- [ ] AC alle testbar? (Messwerte, Pass/Fail)
- [ ] Nicht-Ziele klar? (Was ist bewusst ausgeschlossen?)

**Wenn ein Block Nein:** Rücksprung in Block, nochmal durcharbeiten

### Konflikt-Check

- [ ] Keine widersprüchlichen Anforderungen?
- [ ] Alle Entscheidungen dokumentiert?

**Wenn Konflikte:** Entscheidungsmatrix + Owner-Decision

### Security-Check

- [ ] API-Key-Handling ausreichend?
- [ ] Admin-Zugriff kontrolliert?
- [ ] Input-Validierung geplant?
- [ ] Keine High-Risks offen?

**Wenn High-Risk:** Security-Plan erforderlich, oder Feature blockiert

---

## Pre-Freigabe-Gate

Unmittelbar vor Product Owner-Freigabe:

- [ ] Validator: Freigegeben? (Ja/Nein)
- [ ] Security: Bestanden? (Ja/Nein/Conditional)
- [ ] Restpunkte dokumentiert? (Liste + Priorität)
- [ ] Umsetzungsschaetzung vorhanden? (Story-Points/Tage)

**Output:** Freigabe-Bericht

---

## Freigabe-Gate (Product Owner)

Freigabe erteilt WENN:

- ✓ Alle Exit-Checklisten bestanden
- ✓ Validator & Security freigegeben
- ✓ Restpunkte dokumentiert
- ✓ Kein kritisches Risiko offen

**Output:** Freigabe-Siegel mit Datum (14 Tage gültig)

**Bei Ablehnung:** Rücksprung in Deep-Dive, Fehler beheben

---

## Post-Freigabe-Tracking

Nach Freigabe:

- [ ] Spezifikation versioniert in Git
- [ ] Interview-Log in `/artifacts/requirements_logs/`
- [ ] Freigabe-Datum dokumentiert
- [ ] Revalidierungs-Trigger definiert

---

**Durchführung:** Mit Validator, Security, Owner Agents
