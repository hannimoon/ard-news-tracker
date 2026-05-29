# ARD news tracker

A modern PHP application built with the **Symfony** framework, containerized with **Docker** and served by **Nginx**.

## Prerequisites

Ensure you have the following installed:
- [Docker](https://docker.com)
- [Docker Compose](https://docker.com)

## Tech Stack

- **PHP 8.2+** (FPM)
- **Symfony 7.x**
- **Nginx** (Web Server)
- **MySQL** (Database)
- **Composer** (Dependency Manager)

## Getting Started

### 1. Clone the repository
```bash
git clone https://github.com
cd ard-news-tracker
```

### 2. Environment & Docker Setup
Copy the distribution templates to create your local configuration:
```bash
# Symfony environment variables
cp .env .env.local

# Local Docker overrides (Xdebug, Mailpit, Ports)
cp docker-compose.override.yml.dist docker-compose.override.yml
```

### 2.1. Generate `APP_API_KEY`
Generate a secure API key and set it in `.env.local`:
```bash
# Generate a 64-character hex key
openssl rand -hex 32
```
Update `.env.local` with the generated value:
```dotenv
APP_API_KEY=your-generated-key-here
```

### 3. Build and Start Containers
```bash
docker compose up -d --build
```

### 4. Install Dependencies
```bash
docker compose exec symfony_php composer install
```

### 5. Database Migrations
```bash
docker compose exec symfony_php php bin/console doctrine:migrations:migrate --no-interaction
```

## Development Features

### Xdebug
Xdebug is integrated into the PHP container but must be activated during the build process.
- **Enable/Disable:** Set `INSTALL_XDEBUG=true` or `false` in your `docker-compose.override.yml`.
- **Configuration:** To change the Xdebug mode (e.g., from `debug` to `coverage`), you must edit the `xdebug.mode` line directly in the **Dockerfile**.
- **Apply Changes:** Any changes to Xdebug settings require a rebuild:
  ```bash
  docker compose up -d --build
  ```

## News API Usage
The application exposes a protected news API endpoint at `/api/news`.

### Available query parameters
- `region` — filter by region
- `date` — filter by date (`YYYY-MM-DD`)
- `sentiment` — filter by sentiment (`good`, `bad`, `neutral`)
- `type` — filter by type (`story`, `webview`)
- `page` — pagination page number (default: `1`)
- `limit` — number of items per page (default: `10`)

### Authentication
The endpoint supports:
- `Authorization: Bearer <JWT token>`
- `X-API-KEY: <api-key>`

### Example request
```bash
curl "http://localhost:8080/api/news?region=DE&date=2026-05-12&type=story&page=1&limit=10" \
  -H "Authorization: Bearer <JWT_TOKEN>"
```

### API documentation
Browse the generated OpenAPI docs at:
- `http://localhost:8080/api/doc`

## CronConfig / Scheduled Import
The application stores a single cron configuration in the database using the `CronConfig` entity. This configuration controls automatic news imports and includes:
- `scheduleTime` — the UTC time when the import should run each day (format `HH:MM`, default `03:00`)
- `isActive` — whether the scheduled import is enabled

The scheduler uses this config to generate a daily cron job for `NewsImportMessage` if cron import is active. The admin dashboard exposes controls to update the schedule time and enable or disable the automatic import.

## Optional: Initial Data Import
To populate your database with the latest news immediately after setup, run the import command:
```bash
docker compose exec symfony_php php bin/console app:import-news
```
