const Sortable = window.Sortable;
const Swal = window.Swal;
const $ = window.jQuery;
const { Calendar } = window.FullCalendar;

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
    showClass: { popup: '' },
    hideClass: { popup: '' },
});

const flashMessage = document.querySelector('#flash-message');

if (flashMessage?.dataset.message) {
    toast.fire({
        icon: flashMessage.dataset.type === 'success' ? 'success' : 'error',
        title: flashMessage.dataset.message,
    });
}

const createTaskModal = document.querySelector('#createTaskModal');
const editTaskModal = document.querySelector('#editTaskModal');
const editModalContent = editTaskModal?.querySelector('[data-edit-modal-content]');

if (createTaskModal) {
    $(createTaskModal).on('shown.bs.modal', () => {
        createTaskModal.querySelector('[data-task-title-input]')?.focus({ preventScroll: true });
    });
    $(createTaskModal).on('hidden.bs.modal', () => {
        const form = createTaskModal.querySelector('form');
        form?.reset();
        form?.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        form?.querySelectorAll('[data-progress-date]').forEach((field) => {
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });
        form?.querySelector('[data-task-title-input]')?.dispatchEvent(new Event('input', { bubbles: true }));
        form?.querySelectorAll('[data-progress-toggle]').forEach((toggle) => {
            toggle.setAttribute('aria-expanded', 'false');
            const details = document.getElementById(toggle.getAttribute('aria-controls'));
            if (details) {
                details.hidden = true;
            }
        });
    });

    if (createTaskModal.dataset.openOnLoad === 'true') {
        $(createTaskModal).modal('show');
        createTaskModal.dataset.openOnLoad = 'false';
    }
}

function formatProgressDate(value) {
    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime())
        ? ''
        : new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(date);
}

function initializeTaskForm(form) {
    if (form.dataset.initialized === 'true') {
        return;
    }

    form.dataset.initialized = 'true';
    form.querySelectorAll('[data-progress-date]').forEach((field) => {
        const summary = form.querySelector(`[data-progress-summary="${field.dataset.progressDate}"]`);
        const updateSummary = () => {
            if (summary) {
                summary.textContent = field.value
                    ? formatProgressDate(field.value)
                    : field.dataset.emptyLabel;
            }
        };

        field.addEventListener('input', updateSummary);
        field.addEventListener('change', updateSummary);
        updateSummary();
    });

    const titleField = form.querySelector('[data-task-title-input]');
    const submitButton = form.querySelector('[data-task-submit]');

    if (titleField && submitButton && form.closest('#createTaskModal')) {
        const updateSubmitState = () => {
            submitButton.disabled = titleField.value.trim() === '';
        };

        titleField.addEventListener('input', updateSubmitState);
        updateSubmitState();
    }
}

function initializeProgressToggles(container = document) {
    container.querySelectorAll('[data-progress-toggle]').forEach((toggle) => {
        if (toggle.dataset.initialized === 'true') {
            return;
        }

        const details = document.getElementById(toggle.getAttribute('aria-controls'));
        if (!details) {
            return;
        }

        toggle.dataset.initialized = 'true';
        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!isExpanded));
            details.hidden = isExpanded;
        });
    });
}

