# Local WordPress Test Environment

This project includes a Docker Compose setup for running WordPress locally.

## 1) Prepare environment

```bash
cp .env.example .env
```

Set your productive API key in `.env` (do not commit this file):

```env
SIK_API_KEY=YOUR_PRODUCTION_KEY
# or source-specific
# SIK_API_KEY_SAMS_V2=YOUR_PRODUCTION_KEY
```

## 2) Start WordPress

```bash
docker compose up -d
```

## 3) Install WordPress

Open:

- http://localhost:8080

Complete the setup wizard.

## 4) Activate plugin

Go to `Plugins` and activate `SAMS Integration KI`.

## 5) Configure source

Go to `Settings -> SAMS Sources`.

Recommended for secure key handling:

- Keep API Key field empty.
- Set source id to `sams-v2`.
- Save.

The plugin will resolve the key from environment/wp-config first.

## Useful commands

```bash
# stop containers
docker compose down

# stop and remove DB data
docker compose down -v

# view logs
docker compose logs -f wordpress
```
