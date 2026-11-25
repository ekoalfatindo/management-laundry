<script lang="ts">
    import { onMount, type Snippet } from 'svelte';
    import { Link, router, usePage } from '@inertiajs/svelte';
    import Icon, { type IconName } from './Ui/Icon.svelte';
    import AppFooter from './Ui/AppFooter.svelte';
    import { flashToasts } from '../lib/notify';
    import type { SharedProps } from '../types';

    let { title = '', children }: { title?: string; children: Snippet } = $props();

    const page = usePage<SharedProps>();

    let user = $derived(page.props.auth.user);
    let appName = $derived(page.props.appName || 'Laundry Management');
    let company = $derived(page.props.company);
    let companyName = $derived(company?.nama_perusahaan || appName);
    let currentUrl = $derived(page.url);
    let mobileOpen = $state(false);
    let collapsed = $state(readCollapsed());

    function readCollapsed(): boolean {
        try {
            return localStorage.getItem('sidebar-collapsed') === '1';
        } catch {
            return false;
        }
    }

    function toggleSidebar(): void {
        collapsed = !collapsed;

        try {
            localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
        } catch {}
    }

    let dark = $state(false);

    type NavItem = {
        href: string;
        label: string;
        icon: IconName;
        show: boolean;
    };

    function can(permission: string): boolean {
        return user?.permissions.includes(permission) ?? false;
    }

    let groups = $derived(
        [
            {
                label: 'Operasional',
                items: [
                    { href: '/', label: 'Dashboard', icon: 'dashboard' as IconName, show: can('dashboard.view') },
                    { href: '/nota', label: 'Nota Transaksi', icon: 'receipt' as IconName, show: can('nota.view') },
                ],
            },
            {
                label: 'Master Data',
                items: [
                    { href: '/barang', label: 'Harga Per Barang', icon: 'box' as IconName, show: can('katalog.view') },
                    { href: '/kiloan', label: 'Harga Paket Kiloan', icon: 'washer' as IconName, show: can('katalog.view') },
                    {
                        href: '/jenis-layanan',
                        label: 'Jenis Layanan',
                        icon: 'layers' as IconName,
                        show: can('katalog.view'),
                    },
                ],
            },
            {
                label: 'Manajemen',
                items: [
                    { href: '/karyawan', label: 'Karyawan', icon: 'staff' as IconName, show: can('karyawan.manage') },
                    { href: '/pelanggan', label: 'Pelanggan', icon: 'customer' as IconName, show: can('pelanggan.manage') },
                    { href: '/users', label: 'Pengguna', icon: 'users' as IconName, show: can('users.manage') },
                    { href: '/pengaturan', label: 'Setup', icon: 'settings' as IconName, show: can('pengaturan.manage') },
                ],
            },
        ]
            .map((group) => ({ ...group, items: group.items.filter((item) => item.show) }))
            .filter((group) => group.items.length > 0),
    );

    let roleLabel = $derived(
        (() => {
            const role = user?.roles[0];

            if (role === 'admin') {
                return 'Admin';
            }

            return role === 'karyawan' ? 'Karyawan' : role === 'pelanggan' ? 'Pelanggan' : 'Pengguna';
        })(),
    );

    let initials = $derived(
        (user?.name ?? '?')
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0]?.toUpperCase() ?? '')
            .join(''),
    );

    function isActive(href: string): boolean {
        if (href === '/') {
            return currentUrl === '/';
        }

        return currentUrl === href || currentUrl.startsWith(`${href}/`) || currentUrl.startsWith(`${href}?`);
    }

    function toggleTheme(): void {
        dark = !dark;
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    }

    function logout(): void {
        router.post('/logout');
    }

    onMount(() => {
        dark = document.documentElement.classList.contains('dark');
    });

    $effect(() => {
        currentUrl;
        mobileOpen = false;
    });

    $effect(() => {
        flashToasts(page.props.flash);
    });
</script>

<svelte:head>
    <title>{title ? `${title} · ${companyName}` : companyName}</title>
</svelte:head>

{#if mobileOpen}
    <button
        type="button"
        class="fixed inset-0 z-30 h-full w-full cursor-default bg-slate-900/60 lg:hidden"
        aria-label="Tutup menu samping"
        onclick={() => (mobileOpen = false)}
    ></button>
{/if}

<aside
    id="sidebar"
    class="no-print sidebar fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r transition-[width,transform] duration-200 motion-reduce:transition-none lg:translate-x-0 {collapsed
        ? 'lg:w-20'
        : 'lg:w-72'} {mobileOpen ? 'translate-x-0' : '-translate-x-full'}"
    aria-label="Navigasi utama"
