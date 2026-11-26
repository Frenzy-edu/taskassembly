# TaskAssembly

TaskAssembly is a single Laravel application for managing personal tasks. Laravel renders the pages with Blade and uses Eloquent for database access. Bootstrap 4.6 (loaded from a CDN, no build step) provides the responsive UI; SweetAlert2 handles confirmations and feedback.

## Approach

- Server-rendered Blade over a separate frontend/API: the task list is simple enough that a JSON API and client-side framework would add complexity without adding value.
- CDN-delivered Bootstrap 4, jQuery, FullCalendar, SortableJS, SweetAlert2, ExcelJS, and jsPDF instead of a bundler: no Node/npm build step is required to run or deploy the app, which keeps local setup to just PHP and Composer.
- Tasks are route-bound by a generated slug rather than their numeric id, for cleaner URLs; numeric ids still resolve for backward compatibility.
- Status is a required field (`pending`/`completed`, matching the spec) with `in_progress` added as an optional third state for finer-grained tracking — the original two states are unaffected.
- Search, status/due filtering, pagination, and drag-and-drop ordering are implemented as the specified bonus features.

## Project structure

This is a single Laravel application at the repository root: Blade templates, static assets in `public/js` and `public/css`, migrations, seeders, and tests. There is no JS bundler — Bootstrap 4, jQuery, FullCalendar, SortableJS, SweetAlert2, ExcelJS, and jsPDF are all loaded via CDN `<script>`/`<link>` tags in `resources/views/layouts/app.blade.php`.

There is no separate frontend app or JSON API. Task creation is available from a Bootstrap modal and a dedicated page; editing uses a dedicated page. Search, status filtering, and pagination run through Laravel database queries. Drag-and-drop ordering is included as an optional enhancement.

## Requirements

- PHP 8.3+ with PDO SQLite enabled (or PDO MySQL when using MySQL).
- Composer.
- SQLite for the default local setup, or MySQL if configured below.
- An internet connection in the browser (Bootstrap 4, jQuery, and the other frontend libraries load from a CDN; there is no local Node/npm build step).

## First-time setup

Requires PHP 8.3+ and Composer on your `PATH`. Run these commands from the project root:

```bash
composer install
cp .env.example .env            # Windows (PowerShell): Copy-Item .env.example .env
php artisan key:generate
touch database/database.sqlite  # Windows (PowerShell): New-Item -ItemType File .\database\database.sqlite
php artisan migrate --seed
```

The seeder adds sample tasks for development. To start with an empty task list, use `php artisan migrate` without `--seed`.

## Run locally

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open **http://127.0.0.1:8000**. There is no separate asset-build step or second terminal — `public/js/app.js` and `public/css/taskflow.css` are served as-is, and the rest of the frontend libraries load straight from a CDN.

<details>
<summary>Windows workspace note (this dev machine only)</summary>

This machine's local PHP/Composer toolchain isn't on `PATH` by default. If `php`/`composer` aren't recognized here, run this first:

```powershell
$phpDir = "$env:LOCALAPPDATA\TaskManagerToolchain\php-8.5.13"
$env:PHPRC = $phpDir
$env:Path = "$phpDir;$env:Path"
```

This is specific to this workstation's setup and isn't needed on a machine with PHP already installed normally.

</details>

## Features

- Create and edit tasks with server-validated forms, optional start and due dates, and an expandable progress timeline.
- Enable “Add another task” in the create modal to save the task and reopen a fresh form.
- Open the create-task modal from the sidebar, task list, or calendar; selecting a calendar day pre-fills its due date.
- Delete tasks after SweetAlert2 confirmation.
- Change status between Pending, In Progress, and Completed.
- Search by title or status, filter by status, and paginate while retaining filters.
- Switch between list, card-grid, and calendar views; export the current filtered task set as CSV.
- Dashboard schedule tabs for all, overdue, due today, due this week, and upcoming tasks, with database-calculated counts.
- Week and month calendar views with optional due dates.
- Responsive Dashboard, All Tasks, Completed, and Pending navigation.
- Optional drag-and-drop ordering on the unfiltered task list.

### Bonus features implemented

- Search bar filtering tasks by title or status.
- Pagination for long task lists.
- Drag-and-drop task reordering, persisted to the database.

## Database

The `tasks` table contains a required title, optional description, optional start and indexed due dates, status (`pending`, `in_progress`, or `completed`), ordering position, and timestamps. Laravel migrations and Eloquent handle database access.

SQLite is the default. To use MySQL, configure `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=
```

Enable PHP's `pdo_mysql` extension, then run `php artisan config:clear` and `php artisan migrate --seed`.

## Tests

```bash
php artisan test
vendor/bin/pint --test
```

## Assumptions

- The application is a single-user task list; authentication and task ownership are not required.
- Due dates are optional so existing and unscheduled tasks remain valid; task priority and staff assignment are outside the assessment scope.
- Search, filtering, pagination, and drag-and-drop ordering are included.
