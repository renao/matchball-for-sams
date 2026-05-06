# CI Pipeline (Codeberg / Woodpecker)

Diese Pipeline wird auf Codeberg (Woodpecker CI) ausgefuehrt und deckt die MVP-Basis fuer Qualitaet und Build ab.

## Datei

- `.woodpecker.yml`

## Trigger

Die Pipeline laeuft bei:

- Push
- Pull Request
- Tag

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

## Runner-Hinweise

Empfehlung fuer self-hosted Runner:

- Docker-faehiger Linux-Runner
- Internetzugriff fuer Container-Images
- Keine Secrets fuer die Basis-Pipeline noetig

## Naechste Ausbaustufen

1. Optionaler PHPCS-Job (WordPress Coding Standards) nach Einfuehrung von Composer-Konfiguration.
2. Optionaler Docker-Smoke-Test gegen WordPress.
3. Optionaler manueller Release-Deploy-Job via SVN mit Secrets.
