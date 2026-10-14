# MVP Abnahme-Checkliste

Manueller Test-Plan für Standings und Results Blöcke. Führt diese Tests durch, bevor das Plugin in Produktion geht.

---

## Teil 1: Setup

### 1.1 Plugin aktivieren
- [ ] Plugin im WordPress Admin aktivieren (`Plugins → Matchball - Standings & Tables for SAMS`)
- [ ] Admin-Notice über API-Key-Verschlüsselung sichtbar?

### 1.2 Datenquelle konfigurieren
- [ ] Zu `Settings → SAMS Sources` navigieren
- [ ] Basis-URL und API-Key eintragen
- [ ] Mindestens eine Liga/Saison in API verfügbar?
- [ ] Speichern und API-Verbindung prüfen

---

## Teil 2: Standings Block – Happy Path

### 2.1 Block hinzufügen
- [ ] Neue Seite erstellen
- [ ] Block hinzufügen: `SAMS Standings`
- [ ] Editor zeigt Block-Vorschau?

### 2.2 Block konfigurieren
- [ ] Datenquelle auswählen
- [ ] Saison auswählen
- [ ] Liga auswählen
- [ ] Editor zeigt korrekte Werte in der Preview?

### 2.3 Frontend-Rendering
- [ ] Seite speichern und im Frontend öffnen
- [ ] Tabelle wird angezeigt?
- [ ] Spalten: Rank, Team, Played, Balls (scored:conceded + diff), Points
- [ ] Punkte sind rechtsbündig?
- [ ] "Zuletzt aktualisiert: [Datum/Zeit] Uhr" unter Tabelle sichtbar?

### 2.4 Team-Hervorhebung (optional)
- [ ] Team-Filter im Block-Editor setzen
- [ ] Tabelle zeigt ausgewähltes Team mit Highlight (blauer Hintergrund)?

---

## Teil 3: Results Block – Happy Path

### 3.1 Block hinzufügen
- [ ] Neuen Block `SAMS Results` auf derselben Seite hinzufügen
- [ ] Block konfigurieren: Quelle, Saison, Liga auswählen

### 3.2 Frontend-Rendering
- [ ] Seite im Frontend öffnen
- [ ] Match-Cards werden angezeigt?
- [ ] Format: `[Team1]  [Punktzahl/vs]  [Team2]`
- [ ] Meta-Zeile unter Card: Datum · Zeit · Ort (falls vorhanden)
- [ ] "Zuletzt aktualisiert" Hinweis unter Ergebnisliste sichtbar?

### 3.3 Spielstatus
- [ ] Vergangene Spiele zeigen Satzpunkte
- [ ] Zukünftige Spiele zeigen nur "vs" (kein Ergebnis)
- [ ] `includeFuture` Toggle deaktiviert (default) → nur vergangene Spiele?
- [ ] `includeFuture` Toggle aktiviert → auch zukünftige Spiele?

---

## Teil 4: Edge Cases – API-Fehler & Leere Daten

