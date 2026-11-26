import { router } from '@inertiajs/svelte';
import Swal, { type SweetAlertIcon } from 'sweetalert2';
import type { FlashMessages } from '../types';

const BACKDROP = 'rgba(15, 23, 42, 0.55)';

const toastClasses = {
    popup: 'swal-laundry-toast',
    title: 'swal-laundry-toast-title',
    timerProgressBar: 'swal-laundry-toast-progress',
};

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4500,
    timerProgressBar: true,
    customClass: toastClasses,
});

function isDialogOpen(): boolean {
    const popup = Swal.getPopup();

    return Boolean(popup && Swal.isVisible() && !popup.classList.contains('swal2-toast'));
}

export function showToast(tone: Extract<SweetAlertIcon, 'success' | 'error' | 'info'>, message: string): void {
    if (!message) {
        return;
    }

    const fire = (attempt = 0): void => {
        if (isDialogOpen() && attempt < 40) {
            window.setTimeout(() => fire(attempt + 1), 150);

            return;
        }

        void toast.fire({
            icon: tone,
            title: message,
            customClass: { ...toastClasses, popup: `${toastClasses.popup} swal-laundry-toast-${tone}` },
        });
    };

    window.setTimeout(() => fire(), 100);
}

export function flashToasts(flash?: FlashMessages | null): void {
    if (!flash) {
        return;
    }

    if (flash.success) {
        showToast('success', flash.success);

        return;
    }

    if (flash.error) {
        showToast('error', flash.error);

        return;
    }

    if (flash.status) {
        showToast('info', flash.status);
    }
}

export async function confirmAction(options: {
    title: string;
    text: string;
    confirmText?: string;
    danger?: boolean;
}): Promise<boolean> {
    const result = await Swal.fire({
        icon: options.danger ? 'warning' : 'question',
        title: options.title,
        text: options.text,
        backdrop: BACKDROP,
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: options.confirmText ?? 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        buttonsStyling: false,
        customClass: {
            popup: 'swal-laundry-popup',
            icon: options.danger ? 'swal-laundry-icon swal-laundry-icon-danger' : 'swal-laundry-icon',
            title: 'swal-laundry-title',
            htmlContainer: 'swal-laundry-text',
            actions: 'swal-laundry-actions',
            confirmButton: options.danger ? 'swal-laundry-btn swal-laundry-btn-danger' : 'swal-laundry-btn swal-laundry-btn-confirm',
            cancelButton: 'swal-laundry-btn swal-laundry-btn-cancel',
        },
    });

    return Boolean(result.isConfirmed);
}

export async function confirmDelete(options: {
    url: string;
    title: string;
    text: string;
    confirmText?: string;
    preserveScroll?: boolean;
}): Promise<void> {
    const result = await Swal.fire({
        icon: 'warning',
        title: options.title,
        text: options.text,
        backdrop: BACKDROP,
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: options.confirmText ?? 'Ya, hapus',
        cancelButtonText: 'Batal',
        buttonsStyling: false,
        customClass: {
            popup: 'swal-laundry-popup',
            icon: 'swal-laundry-icon swal-laundry-icon-danger',
            title: 'swal-laundry-title',
            htmlContainer: 'swal-laundry-text',
            actions: 'swal-laundry-actions',
            confirmButton: 'swal-laundry-btn swal-laundry-btn-danger',
            cancelButton: 'swal-laundry-btn swal-laundry-btn-cancel',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(options.url, { preserveScroll: options.preserveScroll ?? true });
}
