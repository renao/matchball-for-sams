# Plan: Umzug Codeberg -> GitHub, Umbenennung zu "Matchball - Standings & Tables for SAMS" + Rechtliche Absicherung

TL;DR: Plugin wird zu **"Matchball - Standings & Tables for SAMS"** umbenannt (Slug/Textdomain `matchball-for-sams`, interner
Präfix `matchball-for-sams`), bevor das Repo per `git push --mirror` komplett zu GitHub umzieht — so
startet das neue Repo direkt sauber unter dem neuen Namen. Da GitHub Actions
syntaktisch fast identisch zu Forgejo Actions ist, werden die bestehenden Workflow-Dateien
nur von `.forgejo/workflows/` nach `.github/workflows/` verschoben und Runner-Label/
Bootstrap-Befehle angepasst (kein Neuschreiben in einer Fremdsyntax wie bei GitLab CI nötig).
Woodpecker-Dateien entfernen, alle Codeberg-Referenzen in Code/Docs aktualisieren, den
kaputten `docs/07_CI_PIPELINE.md` (unresolved merge conflict) bereinigen und neu für
GitHub Actions schreiben, und drei neue Compliance-Dateien (PRIVACY.md, SECURITY.md,
CONTRIBUTING.md) ergänzen. "Matchball - Standings & Tables for SAMS" wird als **neues Plugin** bei WordPress.org
eingereicht (neue Review, neuer SVN-Slug); das alte "SAMS Integration"-Plugin wird
NICHT migriert, sondern mit Deprecation-Hinweis bis ca. Mitte 2027 weiterbetrieben und
danach geschlossen. Lizenz (GPLv2 or later) bleibt unverändert, da für
WordPress.org ohnehin verpflichtend und bereits konsistent gesetzt.

**Entscheidungsgrundlage (bereits geklärt mit User):**
- Ziel: **GitHub** (finale Entscheidung; User-Präferenz wegen Größe/Reichweite).
- Volle Git-Historie via `push --mirror`.
- **Neuer Name: "Matchball - Standings & Tables for SAMS"** — Repo, Plugin-Name, Slug, Textdomain und interne Präfixe
  werden umbenannt (bessere Unterscheidbarkeit vom Vorgänger-Plugin, saubere Übersetzungen).
- Vorgänger-Plugin "SAMS Integration" (bereits auf WP.org live) bleibt bestehen und wird
  mit Deprecation-Hinweis bis ca. Mitte 2027 weitergeführt; kein Migrationspfad geplant
  (bewusste Kosten-Nutzen-Entscheidung des Users).
- Plugin kommt als NEUES Listing auf WordPress.org (neue Plugin-Review erforderlich) —
  externe API wird weiterhin in readme.txt offengelegt.
- Keine eigene Projekt-Webseite/Demo -> keine Impressumspflicht relevant.
- Neue Dateien gewünscht: PRIVACY.md, SECURITY.md, CONTRIBUTING.md.
- `.woodpecker.yml` / `.woodpecker-release.yml` werden entfernt.

**Rechtliche Zusatzfrage: Kennzeichnungspflicht für KI-generierten Code?**
- EU AI Act Art. 50(4) (seit 2. Aug 2026 in Kraft) verlangt Offenlegung von KI-generiertem/
  manipuliertem **Text nur, wenn dieser veröffentlicht wird, um die Öffentlichkeit über
  Angelegenheiten von öffentlichem Interesse zu informieren** (z.B. Nachrichtenartikel) —
  Softwarequellcode fällt nicht darunter.
- Art. 50(2) (Marking-Pflicht für synthetische Inhalte) richtet sich an den **Anbieter des
  KI-Systems** (z.B. den LLM-Tool-Hersteller), nicht an den Entwickler, der damit Code schreibt.
- Es existiert aktuell **keine gesetzliche Kennzeichnungspflicht** (weder EU-weit noch
  deutsches Recht) speziell für "mit KI erstellten Code" in einem Software-Repository.
- Der Codeberg-Ausschluss war eine **plattformeigene ToS-Regel**, keine gesetzliche Vorgabe.
  GitHub hat keine vergleichbare Klausel (baut selbst Copilot).
