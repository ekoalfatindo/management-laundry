<script lang="ts">
    import { Link, usePage } from '@inertiajs/svelte';
    import AppLayout from '../Components/AppLayout.svelte';
    import Icon from '../Components/Ui/Icon.svelte';
    import type { SharedProps } from '../types';

    let { status = 500 }: { status?: number } = $props();

    const page = usePage<SharedProps>();

    let authenticated = $derived(Boolean(page.props.auth?.user));

    const messages: Record<number, { title: string; description: string }> = {
        403: {
            title: 'Akses ditolak',
            description: 'Akun Anda tidak memiliki izin untuk membuka halaman ini.',
        },
        404: {
            title: 'Halaman tidak ditemukan',
            description: 'Halaman yang Anda cari sudah dipindahkan atau tidak pernah ada.',
        },
        419: {
            title: 'Sesi kedaluwarsa',
            description: 'Halaman Anda sudah kedaluwarsa karena tidak ada aktivitas. Silakan muat ulang lalu coba lagi.',
        },
        429: {
            title: 'Terlalu banyak permintaan',
            description: 'Tunggu beberapa saat sebelum mencoba lagi.',
        },
        503: {
            title: 'Layanan sedang tidak tersedia',
            description: 'Aplikasi sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.',
        },
    };

    let info = $derived(messages[status] ?? {
        title: 'Terjadi kesalahan',
        description: 'Terjadi kesalahan tak terduga. Silakan coba lagi atau hubungi administrator.',
    });
</script>

{#if authenticated}
    <AppLayout title={info.title}>
        <div class="flex min-h-[60vh] items-center justify-center">
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                    <Icon name="alert" class="size-4" />
                    Error {status}
                </span>
                <h1 class="mt-4 text-xl font-bold text-slate-900 dark:text-slate-50">{info.title}</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{info.description}</p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                    <Link
                        href="/"
                        class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 dark:bg-orange-500 dark:text-slate-950 dark:hover:bg-orange-400"
                    >
                        <Icon name="dashboard" class="size-4" />
                        Ke Dashboard
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                        onclick={() => window.location.reload()}
                    >
                        Muat ulang
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
{:else}
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                <Icon name="alert" class="size-4" />
                Error {status}
            </span>
            <h1 class="mt-4 text-xl font-bold text-slate-900 dark:text-slate-50">{info.title}</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{info.description}</p>
            <a
                href="/"
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700"
            >
                <Icon name="arrow-left" class="size-4" />
                Kembali ke beranda
            </a>
        </div>
    </div>
{/if}
