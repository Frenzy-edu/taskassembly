import $ from 'jquery';
import Swal from 'sweetalert2';
import toastr from 'toastr';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'sweetalert2/dist/sweetalert2.min.css';
import 'toastr/build/toastr.min.css';
import './styles.css';

window.$ = window.jQuery = $;

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api')
    .replace(/\/+$/, '');

const elements = {
    addTaskButton: document.querySelector('#addTaskButton'),
    clearFiltersButton: document.querySelector('#clearFiltersButton'),
    description: document.querySelector('#description'),
    descriptionError: document.querySelector('#descriptionError'),
    emptyState: document.querySelector('#emptyState'),
    filterForm: document.querySelector('#filterForm'),
    pagination: document.querySelector('#pagination'),
    searchInput: document.querySelector('#searchInput'),
    status: document.querySelector('#status'),
    statusError: document.querySelector('#statusError'),
    statusFilter: document.querySelector('#statusFilter'),
    statistics: document.querySelector('#statistics'),
    taskCountSummary: document.querySelector('#taskCountSummary'),
    taskForm: document.querySelector('#taskForm'),
    taskListMessage: document.querySelector('#taskListMessage'),
    taskModalTitle: document.querySelector('#taskModalTitle'),
    taskRows: document.querySelector('#taskRows'),
    taskTable: document.querySelector('#taskTable'),
    taskTitle: document.querySelector('#title'),
    taskTitleError: document.querySelector('#titleError'),
    taskId: document.querySelector('#taskId'),
    taskSubmitButton: document.querySelector('#taskSubmitButton'),
};

const state = {
    page: 1,
    search: '',
    status: '',
    tasks: [],
};

toastr.options = {
    closeButton: true,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    preventDuplicates: true,
    timeOut: 3500,
};

async function request(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...options.headers,
    };

    if (options.body) {
        headers['Content-Type'] = 'application/json';
    }

    let response;

    try {
        response = await fetch(`${apiBaseUrl}${path}`, {
            ...options,
            headers,
        });
    } catch (error) {
        console.error('Could not reach the Task Manager API.', error);
        throw new Error('Cannot reach the task server. Make sure the Laravel API is running.');
    }

    const responseText = await response.text();
    let responseData = {};

    if (responseText) {
        try {
            responseData = JSON.parse(responseText);
        } catch (error) {
            console.error('The Task Manager API returned invalid JSON.', error);
            throw new Error('The task server returned an invalid response.');
        }
    }

    if (!response.ok) {
        const error = new Error(responseData.message || `The request failed (${response.status}).`);
        error.validationErrors = responseData.errors || {};
        throw error;
    }

    return responseData;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);
}

