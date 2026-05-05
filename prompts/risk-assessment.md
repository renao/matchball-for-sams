# Prompt-Template: Risiko-Bewertung

Hilft Validator & Security-Reviewer, Risiken zu identifizieren und zu priorisieren.

---

## Risk-Matrix

```
          LOW-RISK    MED-RISK    HIGH-RISK   CRITICAL
Impact    |           |           |           |
High      |           |    X      |    X      |    X
          |           |           |           |
Medium    |     X     |     X     |    X      |
          |           |           |           |
Low       |     X     |     X     |           |
          +---+---+---+---+---+---+---+---+---+
            Low         Medium    High
             Likelihood →
```

---

## Risk-Kategorien & Aktion

### ✓ LOW-RISK
- **Definition:** Niedriges Impact + Wahrscheinlichkeit
- **Aktion:** Dokumentieren, im Hinterkopf behalten
- **Blockiert Freigabe:** Nein

### ⚠️ MED-RISK
- **Definition:** Mittleres Impact oder Wahrscheinlichkeit
- **Aktion:** Mitigation-Plan erforderlich
- **Blockiert Freigabe:** Nein (mit Dokumentation)

### 🔴 HIGH-RISK
- **Definition:** Hohes Impact oder hohe Wahrscheinlichkeit
- **Aktion:** MUSS mitigiert oder eskaliert werden
- **Blockiert Freigabe:** Ja (bis gelöst)

### ⛔ CRITICAL-RISK
- **Definition:** Sicherheit/Compliance gefährdet
- **Aktion:** Sofort-Eskalation erforderlich
- **Blockiert Freigabe:** Ja (indefinit bis gelöst)

---

## Risk-Assessment-Template

```
## Risk: [Titel]

**Impact:** [Was passiert im worst-case?]  
**Likelihood:** [Wie wahrscheinlich ist es?]  
**Risk-Level:** LOW / MED / HIGH / CRITICAL

**Mitigation:** [Wie senken wir das Risiko?]  
**Owner:** [Wer kümmert sich?]  
**Deadline:** [Wann muss es gelöst sein?]
```

---

## Beispiele

### Beispiel 1: API-Ausfallrisiko
- Risk: API nicht verfügbar
- Impact: HIGH (Keine Daten angezeigt)
- Likelihood: MEDIUM (APIs können ausfallen)
- Risk-Level: HIGH → MED (mit Fallback 24h)
- Mitigation: Fallback-Strategie implementieren

### Beispiel 2: API-Key-Leak
- Risk: API-Key wird in Logs ausgegeben
- Impact: CRITICAL (Security)
- Likelihood: LOW (mit Maskierung)
- Risk-Level: CRITICAL → MED (mit Maskierung + Encryption)
- Mitigation: Kein Klartext in Logs

---

**Verwendung:** Security-Reviewer & Validator verwenden dies für Risk-Assessment pro Feature.
