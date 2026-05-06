# CI Pipeline (Codeberg)

Es gibt zwei moegliche CI-Systeme auf Codeberg:

- Forgejo Actions (Workflows in `.forgejo/workflows/*.yml`)
- Woodpecker CI (Dateien `.woodpecker*.yml`)

Wenn bei euch kein Woodpecker-Projekt aktiviert ist, starten `.woodpecker*.yml` Dateien nicht. In diesem Fall sind Forgejo-Workflows der richtige Weg.

## Dateien

- Forgejo Actions:
	- `.forgejo/workflows/ci.yml`
	- `.forgejo/workflows/wporg-release.yml`
- Woodpecker (optional/alternativ):
	- `.woodpecker.yml`
	- `.woodpecker-release.yml`

## Trigger

Die Forgejo-CI laeuft bei:

- Push
- Pull Request
- Manueller Start (`workflow_dispatch`)

Die Forgejo-Release-Pipeline laeuft bei:

- Manuellem Start (`workflow_dispatch`)
- nur fuer Branch `main`

## Jobs

1. `php_syntax`
- Prueft alle PHP-Dateien mit `php -l`.

2. `shell_lint`
- Prueft alle Skripte in `scripts/` mit `shellcheck`.

3. `release_consistency`
- Vergleicht Plugin-Version aus `sams-integration.php` mit `Stable tag` aus `readme.txt`.

4. `build_release_zip`
- Baut das Release-ZIP ueber `scripts/create-release-zip.sh`.
- Verifiziert, dass `artifacts/releases/sams-integration-<version>.zip` existiert.

5. `deploy-to-wporg-svn` (Forgejo, Datei `.forgejo/workflows/wporg-release.yml`)
- Startet manuell (`workflow_dispatch`) auf `main`.
- Fuehrt `scripts/deploy-wporg-svn.sh --non-interactive --commit` aus.
- Verwendet Secrets fuer SVN-Authentifizierung.

## Benoetigte Secrets

Fuer den Forgejo-Release-Deploy-Job:

- `WPORG_SVN_USERNAME`
- `WPORG_SVN_PASSWORD`

Hinweis: Die Schreibweise ist in Forgejo/Gitea ueblicherweise uppercase.

## Manueller Release-Start (Forgejo Actions)

1. In Codeberg den Workflow `WP.org Release Deploy` oeffnen.
2. `Run workflow` auf Branch `main` starten.
3. Job-Log pruefen.

## Runner-Hinweise

Empfehlung fuer self-hosted Runner:

- Docker-faehiger Linux-Runner
- Internetzugriff fuer Container-Images
- Keine Secrets fuer die Basis-Pipeline noetig

## Naechste Ausbaustufen

1. Optionaler PHPCS-Job (WordPress Coding Standards) nach Einfuehrung von Composer-Konfiguration.
2. Optionaler Docker-Smoke-Test gegen WordPress.
3. Optionaler Step zur Signatur/Checksum-Generierung fuer Release-Artefakte.
