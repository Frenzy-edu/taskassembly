@php
    $filterQuery = array_filter([
        'search' => $searchTerm !== '' ? $searchTerm : null,
        'status' => $statusFilters !== [] ? $statusFilters : null,
        'due' => $dueFilters !== [] ? $dueFilters : null,
        'next_days' => in_array('next', $dueFilters, true) ? $nextDays : null,
    ], fn ($value): bool => $value !== null && $value !== '' && $value !== []);

    $gridUrl = route('tasks.index', array_merge($filterQuery, ['view' => 'grid']));
    $listUrl = route('tasks.index', array_merge($filterQuery, ['view' => 'list']));
    $calendarUrl = route('tasks.calendar', $filterQuery);
    $gridActive = !$calendarView && request()->routeIs('tasks.index') && request('view') === 'grid';
    $listActive = !$calendarView && !$gridActive;
@endphp

<div class="task-toolbar">
    <div class="task-view-switch" role="group" aria-label="Task view">
        <a
            class="view-switch-button {{ $gridActive ? 'active' : '' }}"
            href="{{ $gridUrl }}"
            aria-label="Grid view"
            title="Grid view"
            @if ($gridActive) aria-current="page" @endif
        >
            <i class="bi bi-grid-3x2-gap" aria-hidden="true"></i>
        </a>
        <a
            class="view-switch-button {{ $listActive ? 'active' : '' }}"
            href="{{ $listUrl }}"
            aria-label="List view"
            title="List view"
            @if ($listActive) aria-current="page" @endif
        >
            <i class="bi bi-list-ul" aria-hidden="true"></i>
        </a>
        <a
            class="view-switch-button {{ $calendarView ? 'active' : '' }}"
            href="{{ $calendarUrl }}"
            aria-label="Calendar view"
            title="Calendar view"
            @if ($calendarView) aria-current="page" @endif
        >
            <i class="bi bi-calendar3" aria-hidden="true"></i>
        </a>
    </div>

    @include('tasks.partials.filters')
</div>
