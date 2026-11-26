<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEventsRequest;
use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskController extends Controller
{
    public function index(TaskFilterRequest $request): View
    {
        [
            'search' => $searchTerm,
            'statuses' => $statusFilters,
            'due' => $dueFilters,
            'nextDays' => $nextDays,
        ] = $this->validatedTaskFilters($request);
        $statusFilter = count($statusFilters) === 1 ? $statusFilters[0] : '';
        $isDashboard = $request->routeIs('dashboard');
        $scheduleFilter = $isDashboard
            ? ($request->validated('schedule') ?? 'all')
            : '';
        $layoutView = $isDashboard
            ? 'list'
            : ($request->validated('view') ?? 'list');

        $taskQuery = $this->filteredTaskQuery($searchTerm, $statusFilters, $dueFilters, $nextDays);
        if ($isDashboard) {
            $taskQuery = $this->applyDashboardSchedule($taskQuery, $scheduleFilter);
        }

        $tasks = $taskQuery
            ->orderBy('due_date')
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($isDashboard ? 20 : 10)
            ->withQueryString();

        $statusCounts = Task::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statistics = [
            'total' => $statusCounts->sum(),
            'pending' => $statusCounts->get(Task::STATUS_PENDING, 0),
            'in_progress' => $statusCounts->get(Task::STATUS_IN_PROGRESS, 0),
            'completed' => $statusCounts->get(Task::STATUS_COMPLETED, 0),
        ];
        $dashboardCounts = $isDashboard
            ? $this->dashboardCounts($searchTerm, $statusFilters, $dueFilters, $nextDays)
            : [];

        $currentView = $isDashboard
            ? 'dashboard'
            : ($statusFilter !== '' ? $statusFilter : 'all');

        return view('tasks.index', compact(
            'tasks',
            'statistics',
            'searchTerm',
            'statusFilter',
            'statusFilters',
            'dueFilters',
            'nextDays',
            'scheduleFilter',
            'layoutView',
            'dashboardCounts',
            'currentView',
        ));
    }

    public function calendar(TaskFilterRequest $request): View
    {
        [
            'search' => $searchTerm,
            'statuses' => $statusFilters,
            'due' => $dueFilters,
            'nextDays' => $nextDays,
        ] = $this->validatedTaskFilters($request);
        $taskCount = Task::query()->count();

        return view('tasks.calendar', compact('searchTerm', 'statusFilters', 'dueFilters', 'nextDays', 'taskCount'));
    }

    public function calendarEvents(CalendarEventsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $start = CarbonImmutable::parse($filters['start'])->toDateString();
        $end = CarbonImmutable::parse($filters['end'])->toDateString();
        $searchTerm = $filters['search'] ?? '';
        $statusFilters = array_values(array_unique((array) ($filters['status'] ?? [])));
        $dueFilters = array_values(array_unique((array) ($filters['due'] ?? [])));
        $nextDays = (int) ($filters['next_days'] ?? 0);

        $events = $this->filteredTaskQuery($searchTerm, $statusFilters, $dueFilters, $nextDays)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start)
            ->whereDate('due_date', '<', $end)
            ->orderBy('due_date')
            ->get()
            ->map(function (Task $task) use ($searchTerm, $statusFilters, $dueFilters, $nextDays): array {
                $editParameters = [
                    'task' => $task,
                    'return_to' => 'calendar',
                ];

                if ($searchTerm !== '') {
                    $editParameters['search'] = $searchTerm;
                }

                if ($statusFilters !== []) {
                    $editParameters['status'] = $statusFilters;
                }
                if ($dueFilters !== []) {
                    $editParameters['due'] = $dueFilters;
                    $editParameters['next_days'] = $nextDays;
                }

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'start' => $task->due_date->toDateString(),
                    'url' => route('tasks.edit', $editParameters),
                    'classNames' => ['task-event--'.$task->status],
                ];
            });

        return response()->json($events);
    }

    public function export(TaskFilterRequest $request): JsonResponse|StreamedResponse
    {
        [
            'search' => $searchTerm,
            'statuses' => $statusFilters,
            'due' => $dueFilters,
            'nextDays' => $nextDays,
        ] = $this->validatedTaskFilters($request);
        $tasks = $this->filteredTaskQuery($searchTerm, $statusFilters, $dueFilters, $nextDays)
            ->orderBy('due_date')
            ->orderBy('position')
            ->orderBy('id');

        if (in_array($request->validated('format'), ['xlsx', 'pdf'], true)) {
            return response()->json([
                'columns' => ['Title', 'Description', 'Created On', 'Start Date', 'Due Date', 'Status'],
                'rows' => $tasks->get()->map(static fn (Task $task): array => [
                    $task->title,
                    $task->description ?? '',
                    $task->created_at->format('Y-m-d H:i:s'),
                    $task->start_date?->toDateString() ?? '',
                    $task->due_date?->toDateString() ?? '',
                    ucwords(str_replace('_', ' ', $task->status)),
                ])->all(),
            ]);
        }

        return response()->streamDownload(static function () use ($tasks): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the task export stream.');
            }

            fputcsv($output, ['Title', 'Description', 'Created On', 'Start Date', 'Due Date', 'Status'], ',', '"', '\\');
            foreach ($tasks->cursor() as $task) {
                fputcsv($output, [
                    $task->title,
                    $task->description ?? '',
                    $task->created_at->format('Y-m-d H:i:s'),
                    $task->start_date?->toDateString() ?? '',
                    $task->due_date?->toDateString() ?? '',
                    ucwords(str_replace('_', ' ', $task->status)),
                ], ',', '"', '\\');
            }

            fclose($output);
        }, 'tasks.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(Request $request): View
    {
        $request->validate([
            'due_date' => ['nullable', 'date'],
        ]);

        $returnToCalendar = $request->query('return_to') === 'calendar';
        $filterQuery = $this->taskFilterQuery($request);
        $returnParameters = $returnToCalendar
            ? array_merge($filterQuery, ['return_to' => 'calendar'])
            : [];
        $formAction = route('tasks.store', $returnParameters);
        $cancelUrl = $returnToCalendar
            ? route('tasks.calendar', $filterQuery)
            : route('tasks.index');

        return view('tasks.create', [
            'task' => new Task([
                'status' => Task::STATUS_PENDING,
                'due_date' => $request->query('due_date'),
            ]),
            'formAction' => $formAction,
            'cancelUrl' => $cancelUrl,
            'taskCount' => Task::query()->count(),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Task::create($request->safe()->except('add_another'));

        return $this->taskChangeRedirect($request, 'Task created successfully.');
    }

    public function edit(Request $request, Task $task): View|Response
    {
        $returnTo = $request->query('return_to');
        $filterQuery = $this->taskFilterQuery($request);
        $returnParameters = $filterQuery;
        if ($returnTo === 'calendar') {
            $returnParameters['return_to'] = 'calendar';
        } elseif ($returnTo === 'dashboard') {
            $returnParameters['return_to'] = 'dashboard';
            $returnParameters['schedule'] = $request->query('schedule', 'all');
        }

        $viewData = [
            'task' => $task,
            'formAction' => route('tasks.update', array_merge(['task' => $task], $returnParameters)),
            'cancelUrl' => match ($returnTo) {
                'calendar' => route('tasks.calendar', $filterQuery),
                'dashboard' => route('dashboard', array_merge($filterQuery, [
                    'schedule' => $request->query('schedule', 'all'),
                ])),
                default => route('tasks.index', $filterQuery),
            },
            'taskCount' => Task::query()->count(),
        ];

        if ($request->boolean('modal')) {
            return response()->view('tasks.partials.edit-modal-content', $viewData);
        }

        return view('tasks.edit', $viewData);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse|RedirectResponse
    {
        $task->update($request->validated());

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Task updated successfully.');

            return response()->json(['message' => 'Task updated successfully.']);
        }

        return $this->taskChangeRedirect($request, 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back()->with('success', 'Task deleted successfully.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());
        $statusLabel = ucwords(str_replace('_', ' ', $task->status));

        return back()->with('success', "Task marked {$statusLabel}.");
    }

    public function reorder(ReorderTasksRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $orderedIds = array_map('intval', $validated['task_ids']);

        DB::transaction(function () use ($validated, $orderedIds): void {
            $currentPage = Task::query()
                ->orderBy('position')
                ->orderBy('id')
                ->forPage($validated['page'], 10)
                ->lockForUpdate()
                ->get(['id', 'position']);

            $currentIds = $currentPage->modelKeys();
            $expectedIds = $orderedIds;
            sort($currentIds);
            sort($expectedIds);

            if ($currentIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'task_ids' => 'The task order is out of date. Refresh the list and try again.',
                ]);
            }

            $positions = $currentPage->pluck('position')->all();

            foreach ($orderedIds as $index => $taskId) {
                Task::query()
                    ->whereKey($taskId)
                    ->update(['position' => $positions[$index]]);
            }
        });

        return back()->with('success', 'Task order saved.');
    }

    private function validatedTaskFilters(TaskFilterRequest $request): array
    {
        $filters = $request->validated();

        return [
            'search' => $filters['search'] ?? '',
            'statuses' => array_values(array_unique((array) ($filters['status'] ?? []))),
            'due' => array_values(array_unique((array) ($filters['due'] ?? []))),
            'nextDays' => (int) ($filters['next_days'] ?? 0),
        ];
    }

    private function filteredTaskQuery(
        string $searchTerm,
        array $statusFilters,
        array $dueFilters = [],
        int $nextDays = 0,
    ): Builder {
        $statuses = [];
        foreach ($statusFilters as $status) {
            if ($status === 'incomplete') {
                $statuses[] = Task::STATUS_PENDING;
                $statuses[] = Task::STATUS_IN_PROGRESS;

                continue;
            }

            $statuses[] = $status;
        }

        $statuses = array_values(array_unique($statuses));
        $today = CarbonImmutable::today()->toDateString();

        return Task::query()
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where(function (Builder $searchQuery) use ($searchTerm): void {
                    $searchQuery
                        ->where('title', 'like', '%'.$searchTerm.'%')
                        ->orWhereRaw("REPLACE(status, '_', ' ') like ?", ['%'.$searchTerm.'%']);
                });
            })
            ->when($statuses !== [], function (Builder $query) use ($statuses): void {
                $query->whereIn('status', $statuses);
            })
            ->when($dueFilters !== [], function (Builder $query) use ($dueFilters, $nextDays, $today): void {
                $query->where(function (Builder $dateQuery) use ($dueFilters, $nextDays, $today): void {
                    $hasDateCondition = false;
                    $addDateCondition = function (string $operator, string $date) use ($dateQuery, &$hasDateCondition): void {
                        if ($hasDateCondition) {
                            $dateQuery->orWhereDate('due_date', $operator, $date);
                        } else {
                            $dateQuery->whereDate('due_date', $operator, $date);
                            $hasDateCondition = true;
                        }
                    };

                    if (in_array('overdue', $dueFilters, true)) {
                        $addDateCondition('<', $today);
                    }

                    if (in_array('today', $dueFilters, true)) {
                        $addDateCondition('=', $today);
                    }

                    if (in_array('next', $dueFilters, true)) {
                        $tomorrow = CarbonImmutable::today()->addDay()->toDateString();
                        $lastDay = CarbonImmutable::today()->addDays($nextDays)->toDateString();
                        $nextDateCondition = function (Builder $nextDateQuery) use ($tomorrow, $lastDay): void {
                            $nextDateQuery
                                ->whereDate('due_date', '>=', $tomorrow)
                                ->whereDate('due_date', '<=', $lastDay);
                        };

                        if ($hasDateCondition) {
                            $dateQuery->orWhere($nextDateCondition);
                        } else {
                            $nextDateCondition($dateQuery);
                        }
                    }
                });
            });
    }

    private function applyDashboardSchedule(Builder $query, string $schedule): Builder
    {
        $today = CarbonImmutable::today()->toDateString();
        $weekEnd = CarbonImmutable::today()->endOfWeek(CarbonImmutable::SUNDAY)->toDateString();

        return match ($schedule) {
            'all' => $query,
            'overdue' => $query
                ->whereDate('due_date', '<', $today)
                ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS]),
            'today' => $query->whereDate('due_date', $today),
            'week' => $query
                ->whereDate('due_date', '>', $today)
                ->whereDate('due_date', '<=', $weekEnd),
            default => $query->where(function (Builder $upcomingQuery) use ($weekEnd): void {
                $upcomingQuery
                    ->whereNull('due_date')
                    ->orWhereDate('due_date', '>', $weekEnd);
            }),
        };
    }

    private function dashboardCounts(
        string $searchTerm,
        array $statusFilters,
        array $dueFilters,
        int $nextDays,
    ): array {
        $today = CarbonImmutable::today()->toDateString();
        $weekEnd = CarbonImmutable::today()->endOfWeek(CarbonImmutable::SUNDAY)->toDateString();
        $filteredQuery = fn (): Builder => $this->filteredTaskQuery($searchTerm, $statusFilters, $dueFilters, $nextDays);

        return [
            'all' => $filteredQuery()->count(),
            'overdue' => $filteredQuery()
                ->whereDate('due_date', '<', $today)
                ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS])
                ->count(),
            'today' => $filteredQuery()->whereDate('due_date', $today)->count(),
            'week' => $filteredQuery()
                ->whereDate('due_date', '>', $today)
                ->whereDate('due_date', '<=', $weekEnd)
                ->count(),
            'upcoming' => $filteredQuery()
                ->where(function (Builder $upcomingQuery) use ($weekEnd): void {
                    $upcomingQuery
                        ->whereNull('due_date')
                        ->orWhereDate('due_date', '>', $weekEnd);
                })
                ->count(),
        ];
    }

    private function taskFilterQuery(Request $request): array
    {
        return array_filter([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'due' => $request->query('due'),
            'next_days' => $request->query('next_days'),
            'view' => in_array($request->query('view'), ['grid', 'list'], true)
                ? $request->query('view')
                : null,
        ], fn ($value): bool => $value !== null && $value !== '' && $value !== []);
    }

    private function taskChangeRedirect(Request $request, string $message): RedirectResponse
    {
        $filterQuery = $this->taskFilterQuery($request);
        $returnTo = $request->query('return_to');
        if ($returnTo === 'calendar') {
            $redirect = redirect()->route('tasks.calendar', $filterQuery);
        } elseif ($returnTo === 'dashboard') {
            $schedule = $request->query('schedule');
            if ($schedule !== null) {
                $filterQuery['schedule'] = $schedule;
            }

            $redirect = redirect()->route('dashboard', $filterQuery);
        } else {
            $redirect = redirect()->route('tasks.index', $filterQuery);
        }

        $redirect->with('success', $message);
        if ($request->boolean('add_another')) {
            $redirect->with('create_another', true);
        }

        return $redirect;
    }
}
