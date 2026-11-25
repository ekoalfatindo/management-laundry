<script lang="ts">
    import { Link, usePage } from '@inertiajs/svelte';
    import AppLayout from '../Components/AppLayout.svelte';
    import PageHeader from '../Components/Ui/PageHeader.svelte';
    import StatCard from '../Components/Ui/StatCard.svelte';
    import MonthlyChart from '../Components/Ui/MonthlyChart.svelte';
    import Badge from '../Components/Ui/Badge.svelte';
    import EmptyState from '../Components/Ui/EmptyState.svelte';
    import Icon from '../Components/Ui/Icon.svelte';
    import { cardClass, tdClass, thClass, buttonClass } from '../lib/ui';
    import { dateTime, rupiah } from '../lib/format';
    import type { NotaRecord, SharedProps } from '../types';

    let {
        summary,
        recent,
    }: {
        summary: {
            totalPendapatan: number;
            totalPenjualan: number;
            totalUsers: number;
            totalPaket: number;
            tahun: number;
            bulanan: number[];
            pendapatanBulanIni: number;
        };
        recent: NotaRecord[];
    } = $props();

    const page = usePage<SharedProps>();

    let permissions = $derived(page.props.auth.user?.permissions ?? []);
    let can = $derived((permission: string) => permissions.includes(permission));
</script>

<AppLayout title="Dashboard">
    <PageHeader title="Dashboard" description="Ringkasan operasional dan pendapatan laundry.">
        {#if can('nota.create')}
            <Link href="/nota/create" class={buttonClass('primary')}>
                <Icon name="plus" class="size-4" />
                Buat Nota
            </Link>
        {/if}
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
            label="Total Pendapatan"
            value={rupiah(summary.totalPendapatan)}
            hint="Akumulasi seluruh nota"
            icon="wallet"
            tone="orange"
        />
        <StatCard
            label="Pendapatan Bulan Ini"
            value={rupiah(summary.pendapatanBulanIni)}
            hint={`Periode ${summary.tahun}`}
            icon="trend"
            tone="indigo"
        />
        <StatCard
            label="Total Nota"
            value={summary.totalPenjualan.toLocaleString('id-ID')}
            hint="Nota kiloan dan satuan"
            icon="receipt"
            tone="amber"
        />
        <StatCard
            label="Paket Kiloan"
            value={summary.totalPaket.toLocaleString('id-ID')}
            hint={`${summary.totalUsers.toLocaleString('id-ID')} pengguna terdaftar`}
            icon="washer"
            tone="slate"
        />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <section class="{cardClass} p-5 xl:col-span-3">
            <MonthlyChart data={summary.bulanan} year={summary.tahun} />
        </section>

        <section class="{cardClass} overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Nota terbaru</h2>
                {#if can('nota.view')}
                    <Link href="/nota" class="text-xs font-semibold text-orange-700 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">
                        Lihat semua
                    </Link>
                {/if}
            </div>

            {#if recent.length === 0}
                <EmptyState icon="receipt" title="Belum ada nota" description="Nota baru akan tampil di sini." />
            {:else}
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    {#each recent as nota (nota.id)}
                        <li>
                            <Link
                                href={`/nota/${nota.id}`}
                                class="flex items-center gap-3 px-5 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                            >
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                    <Icon name="receipt" class="size-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{nota.no_nota}</p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{nota.nama_pelanggan}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{rupiah(nota.jumlah)}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{dateTime(nota.waktu)}</p>
                                </div>
                            </Link>
                        </li>
                    {/each}
                </ul>
            {/if}
        </section>
    </div>

    <section class="{cardClass} mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Transaksi terbaru</h2>
        </div>

        {#if recent.length === 0}
            <EmptyState icon="receipt" title="Belum ada transaksi" description="Buat nota pertama untuk mulai mencatat pendapatan.">
                {#if can('nota.create')}
                    <Link href="/nota/create" class={buttonClass('primary')}>Buat Nota</Link>
                {/if}
            </EmptyState>
        {:else}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class={thClass}>No Nota</th>
                            <th class={thClass}>Jenis</th>
                            <th class={thClass}>Pelanggan</th>
                            <th class={thClass}>Waktu</th>
                            <th class="{thClass} text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {#each recent as nota (nota.id)}
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="{tdClass} font-mono text-xs font-semibold">{nota.no_nota}</td>
                                <td class={tdClass}>
                                    <Badge tone={nota.jenis === 'Paket Kiloan' ? 'teal' : 'indigo'}>{nota.jenis}</Badge>
                                </td>
                                <td class={tdClass}>{nota.nama_pelanggan}</td>
                                <td class="{tdClass} text-slate-500 dark:text-slate-400">{dateTime(nota.waktu)}</td>
                                <td class="{tdClass} text-right font-semibold">{rupiah(nota.jumlah)}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {/if}
    </section>
</AppLayout>
