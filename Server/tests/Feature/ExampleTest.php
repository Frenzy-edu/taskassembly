<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_home_renders_the_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertViewIs('tasks.index');
    }
}