- Freiwillige Offenlegung bleibt trotzdem sinnvoll (Vertrauen, Nachvollziehbarkeit) —
  dafür ist der Hinweis in CONTRIBUTING.md (Phase D.3) vorgesehen, aber rechtlich nicht verpflichtend.
- Disclaimer: keine Rechtsberatung im Einzelfall, Stand der Einschätzung 2026-09-17.

**Code-Fakten (verifiziert):**
- Aktuelles Repo: `ssh://git@codeberg.org/renao/sams-integration.git` (.git/config).
- **Altes Plugin (Vorgänger):** lokal unter `/home/renao/devel/deprecated-sams-integration`
  ausgecheckt; Git-Remote `git@github.com:renao/sams-integration.git` (GitHub), aktiver
  Branch `develop` (kein `main`). **Namens-Kollision:** `github.com/renao/sams-integration`
  ist bereits durch das alte Repo belegt — das neue GitHub-Repo muss daher `matchball-for-sams`
  heißen (ohnehin geplant) oder das alte Repo muss umbenannt werden.
- Altes Plugin ist PHP 7.2+, Version 1.2.0, nutzt Namespace `SAMSPlugin\` und einen
  Composer-Autoloader unter `build/lib/`; hat **keinen** eigenen `admin_notices`-Hook —
  für die Deprecation-Notice (Phase E.2) muss ein neuer Hook ergänzt werden.
- Altes Plugin hat WP.org-Metadaten unter `release-plugin-directory/trunk/readme.txt`
  (Stable tag 1.2.0, "Tested up to 6.8") und `release-plugin-directory/assets/` —
  der Deprecation-Hinweis gehört dort in die `Description` und als Admin-Notice in den Code.
- CI aktuell doppelt: Forgejo Actions (`.forgejo/workflows/ci.yml`, `wporg-release.yml`,
  Runner-Label `forgejo-docker`) UND Woodpecker (`.woodpecker.yml`, `.woodpecker-release.yml`)
  mit identischen Jobs: php_syntax, shell_lint, release_consistency, build_release_zip,
  und separat ein manueller `deploy-to-wporg-svn`-Job (Secrets `WPORG_SVN_USERNAME/PASSWORD`).
- `sams-integration.php` Header: `Plugin URI`/`Author URI` zeigen auf codeberg.org/renao(...).
- `docs/07_CI_PIPELINE.md` enthält UNRESOLVED GIT-MERGE-CONFLICT-MARKER (`<<<<<<< HEAD` etc.)
  über weite Teile der Datei — muss so oder so bereinigt werden, unabhängig vom Umzug.
- Keine personenbezogenen Daten von Website-Besuchern werden verarbeitet: REST-Routen
  (`includes/class-sams-rest.php`) sind alle `edit_posts`-gated (nur Redakteure/Admins),
  Cron-Sync läuft serverseitig (`class-sams-cron.php`) gegen SAMS-API, `error_log()`-Aufrufe
  loggen nur Status-Codes/Source-IDs (`class-sams-api-client.php:331`, `class-sams-cron.php:129`),
  kein Telemetry/Phone-Home zum Plugin-Autor. API-Keys werden laut readme.txt verschlüsselt
  in WP-Options gespeichert.
- readme.txt hat bereits einen "External service usage" + "Service terms and privacy"
  Abschnitt, der die WordPress.org-Pflichtangabe für externe Services erfüllt.
- GitHub Inc. (Microsoft) ist wie GitLab Inc. eine US-Firma ohne EU-only Hosting im
  kostenlosen Tier. Für ein reines Public-FOSS-Repo ohne Verarbeitung personenbezogener
  Daten Dritter ist das i.d.R. unkritisch (Account-Daten von Mitwirkenden werden von
  GitHub selbst als eigener Verantwortlicher verarbeitet).

---

## Phase A0 — Umbenennung zu "Matchball - Standings & Tables for SAMS" (vor dem Umzug)
*keine Abhängigkeiten; als eigener Commit VOR Phase A, damit das GitHub-Repo sauber startet*

**Namenskonventionen (Ziel):**
- Anzeigename: "Matchball - Standings & Tables for SAMS"
- Slug / Plugin-Ordner / Hauptdatei / Textdomain: `matchball-for-sams` (`matchball-for-sams.php`)
- Interner Präfix: `MATCHBALL_` / `matchball_` / `matchball-` / `matchball/` (ersetzt `SIK_`/`sik_`/`sik-`/`sik/`)
  — wichtig für Kollisionsfreiheit, da das alte "SAMS Integration"-Plugin bis Mitte 2027
  parallel live ist und Seiten in der Umstellung beide Plugins installiert haben können.

**Code-Umbenennungen (verifizierte Fundstellen):**
1. Hauptdatei `sams-integration.php` -> `matchball-for-sams.php` (`git mv`), Header: Plugin Name
   "Matchball - Standings & Tables for SAMS", Text Domain `matchball-for-sams`, Domain Path `/languages` bleibt.
2. PHP-Klassen `SIK_Plugin`, `SIK_Admin`, `SIK_Api_Client`, `SIK_Blocks`, `SIK_Cron`,
   `SIK_Rest`, `SIK_Source_Repository` -> `MATCHBALL_*`; Konstanten `SIK_PLUGIN_VERSION/FILE/DIR/URL`;
   Funktionen `sik_bootstrap`, `sik_load_textdomain`, `sik_activate`, `sik_deactivate`.
   Dateinamen `includes/class-sams-*.php` bleiben (beschreiben die SAMS-Domäne, nicht die Marke).
3. DB-/Laufzeit-Bezeichner: Option `sik_sources` -> `matchball_sources`
   (class-sams-source-repository.php:8), Cron-Hook `sik_sync_cron` + Schedule
   `sik_every_five_minutes` (class-sams-cron.php:8-9), Transient-Präfix `sik_`
   (class-sams-api-client.php:304), Verschlüsselungs-Präfix `sikenc:v1`.
   Kein Migrations-Layer nötig (kein Migrationsplan gewünscht; neue Installationen starten leer).
4. REST-Namespace `sik/v1` -> `matchball/v1` (class-sams-rest.php, alle `register_rest_route`,
   plus `rest_url('sik/v1/')` in class-sams-blocks.php:62).
5. Block-Namen `sik/standings`, `sik/results` -> `matchball/standings`, `matchball/results`
   (class-sams-blocks.php:26/32, assets/js/blocks.js:370-371, Demo-Content in docker-compose.yml:84).
   Geplanter `sik/schedules`-Block (ANFORDERUNGEN.md:271) -> `matchball/schedules`.
6. Script/Style-Handles `sik-blocks`/`sik-editor`/`sik-frontend`, JS-Global `sikConfig`,
   Admin-Page-Slug `sik-sources`, Nonce/Action `sik_save_sources`,
   `admin_post_sik_save_sources` (class-sams-admin.php, class-sams-blocks.php).
7. Textdomain in allen `__()`/`esc_html__()`/`esc_attr__()`-Aufrufen: `sams-integration` ->
   `matchball-for-sams` (includes/*, assets/js/blocks.js) inkl. `wp_set_script_translations`
   (class-sams-blocks.php:59) — verhindert Übersetzungs-Durcheinander mit dem alten Plugin.
8. CSS-Klassen `sik-*` -> `matchball-*` in assets/css/frontend.css, assets/css/editor.css,
   assets/js/blocks.js, includes/class-sams-blocks.php (kosmetisch, aber konsistent).
9. Env-/Konfig-Variablen `SIK_*` -> `MATCHBALL_*` in .env.example, docker-compose.yml,
   docker/wp-config-extra.php, LOCAL_DEV.md; Compose-Projektname, Container-Namen und
   Mount-Pfad `/var/www/html/wp-content/plugins/sams-integration` -> `matchball-for-sams`;
   setup-sams.sh BASEDIR an den neuen lokalen Pfad anpassen.
10. Scripts: `PLUGIN_SLUG="matchball-for-sams"` in scripts/create-release-zip.sh (Zeile 5) und
    scripts/deploy-wporg-svn.sh (Zeile 5 + Default in Hilfetext Zeile 23), awk-Verweise auf
    `matchball-for-sams.php` (beide Scripts + später die GitHub-Workflows), Banner-Text
    "SAMS Integration" in scripts/create-wporg-assets.sh:35 -> "Matchball - Standings & Tables for SAMS".
11. Docs aktualisieren: readme.txt (`=== Matchball - Standings & Tables for SAMS ===`, Beschreibungstexte, kurzer Hinweis
    "Successor of the SAMS Integration plugin"), ANFORDERUNGEN.md (Textdomain Zeile 151,
    `sams-integration.php`-Verweis Zeile 217, `sik/schedules` Zeile 271), CRON_TESTING.md
    (`sik_sync_cron` etc.), MVP_ABNAHME_CHECKLISTE.md, docs/*, .env.example WP_TITLE.
12. Sprachdateien: `/languages`-Verzeichnis existiert aktuell nicht — bei späteren
    Übersetzungen Dateinamen `matchball-<locale>.po/.mo` verwenden.

## Phase A — Repo-Migration (GitHub)
*depends on Phase A0 (Umbenennung ist committed)*
1. Neues GitHub-Repository anlegen (unter bestehendem Account/Org `renao`), Sichtbarkeit
   "Public", Name `matchball-for-sams`.
2. Lokal: `git remote add github <neue-url>` und `git push --mirror github` (alle Branches + Tags + volle Historie).
   Hinweis: `github.com/renao/sams-integration` ist bereits durch das alte Repo belegt
   (siehe Code-Fakten) — daher als neues Repo `github.com/renao/matchball-for-sams` verwenden.
3. Default-Branch auf GitHub auf `main` setzen, Branch-Protection-Rule für `main` aktivieren
   (Voraussetzung für sichere Secrets/Environments in Phase B).
4. Optional (Empfehlung, nicht bestätigt): Codeberg-Repo nicht löschen, sondern
   README/Repo-Beschreibung dort auf "Moved to GitHub" + neue URL setzen (Goodwill für
   bestehende Stars/Forks, keine toten Links).

## Phase B — CI/CD-Migration (Forgejo Actions -> GitHub Actions)
*depends on Phase A (Repo + Branch-Protection müssen existieren)*
1. `.forgejo/workflows/ci.yml` nach `.github/workflows/ci.yml` verschieben. Struktur bleibt
   nahezu 1:1 (bereits `actions/checkout@v4`, Standard-Step-Syntax, `on: push/pull_request/
   workflow_dispatch`), nur `runs-on: forgejo-docker` durch `runs-on: ubuntu-latest` ersetzen.
   Jobs bleiben inhaltlich gleich: php_syntax, shell_lint, release_consistency, build_release_zip.
   Wichtig: awk-Verweise in den Workflows zeigen nach Phase A0 auf `matchball-for-sams.php` und
   `artifacts/releases/matchball-for-sams-<version>.zip` (ci.yml Zeilen 30/40/41, wporg-release.yml Zeile 35).
2. `.forgejo/workflows/wporg-release.yml` nach `.github/workflows/wporg-release.yml`
   verschieben: `runs-on: forgejo-docker` -> `runs-on: ubuntu-latest`; `environment: production`
   Konzept bleibt identisch (GitHub Actions unterstützt `environment:` inkl. optionaler
   Required Reviewers); `secrets.WPORG_SVN_USERNAME/PASSWORD` Referenzen bleiben unverändert,
   da GitHub Actions dieselbe `secrets.*`-Syntax nutzt wie Forgejo Actions.
3. GitHub Secrets anlegen (Settings > Secrets and variables > Actions, idealerweise im
   `production`-Environment mit Protection Rules): `WPORG_SVN_USERNAME`, `WPORG_SVN_PASSWORD`.
4. `.forgejo/` (komplettes Verzeichnis) entfernen, `.woodpecker.yml`, `.woodpecker-release.yml`
   entfernen (bestätigt durch User).
5. `docs/07_CI_PIPELINE.md` komplett neu schreiben: Merge-Conflict-Marker entfernen,
   Inhalt auf GitHub Actions umstellen (Workflows/Jobs, Secrets/Environments, Runner-Hinweise:
   GitHub-hosted `ubuntu-latest` Runner reichen aus, kein eigener Runner nötig), Verweise auf
   "Codeberg"/"Forgejo Actions" durch "GitHub"/"GitHub Actions" ersetzen.

## Phase C — Referenz- & Metadaten-Updates
*can run parallel with Phase B; baut auf Phase A0 auf (Datei heißt dann `matchball-for-sams.php`)*
1. `matchball-for-sams.php`: `Plugin URI` und `Author URI` von `codeberg.org/...` auf neue
   GitHub-URLs ändern (`https://github.com/renao/matchball-for-sams`).
