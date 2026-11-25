<script lang="ts">
    import Icon from './Icon.svelte';
    import { FOOTER } from '../../lib/footer';

    let {
        variant = 'bar',
        containerClass = 'max-w-7xl',
        paddingClass = 'px-4 sm:px-6 lg:px-8',
        leftClass = '',
        class: className = '',
    }: {
        variant?: 'bar' | 'auth';
        containerClass?: string;
        paddingClass?: string;
        leftClass?: string;
        class?: string;
    } = $props();
</script>

{#if variant === 'auth'}
    <footer class="mt-7 w-full max-w-lg text-center text-[11px] leading-relaxed text-slate-500 dark:text-orange-100/50 {className}">
        <p class="flex items-center justify-center gap-1.5 text-xs font-bold tracking-wide text-slate-700 dark:text-orange-50">
            <Icon name="shirt" class="size-3.5 text-orange-600 dark:text-orange-400" />
            {FOOTER.brand}
        </p>
        <p class="mt-1.5">{FOOTER.tagline}</p>
        <p class="mt-2.5">
            {FOOTER.creditPrefix} <span class="text-rose-500">{FOOTER.heart}</span> {FOOTER.creditBy}
            <span class="font-semibold text-slate-700 dark:text-orange-100/90">{FOOTER.author}</span>
        </p>
        <p class="mt-1.5 flex flex-wrap items-center justify-center gap-1.5">
            <span class="rounded-full border border-orange-200/90 bg-orange-50/80 px-2 py-0.5 font-semibold text-orange-700 dark:border-orange-500/25 dark:bg-orange-500/10 dark:text-orange-300">
                {FOOTER.pillFree}
            </span>
            <span aria-hidden="true" class="text-slate-300 dark:text-orange-100/30">{FOOTER.separator}</span>
            <span class="rounded-full border border-slate-200 bg-white/70 px-2 py-0.5 font-semibold text-slate-600 dark:border-orange-100/15 dark:bg-white/5 dark:text-orange-100/70">
                {FOOTER.pillDonation}
            </span>
        </p>
        <p class="mt-2.5 text-slate-400 dark:text-orange-100/35">{FOOTER.copyright}</p>
    </footer>
{:else}
    <footer
        class="no-print fixed inset-x-0 bottom-0 z-20 border-t border-slate-200/80 bg-white/95 shadow-[0_-8px_24px_-16px_rgba(15,23,42,0.35)] backdrop-blur transition-[left] duration-200 motion-reduce:transition-none dark:border-slate-800/80 dark:bg-slate-900/95 {leftClass} {className}"
    >
        <div
            class="mx-auto flex w-full {containerClass} flex-col items-center gap-1 py-2.5 text-center text-[11px] leading-relaxed text-slate-500 sm:flex-row sm:justify-between sm:gap-3 sm:text-left {paddingClass} dark:text-slate-400"
        >
            <p>
                {FOOTER.creditPrefix} <span class="text-rose-500">{FOOTER.heart}</span> {FOOTER.creditBy}
                <span class="font-semibold text-slate-700 dark:text-slate-200">{FOOTER.author}</span>
            </p>
            <p class="flex items-center gap-1.5">
                <span>{FOOTER.pillFree}</span>
                <span aria-hidden="true" class="text-slate-300 dark:text-slate-600">{FOOTER.separator}</span>
                <span>{FOOTER.pillDonation}</span>
            </p>
            <p class="text-slate-400 dark:text-slate-500">{FOOTER.copyright}</p>
        </div>
    </footer>
{/if}