document.querySelectorAll('.task-form').forEach(initializeTaskForm);
initializeProgressToggles();

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-delete-form]')) {
        return;
    }

    if (form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    const result = await Swal.fire({
        title: 'Confirm Deletion',
        text: `Are you sure you want to delete “${form.dataset.taskTitle}”?`,
        icon: 'warning',
        scrollbarPadding: false,
        showCancelButton: true,
        confirmButtonText: 'Delete task',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        showClass: { popup: '' },
        hideClass: { popup: '' },
        customClass: {
            popup: 'task-delete-confirmation',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    form.dataset.confirmed = 'true';
    form.requestSubmit();
});

const sidebarToggle = document.querySelector('#sidebarToggle');
const sidebarCollapseToggle = document.querySelector('#sidebarCollapseToggle');
const sidebarOverlay = document.querySelector('#sidebarOverlay');
const filterToggle = document.querySelector('[data-filter-toggle]');
const filterPopover = document.querySelector('[data-filter-popover]');
const exportToggle = document.querySelector('[data-export-toggle]');
const exportPopover = document.querySelector('[data-export-popover]');

$('[data-task-tooltip]').tooltip({
    container: 'body',
    placement: 'bottom',
    trigger: 'hover focus',
    template: '<div class="tooltip task-onboarding-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>',
});

function closeSidebar() {
    document.body.classList.remove('sidebar-open');
    sidebarToggle?.setAttribute('aria-expanded', 'false');
}

const initialEditModalContent = editModalContent?.innerHTML ?? '';
let editModalRequest = null;

if (editTaskModal && editModalContent) {
    $(editTaskModal).on('shown.bs.modal', () => {
        editModalContent.querySelector('[data-task-title-input]')?.focus({ preventScroll: true });
    });
    $(editTaskModal).on('hidden.bs.modal', () => {
        editModalRequest?.abort();
        editModalContent.innerHTML = initialEditModalContent;
    });
}

function getTaskEditModalUrl(href) {
    const editUrl = new URL(href, window.location.origin);
    const currentUrl = new URL(window.location.href);

    ['search', 'status[]', 'due[]', 'next_days', 'view', 'schedule'].forEach((key) => {
        if (!editUrl.searchParams.has(key)) {
            currentUrl.searchParams.getAll(key).forEach((value) => {
                editUrl.searchParams.append(key, value);
            });
        }
    });

    if (currentUrl.pathname === '/') {
        editUrl.searchParams.set('return_to', 'dashboard');
        editUrl.searchParams.set('schedule', currentUrl.searchParams.get('schedule') || 'all');
    } else if (currentUrl.pathname.endsWith('/calendar')) {
        editUrl.searchParams.set('return_to', 'calendar');
    }

    editUrl.searchParams.set('modal', '1');

    return editUrl;
}

async function openTaskEditModal(href) {
    if (!editTaskModal || !editModalContent) {
        window.location.assign(href);
        return;
    }

    editModalRequest?.abort();
    const request = new AbortController();
    editModalRequest = request;
    editModalContent.innerHTML = initialEditModalContent;
    $(editTaskModal).modal('show');

    try {
        const response = await fetch(getTaskEditModalUrl(href), {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: request.signal,
        });
        if (!response.ok) {
            throw new Error('The task details could not be loaded.');
        }

        editModalContent.innerHTML = await response.text();
        editModalContent.querySelectorAll('.task-form').forEach(initializeTaskForm);
        initializeProgressToggles(editModalContent);
        editModalContent.querySelector('[data-task-title-input]')?.focus({ preventScroll: true });
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error(error);
            $(editTaskModal).modal('hide');
            toast.fire({
                icon: 'error',
                title: 'Unable to open edit form',
                text: error.message,
            });
        }
    } finally {
        if (editModalRequest === request) {
            editModalRequest = null;
        }
    }
}

function clearEditFormErrors(form) {
    form.querySelectorAll('[data-edit-field-error]').forEach((feedback) => {
        const field = Array.from(form.elements).find((element) => element.name === feedback.dataset.editFieldError);
        if (field) {
            field.classList.remove('is-invalid');
            const descriptionIds = (field.getAttribute('aria-describedby') || '')
                .split(/\s+/)
                .filter((id) => id && id !== feedback.id);
            if (descriptionIds.length > 0) {
                field.setAttribute('aria-describedby', descriptionIds.join(' '));
            } else {
                field.removeAttribute('aria-describedby');
            }
        }
        feedback.remove();
    });
}

function showEditFormErrors(form, errors) {
    clearEditFormErrors(form);

    Object.entries(errors).forEach(([name, messages]) => {
        const field = Array.from(form.elements).find((element) => element.name === name);
        if (!field) {
            return;
        }

        field.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block';
        feedback.id = `${field.id}-error`;
        feedback.dataset.editFieldError = name;
        feedback.textContent = messages[0];

        const descriptionIds = (field.getAttribute('aria-describedby') || '')
            .split(/\s+/)
            .filter(Boolean);
        descriptionIds.push(feedback.id);
        field.setAttribute('aria-describedby', descriptionIds.join(' '));

        const fieldContainer = field.closest('.task-progress-step-field')
            || field.closest('.form-group')
            || field.parentElement;
        fieldContainer.append(feedback);
    });

    form.querySelector('.is-invalid')?.focus({ preventScroll: true });
}

editTaskModal?.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('task-form')) {
        return;
    }

    event.preventDefault();
    clearEditFormErrors(form);
    const submitButton = form.querySelector('[data-task-submit]');
    if (submitButton) {
        submitButton.disabled = true;
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });
        const result = await response.json().catch(() => ({}));

        if (response.status === 422 && result.errors) {
            showEditFormErrors(form, result.errors);
            return;
        }
        if (!response.ok) {
            throw new Error(result.message || 'The task could not be updated.');
        }

        const modalHidden = new Promise((resolve) => {
            $(editTaskModal).one('hidden.bs.modal', resolve);
        });
        $(editTaskModal).modal('hide');
        await modalHidden;
        window.location.reload();
    } catch (error) {
        console.error(error);
        toast.fire({
            icon: 'error',
            title: 'Task update failed',
            text: error.message,
        });
    } finally {
        if (submitButton?.isConnected) {
            submitButton.disabled = false;
        }
    }
});

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const link = event.target.closest('a[data-edit-task-modal]');
    if (
        !link ||
        !editTaskModal ||
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return;
    }

    event.preventDefault();
    closeSidebar();
    openTaskEditModal(link.href);
});

