@php
    $filterRoute = $calendarView
        ? 'tasks.calendar'
        : (request()->routeIs('dashboard') ? 'dashboard' : 'tasks.index');
    $filterAction = route($filterRoute);
    $activeFilterCount = count($statusFilters) + count($dueFilters);
@endphp

<div class="task-filter-menu" data-filter-menu>
    <button
        class="filter-trigger"
        id="taskFilterToggle"
        type="button"
        aria-label="Filters"
        aria-haspopup="true"
        aria-expanded="{{ request('filters') === 'open' ? 'true' : 'false' }}"
        aria-controls="taskFilterPopover"
        data-filter-toggle
    >
        <i class="bi bi-funnel" aria-hidden="true"></i>
        <span class="filter-label">Filters</span>
        <i class="bi bi-chevron-down filter-chevron" aria-hidden="true"></i>
        @if ($activeFilterCount > 0)
            <span class="filter-count">{{ $activeFilterCount }}</span>
        @endif
    </button>

    <form
        class="filter-popover"
        id="taskFilterPopover"
        action="{{ $filterAction }}"
        method="get"
        aria-labelledby="taskFilterTitle"
        data-filter-popover
        @if (request('filters') !== 'open') hidden @endif
    >
        @if ($searchTerm !== '')
            <input type="hidden" name="search" value="{{ $searchTerm }}">
        @endif
        @if (request()->routeIs('dashboard'))
            <input type="hidden" name="schedule" value="{{ request('schedule', 'all') }}">
        @endif
        @if (request()->routeIs('tasks.index') && request('view'))
            <input type="hidden" name="view" value="{{ request('view') }}">
        @endif
        <input type="hidden" name="filters" value="open">

        <div class="filter-popover-header">
            <h3 id="taskFilterTitle">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                Filters
            </h3>
        </div>

        <fieldset class="filter-group">
            <legend>Progress</legend>
            <div class="filter-options">
                @foreach ([
                    'pending' => ['Not started', 'bi-clock-history'],
                    'in_progress' => ['Marked in progress', 'bi-person-walking'],
                    'incomplete' => ['Incomplete tasks', 'bi-check-circle'],
                    'completed' => ['Completed tasks', 'bi-check-circle-fill'],
                ] as $status => [$label, $icon])
                    <label class="filter-option" for="filter-{{ $status }}">
                        <span class="filter-option-label">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i>
                            {{ $label }}
                        </span>
                        <input
                            class="filter-toggle-input"
                            id="filter-{{ $status }}"
                            name="status[]"
                            type="checkbox"
                            value="{{ $status }}"
                            data-filter-submit
                            @checked(in_array($status, $statusFilters, true))
                        >
                        <span class="filter-switch" aria-hidden="true"></span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="filter-group filter-group-due-date">
            <legend>Due date</legend>
            <div class="filter-options">
                @foreach ([
                    'overdue' => ['Overdue', 'bi-flag-fill'],
                    'today' => ['Due today', 'bi-flag-fill'],
                ] as $dueFilter => [$label, $icon])
                    <label class="filter-option" for="filter-due-{{ $dueFilter }}">
                        <span class="filter-option-label">
                            <i class="bi {{ $icon }}" aria-hidden="true"></i>
                            {{ $label }}
                        </span>
                        <input
                            class="filter-toggle-input"
                            id="filter-due-{{ $dueFilter }}"
                            name="due[]"
                            type="checkbox"
                            value="{{ $dueFilter }}"
                            data-filter-submit
                            @checked(in_array($dueFilter, $dueFilters, true))
                        >
                        <span class="filter-switch" aria-hidden="true"></span>
                    </label>
                @endforeach

                <div class="filter-option filter-option-next">
                    <label class="filter-option-label" for="filter-due-next">
                        <i class="bi bi-flag-fill" aria-hidden="true"></i>
                        Due next
                    </label>
                    <div class="filter-days-control">
                        <button
                            class="filter-days-step"
                            type="button"
                            aria-label="Decrease days"
                            data-filter-step="-1"
                        >−</button>
                        <input
                            class="filter-days-input"
                            id="filterNextDays"
                            name="next_days"
                            type="number"
                            min="0"
                            max="365"
                            value="{{ $nextDays }}"
                            aria-label="Number of days"
                            data-filter-next-days
                        >
                        <button
                            class="filter-days-step"
                            type="button"
                            aria-label="Increase days"
                            data-filter-step="1"
                        >+</button>
                        <span>days</span>
                    </div>
                    <input
                        class="filter-toggle-input"
                        id="filter-due-next"
                        name="due[]"
                        type="checkbox"
                        value="next"
                        data-filter-submit
                        @checked(in_array('next', $dueFilters, true))
                    >
                    <label class="filter-switch" for="filter-due-next" aria-label="Filter by due date in the next number of days"></label>
                </div>
            </div>
        </fieldset>
    </form>
</div>
