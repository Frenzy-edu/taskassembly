<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index(TaskFilterRequest $request): AnonymousResourceCollection
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
            ->orderBy('position')
            ->orderBy('id')
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

        return TaskResource::collection($tasks)
            ->additional(['statistics' => $statistics]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create($request->validated());

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->refresh());
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return new TaskResource($task->refresh());
    }

    public function reorder(ReorderTasksRequest $request): JsonResponse
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

        return response()->json([
            'message' => 'Task order updated successfully.',
        ]);
    }
}
