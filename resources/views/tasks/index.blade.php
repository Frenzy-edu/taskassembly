@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Overview</p>
            <h1>My Tasks</h1>
            <p>Manage your tasks and keep your day on track.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('tasks.create') }}">
            <i class="bi bi-plus-lg mr-1" aria-hidden="true"></i>
            Add Task
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <x-stat-card
                title="Total tasks"
                :value="$statistics['total']"
                icon="list-task"
                tone="blue"
            />
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <x-stat-card
                title="Pending"
                :value="$statistics['pending']"
                icon="hourglass-split"
                tone="amber"
            />
        </div>
        <div class="col-md-4">
            <x-stat-card
                title="Completed"
                :value="$statistics['completed']"
                icon="check-circle"
                tone="green"
            />
        </div>
    </div>

    <section class="card task-panel shadow-sm" aria-labelledby="task-list-heading">
        <div class="task-panel-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h2 id="task-list-heading" class="mb-3 mb-lg-0">Task list</h2>

                <form
                    class="filter-form"
                    method="GET"
                    action="{{ route('tasks.index') }}"
                    role="search"
                >
                    <label class="sr-only" for="task-search">Search task titles</label>
                    <div class="input-group filter-search">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-search text-muted" aria-hidden="true"></i>
                            </span>
                        </div>
                        <input
                            id="task-search"
                            class="form-control border-left-0"
                            type="search"
                            name="search"
                            value="{{ $searchTerm }}"
                            maxlength="255"
                            placeholder="Search tasks..."
                        >
                    </div>

                    <label class="sr-only" for="task-status-filter">Filter by status</label>
                    <select id="task-status-filter" class="form-control filter-status" name="status">
                        <option value="">All statuses</option>
                        <option
                            value="{{ \App\Models\Task::STATUS_PENDING }}"
                            {{ $statusFilter === \App\Models\Task::STATUS_PENDING ? 'selected' : '' }}
                        >Pending</option>
                        <option
                            value="{{ \App\Models\Task::STATUS_COMPLETED }}"
                            {{ $statusFilter === \App\Models\Task::STATUS_COMPLETED ? 'selected' : '' }}
                        >Completed</option>
                    </select>

                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-funnel mr-1" aria-hidden="true"></i>
                        Filter
                    </button>

                    @if ($searchTerm !== '' || $statusFilter !== '')
                        <a class="btn btn-light" href="{{ route('tasks.index') }}">Clear</a>
                    @endif
                </form>
            </div>
        </div>

        @if ($tasks->isNotEmpty())
            <div class="table-responsive">
                <table class="table task-table">
                    <thead>
                        <tr>
                            <th scope="col">Task</th>
                            <th scope="col">Created</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr>
                                <td>
                                    <div class="task-title">{{ $task->title }}</div>
                                    <p class="task-description">
                                        {{ $task->description ?: 'No description provided.' }}
                                    </p>
                                </td>
                                <td class="task-date">{{ $task->created_at->format('M j, Y') }}</td>
                                <td><x-status-badge :status="$task->status" /></td>
                                <td>
                                    <div class="task-actions">
                                        <form method="POST" action="{{ route('tasks.status', $task) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input
                                                type="hidden"
                                                name="status"
                                                value="{{ $task->status === \App\Models\Task::STATUS_PENDING
                                                    ? \App\Models\Task::STATUS_COMPLETED
                                                    : \App\Models\Task::STATUS_PENDING }}"
                                            >
                                            <button
                                                class="btn btn-sm btn-outline-success"
                                                type="submit"
                                                aria-label="{{ $task->status === \App\Models\Task::STATUS_PENDING
                                                    ? 'Mark completed: ' . $task->title
                                                    : 'Mark pending: ' . $task->title }}"
                                                title="{{ $task->status === \App\Models\Task::STATUS_PENDING
                                                    ? 'Mark completed'
                                                    : 'Mark pending' }}"
                                            >
                                                <i
                                                    class="bi {{ $task->status === \App\Models\Task::STATUS_PENDING
                                                        ? 'bi-check2'
                                                        : 'bi-arrow-counterclockwise' }}"
                                                    aria-hidden="true"
                                                ></i>
                                            </button>
                                        </form>

                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="{{ route('tasks.edit', $task) }}"
                                            aria-label="Edit {{ $task->title }}"
                                            title="Edit task"
                                        >
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('tasks.destroy', $task) }}"
                                            data-confirm-delete
                                            data-task-title="{{ $task->title }}"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="btn btn-sm btn-outline-danger"
                                                type="submit"
                                                aria-label="Delete {{ $task->title }}"
                                                title="Delete task"
                                            >
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-footer">
                <p>
                    Showing {{ $tasks->firstItem() ?? 0 }}–{{ $tasks->lastItem() ?? 0 }}
                    of {{ $tasks->total() }} tasks
                </p>
                {{ $tasks->links('pagination::bootstrap-4') }}
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-clipboard2-check" aria-hidden="true"></i>
                </div>
                <h3>{{ $searchTerm !== '' || $statusFilter !== '' ? 'No matching tasks' : 'No tasks yet' }}</h3>
                <p class="mb-3">
                    {{ $searchTerm !== '' || $statusFilter !== ''
                        ? 'Try another search or clear the selected filters.'
                        : 'Create your first task to get started.' }}
                </p>
                @if ($searchTerm === '' && $statusFilter === '')
                    <a class="btn btn-primary" href="{{ route('tasks.create') }}">Add your first task</a>
                @endif
            </div>
        @endif
    </section>
@endsection