2. `readme.txt`: bestehenden "External service usage"/"Service terms and privacy"-Abschnitt
   unverändert lassen (bereits WP.org-konform); optional eine "Source Code"-Zeile mit
   GitHub-Link ergänzen.
3. Repo-weite Suche nach `codeberg` erneut ausführen und verbleibende Treffer bereinigen
   (Stand jetzt: nur `sams-integration.php` Header + `docs/07_CI_PIPELINE.md`, s.o.).

## Phase D — Rechtliche/Compliance-Hardening
*can run parallel with Phase B/C*
1. **PRIVACY.md** (neu, Repo-Root): Scope klarstellen (Repo/Plugin-Code, kein gehosteter
   Dienst), Datenfluss beschreiben (Admin-konfigurierter API-Key, serverseitige
   Cron-Requests an SAMS-API, REST-Routen nur für angemeldete Redakteure/Admins,
   keine Übertragung von Besucher-personenbezogenen Daten, kein Telemetry/Phone-Home
   zum Plugin-Autor, Fehlerlogs enthalten nur Status-Codes/Source-IDs), klarstellen dass
   der **Website-Betreiber** (nicht der Plugin-Autor) datenschutzrechtlich Verantwortlicher
   für seine eigene Seite/Besucher ist, Disclaimer "keine Rechtsberatung im Einzelfall".
