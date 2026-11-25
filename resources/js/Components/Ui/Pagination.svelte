<script lang="ts">
    import Icon from './Icon.svelte';
    import type { PaginationMeta } from '../../types';
    import { number } from '../../lib/format';
    import { buttonClass } from '../../lib/ui';

    let { meta, onpage }: { meta: PaginationMeta; onpage: (page: number) => void } = $props();

    let canPrev = $derived(meta.current_page > 1);
    let canNext = $derived(meta.current_page < meta.last_page);
</script>

<div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm text-slate-500 dark:text-slate-400">
        Menampilkan {meta.from ?? 0}–{meta.to ?? 0} dari {number(meta.total)} data
    </p>

    <div class="flex items-center gap-2">
        <button
            type="button"
            class={buttonClass('secondary', 'px-2.5 py-2')}
            disabled={!canPrev}
            onclick={() => onpage(meta.current_page - 1)}
            aria-label="Halaman sebelumnya"
        >
            <Icon name="chevron-left" class="size-4" />
        </button>

        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">
            Halaman {meta.current_page} dari {meta.last_page}
        </span>

        <button
            type="button"
            class={buttonClass('secondary', 'px-2.5 py-2')}
            disabled={!canNext}
            onclick={() => onpage(meta.current_page + 1)}
            aria-label="Halaman berikutnya"
        >
            <Icon name="chevron-right" class="size-4" />
        </button>
    </div>
</div>
