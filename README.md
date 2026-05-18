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

The project supports both Docker-based and local host execution.

- `app` service: PHP `8.4` container running the Laravel app
- `scheduler` service: PHP `8.4` container running `php artisan schedule:work`
- `mysql` service: MySQL `8.4`

Docker keeps the runtime aligned with the assessment requirements, while the app can also be run locally after configuring PHP, Composer, and MySQL access.

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

For local non-Docker usage on this machine, use:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=33060
DB_DATABASE=uptime_monitor
DB_USERNAME=laravel
DB_PASSWORD=secret
```

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

## Local Setup Without Docker

The project can also run locally without Docker after the dependencies are installed and MySQL is available through the mapped host port.

1. Update `.env` to use the local MySQL connection:

```env
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_PORT=33060
DB_DATABASE=uptime_monitor
DB_USERNAME=laravel
DB_PASSWORD=secret
```

2. Install PHP dependencies:

```bash
composer install
```

3. Generate the app key if needed:

```bash
php artisan key:generate
```

4. Run migrations:

```bash
php artisan migrate
```

5. Start the app:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

6. Run the monitor checks manually when you want to process uptime results:

```bash
php artisan app:check-monitors
```

7. If you want periodic checks locally, run the scheduler in a separate terminal:

```bash
php artisan schedule:work
```

The API will then be available at:

```text
http://localhost:8000
```

Useful local commands:

```bash
php artisan migrate
php artisan app:check-monitors
php artisan schedule:work
php artisan test
php artisan tinker
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

## Postman

Import these files into Postman:

- `postman/Uptime Monitor API.postman_collection.json`

Optional:

- `postman/Uptime Monitor Local.postman_environment.json`

The collection already contains default collection variables, so it can be imported and used on its own. The environment file is only a convenience if testers prefer a separate Postman environment.

Suggested run order:

1. `Create Monitor`
2. `List Monitors`
3. Run `docker compose exec app php artisan app:check-monitors`
4. `Get Monitor History`
5. `Create Duplicate Monitor`
6. `Get Missing Monitor History`

The `Create Monitor` request automatically stores the returned id in the `monitorId` variable for the history request.

## Email Testing With MailHog

If MailHog is running locally and its web UI is available at:

```text
http://localhost:8025
```

configure Laravel to send mail through MailHog SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
UPTIME_ALERT_EMAIL=alerts@example.com
```

Then test notifications with this flow.

### Test a Down Email

1. Create a monitor with a failing URL and threshold `1`, for example:

```json
{
  "url": "https://httpstat.us/500",
  "check_interval": 5,
  "threshold": 1
}
```

2. Run:

```bash
docker compose exec app php artisan app:check-monitors
```

or locally:

```bash
php artisan app:check-monitors
```

3. Open MailHog at `http://localhost:8025` and confirm the down email appears.

### Test a Recovery Email

Because the API does not include an update endpoint, the simplest recovery test is to update the monitor URL in Tinker after the down email has already been sent.

1. Open Tinker:

```bash
docker compose exec app php artisan tinker
```

or locally:

```bash
php artisan tinker
```

2. Update the failing monitor to a working URL:

```php
$monitor = App\Models\Monitor::find(1);
$monitor->url = 'https://example.com';
$monitor->save();
```

3. Run the monitor command again:

```bash
docker compose exec app php artisan app:check-monitors
```

or locally:

```bash
php artisan app:check-monitors
```

4. Check MailHog again and confirm the recovery email appears.

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