function formatDate(value) {
    return new Date(value).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function renderStatistics(statistics) {
    elements.statistics.querySelector('[data-stat="total"]').textContent = statistics.total;
    elements.statistics.querySelector('[data-stat="pending"]').textContent = statistics.pending;
    elements.statistics.querySelector('[data-stat="completed"]').textContent = statistics.completed;
}

function renderTasks(tasks) {
    state.tasks = tasks;

    elements.taskRows.innerHTML = tasks.map((task) => {
        const isCompleted = task.status === 'completed';
        const nextStatus = isCompleted ? 'pending' : 'completed';
        const statusLabel = isCompleted ? 'Completed' : 'Pending';
        const statusClass = isCompleted ? 'badge-success' : 'badge-warning';
        const description = task.description || 'No description provided.';

        return `
            <tr>
                <td>
                    <div class="task-title">${escapeHtml(task.title)}</div>
                    <p class="task-description">${escapeHtml(description)}</p>
                </td>
                <td class="task-date">${escapeHtml(formatDate(task.created_at))}</td>
                <td>
                    <span class="badge badge-pill status-badge ${statusClass}">${statusLabel}</span>
                </td>
                <td>
                    <div class="task-actions">
                        <button
                            class="btn btn-sm btn-outline-success"
                            type="button"
                            data-action="status"
                            data-id="${Number(task.id)}"
                            data-status="${nextStatus}"
                            aria-label="Mark ${escapeHtml(task.title)} ${nextStatus}"
                            title="Mark ${nextStatus}"
                        >
                            <i class="bi ${isCompleted ? 'bi-arrow-counterclockwise' : 'bi-check2'}" aria-hidden="true"></i>
                        </button>
                        <button
                            class="btn btn-sm btn-outline-primary"
                            type="button"
                            data-action="edit"
                            data-id="${Number(task.id)}"
                            aria-label="Edit ${escapeHtml(task.title)}"
                            title="Edit task"
                        >
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger"
                            type="button"
                            data-action="delete"
                            data-id="${Number(task.id)}"
                            aria-label="Delete ${escapeHtml(task.title)}"
                            title="Delete task"
                        >
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination(meta) {
    const firstItem = meta.from ?? 0;
    const lastItem = meta.to ?? 0;

    elements.taskCountSummary.textContent =
        `Showing ${firstItem}–${lastItem} of ${meta.total} tasks`;

    if (meta.last_page <= 1) {
        elements.pagination.innerHTML = '';
        return;
    }

    elements.pagination.innerHTML = `
        <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
            <button class="page-link" type="button" data-page="${meta.current_page - 1}" ${meta.current_page === 1 ? 'disabled' : ''}>
                Previous
            </button>
        </li>
        <li class="page-item disabled" aria-current="page">
            <span class="page-link">Page ${meta.current_page} of ${meta.last_page}</span>
        </li>
        <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
            <button class="page-link" type="button" data-page="${meta.current_page + 1}" ${meta.current_page === meta.last_page ? 'disabled' : ''}>
                Next
            </button>
        </li>
    `;
}

async function loadTasks() {
    const parameters = new URLSearchParams({ page: String(state.page) });

    if (state.search) {
        parameters.set('search', state.search);
    }

    if (state.status) {
        parameters.set('status', state.status);
    }

    elements.taskListMessage.classList.add('d-none');
    elements.taskTable.classList.add('d-none');
    elements.emptyState.classList.add('d-none');
    elements.taskListMessage.textContent = 'Loading tasks...';
    elements.taskListMessage.classList.remove('d-none');

    try {
        const response = await request(`/tasks?${parameters.toString()}`);

        renderStatistics(response.statistics);
        renderPagination(response.meta);
        elements.taskListMessage.classList.add('d-none');

        if (response.data.length === 0) {
            const isFiltered = state.search !== '' || state.status !== '';

            elements.emptyState.querySelector('h3').textContent =
                isFiltered ? 'No matching tasks' : 'No tasks yet';
            elements.emptyState.querySelector('p').textContent = isFiltered
                ? 'Try another search or clear the selected filters.'
                : 'Create your first task to get started.';
            elements.emptyState.querySelector('[data-add-task]').classList.toggle('d-none', isFiltered);
            elements.emptyState.classList.remove('d-none');
            return;
        }

        renderTasks(response.data);
        elements.taskTable.classList.remove('d-none');
    } catch (error) {
        elements.taskListMessage.textContent = error.message;
        toastr.error(error.message);
    }
}

function clearValidationErrors() {
    for (const [field, errorElement] of [
        [elements.taskTitle, elements.taskTitleError],
        [elements.description, elements.descriptionError],
        [elements.status, elements.statusError],
    ]) {
        field.classList.remove('is-invalid');
        errorElement.textContent = '';
        errorElement.classList.add('d-none');
    }
}

function showValidationErrors(errors) {
    const fields = {
        title: [elements.taskTitle, elements.taskTitleError],
        description: [elements.description, elements.descriptionError],
        status: [elements.status, elements.statusError],
    };

    for (const [name, messages] of Object.entries(errors)) {
        const field = fields[name];

        if (!field) {
            continue;
        }

        field[0].classList.add('is-invalid');
        field[1].textContent = messages[0];
        field[1].classList.remove('d-none');
    }

    const firstInvalidField = Object.values(fields).find(([field]) =>
        field.classList.contains('is-invalid')
    );

    firstInvalidField?.[0].focus();
}

function openTaskModal(task = null) {
    clearValidationErrors();
    elements.taskForm.reset();
    elements.taskId.value = task?.id ?? '';
    elements.taskTitle.value = task?.title ?? '';
    elements.description.value = task?.description ?? '';
    elements.status.value = task?.status ?? 'pending';
    elements.taskModalTitle.textContent = task ? 'Edit task' : 'Add a task';
    elements.taskSubmitButton.innerHTML = task
        ? '<i class="bi bi-check2-circle mr-1" aria-hidden="true"></i> Save Changes'
        : '<i class="bi bi-check2-circle mr-1" aria-hidden="true"></i> Save Task';
    $('#taskModal').modal('show');
}

function showRequestError(error) {
    if (Object.keys(error.validationErrors || {}).length > 0) {
        showValidationErrors(error.validationErrors);
        toastr.error('Please correct the highlighted fields.');
        return;
    }

    toastr.error(error.message);
}

async function saveTask(event) {
    event.preventDefault();
    clearValidationErrors();

    const taskId = elements.taskId.value;
    const taskData = {
        title: elements.taskTitle.value.trim(),
        description: elements.description.value.trim() || null,
        status: elements.status.value,
    };

    elements.taskSubmitButton.disabled = true;

    try {
        await request(taskId ? `/tasks/${taskId}` : '/tasks', {
            method: taskId ? 'PUT' : 'POST',
            body: JSON.stringify(taskData),
        });

        $('#taskModal').modal('hide');
        toastr.success(taskId ? 'Task updated successfully.' : 'Task created successfully.');
        await loadTasks();
    } catch (error) {
        showRequestError(error);
    } finally {
        elements.taskSubmitButton.disabled = false;
    }
}

async function changeTaskStatus(taskId, status) {
    try {
        await request(`/tasks/${taskId}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status }),
        });

        toastr.success(`Task marked ${status}.`);
        await loadTasks();
    } catch (error) {
        showRequestError(error);
    }
}

