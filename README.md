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
- **Mailpit** (Mail Testing Tool)

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

### Mail Testing
You can access the Mailpit Web UI to inspect outgoing emails at:
[http://localhost:8025](http://localhost:8025)

## Optional: Initial Data Import
To populate your database with the latest news immediately after setup, run the import command:
```bash
docker compose exec symfony_php php bin/console app:import-news
```
