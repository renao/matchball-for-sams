# WordPress.org Submission Guide for Matchball - Standings & Tables for SAMS

This document collects the steps and checks required to submit **Matchball - Standings & Tables for SAMS** as a new plugin to the WordPress.org plugin directory.

> Note: This is a **new plugin submission**. The predecessor plugin **SAMS Integration** remains live separately and will not be migrated. It will receive deprecation notices and remain available until around mid-2027.

---

## Pre-submission checklist

Before uploading the plugin ZIP to WordPress.org, complete the following checks:

### 1. Legal and metadata consistency

- [ ] Plugin header in `matchball.php` is complete and accurate
  - `Plugin Name: Matchball - Standings & Tables for SAMS`
  - `Version: 3.0.0`
  - `Requires at least: 6.0`
  - `Requires PHP: 7.4`
  - `License: GPL v2 or later`
  - `Text Domain: matchball`
- [ ] `readme.txt` uses WordPress.org format and matches the plugin header
  - `=== Matchball - Standings & Tables for SAMS ===`
  - `Stable tag: 3.0.0`
  - `Contributors: renao`
  - `License: GPLv2 or later`
- [ ] `Stable tag` in `readme.txt` matches `Version` in `matchball.php`
- [ ] `LICENSE` file exists and contains GPLv2 or later text
- [ ] `PRIVACY.md`, `SECURITY.md`, and `CONTRIBUTING.md` exist in the repo root
- [ ] `readme.txt` contains the required external service disclosure sections

### 2. Code quality

- [ ] All PHP files pass `php -l`
- [ ] `shellcheck scripts/*.sh` passes
- [ ] All global identifiers use the `matchball_` / `MATCHBALL_` prefix
- [ ] No `codeberg`, `forgejo`, `woodpecker`, or old `sik_` / `SIK_` identifiers remain
- [ ] No unresolved merge conflict markers (`<<<<<<<`) anywhere

### 3. Build and smoke test

- [ ] `bash scripts/create-release-zip.sh` produces `artifacts/releases/matchball-3.0.0.zip`
- [ ] The release ZIP installs and activates on a clean WordPress installation
- [ ] The plugin activates under slug `matchball`
- [ ] The cron hook `matchball_sync_cron` is scheduled with interval `matchball_every_five_minutes`
- [ ] REST namespace `matchball/v1` is registered
- [ ] Blocks `matchball/standings` and `matchball/results` can be inserted in the editor
- [ ] Admin page `Settings → SAMS Sources` is reachable

### 4. Local development follow-ups

- [ ] `docker-compose.yml`: auto-create a default SAMS source during `wpcli` setup when none exists. Currently the demo page is created, but the source must be configured manually before blocks render data.

### 4. WordPress.org assets

- [ ] Run `bash scripts/create-wporg-assets.sh` to generate banner and icon images
- [ ] Verify the generated files in `wordpress-org-assets/`
  - `banner-772x250.png`
  - `banner-1544x500.png`
  - `icon-128x128.png`
  - `icon-256x256.png`
- [ ] (Optional) Add real screenshots as `screenshot-1.png`, `screenshot-2.png`, ...

### 5. Readme validation

- [ ] Run the plugin through the official WordPress.org readme validator
  - https://wordpress.org/plugins/developers/readme-validator/
- [ ] Fix any reported errors before submission

---

## Submission steps

1. Go to the WordPress.org plugin submission page:
   https://wordpress.org/plugins/developers/add/

2. Upload the release ZIP (`artifacts/releases/matchball-3.0.0.zip`).

3. Fill in the submission form:
   - Plugin name: `Matchball - Standings & Tables for SAMS`
   - Description: brief summary referencing SAMS API, standings, results, and the successor relationship
   - Confirm that the plugin complies with the plugin directory guidelines

4. Submit and wait for the plugin review team to respond.

> Review times vary from days to weeks. Follow-up questions are common. Do not bump the version number while responding to review feedback unless a public release has already been made.

---

## After approval

1. WordPress.org will provide an SVN repository at:
   `https://plugins.svn.wordpress.org/matchball/`

2. Use the release workflow or `scripts/deploy-wporg-svn.sh` to deploy:
   ```bash
   WPORG_SVN_USERNAME=youruser WPORG_SVN_PASSWORD=yourpass \
     ./scripts/deploy-wporg-svn.sh --non-interactive --commit --message "Release 3.0.0"
   ```

3. Verify the plugin page on WordPress.org:
   - Description renders correctly
   - Plugin URI points to GitHub
   - Screenshots and banners display as expected

4. Create the first GitHub Release matching the WordPress.org tag.

---

## Deprecation of SAMS Integration

The old plugin is maintained in a separate repository. The deprecation steps for the old plugin are:

1. Add a deprecation notice to the top of its `readme.txt` description, linking to Matchball - Standings & Tables for SAMS.
2. Add a dismissible admin notice in the old plugin code pointing users to Matchball - Standings & Tables for SAMS.
3. Bump `Tested up to` one last time and release a maintenance version.
4. Around mid-2027, request closure via `plugins@wordpress.org`.

Do not add deprecation logic for the old plugin to this repository.

---

## Useful links

- WordPress Plugin Developer FAQ: https://developer.wordpress.org/plugins/wordpress-org/
- Plugin Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- Readme Validator: https://wordpress.org/plugins/developers/readme-validator/
- SVN Deploy Workflow: `.github/workflows/wporg-release.yml`

---

*Last updated: 2026-09-18*
