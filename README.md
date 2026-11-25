# Laravel Task Manager

A task manager with a standalone Bootstrap 4 frontend and a Laravel JSON API. The frontend and backend are maintained and run independently.

## Project structure

- `Server/` — Laravel API, Eloquent models, migrations, validation, and feature tests.
- `Frontend/` — independent Vite application using HTML, JavaScript, and Bootstrap 4.

The frontend communicates with the API at `http://127.0.0.1:8000/api`. Laravel allows requests from the configured frontend origin through CORS.

## Features

- Create and edit tasks in a Bootstrap modal.
- Delete tasks after confirmation with SweetAlert2.
- Change tasks between Pending and Completed.
- Search by title, filter by status, and paginate results.
- See total, pending, and completed task counts.
- Receive operation feedback through Toastr.
- Validate task data in the Laravel API and display field errors in the frontend.

## Requirements

- PHP 8.3 or later with PDO SQLite enabled (or PDO MySQL when using MySQL).
- Composer.
- Node.js and npm.
- SQLite for the default local setup, or MySQL if configured below.

## First-time setup

Run these commands from the project root in PowerShell.

### Backend

```powershell
cd .\Server
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (-not (Test-Path .\database\database.sqlite)) { New-Item -ItemType File .\database\database.sqlite | Out-Null }
php artisan migrate --seed
```

### Frontend

Open another terminal at the project root:

```powershell
cd .\Frontend
npm install
```

The frontend defaults to `http://127.0.0.1:8000/api`. If the API uses a different address, copy `Frontend/.env.example` to `Frontend/.env.local`, update `VITE_API_BASE_URL`, and set the matching `FRONTEND_URL` in `Server/.env`.

The seeder adds sample tasks for development. To start with an empty task list, run `php artisan migrate` without `--seed`.

## Run locally

Keep both processes running in separate terminals.

**Terminal 1 — Laravel API** (from `D:\TaskManager\Server`):

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 — standalone frontend** (from `D:\TaskManager\Frontend`):

```powershell
npm run dev
```

Open **http://127.0.0.1:5173** to use the application. The backend API is at **http://127.0.0.1:8000/api/tasks**.

### Windows workspace PHP runtime

If PHP is not on your `PATH` in this environment, run these lines in the backend terminal before the PHP commands:

```powershell
$phpDir = "$env:LOCALAPPDATA\TaskManagerToolchain\php-8.5.13"
$env:PHPRC = $phpDir
$env:Path = "$phpDir;$env:Path"
```

On another machine, install PHP and Composer as listed under Requirements.
Composer is also available as a local PHAR in this workspace. If `composer` is not recognized, run this from `Server/` after setting `$phpDir` above:

```powershell
& "$phpDir\php.exe" "$env:LOCALAPPDATA\TaskManagerToolchain\composer.phar" install
```

## API endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/tasks` | List tasks, statistics, search, status filter, and pagination |
| `POST` | `/api/tasks` | Create a task |
| `GET` | `/api/tasks/{id}` | View one task |
| `PUT` | `/api/tasks/{id}` | Update a task |
| `PATCH` | `/api/tasks/{id}/status` | Change task status |
| `DELETE` | `/api/tasks/{id}` | Delete a task |

Validation errors are returned as JSON with HTTP status `422`.

## Database

The `tasks` table contains a required title, optional description, status (`pending` or `completed`), and timestamps. Laravel migrations and Eloquent handle database access.

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

## Tests and builds

Run backend tests and formatting from `Server/`:

```powershell
php artisan test
vendor\bin\pint --test
```

Build the standalone frontend from `Frontend/`:

```powershell
npm run build
```

## Assumptions

- This is a single-user task list; authentication and task ownership are not required.
- Task priority, due dates, staff assignment, and drag-and-drop ordering are outside the assessment scope.
- Search, status filtering, and pagination are included as bonus features.