>
    <div class="sidebar-divider flex h-16 items-center gap-3 border-b px-5 {collapsed ? 'lg:justify-center lg:px-2' : ''}">
        <div class="flex min-w-0 items-center gap-3 {collapsed ? 'lg:hidden' : ''}">
            <span class="sidebar-brand-icon flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl shadow-sm">
                {#if company?.logo_url}
                    <img src={company.logo_url} alt={`Logo ${companyName}`} class="size-full bg-white object-contain p-1" />
                {:else}
                    <Icon name="shirt" class="size-5" />
                {/if}
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-[var(--sidebar-fg-strong)]" title={companyName}>{companyName}</p>
                <p class="sidebar-muted truncate text-xs">Manajemen Laundry</p>
            </div>
        </div>

        <button
            type="button"
            class="sidebar-toggle hidden shrink-0 items-center justify-center rounded-lg p-2 transition lg:inline-flex {collapsed ? 'mx-auto' : 'ml-auto'}"
            aria-controls="sidebar"
            aria-expanded={!collapsed}
            aria-label={collapsed ? 'Perbesar menu sidebar' : 'Perkecil menu sidebar'}
            title={collapsed ? 'Perbesar menu sidebar' : 'Perkecil menu sidebar'}
            onclick={toggleSidebar}
        >
            <Icon name={collapsed ? 'menu' : 'chevron-left'} class="size-5" />
        </button>

        <button
            type="button"
            class="sidebar-toggle ml-auto rounded-lg p-1.5 transition lg:hidden"
            aria-label="Tutup menu"
            onclick={() => (mobileOpen = false)}
        >
            <Icon name="close" class="size-5" />
        </button>
    </div>

    <nav class="sidebar-scroll flex-1 space-y-6 overflow-y-auto px-3 py-5 scrollbar-thin">
        {#each groups as group}
            <div>
                <p class="sidebar-heading px-2 text-[11px] font-semibold uppercase tracking-wider {collapsed ? 'lg:hidden' : ''}">
                    {group.label}
                </p>
                {#if collapsed}
                    <div class="sidebar-rule mx-auto hidden h-px w-8 lg:block"></div>
                {/if}
                <div class="mt-2 space-y-1">
                    {#each group.items as item}
                        <Link
                            href={item.href}
                            class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {collapsed
                                ? 'lg:justify-center lg:px-2'
                                : ''} {isActive(item.href) ? 'sidebar-link-active' : ''}"
                            aria-label={item.label}
                            title={collapsed ? item.label : undefined}
                        >
                            <Icon name={item.icon} class="size-5 shrink-0" />
                            <span class={collapsed ? 'lg:hidden' : ''}>{item.label}</span>
                        </Link>
                    {/each}
                </div>
            </div>
        {/each}
    </nav>

    <div class="sidebar-divider border-t p-3">
        <button
            type="button"
            class="sidebar-link flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {collapsed
                ? 'lg:justify-center lg:px-2'
                : ''}"
            onclick={toggleTheme}
            aria-label={dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'}
            title={collapsed ? (dark ? 'Mode terang' : 'Mode gelap') : undefined}
        >
            <Icon name={dark ? 'sun' : 'moon'} class="size-5 shrink-0" />
            <span class={collapsed ? 'lg:hidden' : ''}>{dark ? 'Mode terang' : 'Mode gelap'}</span>
        </button>

        <div class="sidebar-card mt-2 flex items-center gap-3 rounded-xl border px-3 py-2.5 {collapsed
            ? 'lg:flex-col lg:gap-2 lg:px-2 lg:py-3'
            : ''}">
            <span
                class="sidebar-avatar flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                title={collapsed ? (user?.name ?? undefined) : undefined}
            >
                {initials}
            </span>
            <div class="min-w-0 flex-1 {collapsed ? 'lg:hidden' : ''}">
                <p class="truncate text-sm font-semibold text-[var(--sidebar-fg-strong)]">{user?.name ?? '-'}</p>
                <p class="sidebar-muted truncate text-xs">{roleLabel}</p>
            </div>
            <div class="group relative {collapsed ? 'lg:self-center' : ''}">
                <button
                    type="button"
                    class="sidebar-toggle rounded-lg p-2 transition"
                    aria-label="Logout"
                    onclick={logout}
                >
                    <Icon name="logout" class="size-5" />
                </button>
                <span
                    role="tooltip"
                    class="pointer-events-none invisible absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-lg transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 dark:bg-slate-700"
                >
                    Logout
                </span>
            </div>
        </div>
    </div>
</aside>

<div class="min-h-screen bg-slate-100 transition-[padding] duration-200 motion-reduce:transition-none print:min-h-0 print:bg-white dark:bg-slate-950 dark:print:bg-white {collapsed
    ? 'lg:pl-20'
    : 'lg:pl-72'}">
    <header
        class="no-print sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 lg:hidden"
    >
        <button
            type="button"
            class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
            aria-label="Buka menu"
            onclick={() => (mobileOpen = true)}
        >
            <Icon name="menu" class="size-5" />
        </button>
        <span class="text-sm font-semibold text-slate-900 dark:text-slate-50">{title || appName}</span>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 pt-6 pb-24 sm:px-6 sm:pb-16 lg:px-8 print:pb-0">
        {@render children()}
    </main>

    <AppFooter variant="bar" leftClass={collapsed ? 'lg:left-20' : 'lg:left-72'} />
</div>
