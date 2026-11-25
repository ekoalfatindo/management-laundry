<script lang="ts">
    import type { Snippet } from 'svelte';
    import Icon, { type IconName } from './Icon.svelte';

    let {
        icon = 'receipt',
        title,
        description = '',
        children,
    }: {
        icon?: IconName;
        title: string;
        description?: string;
        children?: Snippet;
    } = $props();
</script>

<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <span class="rounded-2xl bg-slate-100 p-4 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
        <Icon name={icon} class="size-7" />
    </span>

    <h3 class="mt-4 text-sm font-semibold text-slate-800 dark:text-slate-100">{title}</h3>

    {#if description}
        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">{description}</p>
    {/if}

    {#if children}
        <div class="mt-5">
            {@render children()}
        </div>
    {/if}
</div>
