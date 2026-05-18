# Uptime Monitor API

This repository contains a Dockerized Laravel uptime monitor API for the Sevenz Healthcare assessment. The project is targeted at the environment requested in the brief:

- PHP `8.4`
- Laravel `13.x`
- MySQL

## What This Project Does

- Registers URLs to monitor
- Tracks uptime checks and response metadata
- Marks monitors `pending`, `up`, or `down`
- Applies a configurable consecutive-failure threshold before marking a site down
- Sends email notifications when a site goes down or recovers
- Exposes the required API contract for monitors and history
- Runs scheduled checks every minute

## Runtime Stack

The app is meant to run in Docker, not on the host PHP installation.

- `app` service: PHP `8.4` container running the Laravel app
- `scheduler` service: PHP `8.4` container running `php artisan schedule:work`
- `mysql` service: MySQL `8.4`

This avoids local PHP version drift and keeps the assessment aligned with the stated requirements.

## Project Structure

- `docker-compose.yml`: service orchestration
- `docker/php/Dockerfile`: PHP `8.4` image for the app and scheduler
- `docker/php/start-app.sh`: boots the application container
- `docker/php/start-scheduler.sh`: boots the scheduler container
- `docker/mysql/init/01-create-testing-database.sql`: creates the MySQL test database

## Environment

The default `.env` and `.env.example` are configured for Docker + MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=uptime_monitor
DB_USERNAME=laravel
DB_PASSWORD=secret
```

The default alert mailbox is:

```env
UPTIME_ALERT_EMAIL=alerts@example.com
```

## Docker Setup

## Prerequisites

- Docker Desktop installed
- Docker Compose available

## Docker Setup

1. Ensure Docker Desktop is installed and running.
2. From the project root, build and start the containers:

```bash
docker compose up --build
```

3. The API will be available at:

```text
http://localhost:8000
```

The app container boot flow will:

- install Composer dependencies if needed
- wait for MySQL
- generate the app key if required
- run migrations
- start Laravel on `0.0.0.0:8000`

The scheduler container will run:

```bash
php artisan schedule:work
```

## Useful Commands

Run migrations manually:

```bash
docker compose exec app php artisan migrate
```

Run the monitor command manually:

```bash
docker compose exec app php artisan app:check-monitors
```

Run the test suite:

```bash
docker compose exec app php artisan test
```

Open a shell in the app container:

```bash
docker compose exec app sh
```

## Verification

Confirm the required runtime versions:

```bash
docker compose exec app php -v
docker compose exec app php artisan --version
```

Confirm database connectivity and migrations:

```bash
docker compose exec app php artisan migrate:status
```

Confirm the scheduler command is registered:

```bash
docker compose exec app php artisan schedule:list
```

## API Endpoints

### `POST /api/monitors`

Registers a new URL to monitor.

Example body:

```json
{
  "url": "https://example.com",
  "check_interval": 5,
  "threshold": 3
}
```

### `GET /api/monitors`

Returns all monitors with current status and uptime percentage.

### `GET /api/monitors/{id}/history`

Returns paginated check history ordered by `checked_at` descending.

## Status Rules

- `pending`: no completed successful/down state yet
- `up`: latest check returned `2xx` or `3xx`
- `down`: the monitor has reached its configured consecutive failure threshold

If a request times out or fails to connect:

- `status_code` is stored as `0`
- `response_time_ms` is stored as `null`
- `is_up` is stored as `false`

## Notifications

Notifications are sent only on state transitions:

- non-down to `down`
- `down` to `up`

The target recipient is configured through `UPTIME_ALERT_EMAIL`.
