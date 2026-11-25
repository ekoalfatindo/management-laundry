<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Link, router, usePage } from '@inertiajs/svelte';
    import Icon from './Ui/Icon.svelte';
    import AppFooter from './Ui/AppFooter.svelte';
    import { flashToasts } from '../lib/notify';
    import type { SharedProps } from '../types';

    let { title = '', children }: { title?: string; children: Snippet } = $props();

    const page = usePage<SharedProps>();

    let user = $derived(page.props.auth.user);
    let company = $derived(page.props.company);
    let companyName = $derived(company?.nama_perusahaan || page.props.appName || 'Laundry Management');
    let logoUrl = $derived(company?.logo_url ?? null);

    function logout(): void {
        router.post('/logout');
    }

    $effect(() => {
        flashToasts(page.props.flash);
    });
</script>

<svelte:head>
    <title>{title ? `${title} · ${companyName}` : companyName}</title>
</svelte:head>

<div class="flex min-h-screen flex-col bg-slate-100 dark:bg-slate-950">
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
        <div class="mx-auto flex h-16 w-full max-w-5xl items-center gap-3 px-4 sm:px-6">
            <Link href="/akun" class="flex min-w-0 items-center gap-3" aria-label="Beranda nota pelanggan">
                <span
                    class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-linear-to-br from-orange-500 via-orange-600 to-amber-700 text-white shadow-sm dark:from-orange-400 dark:via-orange-500 dark:to-amber-500 dark:text-slate-950"
                >
                    {#if logoUrl}
                        <img src={logoUrl} alt={`Logo ${companyName}`} class="size-full bg-white object-contain p-1" />
                    {:else}
                        <Icon name="shirt" class="size-5" />
                    {/if}
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-slate-900 dark:text-slate-50" title={companyName}>
                        {companyName}
                    </span>
                    <span class="block text-xs text-orange-700 dark:text-orange-400">Portal Pelanggan</span>
                </span>
            </Link>

            <div class="ml-auto flex items-center gap-2">
                <span class="hidden max-w-44 truncate text-sm text-slate-500 sm:block dark:text-slate-400" title={user?.name}>
                    Halo, {user?.name}
                </span>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    onclick={logout}
                >
                    <Icon name="logout" class="size-4" />
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl flex-1 px-4 pt-8 pb-24 sm:px-6 sm:pb-16 print:pb-0">
        {@render children()}
    </main>

    <AppFooter variant="bar" containerClass="max-w-5xl" paddingClass="px-4 sm:px-6" />
</div>
