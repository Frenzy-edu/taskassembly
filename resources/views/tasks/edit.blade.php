@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
    <div class="page-heading">
        <div>
            <p class="page-eyebrow">Task details</p>
            <h1>Edit Task</h1>
            <p>Update the details or status of this task.</p>
        </div>
    </div>

    <section class="card form-panel shadow-sm" aria-labelledby="edit-task-heading">
        <div class="form-panel-header">
            <h2 id="edit-task-heading">Update task</h2>
        </div>

        <form method="POST" action="{{ route('tasks.update', $task) }}">
            @csrf
            @method('PUT')
            <div class="form-panel-body">
                @include('tasks.partials.form', ['task' => $task])
            </div>
            <div class="form-panel-footer">
                <a class="btn btn-light" href="{{ route('tasks.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-check2-circle mr-1" aria-hidden="true"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </section>
@endsection