sidebarToggle?.addEventListener('click', () => {
    const isOpen = document.body.classList.toggle('sidebar-open');
    sidebarToggle.setAttribute('aria-expanded', String(isOpen));
});

sidebarCollapseToggle?.addEventListener('click', () => {
    const isExpanded = document.body.classList.toggle('sidebar-collapsed') === false;
    sidebarCollapseToggle.setAttribute('aria-expanded', String(isExpanded));
    sidebarCollapseToggle.setAttribute(
        'aria-label',
        isExpanded ? 'Collapse navigation' : 'Expand navigation',
    );
    sidebarCollapseToggle.setAttribute(
        'title',
        isExpanded ? 'Collapse navigation' : 'Expand navigation',
    );
});

sidebarOverlay?.addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar-nav .nav-link').forEach((link) => {
    link.addEventListener('click', closeSidebar);
});
document.querySelectorAll('[data-target="#createTaskModal"]').forEach((button) => {
    button.addEventListener('click', closeSidebar);
});
document.querySelector('[data-focus-search]')?.addEventListener('click', () => {
    closeSidebar();
    const searchInput = document.querySelector('#globalSearch');
    const searchForm = searchInput?.closest('form');

    if (searchForm?.hasAttribute('data-search-popover')) {
        searchForm.hidden = !searchForm.hidden;
        if (!searchForm.hidden) {
            searchInput.focus({ preventScroll: true });
        }
        return;
    }

    searchInput?.focus({ preventScroll: true });
});

document.addEventListener('click', (event) => {
    const searchForm = document.querySelector('[data-search-popover]');

    if (
        searchForm &&
        !searchForm.hidden &&
        !searchForm.contains(event.target) &&
        !event.target.closest('[data-focus-search]')
    ) {
        searchForm.hidden = true;
    }
});

function setFilterPopoverOpen(isOpen) {
    if (!filterToggle || !filterPopover) {
        return;
    }

    filterPopover.hidden = !isOpen;
    filterToggle.setAttribute('aria-expanded', String(isOpen));

    if (isOpen) {
        filterPopover.querySelector('input[type="checkbox"]')?.focus({ preventScroll: true });
    }
}

filterToggle?.addEventListener('click', () => {
    setFilterPopoverOpen(filterPopover?.hidden ?? true);
});

filterPopover?.addEventListener('change', (event) => {
    const input = event.target;
    if (
        input instanceof HTMLInputElement &&
        input.matches('[data-filter-submit], [data-filter-next-days]')
    ) {
        input.form?.requestSubmit();
    }
});

filterPopover?.addEventListener('click', (event) => {
    const button = event.target instanceof Element
        ? event.target.closest('[data-filter-step]')
        : null;
    const nextDaysInput = filterPopover.querySelector('[data-filter-next-days]');

    if (!button || !nextDaysInput) {
        return;
    }

    const increment = Number(button.dataset.filterStep);
    const nextValue = Math.min(365, Math.max(0, Number(nextDaysInput.value || 0) + increment));
    nextDaysInput.value = String(nextValue);
    nextDaysInput.form?.requestSubmit();
});

filterPopover?.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || !event.target.closest('[data-filter-close]')) {
        return;
    }

    setFilterPopoverOpen(false);
    filterToggle?.focus({ preventScroll: true });
});

