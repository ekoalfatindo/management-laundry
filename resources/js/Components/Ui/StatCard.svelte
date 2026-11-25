<script lang="ts">
    import Icon, { type IconName } from './Icon.svelte';
    import { cardClass } from '../../lib/ui';

    let {
        label,
        value,
        hint = '',
        icon = 'wallet',
        tone = 'teal',
    }: {
        label: string;
        value: string;
        hint?: string;
        icon?: IconName;
        tone?: 'orange' | 'teal' | 'indigo' | 'amber' | 'rose' | 'slate';
    } = $props();

    const tones: Record<string, string> = {
        orange: 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
        teal: 'bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400',
        indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
        amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        rose: 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
        slate: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    };
</script>

<div class="{cardClass} flex items-start justify-between gap-4 p-5">
    <div>
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{label}</p>
        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{value}</p>
        {#if hint}
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{hint}</p>
        {/if}
    </div>

    <span class="rounded-xl p-2.5 {tones[tone]}">
        <Icon name={icon} class="size-5" />
    </span>
</div>