async function deleteTask(task) {
    const result = await Swal.fire({
        title: 'Delete this task?',
        text: `"${task.title}" will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete task',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        reverseButtons: true,
        focusCancel: true,
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await request(`/tasks/${task.id}`, { method: 'DELETE' });
        toastr.success('Task deleted successfully.');
        await loadTasks();
    } catch (error) {
        showRequestError(error);
    }
}

function handleTaskAction(event) {
    const button = event.target.closest('button[data-action]');

    if (!button) {
        return;
    }

    const task = state.tasks.find((item) => item.id === Number(button.dataset.id));

    if (!task) {
        toastr.error('This task is no longer available. Refresh the list and try again.');
        return;
    }

    if (button.dataset.action === 'edit') {
        openTaskModal(task);
    } else if (button.dataset.action === 'status') {
        changeTaskStatus(task.id, button.dataset.status);
    } else if (button.dataset.action === 'delete') {
        deleteTask(task);
    }
}

async function startApplication() {
    await import('bootstrap/dist/js/bootstrap.bundle.min.js');

    $('#taskModal').on('shown.bs.modal', () => elements.taskTitle.focus());

    elements.addTaskButton.addEventListener('click', () => openTaskModal());
    elements.emptyState.addEventListener('click', (event) => {
        if (event.target.closest('[data-add-task]')) {
            openTaskModal();
        }
    });
    elements.taskForm.addEventListener('submit', saveTask);
    elements.taskRows.addEventListener('click', handleTaskAction);
    elements.filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        state.search = elements.searchInput.value.trim();
        state.status = elements.statusFilter.value;
        state.page = 1;
        elements.clearFiltersButton.classList.toggle(
            'd-none',
            state.search === '' && state.status === ''
        );
        loadTasks();
    });
    elements.clearFiltersButton.addEventListener('click', () => {
        elements.searchInput.value = '';
        elements.statusFilter.value = '';
        state.search = '';
        state.status = '';
        state.page = 1;
        elements.clearFiltersButton.classList.add('d-none');
        loadTasks();
    });
    elements.pagination.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-page]');

        if (!button || button.disabled) {
            return;
        }

        state.page = Number(button.dataset.page);
        loadTasks();
    });

    loadTasks();
}

startApplication().catch((error) => {
    console.error('Could not initialize the Task Manager frontend.', error);
    toastr.error('The frontend could not start. Check the browser console for details.');
});