2. **SECURITY.md** (neu): Meldeweg für Sicherheitslücken (Kontakt-E-Mail oder GitHub
   "private vulnerability reporting"/Security-Advisories-Feature), erwartete Reaktionszeit,
   unterstützte Versionen. *Offen: konkrete Kontakt-E-Mail/Handle vom User einholen.*
3. **CONTRIBUTING.md** (neu): Bezug auf bestehenden Agenten-Workflow (`.instructions.md`),
   Lizenz-Hinweis (Beiträge müssen GPLv2-or-later-kompatibel sein), freiwillige Offenlegung
   von KI-Unterstützung bei Beiträgen (nicht gesetzlich verpflichtend, siehe Rechtliche
   Zusatzfrage oben, aber gute Praxis) + leichte "Signed-off-by"/Origin-Bestätigung, dass
   der Contributor die Rechte zur Einreichung unter GPLv2 hat.
4. Lizenz: **keine Änderung** — GPLv2 or later ist für WordPress.org-Vertrieb verpflichtend
   und bereits konsistent in `LICENSE`, `sams-integration.php`-Header und `readme.txt` gesetzt.
5. Kein Impressum notwendig (bestätigt: keine eigene Webseite/Demo, nur Repo + WP.org-Listing;
   bei späterer eigener Projektseite neu bewerten).
