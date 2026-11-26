@extends('layouts.app')

@php
    $dashboardTabs = [
        'all' => 'All tasks',
        'overdue' => 'Overdue',
        'today' => 'Due today',
        'week' => 'Due this week',
        'upcoming' => 'Upcoming',
    ];
    $pageTitle = match ($currentView) {
        'completed' => 'Completed Tasks',
        'in_progress' => 'In Progress Tasks',
        'incomplete' => 'Incomplete Tasks',
        'pending' => 'Pending Tasks',
        'all' => 'All Tasks',
        default => 'Dashboard',
    };
    $filterQuery = array_filter([
        'search' => $searchTerm !== '' ? $searchTerm : null,
        'status' => $statusFilters !== [] ? $statusFilters : null,
        'due' => $dueFilters !== [] ? $dueFilters : null,
        'next_days' => in_array('next', $dueFilters, true) ? $nextDays : null,
    ], fn ($value): bool => $value !== null && $value !== '' && $value !== []);
    $canReorder = $currentView !== 'dashboard'
        && $layoutView === 'list'
        && $searchTerm === ''
        && $statusFilters === []
        && $dueFilters === [];
    $weekStart = now()->startOfWeek(\Carbon\Carbon::SUNDAY);
    $weekEnd = $weekStart->copy()->addDays(6);
    $weekRange = $weekStart->format('F') === $weekEnd->format('F')
        ? $weekStart->format('F j').'–'.$weekEnd->format('j')
        : $weekStart->format('F j').'–'.$weekEnd->format('F j');
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@section('content')
    @if ($currentView === 'dashboard')
        <section class="dashboard-date-heading" aria-labelledby="dashboard-date">
            <div>
                <p class="dashboard-week-label">Week {{ now()->isoWeek }}, {{ $weekRange }}</p>
                <h2 id="dashboard-date">{{ now()->format('l F jS') }}</h2>
            </div>
        </section>

        <nav class="dashboard-schedule-tabs" aria-label="Tasks by due date">
            @foreach ($dashboardTabs as $key => $label)
                <a
                    class="dashboard-schedule-tab {{ $scheduleFilter === $key ? 'active' : '' }}"
                    href="{{ route('dashboard', array_merge($filterQuery, ['schedule' => $key])) }}"
                    @if ($scheduleFilter === $key) aria-current="page" @endif
                >
                    <span>{{ $label }}</span>
                    <span class="schedule-count schedule-count-{{ $key }}">{{ number_format($dashboardCounts[$key]) }}</span>
                </a>
            @endforeach
        </nav>

        <div class="dashboard-task-heading">
            <h2>{{ $dashboardTabs[$scheduleFilter] }}</h2>
            <p>
                @if ($searchTerm !== '')
                    Results for <strong>{{ $searchTerm }}</strong>
                @else
                    {{ number_format($tasks->total()) }} {{ $tasks->total() === 1 ? 'task' : 'tasks' }}
                @endif
            </p>
        </div>
    @endif

    <section
        class="task-panel {{ $currentView === 'dashboard' ? 'dashboard-task-panel' : '' }}"
        id="task-list"
        aria-label="Task list"
    >
        @if ($currentView !== 'dashboard')
            <div class="task-panel-header">
                <div>
                    <h3 class="task-panel-title">Tasks</h3>
                    @if ($canReorder && $tasks->count() > 1)
                        <p class="task-panel-subtitle">Drag the handle to adjust task order.</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($tasks->isNotEmpty())
            @if ($currentView === 'dashboard')
                <ul class="dashboard-task-list" aria-label="{{ $dashboardTabs[$scheduleFilter] }} tasks">
                    @foreach ($tasks as $task)
                        @php($completed = $task->status === \App\Models\Task::STATUS_COMPLETED)
                        @php($inProgress = $task->status === \App\Models\Task::STATUS_IN_PROGRESS)
                        <li class="dashboard-task-row">
                            <form action="{{ route('tasks.status', $task) }}" method="post">
                                @csrf
                                @method('PATCH')
                                <input
                                    type="hidden"
                                    name="status"
                                    value="{{ $completed ? \App\Models\Task::STATUS_PENDING : \App\Models\Task::STATUS_COMPLETED }}"
                                >
                                <button
                                    class="dashboard-completion-toggle"
                                    type="submit"
                                    aria-label="Mark {{ $task->title }} {{ $completed ? 'Pending' : 'Completed' }}"
                                    title="Mark {{ $completed ? 'Pending' : 'Completed' }}"
                                >
                                    <i class="bi {{ $completed ? 'bi-check-circle-fill' : 'bi-circle' }}" aria-hidden="true"></i>
                                </button>
                            </form>

                            <div class="dashboard-task-main">
                                <a class="dashboard-task-title" href="{{ route('tasks.edit', $task) }}" data-edit-task-modal>
                                    {{ $task->title }}
                                </a>
                                @if ($task->description)
                                    <p class="dashboard-task-description">{{ $task->description }}</p>
                                @endif
                            </div>

                            <div class="dashboard-task-meta">
                                <span class="badge status-badge {{ $completed ? 'badge-success' : ($inProgress ? 'badge-info' : 'badge-warning') }}">
                                    {{ $completed ? 'Completed' : ($inProgress ? 'In Progress' : 'Pending') }}
                                </span>
                                <span class="dashboard-task-date">
                                    {{ $task->due_date?->format('M j') ?? 'No due date' }}
                                </span>
                            </div>

                            <div class="dashboard-task-actions">
                                <a
                                    class="dashboard-icon-action"
                                    href="{{ route('tasks.edit', $task) }}"
                                    data-edit-task-modal
                                    aria-label="Edit {{ $task->title }}"
                                    title="Edit task"
                                >
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <form
                                    action="{{ route('tasks.destroy', $task) }}"
                                    method="post"
                                    data-delete-form
                                    data-task-title="{{ $task->title }}"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="dashboard-icon-action"
                                        type="submit"
                                        aria-label="Delete {{ $task->title }}"
                                        title="Delete task"
                                    >
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @elseif ($layoutView === 'grid')
                <div class="task-card-grid">
                    @foreach ($tasks as $task)
                        @php($completed = $task->status === \App\Models\Task::STATUS_COMPLETED)
                        @php($inProgress = $task->status === \App\Models\Task::STATUS_IN_PROGRESS)
                        <article class="task-card">
                            <div class="task-card-header">
                                <span class="badge status-badge {{ $completed ? 'badge-success' : ($inProgress ? 'badge-info' : 'badge-warning') }}">
                                    {{ $completed ? 'Completed' : ($inProgress ? 'In Progress' : 'Pending') }}
                                </span>
                                <div class="task-card-actions">
                                    <form action="{{ route('tasks.status', $task) }}" method="post">
                                        @csrf
                                        @method('PATCH')
                                        <input
                                            type="hidden"
                                            name="status"
                                            value="{{ $completed ? \App\Models\Task::STATUS_PENDING : \App\Models\Task::STATUS_COMPLETED }}"
                                        >
                                        <button
                                            class="task-card-icon-action"
                                            type="submit"
                                            aria-label="Mark {{ $task->title }} {{ $completed ? 'Pending' : 'Completed' }}"
                                            title="Mark {{ $completed ? 'Pending' : 'Completed' }}"
                                        >
                                            <i class="bi {{ $completed ? 'bi-arrow-counterclockwise' : 'bi-check2' }}" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <a
                                        class="task-card-icon-action"
                                        href="{{ route('tasks.edit', $task) }}"
                                        data-edit-task-modal
                                        aria-label="Edit {{ $task->title }}"
                                        title="Edit task"
                                    >
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <form
                                        action="{{ route('tasks.destroy', $task) }}"
                                        method="post"
                                        data-delete-form
                                        data-task-title="{{ $task->title }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="task-card-icon-action"
                                            type="submit"
                                            aria-label="Delete {{ $task->title }}"
                                            title="Delete task"
                                        >
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <h3 class="task-card-title">
                                <a href="{{ route('tasks.edit', $task) }}" data-edit-task-modal>{{ $task->title }}</a>
                            </h3>
                            @if ($task->description)
                                <p class="task-card-description">{{ $task->description }}</p>
                            @endif
                            <div class="task-card-dates">
                                <span>Created {{ $task->created_at->format('M j, Y') }}</span>
                                <span>{{ $task->due_date?->format('M j, Y') ?? 'No due date' }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="table-responsive">
                    <table class="table task-table">
                        <thead>
                            <tr>
                                <th class="task-number" scope="col" aria-label="Task number">#</th>
                                <th scope="col">Task</th>
                                <th scope="col">Created On</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Status</th>
                                <th class="text-right" scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody
                            data-sortable-list
                            data-sortable-enabled="{{ $canReorder && $tasks->count() > 1 ? 'true' : 'false' }}"
                            data-sortable-url="{{ route('tasks.reorder') }}"
                            data-sortable-page="{{ $tasks->currentPage() }}"
                        >
                            @foreach ($tasks as $task)
                                @php($completed = $task->status === \App\Models\Task::STATUS_COMPLETED)
                                @php($inProgress = $task->status === \App\Models\Task::STATUS_IN_PROGRESS)
                                <tr data-task-id="{{ $task->id }}">
                                    <td class="task-number">
                                        @if ($canReorder && $tasks->count() > 1)
                                            <button
                                                class="task-drag-handle"
                                                type="button"
                                                aria-label="Reorder {{ $task->title }}"
                                                title="Drag to reorder"
                                            >
                                                <i class="bi bi-grip-vertical" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            {{ $loop->iteration + (($tasks->currentPage() - 1) * $tasks->perPage()) }}
                                        @endif
                                    </td>
                                    <td>
                                        <p class="task-name">{{ $task->title }}</p>
                                        @if ($task->description)
                                            <p class="task-description">{{ $task->description }}</p>
                                        @endif
                                    </td>
                                    <td class="task-date">{{ $task->created_at->format('M j, Y') }}</td>
                                    <td class="task-date">
                                        {{ $task->due_date?->format('M j, Y') ?? 'Not scheduled' }}
                                    </td>
                                    <td>
                                        <span class="badge status-badge {{ $completed ? 'badge-success' : ($inProgress ? 'badge-info' : 'badge-warning') }}">
                                            {{ $completed ? 'Completed' : ($inProgress ? 'In Progress' : 'Pending') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="task-actions">
                                            <form action="{{ route('tasks.status', $task) }}" method="post">
                                                @csrf
                                                @method('PATCH')
                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="{{ $completed ? \App\Models\Task::STATUS_PENDING : \App\Models\Task::STATUS_COMPLETED }}"
                                                >
                                                <button class="btn btn-outline-secondary" type="submit">
                                                    <i class="bi {{ $completed ? 'bi-arrow-counterclockwise' : 'bi-check2' }}" aria-hidden="true"></i>
                                                    {{ $completed ? 'Reopen' : 'Complete' }}
                                                </button>
                                            </form>
                                            <a
                                                class="btn btn-outline-primary"
                                                href="{{ route('tasks.edit', $task) }}"
                                                data-edit-task-modal
                                                aria-label="Edit {{ $task->title }}"
                                            >
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                Edit
                                            </a>
                                            <form
                                                action="{{ route('tasks.destroy', $task) }}"
                                                method="post"
                                                data-delete-form
                                                data-task-title="{{ $task->title }}"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger" type="submit">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="task-pagination">
                <p class="task-count mb-0">
                    Showing {{ $tasks->firstItem() }}–{{ $tasks->lastItem() }} of {{ $tasks->total() }} tasks
                </p>
                {{ $tasks->links() }}
            </div>
        @else
            <div class="empty-state" role="status">
                <span class="empty-state-icon" aria-hidden="true">
                    <i class="bi bi-clipboard2-check"></i>
                </span>
                @if ($searchTerm !== '' || $statusFilters !== [] || $dueFilters !== [])
                    <h3>No matching tasks</h3>
                    <p>Try changing your search or clearing the selected filters.</p>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('tasks.index') }}">Clear filters</a>
                @elseif ($currentView === 'dashboard')
                    <h3>No tasks in this view</h3>
                    <p>Tasks scheduled for this period will appear here.</p>
                    <button
                        class="btn btn-task-create btn-sm"
                        type="button"
                        data-toggle="modal"
                        data-target="#createTaskModal"
                        aria-haspopup="dialog"
                    >Add a task</button>
                @else
                    <h3>No tasks yet</h3>
                    <p>Create your first task to get started.</p>
                    <button
                        class="btn btn-task-create btn-sm"
                        type="button"
                        data-toggle="modal"
                        data-target="#createTaskModal"
                        aria-haspopup="dialog"
                    >Add your first task</button>
                @endif
            </div>
        @endif
    </section>
@endsection
