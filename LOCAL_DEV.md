# Local WordPress Test Environment

This project includes a Docker Compose setup for running WordPress locally.

## 1) Prepare environment

```bash
cp .env.example .env
```

Set your productive API key in `.env` (do not commit this file):

```env
MATCHBALL_API_KEY=YOUR_PRODUCTION_KEY
# or source-specific
# MATCHBALL_API_KEY_SAMS_V2=YOUR_PRODUCTION_KEY
```

## 2) Start WordPress

```bash
docker compose up -d
```

WordPress wird automatisch installiert und das Plugin aktiviert. Der `wpcli`-Container führt beim ersten Start `wp core install` aus (mit den Zugangsdaten aus `.env`) und aktiviert das Plugin. Der Fortschritt ist in den Logs sichtbar:

```bash
docker compose logs -f wpcli
```

## 3) Login

Öffne http://localhost:8080/wp-admin und melde dich mit den in `.env` konfigurierten Zugangsdaten an (Standard: `admin` / `admin`).

## 4) Configure source

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
