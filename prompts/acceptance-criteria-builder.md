# Prompt-Template: AC-Builder (Akzeptanzkriterien schärfen)

Hilft bei Formulierung testbarer, messbarer Akzeptanzkriterien.

---

## Checkliste für jedes AC

- [ ] Ist der Satz konkret (nicht: „schön", „angemessen", „gut")?
- [ ] Hat das AC messbare Werte (Zahlen, Ja/Nein, Vergleiche)?
- [ ] Ist es automatisiert testbar ODER manuell reproduzierbar?
- [ ] Ist es Business-Anforderung ODER technisches Detail?
- [ ] Ist die Pass/Fail-Bedingung eindeutig?

---

## Anti-Pattern ❌ → Pattern ✓

### Anti-Pattern 1
❌ „Die Tabelle sieht gut aus"  
✓ „Die Sortierung folgt dem Regelwerk: Punkte absteigend, danach Tordifferenz"

### Anti-Pattern 2
❌ „Schneller Response"  
✓ „95. Perzentil Block-Render ≤ 2.5 Sekunden (gemessen am Frontend)"

### Anti-Pattern 3
❌ „Fehlerbehandlung funktioniert"  
✓ „Bei API-Fehler wird Fallback-Banner sichtbar ≤ 5 Sekunden"

### Anti-Pattern 4
❌ „Daten sind aktuell"  
✓ „Anzeige aktualisiert sich spätestens alle 5 Minuten (TTL-Check + Cron-Log)"

### Anti-Pattern 5
❌ „Redakteur kann filtern"  
✓ „Filter Liga/Saison/Team ändern ohne Seite zu laden (AJAX/JS-Test)"

---

## Test-Template pro AC

```
## AC: [Titel]

**Beschreibung:** [Was soll getestet werden]

**Messung:** [Wie wird es gemessen? Automatisch oder manuell?]

**Pass-Kriterium:** [Explizit: Was bedeutet Pass?]

**Fail-Kriterium:** [Explizit: Was bedeutet Fail?]

**Testfall:** [Schritt-für-Schritt Anleitung zum Testen]
  1. [Vorbedingung]
  2. [Aktion]
  3. [Erwartetes Ergebnis]
  4. [Assert/Prüfung]
```

---

**Verwendung:** Requirements-Interviewer & Validator verwenden dies zur AC-Finalisierung.
