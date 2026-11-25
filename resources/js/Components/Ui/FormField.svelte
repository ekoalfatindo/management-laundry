<script lang="ts">
    import type { Snippet } from 'svelte';
    import { errorClass, labelClass } from '../../lib/ui';

    let {
        id = '',
        label,
        error = '',
        hint = '',
        children,
    }: {
        id?: string;
        label: string;
        error?: string;
        hint?: string;
        children: Snippet;
    } = $props();
</script>

<div>
    <label class={labelClass} for={id || undefined}>{label}</label>
    {@render children()}
    {#if hint}
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{hint}</p>
    {/if}
    {#if error}
        <p class={errorClass}>{error}</p>
    {/if}
</div>