6. Keine zusätzliche "KI-Kennzeichnung" im rechtlichen Sinn nötig (siehe oben) — freiwilliger
   Hinweis in CONTRIBUTING.md/README reicht aus, falls gewünscht.

## Phase E — WordPress.org: neues Listing "Matchball - Standings & Tables for SAMS" + Abkündigung des Vorgängers
*depends on Phase A0 (Slug steht) + Phase B (Deploy-Workflow); Review-Wartezeit einplanen*
1. **Neues WP.org-Listing "Matchball - Standings & Tables for SAMS" einreichen (größtenteils manuell):**
   a. **Pre-Flight-Check** gegen die WP.org Plugin Guidelines (vor der Einreichung):
      - readme.txt mit dem offiziellen WP.org-Readme-Validator prüfen.
      - Release-ZIP via scripts/create-release-zip.sh bauen und auf einer frischen
        WordPress-Installation hochladen/aktivieren (Install-Smoke-Test).
      - Präfix-Konformität: alle globalen Funktionen/Klassen/Konstanten tragen den
        eindeutigen Präfix `matchball-for-sams`/`Matchball` (durch Phase A0 erfüllt; die Review prüft das).
      - Namens-Kollision prüfen: "Matchball - Standings & Tables for SAMS" im WP.org-Verzeichnis noch nicht vergeben;
        Nähe zu "SAMS" (Produktname sams-server.de) bewusst beachten — das Vorgänger-Plugin
        nutzte bereits "SAMS Integration", aber neue Namen werden genauer geschaut.
      - Keine obfuskierten/verminften Sourcen ohne Quelltext (blocks.js ist lesbares JS — ok).
   b. **Einreichung:** ZIP über das Developer-Portal hochladen
      (https://wordpress.org/plugins/developers/add/). Der Slug `matchball-for-sams` wird dabei
      reserviert. Frühzeitig einreichen — die Review dauert erfahrungsgemäß Tage bis
      Wochen, Rückfragen sind üblich. Bis zur Freischaltung kann Matchball - Standings & Tables for SAMS über
      GitHub-Releases (ZIP) verteilt werden.
   c. **Review-Loop:** auf Feedback-Mails des Plugin Review Teams reagieren, ggf. Fixes
      committen und ZIP erneut hochladen. Versionsnummer dabei NICHT erhöhen, solange
      noch nichts öffentlich released wurde.
   d. **Nach Freischaltung:** SVN-Repo `https://plugins.svn.wordpress.org/matchball-for-sams/`
      wird bereitgestellt. Initial-Import nach `trunk/` + `assets/` (Banner/Icons via
      scripts/create-wporg-assets.sh mit neuem Namen bauen), Tag `3.0.0` anlegen.
      Danach übernimmt der Workflow `wporg-release` (Phase B) alle weiteren Releases.
      Secrets `WPORG_SVN_USERNAME/PASSWORD` bleiben gleich (gleicher WP.org-Account).
   e. **Listing-Seite prüfen:** Beschreibung, FAQ, Screenshots; Plugin URI zeigt auf GitHub.
2. **Altes Plugin abkündigen (bleibt bis ca. Mitte 2027):** — Änderungen im Repo des
   ALTEN Plugins, nicht in diesem Repo:
   - readme.txt: Deprecation-Hinweis an den Anfang der Description
     ("This plugin is deprecated and will be retired in mid-2027. Successor: Matchball - Standings & Tables for SAMS" + Link).
   - Admin-Notice im Dashboard des alten Plugins ergänzen (dismissible, Hinweis + Link auf
     das neue Plugin; finaler WP.org-Link erst nach Freischaltung, vorher GitHub-Link).
   - Letzte Maintenance-Release des alten Plugins mit "Tested up to"-Bump.
   - Kein Daten-/User-Migrationspfad (bewusste Entscheidung, siehe Decisions).
3. **Sunset (ca. Mitte 2027):** altes Plugin über plugins@wordpress.org schließen lassen
   ("close plugin"-Request); Kalender-Reminder setzen. Nach der Schließung bleibt der
   alte Slug dauerhaft reserviert/gesperrt — kein Konflikt mit `matchball-for-sams`.

## Verification
1. `git remote -v` im lokalen Klon zeigt neuen GitHub-Remote; `git log --oneline | wc -l`
   vor/nach Mirror-Push vergleichen (Historie vollständig).
2. Testpush/PR auf GitHub auslösen, alle 4 CI-Jobs (Actions-Tab) grün prüfen.
3. Manuellen `wporg-release`-Workflow einmal per `workflow_dispatch` im Dry-Run/Log prüfen
   (Secrets werden nicht im Log ausgegeben, `set -x` vermeiden).
4. `grep -rn "codeberg" .` liefert keine Treffer mehr außer ggf. historischen Changelog-Einträgen.
5. `grep -rn "<<<<<<<" docs/` liefert keine Treffer mehr (Merge-Marker entfernt).
6. Gegenlesen von PRIVACY.md/SECURITY.md/CONTRIBUTING.md gegen tatsächliches Verhalten in
   `class-sams-api-client.php`, `class-sams-cron.php`, `class-sams-rest.php`.
7. `grep -rn "sams-integration\|sik_\|SIK_\|sik/\|sik-" .` liefert außerhalb von
   `.git`/Changelog-Historie keine Treffer mehr (Umbenennung vollständig).
8. `docker compose up` Smoke-Test: Plugin aktiviert sich unter neuem Slug, Blöcke
   `matchball/standings` + `matchball/results` registrieren sich im Editor, Admin-Seite erreichbar,
   Cron-Hook `matchball_sync_cron` geplant.
9. Kollisions-Check: altes Plugin und Matchball - Standings & Tables for SAMS auf derselben Testinstanz gleichzeitig
   aktivierbar (keine Klassen-/Konstanten-/Hook-Kollision durch getrennte Präfixe).
10. readme.txt besteht den offiziellen WP.org-Readme-Validator; Release-ZIP lässt sich auf
    einer frischen WordPress-Installation hochladen und aktivieren (Pre-Flight für Phase E).

## Decisions
- Ziel-Plattform: GitHub (final).
- Volle Historie via `push --mirror` (bestätigt).
- Neuer Name: "Matchball - Standings & Tables for SAMS"; Slug/Textdomain/Repo `matchball-for-sams`; interner Präfix `matchball-for-sams` (bestätigt).
- Vorgänger-Plugin "SAMS Integration" wird NICHT migriert: Deprecation-Hinweis, Weiterbetrieb
  bis ca. Mitte 2027, danach Schließung auf WP.org (bestätigt, Sunset-Datum grob).
- Kein Migrations-/Upgrade-Pfad zwischen altem und neuem Plugin (bewusste Kosten-Nutzen-Entscheidung).
- Umbenennung erfolgt VOR dem Mirror-Push, damit GitHub direkt unter neuem Namen startet.
- Woodpecker-Dateien werden gelöscht, nicht nur deaktiviert (bestätigt).
- Lizenz bleibt GPLv2 or later (durch WP.org-Vertrieb ohnehin gesetzt).
- Kein Impressum, da keine eigene Webseite (bestätigt).
- Keine gesetzliche KI-Kennzeichnungspflicht für Code; nur freiwillige Doku in CONTRIBUTING.md.
- readme.txt "External service"-Disclosure bleibt inhaltlich wie sie ist, nur ggf. Source-Link ergänzt.

## Further Considerations
1. **Codeberg-Repo-Schicksal**: archivieren mit Verweis auf neue URL (empfohlen) oder löschen?
   Noch nicht bestätigt.
2. **SECURITY.md-Kontakt**: konkrete Melde-Adresse/Handle vom User einholen, bevor die Datei final ist.
3. **Repo des alten Plugins**: liegt lokal unter `/home/renao/devel/deprecated-sams-integration`
   (ausgecheckt, Branch `develop`, Remote `git@github.com:renao/sams-integration.git`).
   Die Deprecation-Änderungen aus Phase E.2 können dort direkt vorbereitet werden.
   Das alte Plugin hat aktuell keinen `admin_notices`-Hook — für die Admin-Notice
   muss einer ergänzt werden (siehe Phase E.2).
4. **Deprecation-Texte** (readme.txt + Admin-Notice des alten Plugins) abstimmen; finaler
   WP.org-Link auf Matchball - Standings & Tables for SAMS erst nach Freischaltung des neuen Listings bekannt.
5. **WP.org-Review-Timing**: Neueinreichung früh starten (Review kann Wochen dauern);
   bis dahin kann Matchball - Standings & Tables for SAMS über GitHub-Releases (ZIP) verteilt werden.
6. **Präfix-Wahl `matchball-for-sams`**: Vorschlag; falls dir ein anderer Präfix lieber ist (z.B. `ezs`),
   vor Umsetzung von Phase A0 final festlegen — danach ist die Änderung aufwendig.
