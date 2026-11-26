<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="A focused workspace for managing tasks.">
    <title>@yield('title', 'TaskFlow') · TaskFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $calendarPage = request()->routeIs('tasks.calendar');
    $workspacePage = $calendarPage || request()->routeIs('dashboard', 'tasks.index');
    $headerSearchTerm = $searchTerm ?? request('search', '');
    $headerStatusFilters = $statusFilters ?? array_values((array) request('status', []));
    $headerDueFilters = $dueFilters ?? array_values((array) request('due', []));
    $headerNextDays = $nextDays ?? (int) request('next_days', 0);
    $exportQuery = array_filter([
        'search' => request('search'),
        'status' => request('status'),
        'due' => request('due'),
        'next_days' => in_array('next', $headerDueFilters, true) ? $headerNextDays : null,
    ], fn ($value): bool => $value !== null && $value !== '' && $value !== []);
@endphp
<body class="{{ $workspacePage ? 'workspace-page ' : '' }}{{ $calendarPage ? 'calendar-page' : '' }}">
    <div class="app-shell">
        @include('layouts.partials.sidebar')
        <button class="sidebar-overlay" id="sidebarOverlay" type="button" aria-label="Close navigation"></button>

        <div class="app-main">
            <header class="topbar {{ $workspacePage ? 'workspace-topbar' : '' }}">
                <button
                    class="sidebar-toggle"
                    id="sidebarToggle"
                    type="button"
                    aria-label="Open navigation"
                    aria-controls="appSidebar"
                    aria-expanded="false"
                >
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>

                <div class="topbar-heading">
                    @unless ($workspacePage)
                        <p class="topbar-eyebrow">TaskFlow workspace</p>
                    @endunless
                    <h1 class="topbar-title">{{ trim($__env->yieldContent('page-title', 'Dashboard')) }}</h1>
                </div>

                @if ($workspacePage)
                    <div class="workspace-topbar-actions">
                        <div class="task-export-menu" data-export-menu>
                            <button
                                class="calendar-export-button"
                                type="button"
                                aria-label="Export tasks"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-controls="taskExportPopover"
                                data-export-toggle
                            >
                                <i class="bi bi-download" aria-hidden="true"></i>
                                <i class="bi bi-chevron-down export-chevron" aria-hidden="true"></i>
                            </button>
                            <div class="task-export-popover" id="taskExportPopover" data-export-popover hidden>
                                <button class="task-export-option" type="button" data-export-action="print">
                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                    Print Todo-List
                                </button>
                                <button
                                    class="task-export-option"
                                    type="button"
                                    data-export-pdf
                                    data-export-url="{{ route('tasks.export', array_merge($exportQuery, ['format' => 'pdf'])) }}"
                                >
                                    <i class="bi bi-filetype-pdf" aria-hidden="true"></i>
                                    Save as PDF
                                </button>
                                <a
                                    class="task-export-option"
                                    href="{{ route('tasks.export', array_merge($exportQuery, ['format' => 'csv'])) }}"
                                >
                                    <i class="bi bi-filetype-csv" aria-hidden="true"></i>
                                    Save as CSV
                                </a>
                                <button
                                    class="task-export-option"
                                    type="button"
                                    data-export-xlsx
                                    data-export-url="{{ route('tasks.export', array_merge($exportQuery, ['format' => 'xlsx'])) }}"
                                >
                                    <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                                    Save as Excel
                                </button>
                            </div>
                        </div>
                        @include('tasks.partials.view-toolbar', [
                            'calendarView' => $calendarPage,
                            'searchTerm' => $headerSearchTerm,
                            'statusFilters' => $headerStatusFilters,
                            'dueFilters' => $headerDueFilters,
                            'nextDays' => $headerNextDays,
                        ])
                    </div>
                @endif

                @php
                    $searchAction = request()->routeIs('tasks.calendar')
                        ? route('tasks.calendar')
                        : (request()->routeIs('dashboard') ? route('dashboard') : route('tasks.index'));
                @endphp
                <form
                    class="global-search"
                    action="{{ $searchAction }}"
                    method="get"
                    role="search"
                >
                    @foreach ((array) request('status', []) as $status)
                        <input type="hidden" name="status[]" value="{{ $status }}">
                    @endforeach
                    @foreach ((array) request('due', []) as $dueFilter)
                        <input type="hidden" name="due[]" value="{{ $dueFilter }}">
                    @endforeach
                    @if (request()->routeIs('tasks.index') && request('view'))
                        <input type="hidden" name="view" value="{{ request('view') }}">
                    @endif
                    @if (request()->query->has('next_days'))
                        <input type="hidden" name="next_days" value="{{ request('next_days') }}">
                    @endif
                    @if (request()->routeIs('dashboard'))
                        <input type="hidden" name="schedule" value="{{ request('schedule', 'all') }}">
                    @endif
                    <label class="sr-only" for="globalSearch">Search by task title or status</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text border-right-0">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                        </div>
                        <input
                            class="form-control border-left-0"
                            id="globalSearch"
                            name="search"
                            type="search"
                            maxlength="255"
                            value="{{ request('search') }}"
                            placeholder="Search tasks..."
                        >
                        <div class="input-group-append">
                            <button class="btn btn-search-submit" type="submit">Search</button>
                        </div>
                    </div>
                </form>
            </header>

            <main class="main-content">
                <h1 class="print-page-title">{{ trim($__env->yieldContent('page-title', 'Task List')) }}</h1>
                @yield('content')
            </main>

            <footer class="app-footer">TaskFlow · Stay focused on what matters.</footer>
        </div>
    </div>

    @if (session('success'))
        <div
            id="flash-message"
            class="d-none"
            data-type="success"
            data-message="{{ session('success') }}"
        ></div>
    @elseif ($errors->any())
        <div
            id="flash-message"
            class="d-none"
            data-type="error"
            data-message="{{ $errors->first() }}"
        ></div>
    @endif
    @include('tasks.partials.create-modal')
    @if ($workspacePage)
        @include('tasks.partials.edit-modal')
    @endif
</body>
</html>
