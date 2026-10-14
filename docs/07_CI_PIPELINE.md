# CI Pipeline (GitHub Actions)

This project uses **GitHub Actions** for continuous integration and release automation.

## Workflow files

- `.github/workflows/ci.yml` — Quality checks and release artifact build
- `.github/workflows/wporg-release.yml` — Manual deployment to WordPress.org SVN

## Triggers

The CI workflow runs on:

- Push to any branch
- Pull requests
- Manual start (`workflow_dispatch`)

The release workflow runs on:

- Manual start (`workflow_dispatch`) only
- Only on the `main` branch

## Runners

All jobs use GitHub-hosted runners:

- `ubuntu-latest`

No self-hosted runner is required.

## CI jobs

### 1. PHP syntax check

Runs `php -l` on every `*.php` file except files under `artifacts/`.

### 2. Shell script lint

Runs `shellcheck` on all scripts in `scripts/`.

### 3. Version consistency check

Compares the `Version` header in `matchball-for-sams.php` with the `Stable tag` in `readme.txt`. Both must be equal and non-empty.

### 4. Build release zip

Runs `scripts/create-release-zip.sh` and verifies that `artifacts/releases/matchball-for-sams-<version>.zip` exists.

## Release job

`deploy-to-wporg-svn` in `.github/workflows/wporg-release.yml`:

- Checks out the repository
- Installs bash, zip, unzip, rsync, and subversion
- Validates that `WPORG_SVN_USERNAME` and `WPORG_SVN_PASSWORD` secrets are set
- Runs `scripts/deploy-wporg-svn.sh --non-interactive --commit --message "Release <version>"`

## Required secrets

Configure the following secrets in the GitHub repository (recommended: scoped to a `production` environment with protection rules):

- `WPORG_SVN_USERNAME`
- `WPORG_SVN_PASSWORD`

Add them via **Settings → Secrets and variables → Actions** (or in the `production` environment).

## Manual release start

1. Go to the **Actions** tab in the GitHub repository.
2. Select **WP.org Release Deploy**.
3. Click **Run workflow**, choose branch `main`, and start the workflow.
4. Review the job log.

## Local CI simulation

You can run the same checks locally before pushing:

```bash
# PHP syntax
find . -type f -name "*.php" -not -path "./artifacts/*" -print0 | xargs -0 -n1 php -l

# Shell lint
shellcheck scripts/*.sh

# Version consistency
plugin_version="$(awk -F': ' '/^ \* Version:/ {print $2; exit}' matchball-for-sams.php | tr -d '[:space:]')"
readme_version="$(awk -F': ' 'tolower($1)=="stable tag" {print $2; exit}' readme.txt | tr -d '[:space:]')"
test "$plugin_version" = "$readme_version" && echo "Version check passed: $plugin_version"

# Build release zip
bash ./scripts/create-release-zip.sh
```
