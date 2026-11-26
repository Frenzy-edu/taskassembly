<div
    class="modal fade task-form-modal task-edit-modal"
    id="editTaskModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="editTaskModalTitle"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" data-edit-modal-content>
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
                <div class="task-modal-loading" role="status">
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    Loading task details…
                </div>
            </div>
        </div>
    </div>
</div>