### 4.1 API-Fehler simulieren
- [ ] Basis-URL im Block temporär auf ungültige URL ändern (z.B. http://invalid.example.com)
- [ ] Seite im Frontend laden
- [ ] Fehler-Notice wird angezeigt: „Standings unavailable" / „Results unavailable"?
- [ ] Notice ist lesbar und nicht aufdringlich?

### 4.2 Leere Daten (keine Rankings/Matches)
- [ ] Liga wählen, für die keine Daten verfügbar sind
- [ ] Seite laden
- [ ] Notice wird angezeigt: „No standings data available for this selection" / „No results available"?

### 4.3 Fehlende Team-Namen
- [ ] (Falls API leere `team1Description` / `team2Description` liefert)
- [ ] Match-Card zeigt stattdessen: `Unbekannt vs Unbekannt`?

### 4.4 Unvollständige Metadaten (Results)
- [ ] Match ohne Ort-Daten → Ort-Teil wird nicht angezeigt (nur Datum/Zeit)
- [ ] Match ohne Zeit → nur Datum und Ort angezeigt
- [ ] Alle Komponenten fehlen → keine Meta-Zeile?

---

## Teil 5: Cron-Synchronisierung

### 5.1 Cron-Event registriert?
- [ ] WP-CLI: `wp cron event list | grep matchball_sync_cron`
- [ ] Event wird angezeigt mit Hook `matchball_sync_cron` und Interval `matchball_every_five_minutes`?

### 5.2 Manuell ausführen
- [ ] WP-CLI: `wp cron test --hook=matchball_sync_cron`
- [ ] Kein Fehler?
- [ ] Logs zeigen erfolgreiche Ausführung oder nur bei Problemen?

### 5.3 Datenfresh nach 5 Minuten
- [ ] Notizt die aktuelle Zeit aus „Zuletzt aktualisiert" (z.B. 14:20)
- [ ] Wartet 5-6 Minuten
- [ ] Seite neu laden (F5)
- [ ] Zeitstempel aktualisiert? (z.B. 14:25 oder später)

---

## Teil 6: API-Key Verschlüsselung

### 6.1 Key wird verschlüsselt
- [ ] Admin-Settings öffnen
- [ ] API-Key neuer eintragen
- [ ] Database prüfen (z.B. mit phpMyAdmin oder WP-CLI):
   ```bash
   wp option get matchball_sources --format=json | grep api_key
   ```
- [ ] Key beginnt mit `matchballenc:v1:` (verschlüsselt)?

### 6.2 Key wird bei Admin-Edit maskiert
- [ ] Zu `Settings → SAMS Sources` zurück
- [ ] API-Key-Feld ist leer (Password-Eingabe zeigt nicht die existierenden Chars)?
- [ ] Speichern ohne neuen Key einzutragen → alter Key bleibt erhalten (unsichtbar)?

---

## Teil 7: Mehrere Blöcke auf einer Seite

### 7.1 Performance
- [ ] Seite mit 2 Standings + 1 Results Block laden
- [ ] Alle Blöcke rendern korrekt?
- [ ] Keine Duplicates oder Fehler in den Daten?
- [ ] „Zuletzt aktualisiert" Zeitstempel sind jeweils aktuell?

### 7.2 Editor-Performance
- [ ] Alle Dropdowns laden ohne Verzögerung (Shared Cache funktioniert)?
- [ ] Liga-Namen werden korrekt aufgelöst (nicht nur UUIDs angezeigt)?

---

## Teil 8: Responsive & Accessibility

### 8.1 Mobile View
- [ ] Standings-Tabelle scrollbar auf Smartphone (keine Überläufe)?
- [ ] Match-Cards lesbar auf Mobile?
- [ ] „Zuletzt aktualisiert" ist auf Mobile sichtbar?

### 8.2 Accessibility (optional)
- [ ] Tabelle hat `<caption>` für Screen Reader?
- [ ] Match-Card-Elemente sind semantisch sinnvoll tagget?
- [ ] Farbkontraste ausreichend (WCAG AA)?

---

## Teil 9: Admin Experience

### 9.1 Einstellungs-Seite
- [ ] Neue Quellen-Zeile kann hinzugefügt werden
- [ ] Speichern funktioniert
- [ ] Success-Notice angezeigt

### 9.2 Drag & Drop (optional)
- [ ] Wenn Sortierfunktion vorhanden: funktioniert sie?

---

## Test-Ergebnis

Alle Tests durchlaufen:
- [ ] Ja, alle bestanden
- [ ] Nein, folgende Probleme gefunden:

**Probleme:**
```
[Hier auflisten]
```

**Getestete Version:**
- Plugin Version: ____
- WordPress Version: ____
- PHP Version: ____
- Tester: ____
- Datum: ____

---

## Sign-Off

- [ ] Testplan vollständig durchlaufen
- [ ] Alle kritischen Tests (2, 3, 5) bestanden
- [ ] Freigegeben für MVP-Release

**Genehmigung durch:** ____________________  
**Datum:** ____________________
