@php
    $selectedStatus = old('status', $task->status ?: \App\Models\Task::STATUS_PENDING);
@endphp

<div class="form-group">
    <label for="title">Task title <span class="required-indicator">*</span></label>
    <input
        id="title"
        class="form-control @error('title') is-invalid @enderror"
        type="text"
        name="title"
        value="{{ old('title', $task->title) }}"
        maxlength="255"
        required
        autofocus
        @if ($errors->has('title')) aria-describedby="title-error" @endif
    >
    @error('title')
        <div id="title-error" class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="description">Description <span class="text-muted font-weight-normal">(optional)</span></label>
    <textarea
        id="description"
        class="form-control @error('description') is-invalid @enderror"
        name="description"
        rows="5"
        @if ($errors->has('description')) aria-describedby="description-error" @endif
    >{{ old('description', $task->description) }}</textarea>
    @error('description')
        <div id="description-error" class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-0">
    <label for="status">Status <span class="required-indicator">*</span></label>
    <select
        id="status"
        class="form-control @error('status') is-invalid @enderror"
        name="status"
        required
        @if ($errors->has('status')) aria-describedby="status-error" @endif
    >
        <option
            value="{{ \App\Models\Task::STATUS_PENDING }}"
            {{ $selectedStatus === \App\Models\Task::STATUS_PENDING ? 'selected' : '' }}
        >Pending</option>
        <option
            value="{{ \App\Models\Task::STATUS_COMPLETED }}"
            {{ $selectedStatus === \App\Models\Task::STATUS_COMPLETED ? 'selected' : '' }}
        >Completed</option>
    </select>
    @error('status')
        <div id="status-error" class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
