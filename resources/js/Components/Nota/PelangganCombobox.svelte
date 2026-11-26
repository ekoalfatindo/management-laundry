<script lang="ts">
    import Icon from '../Ui/Icon.svelte';
    import { inputClass } from '../../lib/ui';
    import type { PelangganOption } from '../../types';

    let {
        id,
        value,
        options,
        onselect,
    }: {
        id: string;
        value: string | number;
        options: PelangganOption[];
        onselect: (pelanggan: PelangganOption | null) => void;
    } = $props();

    type Entry = {
        key: string;
        pelanggan: PelangganOption | null;
        name: string;
    };

    let open = $state(false);
    let typing = $state(false);
    let query = $state('');
    let highlight = $state(0);
    let container = $state<HTMLDivElement>();
    let input = $state<HTMLInputElement>();

    let selected = $derived(options.find((option) => option.id === Number(value)) ?? null);
    let label = $derived(selected?.name ?? 'Pelanggan umum');

    let entries = $derived.by<Entry[]>(() => {
        const term = typing ? query.trim().toLowerCase() : '';
        const walkIn: Entry = { key: 'umum', pelanggan: null, name: 'Pelanggan umum' };
        const matches = options
            .filter((option) => !term || option.name.toLowerCase().includes(term))
            .map((option) => ({ key: `pelanggan-${option.id}`, pelanggan: option, name: option.name }));

        return term && !walkIn.name.toLowerCase().includes(term) ? matches : [walkIn, ...matches];
    });

    function close(): void {
        open = false;
        typing = false;
        query = '';
    }

    function openList(): void {
        open = true;
        const index = entries.findIndex((entry) => (entry.pelanggan?.id ?? null) === (selected?.id ?? null));
        highlight = index >= 0 ? index : 0;
    }

    function choose(entry: Entry): void {
        onselect(entry.pelanggan);
        close();
    }

    function handleFocus(event: FocusEvent): void {
        openList();
        (event.currentTarget as HTMLInputElement).select();
    }

    function handleInput(event: Event): void {
        typing = true;
        query = (event.currentTarget as HTMLInputElement).value;
        open = true;
        highlight = 0;
    }

    function handleKeydown(event: KeyboardEvent): void {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (!open) {
                openList();
            } else if (entries.length > 0) {
                highlight = (highlight + 1) % entries.length;
            }
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (!open) {
                openList();
            } else if (entries.length > 0) {
                highlight = (highlight - 1 + entries.length) % entries.length;
            }
        } else if (event.key === 'Enter') {
            if (open) {
                event.preventDefault();
                const entry = entries[highlight];

                if (entry) {
                    choose(entry);
                }
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    }

    function handleFocusOut(event: FocusEvent): void {
        if (!container?.contains(event.relatedTarget as Node | null)) {
            close();
        }
    }

    $effect(() => {
        if (!open) {
            return;
        }

        const handlePointer = (event: PointerEvent): void => {
            if (!container?.contains(event.target as Node)) {
                close();
            }
        };

        document.addEventListener('pointerdown', handlePointer);

        return () => document.removeEventListener('pointerdown', handlePointer);
    });
</script>

<div class="relative" bind:this={container} onfocusout={handleFocusOut}>
    <input
        {id}
        type="text"
        role="combobox"
        autocomplete="off"
        aria-autocomplete="list"
        aria-expanded={open}
        aria-controls="{id}-listbox"
        aria-activedescendant={open ? `${id}-option-${entries[highlight]?.key ?? ''}` : undefined}
        class="{inputClass} pr-9 placeholder:text-slate-500"
        placeholder="Pelanggan umum"
        value={typing ? query : label}
        bind:this={input}
        onfocus={handleFocus}
        oninput={handleInput}
        onkeydown={handleKeydown}
    />
    <button
        type="button"
        class="absolute inset-y-0 right-0 flex w-9 items-center justify-center text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
        aria-label={open ? 'Tutup daftar pelanggan' : 'Buka daftar pelanggan'}
        onmousedown={(event) => event.preventDefault()}
        onclick={() => (open ? close() : input?.focus())}
    >
        <Icon name="chevron-down" class="size-4 transition {open ? 'rotate-180' : ''}" />
    </button>

    {#if open}
        <ul
            id="{id}-listbox"
            role="listbox"
            class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
        >
            {#each entries as entry, index (entry.key)}
                <li
                    id="{id}-option-{entry.key}"
                    role="option"
                    aria-selected={(entry.pelanggan?.id ?? null) === (selected?.id ?? null)}
                    class="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm {index === highlight
                        ? 'bg-orange-50 text-orange-900 dark:bg-orange-500/15 dark:text-orange-100'
                        : 'text-slate-700 dark:text-slate-200'}"
                    onmouseenter={() => (highlight = index)}
                    onmousedown={(event) => {
                        event.preventDefault();
                        choose(entry);
                    }}
                >
                    <span class="truncate">{entry.name}</span>
                    {#if entry.pelanggan?.kategori_pelanggan}
                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                            {entry.pelanggan.kategori_pelanggan}
                        </span>
                    {/if}
                </li>
            {:else}
                <li class="px-3 py-2 text-sm text-slate-500 dark:text-slate-400">Tidak ada pelanggan yang cocok.</li>
            {/each}
        </ul>
    {/if}
</div>
