<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
