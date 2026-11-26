<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_tasks_and_database_statistics(): void
    {
        Task::factory()->create([
            'title' => 'Prepare the report',
            'status' => Task::STATUS_PENDING,
        ]);
        Task::factory()->create([
            'title' => 'Review the plan',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->get('/?schedule=all')
            ->assertOk()
            ->assertViewIs('tasks.index')
            ->assertViewHas('statistics', [
                'total' => 2,
                'pending' => 1,
                'in_progress' => 0,
                'completed' => 1,
            ])
            ->assertSee('id="createTaskModal"', false)
            ->assertSee('id="editTaskModal"', false)
            ->assertSee('data-edit-task-modal', false)
            ->assertSee('data-target="#createTaskModal"', false)
            ->assertSee('title="Search"', false)
            ->assertSee('title="New task"', false)
            ->assertSee('Add another task after creating this one')
            ->assertSee('data-progress-toggle', false)
            ->assertSee('Prepare the report')
            ->assertSee('Review the plan');
    }

    public function test_create_task_modal_reopens_with_validation_errors(): void
    {
        $this->from('/')
            ->post('/tasks', [
                'title' => '',
                'description' => 'A task without a title.',
                'status' => Task::STATUS_PENDING,
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors(['title']);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-open-on-load="true"', false);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_modal_task_creation_returns_to_its_originating_view(): void
    {
        $this->post('/tasks?return_to=dashboard&schedule=week', [
            'title' => 'Task from dashboard modal',
            'status' => Task::STATUS_PENDING,
        ])
            ->assertRedirect(route('dashboard', ['schedule' => 'week']))
            ->assertSessionHas('success', 'Task created successfully.');

        $this->post('/tasks?return_to=calendar', [
            'title' => 'Task from calendar modal',
            'due_date' => '2026-11-25',
            'status' => Task::STATUS_PENDING,
        ])
            ->assertRedirect(route('tasks.calendar'))
            ->assertSessionHas('success', 'Task created successfully.');
    }

    public function test_create_and_edit_forms_are_server_rendered(): void
    {
        $this->get('/tasks/create')
            ->assertOk()
            ->assertViewIs('tasks.create')
            ->assertSee('name="title"', false)
            ->assertSee('name="description"', false)
            ->assertSee('value="pending"', false)
            ->assertSee('value="in_progress"', false)
            ->assertSee('value="completed"', false);

        $task = Task::factory()->create([
            'title' => 'Existing task',
            'description' => 'Existing description',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->get("/tasks/{$task->id}/edit")
            ->assertOk()
            ->assertViewIs('tasks.edit')
            ->assertSee('value="Existing task"', false)
            ->assertSee('Existing description')
            ->assertSee('checked', false);

        $this->get("/tasks/{$task->id}/edit?modal=1")
            ->assertOk()
            ->assertSee('id="editTaskModalTitle"', false)
            ->assertSee('id="edit-modal-title"', false)
            ->assertSee('value="Existing task"', false)
            ->assertSee('Existing description')
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function test_task_urls_use_unique_slugs_and_legacy_numeric_urls_still_resolve(): void
    {
        $firstTask = Task::factory()->create(['title' => 'Prepare the report']);
        $secondTask = Task::factory()->create(['title' => 'Prepare the report']);

        $this->assertSame('prepare-the-report', $firstTask->slug);
        $this->assertSame('prepare-the-report-2', $secondTask->slug);
        $this->assertSame('/tasks/prepare-the-report/edit', route('tasks.edit', $firstTask, false));

        $this->get(route('tasks.edit', $firstTask))
            ->assertOk()
            ->assertSee('value="Prepare the report"', false);

        $this->get("/tasks/{$firstTask->id}/edit")
            ->assertOk()
            ->assertSee('value="Prepare the report"', false);
    }

    public function test_task_creation_persists_validated_data(): void
    {
        $startDate = now()->toDateString();
        $dueDate = now()->addWeek()->toDateString();

        $this->from('/tasks/create')
            ->post('/tasks', [
                'title' => 'Complete the assignment',
                'description' => 'Finish the assessment.',
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'status' => Task::STATUS_PENDING,
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task created successfully.');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Complete the assignment',
            'description' => 'Finish the assessment.',
            'status' => Task::STATUS_PENDING,
        ]);
        $task = Task::query()->where('title', 'Complete the assignment')->firstOrFail();
        $this->assertSame($startDate, $task->start_date->toDateString());
        $this->assertSame($dueDate, $task->due_date->toDateString());
    }

    public function test_add_another_reopens_a_fresh_create_modal_after_saving(): void
    {
        $this->post('/tasks?return_to=dashboard&schedule=week', [
            'title' => 'First task',
            'status' => Task::STATUS_PENDING,
            'add_another' => '1',
        ])
            ->assertRedirect(route('dashboard', ['schedule' => 'week']))
            ->assertSessionHas('create_another', true)
            ->assertSessionHas('success', 'Task created successfully.');

        $dashboard = $this->get(route('dashboard', ['schedule' => 'week']))
            ->assertOk()
            ->assertSee('data-open-on-load="true"', false);

        $this->assertMatchesRegularExpression(
            '/<input[^>]*id="modal-title"[^>]*value=""/s',
            $dashboard->getContent(),
        );

        $this->assertDatabaseHas('tasks', [
            'title' => 'First task',
            'status' => Task::STATUS_PENDING,
        ]);
    }

    public function test_calendar_events_include_only_tasks_in_the_requested_date_range(): void
    {
        $scheduledTask = Task::factory()->create([
            'title' => 'Scheduled task',
            'due_date' => '2026-11-25',
            'status' => Task::STATUS_PENDING,
        ]);
        Task::factory()->create([
            'title' => 'Outside requested range',
            'due_date' => '2026-12-01',
        ]);
        Task::factory()->create([
            'title' => 'Unscheduled task',
            'due_date' => null,
        ]);

        $this->getJson(route('tasks.calendar.events', [
            'start' => '2026-11-22',
            'end' => '2026-11-29',
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $scheduledTask->id)
            ->assertJsonPath('0.title', 'Scheduled task')
            ->assertJsonPath('0.start', '2026-11-25')
            ->assertJsonPath('0.classNames', ['task-event--pending']);
    }

    public function test_calendar_uses_the_reference_header_without_get_started(): void
    {
        $this->get(route('tasks.calendar'))
            ->assertOk()
            ->assertSee('<h1 class="topbar-title">All Tasks</h1>', false)
            ->assertSee('aria-label="Grid view"', false)
            ->assertSee('aria-label="List view"', false)
            ->assertSee('aria-label="Calendar view"', false)
            ->assertSee('Save as CSV')
            ->assertSee('Save as PDF')
            ->assertSee('Save as Excel')
            ->assertSee('Print Todo-List')
            ->assertSee('data-calendar-view="dayGridWeek"', false)
            ->assertDontSee('Get started');
    }

    public function test_task_workspace_tabs_share_the_calendar_header_and_search(): void
    {
        $pages = [
            [route('dashboard'), 'Dashboard'],
            [route('tasks.index'), 'All Tasks'],
            [route('tasks.index', ['view' => 'grid']), 'All Tasks'],
            [route('tasks.index', ['status' => Task::STATUS_COMPLETED]), 'Completed Tasks'],
            [route('tasks.index', ['status' => Task::STATUS_PENDING]), 'Pending Tasks'],
            [route('tasks.calendar'), 'All Tasks'],
        ];

        foreach ($pages as [$url, $title]) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<header class="topbar workspace-topbar">', false)
                ->assertSee('<div class="workspace-topbar-actions">', false)
                ->assertSee('<h1 class="topbar-title">'.$title.'</h1>', false)
                ->assertSee('aria-label="Calendar view"', false)
                ->assertSee('id="globalSearch"', false)
                ->assertSee('Not started')
                ->assertSee('Marked in progress')
                ->assertSee('Incomplete tasks')
                ->assertSee('Completed tasks')
                ->assertSee('Due next')
                ->assertDontSee('TaskFlow workspace')
                ->assertDontSee('Task management')
                ->assertDontSee('Add New Task');
        }
    }

    public function test_dashboard_header_filters_preserve_the_selected_schedule(): void
    {
        $this->get(route('dashboard', ['schedule' => 'week']))
            ->assertOk()
            ->assertSee('action="'.route('dashboard').'"', false)
            ->assertSee('name="schedule" value="week"', false);
    }

    public function test_task_grid_view_renders_cards_and_keeps_the_view_selection(): void
    {
        Task::factory()->create([
            'title' => 'Card view task',
            'description' => 'Task description',
        ]);

        $this->get('/tasks?view=grid')
            ->assertOk()
            ->assertViewHas('layoutView', 'grid')
            ->assertSee('class="task-card-grid"', false)
            ->assertSee('Card view task')
            ->assertSee('Task description')
            ->assertSee('view=grid', false);
    }

    public function test_tasks_can_be_downloaded_as_a_csv_file(): void
    {
        Task::factory()->create(['title' => 'Exported task']);

        $this->get(route('tasks.export'))
            ->assertOk()
            ->assertDownload('tasks.csv');
    }

    public function test_task_creation_rejects_missing_title_and_invalid_status(): void
    {
        $this->from('/tasks/create')
            ->post('/tasks', [
                'title' => '',
                'description' => 'Description without a title.',
                'status' => 'in-progress',
            ])
            ->assertRedirect('/tasks/create')
            ->assertSessionHasErrors(['title', 'status']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_updates_persist_and_validate_fields(): void
    {
        $task = Task::factory()->create([
            'title' => 'Original title',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->put("/tasks/{$task->id}", [
            'title' => 'Updated title',
            'description' => null,
            'start_date' => '2026-11-20',
            'due_date' => '2026-11-25',
            'status' => Task::STATUS_COMPLETED,
        ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task updated successfully.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'description' => null,
            'status' => Task::STATUS_COMPLETED,
        ]);
        $task->refresh();
        $this->assertSame('2026-11-20', $task->start_date->toDateString());
        $this->assertSame('2026-11-25', $task->due_date->toDateString());

        $this->from("/tasks/{$task->id}/edit")
            ->put("/tasks/{$task->id}", [
                'title' => '',
                'description' => null,
                'status' => 'unknown',
            ])
            ->assertRedirect("/tasks/{$task->id}/edit")
            ->assertSessionHasErrors(['title', 'status']);
    }

    public function test_task_modal_updates_return_json_and_validate_inline_errors(): void
    {
        $task = Task::factory()->create([
            'title' => 'Original modal task',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->putJson("/tasks/{$task->id}?return_to=dashboard&schedule=week", [
            'title' => 'Updated from modal',
            'description' => 'Updated description',
            'status' => Task::STATUS_IN_PROGRESS,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Task updated successfully.')
            ->assertSessionHas('success', 'Task updated successfully.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated from modal',
            'description' => 'Updated description',
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $this->putJson("/tasks/{$task->id}", [
            'title' => '',
            'description' => null,
            'status' => 'unknown',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status']);
    }

    public function test_task_status_can_be_changed_in_either_direction(): void
    {
        $task = Task::factory()->create([
            'status' => Task::STATUS_PENDING,
        ]);

        $this->from('/tasks')
            ->patch("/tasks/{$task->id}/status", [
                'status' => Task::STATUS_COMPLETED,
            ])
            ->assertRedirect('/tasks')
            ->assertSessionHas('success', 'Task marked Completed.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->from('/tasks?status=completed')
            ->patch("/tasks/{$task->id}/status", [
                'status' => Task::STATUS_PENDING,
            ])
            ->assertRedirect('/tasks?status=completed');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_PENDING,
        ]);
    }

    public function test_task_deletion_removes_the_task_after_the_confirmed_form_submission(): void
    {
        $task = Task::factory()->create(['title' => 'Task to remove']);

        $this->from('/tasks')
            ->delete("/tasks/{$task->id}")
            ->assertRedirect('/tasks')
            ->assertSessionHas('success', 'Task deleted successfully.');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_search_matches_title_or_status_and_combines_with_status_filter(): void
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

        $this->get('/tasks?search=completed')
            ->assertOk()
            ->assertSee('Review report draft')
            ->assertDontSee('Write weekly report');

        $this->get('/tasks?search=report&status=pending')
            ->assertOk()
            ->assertSee('Write weekly report')
            ->assertDontSee('Review report draft')
            ->assertDontSee('Prepare meeting notes');
    }

    public function test_status_filter_supports_multiple_selected_statuses(): void
    {
        Task::factory()->create([
            'title' => 'Pending task',
            'status' => Task::STATUS_PENDING,
        ]);
        Task::factory()->create([
            'title' => 'Completed task',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->get('/tasks?status%5B%5D=pending&status%5B%5D=completed')
            ->assertOk()
            ->assertSee('Pending task')
            ->assertSee('Completed task');
    }

    public function test_incomplete_filter_includes_pending_and_in_progress_tasks(): void
    {
        Task::factory()->create(['title' => 'Pending record', 'status' => Task::STATUS_PENDING]);
        Task::factory()->create(['title' => 'Active record', 'status' => Task::STATUS_IN_PROGRESS]);
        Task::factory()->create(['title' => 'Completed record', 'status' => Task::STATUS_COMPLETED]);

        $this->get(route('tasks.index', ['status' => 'incomplete']))
            ->assertOk()
            ->assertSee('Pending record')
            ->assertSee('Active record')
            ->assertDontSee('Completed record');

        $this->get(route('tasks.index', ['status' => Task::STATUS_IN_PROGRESS]))
            ->assertOk()
            ->assertSee('Active record')
            ->assertDontSee('Pending record')
            ->assertDontSee('Completed record');
    }

    public function test_due_next_filter_excludes_today_and_limits_the_selected_days(): void
    {
        $this->travelTo(now()->startOfDay());
        $today = today();

        try {
            Task::factory()->create(['title' => 'Yesterday task', 'due_date' => $today->copy()->subDay()]);
            Task::factory()->create(['title' => 'Today task', 'due_date' => $today]);
            Task::factory()->create(['title' => 'Tomorrow task', 'due_date' => $today->copy()->addDay()]);
            Task::factory()->create(['title' => 'Two-day task', 'due_date' => $today->copy()->addDays(2)]);
            Task::factory()->create(['title' => 'Three-day task', 'due_date' => $today->copy()->addDays(3)]);

            $this->get(route('tasks.index', [
                'due' => ['next'],
                'next_days' => 2,
            ]))
                ->assertOk()
                ->assertSee('Tomorrow task')
                ->assertSee('Two-day task')
                ->assertDontSee('Yesterday task')
                ->assertDontSee('Today task')
                ->assertDontSee('Three-day task');
        } finally {
            $this->travelBack();
        }
    }

    public function test_excel_export_returns_all_filtered_rows_and_columns(): void
    {
        Task::factory()->create([
            'title' => 'Excel pending row',
            'status' => Task::STATUS_PENDING,
        ]);
        Task::factory()->create([
            'title' => 'Excel completed row',
            'status' => Task::STATUS_COMPLETED,
        ]);

        $this->getJson(route('tasks.export', [
            'format' => 'xlsx',
            'status' => 'incomplete',
        ]))
            ->assertOk()
            ->assertJsonPath('columns.0', 'Title')
            ->assertJsonPath('columns.5', 'Status')
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.0', 'Excel pending row');

        $this->getJson(route('tasks.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertJsonPath('columns.0', 'Title')
            ->assertJsonPath('columns.5', 'Status');
    }

    public function test_pagination_keeps_search_and_status_filters(): void
    {
        Task::factory()->count(11)->create([
            'title' => 'Recurring report task',
            'status' => Task::STATUS_PENDING,
        ]);

        $this->get('/tasks?search=report&status=pending&page=2')
            ->assertOk()
            ->assertViewHas('tasks', fn ($tasks): bool => $tasks->currentPage() === 2
                && $tasks->total() === 11
                && $tasks->count() === 1)
            ->assertSee('name="search" value="report"', false)
            ->assertSee('name="status[]" value="pending"', false);
    }

    public function test_tasks_can_be_reordered_on_the_current_page(): void
    {
        Task::factory()->count(3)->create();
        $orderedIds = Task::query()->orderBy('position')->pluck('id')->all();
        $reversedIds = array_reverse($orderedIds);

        $this->from('/tasks')
            ->post('/tasks/reorder', [
                'task_ids' => $reversedIds,
                'page' => 1,
            ])
            ->assertRedirect('/tasks')
            ->assertSessionHas('success', 'Task order saved.');

        $this->assertSame(
            $reversedIds,
            Task::query()->orderBy('position')->pluck('id')->all(),
        );
    }

    public function test_reordering_rejects_tasks_outside_the_requested_page(): void
    {
        Task::factory()->count(11)->create();
        $orderedIds = Task::query()->orderBy('position')->pluck('id')->all();
        $firstPageIds = array_slice($orderedIds, 0, 10);
        $firstPageIds[0] = $orderedIds[10];

        $this->from('/tasks')
            ->post('/tasks/reorder', [
                'task_ids' => $firstPageIds,
                'page' => 1,
            ])
            ->assertRedirect('/tasks')
            ->assertSessionHasErrors('task_ids');

        $this->assertSame(
            $orderedIds,
            Task::query()->orderBy('position')->pluck('id')->all(),
        );
    }

    public function test_the_old_json_api_is_not_registered(): void
    {
        $this->getJson('/api/tasks')->assertNotFound();
    }
}
