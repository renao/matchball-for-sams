# Prompt-Template: Feature-Zerlegung

Hilft Dev-Assistant, Feature in Implementierungs-Komponenten zu zerlegen.

---

## Template für Componentisierung

```
## Feature: [Feature-Name]

### Komponente A: [Gutenberg-Block Rendering]
Was wird gebaut: Block-UI für Parameter und Ausgabe
- Task A1: Block-Registration
- Task A2: Block-Attribute definieren
- Task A3: RenderCallback implementieren

### Komponente B: [Cache-Handling]
Was wird gebaut: Transient-Management
- Task B1: Cache-Key-Schema
- Task B2: Cache-Schreiben beim Sync
- Task B3: Cache-Lesen beim Block-Render

### Komponente C: [API-Integration]
Was wird gebaut: API-Abruf + Error-Handling
- Task C1: API-Request bauen
- Task C2: Response parsen/validieren
- Task C3: Fehlerbehandlung + Logging

### Komponente D: [WP-Cron]
Was wird gebaut: Synchronisierungs-Job
- Task D1: Cron-Hook registrieren
- Task D2: Cron-Logik für Tabellen
- Task D3: Cron-Logik für Ergebnisse
- Task D4: Cron-Logik für Spielpläne

## Abhängigkeiten

- D (Cron) muss vor Render-Test funktionieren
- C (API) muss vor D (Cron) funktionieren
- B (Cache) kann parallel zu C laufen
- A (Block) kann nach B+C parallel laufen

## Implementierungs-Reihenfolge (Empfehlung)

1. C (API-Integration) – Foundation
2. B (Cache) – Daten persistieren
3. D (Cron) – Automatisierter Abruf
4. A (Block) – UI/UX

## Parallelisierung

- Unit-Tests für C können parallel zu B+D laufen
- Block-Skeleton (A) kann parallel zu C entwickelt werden
```

---

**Verwendung:** Dev-Assistant füllt dies pro Feature aus.
