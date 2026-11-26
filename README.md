# TaskFlow

TaskFlow is a single Laravel application for managing personal tasks. Laravel renders the pages with Blade and uses Eloquent for database access. Bootstrap 4.6 provides the responsive UI; SweetAlert2 handles confirmations and feedback.

## Project structure

- `Server/` — the Laravel application, Blade templates, Vite assets, migrations, seeders, and tests.

There is no separate frontend app or JSON API. Task creation is available from a Bootstrap modal and a dedicated page; editing uses a dedicated page. Search, status filtering, and pagination run through Laravel database queries. Drag-and-drop ordering is included as an optional enhancement.

## Requirements

- PHP 8.3+ with PDO SQLite enabled (or PDO MySQL when using MySQL).
- Composer.
- Node.js and pnpm 10.33.0.
- SQLite for the default local setup, or MySQL if configured below.

## First-time setup

Run these commands from the project root in PowerShell:

```powershell
cd .\Server
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (-not (Test-Path .\database\database.sqlite)) { New-Item -ItemType File .\database\database.sqlite | Out-Null }
php artisan migrate --seed
pnpm install
```

The seeder adds sample tasks for development. To start with an empty task list, use `php artisan migrate` without `--seed`.

### Windows workspace PHP runtime

If PHP is not on your `PATH`, run these lines in the backend terminal before the PHP commands:

```powershell
$phpDir = "$env:LOCALAPPDATA\TaskManagerToolchain\php-8.5.13"
$env:PHPRC = $phpDir
$env:Path = "$phpDir;$env:Path"
```

On another machine, install PHP and Composer as listed under Requirements. If `composer` is not recognized in this workspace, run this from `Server/` after setting `$phpDir` above:

```powershell
& "$phpDir\php.exe" "$env:LOCALAPPDATA\TaskManagerToolchain\composer.phar" install
```

## Run locally

Keep both processes running in separate terminals.

**Terminal 1 — Laravel application** (from `D:\TaskManager\Server`):

```powershell
$phpDir = "$env:LOCALAPPDATA\TaskManagerToolchain\php-8.5.13"
$env:PHPRC = $phpDir
$env:Path = "$phpDir;$env:Path"
php artisan serve --host=127.0.0.1 --port=8000
```

If PHP is already on `PATH`, the first three lines are not needed.

**Terminal 2 — Vite assets** (also from `D:\TaskManager\Server`):

```powershell
pnpm dev
```

Open **http://127.0.0.1:8000**. Vite serves the development assets; it is not a separate application. For a production-style local run, use `pnpm build` instead of `pnpm dev`.

## Features

- Create and edit tasks with server-validated forms, optional start and due dates, and an expandable progress timeline.
- Enable “Add another task” in the create modal to save the task and reopen a fresh form.
- Open the create-task modal from the sidebar, task list, or calendar; selecting a calendar day pre-fills its due date.
- Delete tasks after SweetAlert2 confirmation.
- Change status between Pending and Completed.
- Search by title or status, filter by status, and paginate while retaining filters.
- Switch between list, card-grid, and calendar views; export the current filtered task set as CSV.
- Dashboard schedule tabs for all, overdue, due today, due this week, and upcoming tasks, with database-calculated counts.
- Week and month calendar views with optional due dates.
- Responsive Dashboard, All Tasks, Completed, and Pending navigation.
- Optional drag-and-drop ordering on the unfiltered task list.

## Database

The `tasks` table contains a required title, optional description, optional start and indexed due dates, status (`pending` or `completed`), ordering position, and timestamps. Laravel migrations and Eloquent handle database access.

SQLite is the default. To use MySQL, configure `Server/.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=
```

Enable PHP's `pdo_mysql` extension, then run `php artisan config:clear` and `php artisan migrate --seed` from `Server/`.

## Tests and assets

From `Server/`:

```powershell
php artisan test
vendor\bin\pint --test
pnpm build
```

## Assumptions

- The application is a single-user task list; authentication and task ownership are not required.
- Due dates are optional so existing and unscheduled tasks remain valid; task priority and staff assignment are outside the assessment scope.
- Search, filtering, pagination, and drag-and-drop ordering are included.
