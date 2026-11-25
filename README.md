# Laravel Task Manager

A server-rendered task manager built with Laravel 13, Blade, Bootstrap 4, and Eloquent. It supports task creation, editing, deletion, status changes, search, status filtering, and pagination.

## Features

- Create and edit tasks with server-side validation.
- Delete tasks only after confirming in a SweetAlert2 dialog.
- Change between Pending and Completed from the task list.
- Search by title and filter by status.
- Paginate task results while retaining active filters.
- View live counts for total, pending, and completed tasks.
- Receive operation feedback through Toastr notifications.
- Responsive interface styled with Bootstrap 4.

## Requirements

- PHP 8.3 or later with PDO SQLite enabled (or PDO MySQL when using MySQL).
- Composer.
- Node.js and npm.
- SQLite for the default local setup, or MySQL if configured below.

## Setup with SQLite

From the project directory:

```sh
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Then open `http://127.0.0.1:8000`.

The seeder adds sample tasks for development. To start with an empty task list, run migrations without `--seed`.

## Using MySQL

Create a database, then update the database values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=
```

Ensure PHP has the `pdo_mysql` extension enabled, then run:

```sh
php artisan config:clear
php artisan migrate --seed
```

The migration and Eloquent model are database-agnostic.

## Tests and formatting

```sh
php artisan test
vendor/bin/pint --test
```

Feature tests use an in-memory SQLite database and do not require the development database to be reset.

## Data model

The `tasks` table contains:

- `id`
- `title` (required, maximum 255 characters)
- `description` (optional)
- `status` (`pending` or `completed`)
- `created_at` and `updated_at`

Status values are stored in lowercase and displayed as Pending or Completed.

## Assumptions

- This is a single-user task list; authentication and task ownership are not required.
- Task priority, due dates, staff assignment, and drag-and-drop ordering are outside the assessment scope.
- SQLite is the default for a quick local setup; MySQL can be selected through `.env`.
