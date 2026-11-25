@extends('layouts.app')

@section('title', 'Add Task')

@section('content')
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Task details</p>
            <h1>Add Task</h1>
            <p>Create a task and choose its current status.</p>
        </div>
    </div>

    <section class="card form-panel shadow-sm" aria-labelledby="create-task-heading">
        <div class="form-panel-header">
            <h2 id="create-task-heading">New task</h2>
        </div>

        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <div class="form-panel-body">
                @include('tasks.partials.form', ['task' => $task])
            </div>
            <div class="form-panel-footer">
                <a class="btn btn-light" href="{{ route('tasks.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check2-circle mr-1" aria-hidden="true"></i>
                    Create Task
                </button>
            </div>
        </form>
    </section>
@endsection
