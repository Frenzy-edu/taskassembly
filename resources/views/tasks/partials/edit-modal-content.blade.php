<div class="modal-header">
    <h2 class="modal-title" id="editTaskModalTitle">
        <i class="bi bi-pencil-square" aria-hidden="true"></i>
        Edit Task
    </h2>
    <button
        class="close"
        type="button"
        data-dismiss="modal"
        aria-label="Close edit task dialog"
    >
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
</div>
<div class="modal-body">
    @include('tasks.partials.form', [
        'task' => $task,
        'formAction' => $formAction,
        'cancelUrl' => $cancelUrl,
        'inModal' => true,
        'idPrefix' => 'edit-modal-',
    ])
</div>
