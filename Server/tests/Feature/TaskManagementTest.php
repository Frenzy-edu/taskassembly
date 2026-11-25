<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_lists_tasks_and_global_statistics(): void
    {
        Task::factory()->create([
            'title' => 'Prepare the report',
            'status' => Task::STATUS_PENDING,
        ]);
        Task::factory()->create([
            'title' => 'Review the plan',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Prepare the report'])
            ->assertJsonFragment(['title' => 'Review the plan'])
            ->assertJsonPath('statistics.total', 2)
            ->assertJsonPath('statistics.pending', 1)
            ->assertJsonPath('statistics.completed', 1);
    }

    public function test_api_shows_a_single_task(): void
    {
        $task = Task::factory()->create([
            'title' => 'Review the task',
            'description' => 'Check the current details.',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.title', 'Review the task')
            ->assertJsonPath('data.description', 'Check the current details.')
            ->assertJsonPath('data.status', Task::STATUS_COMPLETED);
    }

    public function test_api_creates_a_task(): void
    {
        $this->postJson('/api/tasks', [
            'title' => 'Complete the assignment',
            'description' => 'Finish the assessment.',
            'status' => Task::STATUS_PENDING,
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Complete the assignment')
            ->assertJsonPath('data.status', Task::STATUS_PENDING);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Complete the assignment',
            'description' => 'Finish the assessment.',
            'status' => Task::STATUS_PENDING,
        ]);
    }

    public function test_task_description_is_optional(): void
    {
        $this->postJson('/api/tasks', [
            'title' => 'Task without a description',
            'status' => Task::STATUS_PENDING,
        ])->assertCreated();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Task without a description',
            'description' => null,
        ]);
    }

    public function test_task_creation_requires_a_title_and_valid_status(): void
    {
        $this->postJson('/api/tasks', [
            'title' => '',
            'description' => 'A description without a title.',
            'status' => 'in-progress',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_title_cannot_exceed_255_characters(): void
    {
        $this->postJson('/api/tasks', [
            'title' => str_repeat('a', 256),
            'status' => Task::STATUS_PENDING,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_api_updates_a_task(): void
    {
        $task = Task::factory()->create([
            'title' => 'Old title',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated title',
            'description' => null,
            'status' => Task::STATUS_COMPLETED,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.status', Task::STATUS_COMPLETED);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'description' => null,
            'status' => Task::STATUS_COMPLETED,
        ]);
    }

    public function test_task_updates_require_valid_fields(): void
    {
        $task = Task::factory()->create(['title' => 'Original title']);

        $this->putJson("/api/tasks/{$task->id}", [
            'title' => '',
            'description' => null,
            'status' => 'in-progress',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Original title',
        ]);
    }

    public function test_api_deletes_a_task(): void
    {
        $task = Task::factory()->create(['title' => 'Task to remove']);

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Task deleted successfully.');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_can_change_task_status_in_either_direction(): void
    {
        $task = Task::factory()->create(['status' => Task::STATUS_PENDING]);

        $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => Task::STATUS_COMPLETED,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Task::STATUS_COMPLETED);

        $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => Task::STATUS_PENDING,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Task::STATUS_PENDING);
    }

    public function test_status_update_rejects_unknown_statuses(): void
    {
        $task = Task::factory()->create(['status' => Task::STATUS_PENDING]);

        $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'in-progress',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_PENDING,
        ]);
    }

    public function test_search_and_status_filter_can_be_combined(): void
    {
        Task::factory()->create([
            'title' => 'Write weekly report',
            'status' => Task::STATUS_PENDING,
        ]);
        Task::factory()->create([
            'title' => 'Review report draft',
            'status' => Task::STATUS_COMPLETED,
        ]);
        Task::factory()->create([
            'title' => 'Prepare meeting notes',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->getJson('/api/tasks?search=report&status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Write weekly report');
    }

    public function test_api_pagination_reports_the_requested_page(): void
    {
        Task::factory()->count(11)->create([
            'title' => 'Recurring task',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->getJson('/api/tasks?search=Recurring&status=pending&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Recurring task')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 11);
    }

    public function test_tasks_can_be_reordered_within_their_current_page(): void
    {
        Task::factory()->count(3)->create();
        $orderedIds = Task::query()->orderBy('position')->pluck('id')->all();
        $reversedIds = array_reverse($orderedIds);

        $this->putJson('/api/tasks/reorder', [
            'task_ids' => $reversedIds,
            'page' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Task order updated successfully.');

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $reversedIds[0])
            ->assertJsonPath('data.1.id', $reversedIds[1])
            ->assertJsonPath('data.2.id', $reversedIds[2]);
    }

    public function test_reorder_rejects_tasks_outside_the_requested_page(): void
    {
        Task::factory()->count(11)->create();
        $orderedIds = Task::query()->orderBy('position')->pluck('id')->all();
        $firstPageIds = array_slice($orderedIds, 0, 10);
        $firstPageIds[0] = $orderedIds[10];

        $this->putJson('/api/tasks/reorder', [
            'task_ids' => $firstPageIds,
            'page' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task_ids');

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $orderedIds[0]);
    }

    public function test_nonexistent_tasks_return_not_found_responses(): void
    {
        $this->getJson('/api/tasks/999')->assertNotFound();
        $this->deleteJson('/api/tasks/999')->assertNotFound();
    }

    public function test_api_allows_the_configured_frontend_origin(): void
    {
        $this->getJson('/api/tasks', [
            'Origin' => 'http://127.0.0.1:5173',
        ])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
    }
}
