@props(['status'])

@php
    $badgeClass = match ($status) {
        \App\Models\Task::STATUS_PENDING => 'badge-warning',
        \App\Models\Task::STATUS_COMPLETED => 'badge-success',
        default => 'badge-secondary',
    };

    $label = match ($status) {
        \App\Models\Task::STATUS_PENDING => 'Pending',
        \App\Models\Task::STATUS_COMPLETED => 'Completed',
        default => ucfirst($status),
    };
@endphp

<span class="badge status-badge {{ $badgeClass }}">{{ $label }}</span>
