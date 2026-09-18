# Privacy Notice for Easy SAMS

This document describes the data flows of the **Easy SAMS** WordPress plugin. It applies to the plugin code and its behavior when installed on a WordPress site. It does **not** govern the GitHub repository itself or the policies of wordpress.org.

## Scope

Easy SAMS is a self-hosted WordPress plugin. It fetches sports data (standings, results) from a SAMS API endpoint configured by the site administrator. The plugin does not provide a hosted service and does not process personal data of website visitors by default.

## What data is processed

### Administrator-provided configuration

- **SAMS API credentials** (base URL, API key, optional association UUID) are stored in the WordPress database via `wp_options`.
- API keys are **encrypted at rest** before storage. The encryption key is derived from WordPress salts or from an optional environment/constant override (`ESAMS_ENCRYPTION_KEY`).
- API keys are **never logged, displayed, or transmitted** anywhere except to the configured SAMS API endpoint.

### Server-to-server API requests

- The plugin sends HTTP requests from the WordPress server to the configured SAMS API endpoint.
- Each request includes the configured API key in the `X-Api-Key` header, as required by the SAMS API.
- Requests are initiated by:
  - WP-Cron (`esams_sync_cron`), running every 5 minutes.
  - Block rendering on the frontend, when cached data is missing or stale.
  - REST API calls from the block editor (authenticated users with `edit_posts`).
- No personal data of website visitors is included in these requests.

### Caching

- API responses are cached using WordPress transients.
- Cache entries contain only sports data returned by the SAMS API (leagues, teams, rankings, matches).
- Cache keys are derived from endpoint + query parameters and contain no personal data.

### Error logging

- On errors, the plugin writes short log messages via WordPress `error_log()`.
- Log entries contain only status codes, source IDs, and internal stage names (for example `esams_sync_cron`, `leagues`).
- API keys, request bodies, and personal data are **never** logged.

### REST API

- The plugin registers REST routes under `esams/v1/`.
- All routes require the `edit_posts` capability. Only authenticated editors and administrators can access them.
- These routes return SAMS metadata (sources, seasons, associations, leagues, teams) needed by the block editor. They do not expose visitor data.

## What is NOT processed

- **No visitor personal data**: IP addresses, cookies, form submissions, analytics, or tracking data of website visitors are not collected by the plugin.
- **No telemetry or phone-home**: The plugin does not contact the plugin author, GitHub, wordpress.org, or any other third party.
- **No external assets**: The plugin does not load scripts, styles, fonts, or other resources from third-party servers.

## Responsibility

- The **site operator** is the data controller for any personal data processed on the WordPress site.
- The SAMS API provider (the association or operator of the configured endpoint) is responsible for the data it returns and the terms under which it provides API access.
- The plugin author only provides the open-source software. By using the plugin, the site operator remains responsible for complying with applicable data protection laws, including informing end users if the site itself processes personal data outside the plugin.

## Third-party services

The plugin connects only to the SAMS API endpoint configured by the site administrator. The official SAMS REST API documentation is published by the provider at:

<https://wiki.sams-server.de/wiki/REST-API-Schnittstelle>

Use of the SAMS service is subject to the policies and operating rules of the respective association or operator.

## Changes to this notice

Updates to this document will be tracked in the repository changelog. The current version reflects the plugin behavior as of the date below.

---

*Last updated: 2026-09-18*

*Disclaimer: This document provides general information and does not constitute legal advice for a specific situation.*
