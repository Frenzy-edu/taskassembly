import Sortable from 'sortablejs';
import Swal from 'sweetalert2';
import $ from 'jquery';
import 'bootstrap/js/dist/modal';
import 'bootstrap/js/dist/tooltip';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

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

document.querySelectorAll('[data-delete-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
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

filterPopover?.querySelectorAll('[data-filter-submit], [data-filter-next-days]').forEach((input) => {
    input.addEventListener('change', () => input.form?.requestSubmit());
});

const nextDaysInput = filterPopover?.querySelector('[data-filter-next-days]');
filterPopover?.querySelectorAll('[data-filter-step]').forEach((button) => {
    button.addEventListener('click', () => {
        if (!nextDaysInput) {
            return;
        }

        const increment = Number(button.dataset.filterStep);
        const nextValue = Math.min(365, Math.max(0, Number(nextDaysInput.value || 0) + increment));
        nextDaysInput.value = String(nextValue);
        nextDaysInput.form?.requestSubmit();
    });
});

filterPopover?.querySelector('[data-filter-close]')?.addEventListener('click', () => {
    setFilterPopoverOpen(false);
    filterToggle?.focus({ preventScroll: true });
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
        const { default: ExcelJS } = await import('exceljs');
        const workbook = new ExcelJS.Workbook();
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
        const [{ jsPDF }, { default: autoTable }] = await Promise.all([
            import('jspdf'),
            import('jspdf-autotable'),
        ]);
        const pdf = new jsPDF({ orientation: 'landscape' });
        pdf.setFontSize(18);
        pdf.text('Task List', 14, 16);
        autoTable(pdf, {
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

const taskList = document.querySelector('[data-sortable-list]');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

if (taskList?.dataset.sortableEnabled === 'true' && csrfToken) {
    let orderBeforeDrag = [];

    Sortable.create(taskList, {
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

const calendarElement = document.querySelector('#taskCalendar');

if (calendarElement) {
    const calendarTitle = document.querySelector('[data-calendar-title]');
    const emptyMessage = document.querySelector('[data-calendar-empty]');
    const searchTerm = calendarElement.dataset.search || '';
    const statusFilters = (calendarElement.dataset.statuses || '')
        .split(',')
        .filter(Boolean);
    const dueFilters = (calendarElement.dataset.dueFilters || '')
        .split(',')
        .filter(Boolean);
    const nextDays = calendarElement.dataset.nextDays || '0';

    const calendar = new Calendar(calendarElement, {
        plugins: [dayGridPlugin, interactionPlugin],
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

            document.querySelectorAll('[data-calendar-view]').forEach((button) => {
                const isActive = button.dataset.calendarView === info.view.type;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
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
            const createTaskModal = document.querySelector('#createTaskModal');
            const dueDateField = createTaskModal?.querySelector('#modal-due_date');

            if (createTaskModal && dueDateField) {
                dueDateField.value = info.dateStr.slice(0, 10);
                dueDateField.dispatchEvent(new Event('input', { bubbles: true }));
                $(createTaskModal).modal('show');
                return;
            }

            const createUrl = new URL(calendarElement.dataset.createUrl, window.location.origin);
            createUrl.searchParams.set('due_date', info.dateStr.slice(0, 10));
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
        },
    });

    document.querySelector('[data-calendar-prev]')?.addEventListener('click', () => calendar.prev());
    document.querySelector('[data-calendar-next]')?.addEventListener('click', () => calendar.next());
    document.querySelector('[data-calendar-today]')?.addEventListener('click', () => calendar.today());
    document.querySelectorAll('[data-calendar-view]').forEach((button) => {
        button.addEventListener('click', () => calendar.changeView(button.dataset.calendarView));
    });

    calendar.render();
}
