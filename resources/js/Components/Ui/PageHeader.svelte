<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title,
        description = '',
        children,
    }: {
        title: string;
        description?: string;
        children?: Snippet;
    } = $props();
</script>

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{title}</h1>
        {#if description}
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>
        {/if}
    </div>

    {#if children}
        <div class="flex flex-wrap items-center gap-2">
            {@render children()}
        </div>
    {/if}
</div>