filterPopover?.addEventListener('submit', (event) => {
    event.preventDefault();

    if (!filterPopover) {
        return;
    }

    const targetUrl = new URL(filterPopover.action, window.location.href);
    targetUrl.search = new URLSearchParams(new FormData(filterPopover)).toString();
    navigateWorkspace(targetUrl.href);
});

document.addEventListener('click', (event) => {
    if (
        filterPopover &&
        !filterPopover.hidden &&
        !filterPopover.closest('[data-filter-menu]')?.contains(event.target)
    ) {
        setFilterPopoverOpen(false);
    }
});

function setExportPopoverOpen(isOpen) {
    if (!exportToggle || !exportPopover) {
        return;
    }

    exportPopover.hidden = !isOpen;
    exportToggle.setAttribute('aria-expanded', String(isOpen));
}

exportToggle?.addEventListener('click', () => {
    setExportPopoverOpen(exportPopover?.hidden ?? true);
});

document.addEventListener('click', (event) => {
    if (
        exportPopover &&
        !exportPopover.hidden &&
        !exportPopover.closest('[data-export-menu]')?.contains(event.target)
    ) {
        setExportPopoverOpen(false);
    }
});

document.querySelectorAll('[data-export-action]').forEach((button) => {
    button.addEventListener('click', () => {
        setExportPopoverOpen(false);
        document.title = 'Task List';
        window.addEventListener('afterprint', () => {
            document.title = document.querySelector('.topbar-title')?.textContent.trim() || 'TaskFlow';
        }, { once: true });
        window.print();
    });
});

async function loadExportData(url) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
        throw new Error('The task export could not be loaded.');
    }

    return response.json();
}

