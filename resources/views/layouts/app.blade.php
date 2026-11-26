<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="A focused workspace for managing tasks.">
    <title>@yield('title', 'TaskAssembly') · TaskAssembly</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.css" integrity="sha384-4dDVqFXqo6x4FK1oShAJhyqDCxAdfIi+HIlq/A7PaZFeN4LQEs1pnAocMtsPQceu" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/taskflow.css') }}">
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
                        <p class="topbar-eyebrow">TaskAssembly workspace</p>
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

            <footer class="app-footer">TaskAssembly · Stay focused on what matters.</footer>
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

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.8.0/dist/jquery.min.js" integrity="sha384-y+mmtWM/UTOfSHNDviSCB4KK3sjOB+d26CdhTfXdfo+iil53SyrMmplvPuHHGfaD" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.7/Sortable.min.js" integrity="sha384-DgmC6Xe2bSN2WjTDXzWYbUbxyhNP+NNkGDR/g78pCXV7E7rcVTGxVg0uIVCUUcBc" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.26/dist/sweetalert2.all.min.js" integrity="sha384-YSynQEAHJheMtXcWX0UbcX9moVoO5lqUapIunmQ0nJIYuYW3CdpHh4ckF/Loiasx" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.22/index.global.min.js" integrity="sha384-a9qx08ufzJ+0qbHzdqA+GYiSayQQB5BK2YGS/HQT9cxhQm+GhiXFj1ByVXa93ZRR" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js" integrity="sha384-Pqp51FUN2/qzfxZxBCtF0stpc9ONI6MYZpVqmo8m20SoaQCzf+arZvACkLkirlPz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@4.2.1/dist/jspdf.umd.min.js" integrity="sha384-qovJwSBbRDPP5cEjCp8S0UP66wrvnjaa60XMOGzTNanrThcrGfXfnZkvgY8N1KT3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@5.0.9/dist/jspdf.plugin.autotable.min.js" integrity="sha384-A77YsHWitrPuAj8E0AuB033EXzymUxIajnWKPzrrzqFoYYSCEpFennDUjMG2R1L3" crossorigin="anonymous"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
