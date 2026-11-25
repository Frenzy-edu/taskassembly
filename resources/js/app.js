import $ from 'jquery';
import Swal from 'sweetalert2';
import toastr from 'toastr';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'sweetalert2/dist/sweetalert2.min.css';
import 'toastr/build/toastr.min.css';

window.$ = window.jQuery = $;

toastr.options = {
    closeButton: true,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    preventDuplicates: true,
    timeOut: 3500,
};

const flashMessages = document.querySelector('#flash-messages');

if (flashMessages?.dataset.success) {
    toastr.success(flashMessages.dataset.success);
}

if (flashMessages?.dataset.error) {
    toastr.error(flashMessages.dataset.error);
}

document.addEventListener('submit', async (event) => {
    const deleteForm = event.target.closest('form[data-confirm-delete]');

    if (!deleteForm || deleteForm.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();

    const result = await Swal.fire({
        title: 'Delete this task?',
        text: `"${deleteForm.dataset.taskTitle}" will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete task',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        reverseButtons: true,
        focusCancel: true,
    });

    if (result.isConfirmed) {
        deleteForm.dataset.confirmed = 'true';
        deleteForm.submit();
    }
});