function downloadBlob(file, filename) {
    const downloadUrl = URL.createObjectURL(file);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    document.body.append(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
}

function showExportError() {
    Swal.fire({
        icon: 'error',
        title: 'Export failed',
        text: 'The file could not be created. Please try again.',
        buttonsStyling: false,
        customClass: { confirmButton: 'btn btn-primary' },
    });
}

document.querySelector('[data-export-xlsx]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;

    try {
        const data = await loadExportData(button.dataset.exportUrl);
        const workbook = new window.ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Tasks');
        worksheet.addRow(data.columns);
        worksheet.getRow(1).font = { bold: true };
        worksheet.addRows(data.rows);
        worksheet.columns = data.columns.map((heading) => ({
            header: heading,
            width: Math.min(40, Math.max(14, heading.length + 4)),
        }));

        const contents = await workbook.xlsx.writeBuffer();
        const file = new Blob([contents], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        downloadBlob(file, 'tasks.xlsx');
        setExportPopoverOpen(false);
    } catch (error) {
        console.error(error);
        showExportError();
    } finally {
        button.disabled = false;
    }
});

document.querySelector('[data-export-pdf]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;

    try {
        const data = await loadExportData(button.dataset.exportUrl);
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape' });
        pdf.setFontSize(18);
        pdf.text('Task List', 14, 16);
        pdf.autoTable({
            head: [data.columns],
            body: data.rows,
            startY: 23,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [51, 51, 51] },
        });
        downloadBlob(pdf.output('blob'), 'tasks.pdf');
        setExportPopoverOpen(false);
    } catch (error) {
        console.error(error);
        showExportError();
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSidebar();
        const searchPopover = document.querySelector('[data-search-popover]');
        if (searchPopover && !searchPopover.hidden) {
            searchPopover.hidden = true;
        }
        if (filterPopover && !filterPopover.hidden) {
            setFilterPopoverOpen(false);
            filterToggle?.focus({ preventScroll: true });
        }
        if (exportPopover && !exportPopover.hidden) {
            setExportPopoverOpen(false);
            exportToggle?.focus({ preventScroll: true });
        }
    }
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
let sortableInstance = null;

function initializeSortable(container = document) {
    sortableInstance?.destroy();
    sortableInstance = null;

    const taskList = container.querySelector('[data-sortable-list]');
    if (taskList?.dataset.sortableEnabled !== 'true' || !csrfToken) {
        return;
    }

    let orderBeforeDrag = [];

    sortableInstance = Sortable.create(taskList, {
        animation: 120,
        draggable: 'tr[data-task-id]',
        forceFallback: true,
        handle: '.task-drag-handle',
        onStart() {
            orderBeforeDrag = Array.from(taskList.querySelectorAll('tr[data-task-id]'))
                .map((row) => row.dataset.taskId);
        },
        async onEnd(event) {
            if (event.oldIndex === event.newIndex) {
                return;
            }

            const formData = new URLSearchParams();
            Array.from(taskList.querySelectorAll('tr[data-task-id]')).forEach((row, index) => {
                formData.append(`task_ids[${index}]`, row.dataset.taskId);
            });
            formData.append('page', taskList.dataset.sortablePage);

            try {
                const response = await fetch(taskList.dataset.sortableUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'text/html',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error('The task order could not be saved.');
                }

                window.location.reload();
            } catch (error) {
                const rowsById = new Map(
                    Array.from(taskList.querySelectorAll('tr[data-task-id]'))
                        .map((row) => [row.dataset.taskId, row]),
                );
                orderBeforeDrag.forEach((taskId) => {
                    const row = rowsById.get(taskId);
                    if (row) {
                        taskList.append(row);
                    }
                });

                Swal.fire({
                    icon: 'error',
                    title: 'Could not save order',
                    text: error.message,
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn btn-primary' },
                });
            }

        },
    });
}

initializeSortable();

let calendarInstance = null;

function initializeCalendar(container = document) {
    calendarInstance?.destroy();
    calendarInstance = null;

    const calendarElement = container.querySelector('#taskCalendar');
    if (!calendarElement) {
        return;
    }

    const calendarTitle = container.querySelector('[data-calendar-title]');
    const emptyMessage = container.querySelector('[data-calendar-empty]');
    const searchTerm = calendarElement.dataset.search || '';
    const statusFilters = (calendarElement.dataset.statuses || '')
        .split(',')
        .filter(Boolean);
    const dueFilters = (calendarElement.dataset.dueFilters || '')
        .split(',')
        .filter(Boolean);
    const nextDays = calendarElement.dataset.nextDays || '0';

    const openCreateTaskModal = (dateString) => {
        const dueDate = dateString.slice(0, 10);
        const createTaskModal = document.querySelector('#createTaskModal');
        const dueDateField = createTaskModal?.querySelector('#modal-due_date');

        if (createTaskModal && dueDateField) {
            dueDateField.value = dueDate;
            dueDateField.dispatchEvent(new Event('input', { bubbles: true }));
            $(createTaskModal).modal('show');
            return;
        }

        const createUrl = new URL(calendarElement.dataset.createUrl, window.location.origin);
        createUrl.searchParams.set('due_date', dueDate);
        createUrl.searchParams.set('return_to', 'calendar');
        if (searchTerm) {
            createUrl.searchParams.set('search', searchTerm);
        }
        statusFilters.forEach((status) => createUrl.searchParams.append('status[]', status));
        dueFilters.forEach((dueFilter) => createUrl.searchParams.append('due[]', dueFilter));
        if (dueFilters.includes('next')) {
            createUrl.searchParams.set('next_days', nextDays);
        }
        window.location.assign(createUrl);
    };

    const calendar = new Calendar(calendarElement, {
        initialView: 'dayGridWeek',
        firstDay: 0,
        headerToolbar: false,
        height: calendarElement.dataset.fullHeight === 'true' ? '100%' : 'auto',
        expandRows: calendarElement.dataset.fullHeight === 'true',
        fixedWeekCount: false,
        dayMaxEvents: true,
        dayHeaderContent: (info) => {
            const header = document.createElement('span');
            header.className = 'calendar-day-header';

            const weekday = document.createElement('span');
            weekday.className = 'calendar-day-header-weekday';
            weekday.textContent = new Intl.DateTimeFormat(undefined, { weekday: 'short' })
                .format(info.date)
                .toUpperCase();
            header.append(weekday);

            if (info.view.type === 'dayGridWeek') {
                const dayNumber = document.createElement('span');
                dayNumber.className = 'calendar-day-header-number';
                dayNumber.textContent = String(info.date.getDate());
                if (info.isToday) {
                    dayNumber.classList.add('is-today');
                }
                header.append(dayNumber);
            }

            return { domNodes: [header] };
        },
        events: async (fetchInfo, successCallback, failureCallback) => {
            const url = new URL(calendarElement.dataset.eventsUrl, window.location.origin);
            url.searchParams.set('start', fetchInfo.startStr);
            url.searchParams.set('end', fetchInfo.endStr);
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            }
            statusFilters.forEach((status) => url.searchParams.append('status[]', status));
            dueFilters.forEach((dueFilter) => url.searchParams.append('due[]', dueFilter));
            if (dueFilters.includes('next')) {
                url.searchParams.set('next_days', nextDays);
            }

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('Calendar tasks could not be loaded. Refresh and try again.');
                }

                successCallback(await response.json());
            } catch (error) {
                failureCallback(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Calendar unavailable',
                    text: error.message,
                    buttonsStyling: false,
                    customClass: { confirmButton: 'btn btn-primary' },
                });
            }
        },
        datesSet: (info) => {
            if (calendarTitle) {
                calendarTitle.textContent = info.view.title;
            }

            container.querySelectorAll('[data-calendar-view]').forEach((button) => {
                const isActive = button.dataset.calendarView === info.view.type;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
        },
        dayCellDidMount: (info) => {
            const dayFrame = info.el.querySelector('.fc-daygrid-day-frame');
            if (!dayFrame) {
                return;
            }

            const date = info.date;
            const dateString = [
                date.getFullYear(),
                String(date.getMonth() + 1).padStart(2, '0'),
                String(date.getDate()).padStart(2, '0'),
            ].join('-');
            const addTaskButton = document.createElement('button');
            addTaskButton.className = 'calendar-add-task-button';
            addTaskButton.type = 'button';
            addTaskButton.setAttribute(
                'aria-label',
                `Add task on ${date.toLocaleDateString(undefined, { dateStyle: 'long' })}`,
            );

            const icon = document.createElement('i');
            icon.className = 'bi bi-plus';
            icon.setAttribute('aria-hidden', 'true');

            const label = document.createElement('span');
            label.textContent = 'Add task';

            addTaskButton.append(icon, label);
            addTaskButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                openCreateTaskModal(dateString);
            });
            dayFrame.append(addTaskButton);
        },
        eventsSet: (events) => {
            if (emptyMessage) {
                emptyMessage.hidden = events.length > 0;
            }
        },
        eventClick: (info) => {
            if (!info.event.url) {
                return;
            }

            info.jsEvent.preventDefault();
            openTaskEditModal(info.event.url);
        },
        loading: (isLoading) => {
            calendarElement.setAttribute('aria-busy', String(isLoading));
        },
        dateClick: (info) => {
            if (
                info.jsEvent.target instanceof Element &&
                info.jsEvent.target.closest('.calendar-add-task-button')
            ) {
                return;
            }

            openCreateTaskModal(info.dateStr);
        },
    });

    container.querySelector('[data-calendar-prev]')?.addEventListener('click', () => calendar.prev());
    container.querySelector('[data-calendar-next]')?.addEventListener('click', () => calendar.next());
    container.querySelector('[data-calendar-today]')?.addEventListener('click', () => calendar.today());
    container.querySelectorAll('[data-calendar-view]').forEach((button) => {
        button.addEventListener('click', () => calendar.changeView(button.dataset.calendarView));
    });

    calendar.render();
    calendarInstance = calendar;
}

