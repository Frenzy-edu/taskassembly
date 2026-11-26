@php
    $selectedStatuses = (array) request('status', []);
    $sidebarTaskCount = $taskCount ?? ($statistics['total'] ?? 0);
    $calendarSidebar = request()->routeIs('tasks.calendar');
@endphp

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand-row">
        <a
            class="sidebar-brand"
            href="{{ route('dashboard') }}"
            aria-label="{{ $calendarSidebar ? 'TaskManager dashboard' : 'TaskFlow dashboard' }}"
        >
            <span class="sidebar-brand-icon">
                <i class="bi bi-check2" aria-hidden="true"></i>
            </span>
            <span>{{ $calendarSidebar ? 'TaskManager' : 'TaskFlow' }}</span>
            @if ($calendarSidebar)
                <i class="bi bi-chevron-down sidebar-brand-caret" aria-hidden="true"></i>
            @endif
        </a>
        <button
            class="sidebar-collapse-toggle"
            id="sidebarCollapseToggle"
            type="button"
            aria-label="Collapse navigation"
            aria-controls="appSidebar"
            aria-expanded="true"
            title="Collapse navigation"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
    </div>

    <div class="sidebar-quick-actions">
        <button
            class="sidebar-search"
            type="button"
            aria-label="Focus task search"
            title="Search"
            data-placement="bottom"
            data-task-tooltip
            data-focus-search
        >
            <i class="bi bi-search" aria-hidden="true"></i>
        </button>
        <button
            class="sidebar-add-task"
            type="button"
            data-toggle="modal"
            data-target="#createTaskModal"
            aria-haspopup="dialog"
            title="New task"
            data-placement="bottom"
            data-task-tooltip
        >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Add Task
        </button>
    </div>

    @if ($calendarSidebar)
        <p class="sidebar-label">My tasks</p>
        <nav class="nav flex-column sidebar-nav" aria-label="Main navigation">
            <a class="nav-link active" href="{{ route('tasks.index') }}" aria-current="page">
                <i class="bi bi-check-lg" aria-hidden="true"></i>
                <span>All tasks</span>
                <span class="sidebar-task-count">{{ $sidebarTaskCount }}</span>
            </a>
            <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="bi bi-check-lg" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
        </nav>
        <div class="sidebar-divider" aria-hidden="true"></div>
    @else
        <p class="sidebar-label">Workspace</p>
        <nav class="nav flex-column sidebar-nav" aria-label="Main navigation">
            <a
                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                href="{{ route('dashboard') }}"
                @if (request()->routeIs('dashboard')) aria-current="page" @endif
            >
                <i class="bi bi-house-door" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
            <a
                class="nav-link {{ request()->routeIs('tasks.index') && $selectedStatuses === [] ? 'active' : '' }}"
                href="{{ route('tasks.index') }}#task-list"
                @if (request()->routeIs('tasks.index') && $selectedStatuses === []) aria-current="page" @endif
            >
                <i class="bi bi-list-task" aria-hidden="true"></i>
                <span>All Tasks</span>
            </a>
            <a
                class="nav-link {{ $selectedStatuses === ['completed'] ? 'active' : '' }}"
                href="{{ route('tasks.index', ['status' => 'completed']) }}"
                @if ($selectedStatuses === ['completed']) aria-current="page" @endif
            >
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>Completed</span>
            </a>
            <a
                class="nav-link {{ $selectedStatuses === ['pending'] ? 'active' : '' }}"
                href="{{ route('tasks.index', ['status' => 'pending']) }}"
                @if ($selectedStatuses === ['pending']) aria-current="page" @endif
            >
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <span>Pending</span>
            </a>
        </nav>

        <p class="sidebar-footer">Organize. Prioritize.<br>Get it done.</p>
    @endif
</aside>
