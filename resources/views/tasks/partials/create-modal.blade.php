@php
    $modalTask = new \App\Models\Task([
        'status' => \App\Models\Task::STATUS_PENDING,
        'start_date' => request('start_date'),
        'due_date' => request('due_date'),
    ]);
    $modalActionParameters = array_filter([
        'return_to' => request()->routeIs('tasks.calendar')
            ? 'calendar'
            : (request()->routeIs('dashboard') ? 'dashboard' : null),
        'search' => request()->query('search'),
        'status' => request()->query('status'),
        'schedule' => request()->routeIs('dashboard') ? request()->query('schedule', 'all') : null,
    ]);
    $modalAction = route('tasks.store', $modalActionParameters);
    $previousInput = (array) session()->get('_old_input', []);
    $openModalForErrors = request()->routeIs('dashboard', 'tasks.index', 'tasks.calendar')
        && array_key_exists('title', $previousInput);
    $openCreateModal = $openModalForErrors || session('create_another', false);
@endphp

<div
    class="modal fade task-form-modal task-create-modal"
    id="createTaskModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="createTaskModalTitle"
    aria-hidden="true"
    data-open-on-load="{{ $openCreateModal ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="createTaskModalTitle">
                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                    Create Task
                </h2>
                <button
                    class="close"
                    type="button"
                    data-dismiss="modal"
                    aria-label="Close create task dialog"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                @include('tasks.partials.form', [
                    'task' => $modalTask,
                    'formAction' => $modalAction,
                    'cancelUrl' => route('dashboard'),
                    'inModal' => true,
                ])
            </div>
        </div>
    </div>
</div>
