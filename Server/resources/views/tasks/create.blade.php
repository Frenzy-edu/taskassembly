@extends('layouts.app')

@section('title', 'Create Task')
@section('page-title', 'Create Task')

@section('content')
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Task management</p>
            <h2>Create a task</h2>
            <p>Add the details you need to keep work moving.</p>
        </div>
        <a class="btn btn-light" href="{{ $cancelUrl }}">Cancel</a>
    </div>

    <section class="form-panel" aria-labelledby="task-form-heading">
        <div class="form-panel-header">
            <h2 id="task-form-heading">Task details</h2>
        </div>
        <div class="form-panel-body">
            @include('tasks.partials.form', ['task' => $task, 'formAction' => $formAction, 'cancelUrl' => $cancelUrl])
        </div>
    </section>
@endsection
