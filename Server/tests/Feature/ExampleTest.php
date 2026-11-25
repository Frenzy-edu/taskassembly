<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_api_root_describes_the_task_endpoint(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertJsonPath('message', 'Task Manager API')
            ->assertJsonPath('tasks_endpoint', url('/api/tasks'));
    }
}
