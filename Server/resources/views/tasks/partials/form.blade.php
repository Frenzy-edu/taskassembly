@php
    $inModal = $inModal ?? false;
    $idPrefix = $inModal ? ($idPrefix ?? 'modal-') : '';
    $startDate = $task->start_date?->format('Y-m-d');
    $dueDate = $task->due_date?->format('Y-m-d');
    $createdDate = $task->exists
        ? $task->created_at->format('M j, Y')
        : now()->format('M j, Y');
    $progressHasErrors = $errors->has('start_date') || $errors->has('due_date');
@endphp

<form
    class="task-form"
    method="post"
    action="{{ $formAction }}"
>
    @csrf
    @if ($task->exists)
        @method('PUT')
    @endif

    <div class="form-group">
        <label class="{{ $inModal ? 'sr-only' : '' }}" for="{{ $idPrefix }}title">
            Task title <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <input
            class="form-control @error('title') is-invalid @enderror"
            id="{{ $idPrefix }}title"
            name="title"
            type="text"
            maxlength="255"
            value="{{ old('title', $task->title) }}"
            placeholder="{{ $inModal ? 'Write a task name' : '' }}"
            data-task-title-input
            required
            @if (!$inModal) autofocus @endif
            aria-describedby="{{ $idPrefix }}titleHelp @error('title') {{ $idPrefix }}titleError @enderror"
        >
        <small class="form-text text-muted {{ $inModal ? 'sr-only' : '' }}" id="{{ $idPrefix }}titleHelp">
            Use a short, descriptive title.
        </small>
        @error('title')
            <div class="invalid-feedback" id="{{ $idPrefix }}titleError">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label class="{{ $inModal ? 'sr-only' : '' }}" for="{{ $idPrefix }}description">
            Description <span class="font-weight-normal text-muted">(optional)</span>
        </label>
        <textarea
            class="form-control @error('description') is-invalid @enderror"
            id="{{ $idPrefix }}description"
            name="description"
            rows="4"
            placeholder="{{ $inModal ? 'Add description' : '' }}"
            aria-describedby="@error('description') {{ $idPrefix }}descriptionError @enderror"
        >{{ old('description', $task->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback" id="{{ $idPrefix }}descriptionError">{{ $message }}</div>
        @enderror
    </div>

    <div class="task-progress">
        <button
            class="task-progress-toggle"
            type="button"
            aria-controls="{{ $idPrefix }}progressDetails"
            aria-expanded="{{ $progressHasErrors ? 'true' : 'false' }}"
            data-progress-toggle
        >
            <span class="task-progress-chevron" aria-hidden="true">
                <i class="bi bi-chevron-right"></i>
            </span>
            <span class="task-progress-label">Progress</span>
            <span class="task-progress-summary">
                <span class="task-progress-summary-date">
                    <span class="task-progress-date-icon" aria-hidden="true">
                        <i class="bi bi-person-walking"></i>
                    </span>
                    <span data-progress-summary="start">{{ $task->start_date?->format('M j') ?? 'No start date' }}</span>
                </span>
                <i class="bi bi-arrow-right task-progress-range-arrow" aria-hidden="true"></i>
                <span class="task-progress-summary-date">
                    <span class="task-progress-date-icon" aria-hidden="true">
                        <i class="bi bi-flag"></i>
                    </span>
                    <span data-progress-summary="due">{{ $task->due_date?->format('M j') ?? 'No due date' }}</span>
                </span>
            </span>
        </button>

        <div
            class="task-progress-details"
            id="{{ $idPrefix }}progressDetails"
            data-progress-details
            @unless ($progressHasErrors) hidden @endunless
        >
            <div class="task-progress-timeline">
                <div class="task-progress-step task-progress-step-created">
                    <span class="task-progress-step-marker" aria-hidden="true"></span>
                    <span class="task-progress-step-label">Created</span>
                    <strong>{{ $createdDate }}</strong>
                </div>
                <div class="task-progress-step">
                    <span class="task-progress-step-marker" aria-hidden="true"></span>
                    <label class="task-progress-step-label" for="{{ $idPrefix }}start_date">Started</label>
                    <div class="task-progress-step-field">
                        <input
                            class="form-control @error('start_date') is-invalid @enderror"
                            id="{{ $idPrefix }}start_date"
                            name="start_date"
                            type="date"
                            value="{{ old('start_date', $startDate) }}"
                            data-progress-date="start"
                            data-empty-label="No start date"
                            aria-describedby="@error('start_date') {{ $idPrefix }}startDateError @enderror"
                        >
                        @error('start_date')
                            <span class="invalid-feedback" id="{{ $idPrefix }}startDateError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="task-progress-step">
                    <span class="task-progress-step-marker" aria-hidden="true"></span>
                    <label class="task-progress-step-label" for="{{ $idPrefix }}due_date">Due</label>
                    <div class="task-progress-step-field">
                        <input
                            class="form-control @error('due_date') is-invalid @enderror"
                            id="{{ $idPrefix }}due_date"
                            name="due_date"
                            type="date"
                            value="{{ old('due_date', $dueDate) }}"
                            data-progress-date="due"
                            data-empty-label="No due date"
                            aria-describedby="@error('due_date') {{ $idPrefix }}dueDateError @enderror"
                        >
                        @error('due_date')
                            <span class="invalid-feedback" id="{{ $idPrefix }}dueDateError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <fieldset class="form-group">
        <legend class="form-label">Status <span class="text-danger" aria-hidden="true">*</span></legend>
        <div class="status-options">
            <div class="status-choice">
                <input
                    id="{{ $idPrefix }}statusPending"
                    name="status"
                    type="radio"
                    value="pending"
                    @checked(old('status', $task->status) === 'pending')
                    required
                >
                <label class="status-option" for="{{ $idPrefix }}statusPending">Pending</label>
            </div>
            <div class="status-choice">
                <input
                    id="{{ $idPrefix }}statusInProgress"
                    name="status"
                    type="radio"
                    value="{{ \App\Models\Task::STATUS_IN_PROGRESS }}"
                    @checked(old('status', $task->status) === \App\Models\Task::STATUS_IN_PROGRESS)
                    required
                >
                <label class="status-option" for="{{ $idPrefix }}statusInProgress">In Progress</label>
            </div>
            <div class="status-choice">
                <input
                    id="{{ $idPrefix }}statusCompleted"
                    name="status"
                    type="radio"
                    value="completed"
                    @checked(old('status', $task->status) === 'completed')
                    required
                >
                <label class="status-option" for="{{ $idPrefix }}statusCompleted">Completed</label>
            </div>
        </div>
        @error('status')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </fieldset>

    <div class="form-actions {{ $inModal ? 'task-modal-actions' : '' }}">
        @if ($inModal && !$task->exists)
            <label class="add-another-option" for="{{ $idPrefix }}add_another">
                <input
                    id="{{ $idPrefix }}add_another"
                    name="add_another"
                    type="checkbox"
                    value="1"
                    @checked(old('add_another'))
                >
                <span>Add another task after creating this one</span>
            </label>
        @endif

        <div class="task-form-buttons">
            <button
                class="btn btn-primary"
                type="submit"
                data-task-submit
                @if ($inModal && trim((string) old('title', $task->title)) === '') disabled @endif
            >
                <i class="bi bi-check2 mr-1" aria-hidden="true"></i>
                {{ $task->exists ? 'Update Task' : ($inModal ? 'Create Task' : 'Save Task') }}
            </button>
            @unless ($inModal)
                <a class="btn btn-light" href="{{ $cancelUrl }}">Cancel</a>
            @endunless
        </div>
    </div>
</form>
