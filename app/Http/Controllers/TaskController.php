<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(TaskFilterRequest $request): View
    {
        $filters = $request->validated();
        $searchTerm = $filters['search'] ?? '';
        $statusFilter = $filters['status'] ?? '';

        $tasks = Task::query()
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where('title', 'like', '%'.$searchTerm.'%');
            })
            ->when($statusFilter !== '', function (Builder $query) use ($statusFilter): void {
                $query->where('status', $statusFilter);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $statusCounts = Task::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statistics = [
            'total' => $statusCounts->sum(),
            'pending' => $statusCounts->get(Task::STATUS_PENDING, 0),
            'completed' => $statusCounts->get(Task::STATUS_COMPLETED, 0),
        ];

        return view('tasks.index', compact(
            'tasks',
            'statistics',
            'searchTerm',
            'statusFilter'
        ));
    }

    public function create(): View
    {
        return view('tasks.create', ['task' => new Task]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Task::create($request->validated());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function edit(Task $task): View
    {
        return view('tasks.edit', compact('task'));
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return back()->with('success', 'Task status updated successfully.');
    }
}
