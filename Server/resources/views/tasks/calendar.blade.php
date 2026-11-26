@extends('layouts.app')

@section('title', 'All Tasks')
@section('page-title', 'All Tasks')

@section('content')
    <section class="task-panel calendar-panel" aria-label="Task calendar">
        <div class="calendar-controls">
            <div class="calendar-navigation">
                <button class="calendar-nav-button" type="button" aria-label="Previous period" data-calendar-prev>
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="calendar-today-button" type="button" data-calendar-today>Today</button>
                <button class="calendar-nav-button" type="button" aria-label="Next period" data-calendar-next>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
                <h3 class="calendar-period-title" data-calendar-title></h3>
            </div>

            <div class="calendar-view-toggle" role="group" aria-label="Calendar period">
                <button class="active" type="button" data-calendar-view="dayGridWeek" aria-pressed="true">Week</button>
                <button type="button" data-calendar-view="dayGridMonth" aria-pressed="false">Month</button>
            </div>
        </div>

        <div class="calendar-canvas">
            <div
                class="task-calendar"
                id="taskCalendar"
                data-events-url="{{ route('tasks.calendar.events') }}"
                data-create-url="{{ route('tasks.create') }}"
                data-search="{{ $searchTerm }}"
                data-statuses="{{ implode(',', $statusFilters) }}"
                data-due-filters="{{ implode(',', $dueFilters) }}"
                data-next-days="{{ $nextDays }}"
                data-full-height="true"
            ></div>
            <p class="calendar-empty" data-calendar-empty hidden>No tasks with due dates in this period.</p>
        </div>
    </section>
@endsection