initializeCalendar();

let workspaceNavigationRequest = null;

function updateWorkspaceHeader(nextDocument, filterWasOpen, filterScrollTop) {
    const nextTitle = nextDocument.querySelector('.topbar-title');
    const currentTitle = document.querySelector('.topbar-title');
    if (nextTitle && currentTitle) {
        currentTitle.textContent = nextTitle.textContent;
    }

    document.title = nextDocument.title;

    const nextViewLinks = nextDocument.querySelectorAll('.view-switch-button');
    document.querySelectorAll('.view-switch-button').forEach((link) => {
        const nextLink = Array.from(nextViewLinks)
            .find((candidate) => candidate.getAttribute('aria-label') === link.getAttribute('aria-label'));
        if (!nextLink) {
            return;
        }

        link.href = nextLink.href;
        link.className = nextLink.className;
        if (nextLink.hasAttribute('aria-current')) {
            link.setAttribute('aria-current', nextLink.getAttribute('aria-current'));
        } else {
            link.removeAttribute('aria-current');
        }
    });

    const currentSearch = document.querySelector('.global-search');
    const nextSearch = nextDocument.querySelector('.global-search');
    if (currentSearch && nextSearch) {
        currentSearch.action = nextSearch.action;
        currentSearch.querySelectorAll('input[type="hidden"]').forEach((input) => input.remove());
        nextSearch.querySelectorAll('input[type="hidden"]').forEach((input) => {
            currentSearch.prepend(input.cloneNode(true));
        });
    }

    if (filterPopover) {
        const nextFilterPopover = nextDocument.querySelector('[data-filter-popover]');
        if (nextFilterPopover) {
            filterPopover.action = nextFilterPopover.action;
            filterPopover.innerHTML = nextFilterPopover.innerHTML;
            filterPopover.hidden = !filterWasOpen;
            filterPopover.scrollTop = filterScrollTop;
        }
    }

    if (filterToggle) {
        const nextFilterToggle = nextDocument.querySelector('[data-filter-toggle]');
        if (nextFilterToggle) {
            filterToggle.innerHTML = nextFilterToggle.innerHTML;
            filterToggle.setAttribute('aria-expanded', String(filterWasOpen));
        }
    }

    const currentExportUrls = document.querySelectorAll('[data-export-url]');
    const nextExportUrls = nextDocument.querySelectorAll('[data-export-url]');
    currentExportUrls.forEach((element, index) => {
        if (nextExportUrls[index]) {
            element.dataset.exportUrl = nextExportUrls[index].dataset.exportUrl;
        }
    });

    const currentCsvLink = document.querySelector('.task-export-option[href]');
    const nextCsvLink = nextDocument.querySelector('.task-export-option[href]');
    if (currentCsvLink && nextCsvLink) {
        currentCsvLink.href = nextCsvLink.href;
    }
}

