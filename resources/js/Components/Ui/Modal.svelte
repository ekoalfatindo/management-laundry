<script lang="ts">
    import type { Snippet } from 'svelte';
    import Icon from './Icon.svelte';

    let {
        open = false,
        title = '',
        size = 'md',
        onclose,
        children,
    }: {
        open?: boolean;
        title?: string;
        size?: 'sm' | 'md' | 'lg';
        onclose: () => void;
        children: Snippet;
    } = $props();

    const sizes: Record<string, string> = {
        sm: 'max-w-md',
        md: 'max-w-xl',
        lg: 'max-w-3xl',
    };

    let dialog = $state<HTMLDialogElement | null>(null);

    $effect(() => {
        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    });

    function handleBackdrop(event: MouseEvent): void {
        if (event.target === dialog) {
            onclose();
        }
    }

    function handleCancel(event: Event): void {
        event.preventDefault();
        onclose();
    }
</script>

<dialog
    bind:this={dialog}
    oncancel={handleCancel}
    onclick={handleBackdrop}
    class="fixed inset-0 m-auto h-fit max-h-[90vh] w-[calc(100%-2rem)] {sizes[
        size
    ]} overflow-y-auto rounded-2xl border border-slate-200 bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-900/60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
>
    <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{title}</h2>
        <button
            type="button"
            onclick={onclose}
            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
            aria-label="Tutup"
        >
            <Icon name="close" class="size-5" />
        </button>
    </div>

    <div class="px-5 py-5">
        {@render children()}
    </div>
</dialog>
