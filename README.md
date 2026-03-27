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
git clone https://github.com/hannimoon/ard-news-tracker.git
cd ard-news-tracker
```

### 2. Environment Setup
```bash
cp .env .env.local
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

### Optional: Initial Data Import
To populate your database with the lastest news immediately after setup, run the import command:
```bash
docker compose exec symfony_php php bin/console app:import-news
```
