# WP-Cron Testing Guide

Das Plugin nutzt WordPress WP-Cron für automatische 5-Minuten-Synchronisierung. Diese Seite erklärt, wie ihr den Cron-Betrieb testet und verifiziert.

## Voraussetzungen

- WordPress mit aktiviertem Plugin (`SAMS Integration`)
- Mindestens eine aktive SAMS-Datenquelle in den Plugin-Settings
- WP-CLI installiert (optional, aber empfohlen)

---

## Methode 1: WP-CLI (empfohlen)

### Cron manuell auslösen
```bash
wp cron test --hook=sik_sync_cron
```

Das führt den Sync-Job sofort aus und zeigt Fehler an, falls vorhanden.

### Cron-Status prüfen
```bash
wp cron test --list
```

Dies zeigt alle geplanten Events. Ihr solltet ein Event mit Hook `sik_sync_cron` und Interval `sik_every_five_minutes` sehen.

### Nächster Lauf anzeigen
```bash
wp cron event list
```

Sucht nach `sik_sync_cron` und prüft die Spalte `Next Run`.

---

## Methode 2: Browser-Debugging (ohne WP-CLI)

### Option 2a: Debug-Logging prüfen

1. Aktiviert in `wp-config.php` Error-Logging:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

2. Das Plugin schreibt Cron-Fehler in `/wp-content/debug.log`:
   ```
   [SIK] Cron sync issue source=sams-v2 stage=seasons code=...
   ```

3. Nach mehreren Seiten-Anfragen sollte der Cron automatisch laufen (WP-Cron triggert bei Page-Requests).

### Option 2b: Loopback-Request erzwingen

WordPress braucht mindestens einen HTTP-Request pro 5 Minuten, um WP-Cron auszulösen.
Manuell lokal triggern:
```bash
curl -I https://euer-wordpress.local/wp-cron.php
```

Dies triggert sofort einen Cron-Run.

---

## Methode 3: Test-Check im Admin

1. Geht auf **Settings → SAMS Sources**.
2. Speichert eine Datenquelle (ändert nichts, nur speichert).
3. Öffnet die Seite mehrmals in verschiedenen Tabs (erzeugt HTTP-Traffic).
4. Wartet ~1-2 Minuten.
5. Prüft `/wp-content/debug.log` auf Cron-Ausführung:
   ```
   [SIK] Cron sync issue source=...
   [INFO] ...
   ```

---

## Erwartetes Verhalten

### Erfolgreicher Cron-Run

1. Das Cron-Event wird gestartet.
2. Jede aktive Datenquelle wird durchlaufen.
3. Für jede Quelle werden Saisons, Ligen, Rankings und Matches abgerufen.
4. Transienten (Cache) werden aktualisiert.
5. Keine Fehler im Log (oder nur transiently bei API-Timeouts).

### Normaler Log-Output (bei WP_DEBUG_LOG)

```
[06-May-2026 15:00:23] Cron job sik_sync_cron started.
[06-May-2026 15:00:30] Cron job sik_sync_cron completed.
```

Bei Fehlern:
```
[06-May-2026 15:05:00] [SIK] Cron sync issue source=sams-v2 stage=leagues code=network_error
```

---

## Troubleshooting

| Problem | Lösung |
|---------|--------|
| **Cron triggert nicht** | WP-Cron braucht HTTP-Traffic; `curl https://site/wp-cron.php` manuell aufrufen oder `define('DISABLE_WP_CRON', false);` prüfen |
| **`Unable to find event sik_sync_cron`** | Plugin nicht aktiviert oder `register_activation_hook` fehlgeschlagen; Plugin deaktivieren/reaktivieren |
| **API-Fehler in Logs** | Prüft API-Key und Basis-URL in Settings; testen mit `curl -H "X-Api-Key: ..." https://api.example.com/... ` |
| **Cache wird nicht aktualisiert** | Transienten sind für 5 Minuten gültig; prüft mit `wp transient list` oder Debug-Log auf Cron-Runs |

---

## Produktionscheck vor Release

Vor der Freigabe führt aus:

1. **Cron läuft alle 5 Minuten:**
   ```bash
   # Mehrfach aufrufen, 5 Min Abstand prüfen
   wp cron test --hook=sik_sync_cron
   wp transient list | grep sik_
   ```

2. **Keine Error-Logs nach normalem Lauf:**
   ```bash
   tail -f /wp-content/debug.log | grep SIK
   ```

3. **Daten aktuell im Frontend:**
   - Zeigt „Zuletzt aktualisiert" einen aktuellen Zeitstempel?
   - Entsprechen Standings/Results der API?

---

## Weitere Ressourcen

- WordPress WP-Cron: https://developer.wordpress.org/plugins/cron/
- WP-CLI Cron Commands: https://developer.wordpress.org/cli/commands/cron/
