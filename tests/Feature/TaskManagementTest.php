<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_list_displays_tasks_and_database_statistics(): void
    {
        Task::factory()->create([
            'title' => 'Prepare the report',
            'status' => Task::STATUS_PENDING,
        ]);
        Task::factory()->create([
            'title' => 'Review the plan',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Prepare the report')
            ->assertSee('Review the plan')
            ->assertViewHas('statistics', [
                'total' => 2,
                'pending' => 1,
                'completed' => 1,
            ]);
    }

    public function test_user_can_create_a_task(): void
    {
        $response = $this->post(route('tasks.store'), [
            'title' => 'Complete the assignment',
            'description' => 'Finish the Laravel assessment.',
            'status' => Task::STATUS_PENDING,
        ]);

        $response->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Complete the assignment',
            'description' => 'Finish the Laravel assessment.',
            'status' => Task::STATUS_PENDING,
        ]);
    }

    public function test_task_description_is_optional(): void
    {
        $this->post(route('tasks.store'), [
            'title' => 'Task without a description',
            'status' => Task::STATUS_PENDING,
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'Task without a description',
            'description' => null,
        ]);
    }

    public function test_task_creation_requires_a_title_and_valid_status(): void
    {
        $this->from(route('tasks.create'))
            ->post(route('tasks.store'), [
                'title' => '',
                'description' => 'A description without a title.',
                'status' => 'in-progress',
            ])
            ->assertRedirect(route('tasks.create'))
            ->assertSessionHasErrors(['title', 'status']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_title_cannot_exceed_255_characters(): void
    {
        $this->from(route('tasks.create'))
            ->post(route('tasks.store'), [
                'title' => str_repeat('a', 256),
                'status' => Task::STATUS_PENDING,
            ])
            ->assertRedirect(route('tasks.create'))
            ->assertSessionHasErrors('title');
    }

    public function test_user_can_update_a_task(): void
    {
        $task = Task::factory()->create([
            'title' => 'Old title',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->put(route('tasks.update', $task), [
            'title' => 'Updated title',
            'description' => null,
            'status' => Task::STATUS_COMPLETED,
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'description' => null,
            'status' => Task::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_edit_form_shows_the_existing_task_values(): void
    {
        $task = Task::factory()->create([
            'title' => 'Review the task',
            'description' => 'Check the current details.',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('value="Review the task"', false)
            ->assertSee('Check the current details.')
            ->assertSee('value="completed"', false);
    }

    public function test_task_updates_require_the_same_valid_fields_as_creation(): void
    {
        $task = Task::factory()->create(['title' => 'Original title']);

        $this->from(route('tasks.edit', $task))
            ->put(route('tasks.update', $task), [
                'title' => '',
                'description' => null,
                'status' => 'in-progress',
            ])
            ->assertRedirect(route('tasks.edit', $task))
            ->assertSessionHasErrors(['title', 'status']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Original title',
        ]);
    }

    public function test_delete_form_requires_confirmation_and_delete_endpoint_removes_task(): void
    {
        $task = Task::factory()->create(['title' => 'Task to remove']);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('data-confirm-delete', false)
            ->assertSee('data-task-title="Task to remove"', false)
            ->assertSee('Task to remove');

        $this->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_can_change_task_status_in_either_direction(): void
    {
        $task = Task::factory()->create(['status' => Task::STATUS_PENDING]);

        $this->patch(route('tasks.status', $task), [
            'status' => Task::STATUS_COMPLETED,
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->patch(route('tasks.status', $task), [
            'status' => Task::STATUS_PENDING,
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_PENDING,
        ]);
    }

    public function test_status_update_rejects_unknown_statuses(): void
    {
        $task = Task::factory()->create(['status' => Task::STATUS_PENDING]);

        $this->from(route('tasks.index'))
            ->patch(route('tasks.status', $task), ['status' => 'in-progress'])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHasErrors('status');

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

        $this->get(route('tasks.index', [
            'search' => 'report',
            'status' => Task::STATUS_PENDING,
        ]))
            ->assertOk()
            ->assertSee('Write weekly report')
            ->assertDontSee('Review report draft')
            ->assertDontSee('Prepare meeting notes')
            ->assertViewHas('tasks', fn ($tasks): bool => $tasks->total() === 1);
    }

    public function test_task_list_is_paginated_and_keeps_filters_in_links(): void
    {
        Task::factory()->count(11)->create([
            'title' => 'Recurring task',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->get(route('tasks.index', [
            'search' => 'Recurring',
            'status' => Task::STATUS_PENDING,
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('search=Recurring', false)
            ->assertSee('status=pending', false)
            ->assertViewHas('tasks', fn ($tasks): bool => $tasks->count() === 1 && $tasks->total() === 11);
    }

    public function test_nonexistent_tasks_return_a_not_found_response(): void
    {
        $this->get(route('tasks.edit', 999))
            ->assertNotFound();

        $this->delete(route('tasks.destroy', 999))
            ->assertNotFound();
    }
}
