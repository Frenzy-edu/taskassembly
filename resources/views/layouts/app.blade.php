<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div
        id="flash-messages"
        class="d-none"
        data-success="{{ session('success') }}"
        data-error="{{ session('error') }}"
        aria-live="polite"
    ></div>

    <div class="app-shell">
        <aside class="app-sidebar">
            <a class="sidebar-brand" href="{{ route('tasks.index') }}">
                <span class="sidebar-brand-icon"><i class="bi bi-check2-square"></i></span>
                <span>Task Manager</span>
            </a>

            <p class="sidebar-label">Workspace</p>
            <nav class="nav flex-column sidebar-nav" aria-label="Main navigation">
                <a
                    class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}"
                    href="{{ route('tasks.index') }}"
                >
                    <i class="bi bi-list-task" aria-hidden="true"></i>
                    <span>My Tasks</span>
                </a>
            </nav>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <span class="topbar-context">Organize your work, one task at a time.</span>
                <div class="topbar-user">
                    <span class="topbar-avatar" aria-hidden="true">
                        <i class="bi bi-person"></i>
                    </span>
                    <span>Personal workspace</span>
                </div>
            </header>

            <main class="main-content">
                @yield('content')
            </main>

            <footer class="app-footer">
                Task Manager · Stay focused on what matters.
            </footer>
        </div>
    </div>
</body>
</html>