async function navigateWorkspace(href, { pushHistory = true } = {}) {
    const targetUrl = new URL(href, window.location.href);
    if (targetUrl.origin !== window.location.origin) {
        throw new Error('Workspace navigation must stay on this site.');
    }

    workspaceNavigationRequest?.abort();
    const request = new AbortController();
    workspaceNavigationRequest = request;

    const mainContent = document.querySelector('.main-content');
    if (!mainContent) {
        throw new Error('The task workspace could not be found.');
    }

    const filterWasOpen = Boolean(filterPopover && !filterPopover.hidden);
    const filterScrollTop = filterPopover?.scrollTop ?? 0;
    const focusedFilterId = filterPopover?.contains(document.activeElement)
        ? document.activeElement.id
        : '';

    try {
        const response = await fetch(targetUrl, {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: request.signal,
        });
        if (!response.ok) {
            throw new Error('The task view could not be loaded. Please try again.');
        }

        const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
        const nextMainContent = nextDocument.querySelector('.main-content');
        if (!nextMainContent) {
            throw new Error('The task view returned an unexpected response.');
        }

        sortableInstance?.destroy();
        sortableInstance = null;
        calendarInstance?.destroy();
        calendarInstance = null;

        mainContent.innerHTML = nextMainContent.innerHTML;
        document.body.className = nextDocument.body.className;
        updateWorkspaceHeader(nextDocument, filterWasOpen, filterScrollTop);

        if (pushHistory) {
            window.history.pushState({}, '', response.url);
        }

        initializeProgressToggles(mainContent);
        initializeSortable(mainContent);
        initializeCalendar(mainContent);

        if (focusedFilterId) {
            Array.from(filterPopover.querySelectorAll('[id]'))
                .find((element) => element.id === focusedFilterId)
                ?.focus({ preventScroll: true });
        }
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error(error);
        toast.fire({
            icon: 'error',
            title: 'Unable to update the workspace',
            text: error.message,
        });
    } finally {
        if (workspaceNavigationRequest === request) {
            workspaceNavigationRequest = null;
        }
    }
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const viewLink = event.target.closest('.view-switch-button');
    if (
        !viewLink ||
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return;
    }

    event.preventDefault();
    if (!viewLink.hasAttribute('aria-current')) {
        navigateWorkspace(viewLink.href);
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('.global-search')) {
        return;
    }

    event.preventDefault();
    const targetUrl = new URL(form.action, window.location.href);
    targetUrl.search = new URLSearchParams(new FormData(form)).toString();
    navigateWorkspace(targetUrl.href);
});

window.addEventListener('popstate', () => {
    navigateWorkspace(window.location.href, { pushHistory: false });
});
