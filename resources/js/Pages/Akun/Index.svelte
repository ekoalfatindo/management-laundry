<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import CustomerLayout from '../../Components/CustomerLayout.svelte';
    import Badge from '../../Components/Ui/Badge.svelte';
    import EmptyState from '../../Components/Ui/EmptyState.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import Pagination from '../../Components/Ui/Pagination.svelte';
    import StatCard from '../../Components/Ui/StatCard.svelte';
    import { buttonClass, cardClass, tdClass, thClass } from '../../lib/ui';
    import { dateTime, number, rupiah } from '../../lib/format';
    import type { NotaRecord, Paginated } from '../../types';

    let {
        notas,
        ringkasan,
    }: {
        notas: Paginated<NotaRecord>;
        ringkasan: { total_nota: number; total_biaya: number };
    } = $props();

    function layananLabel(nota: NotaRecord): string {
        return nota.jenis === 'Paket Kiloan'
            ? (nota.kiloan?.paket ?? 'Paket dihapus')
            : (nota.jenis_layanan?.jenis_cuci ?? 'Layanan dihapus');
    }

    function detailLabel(nota: NotaRecord): string {
        return nota.jenis === 'Paket Kiloan' ? `${number(nota.berat)} kg` : 'Barang satuan';
    }

    function goToPage(pageNumber: number): void {
        router.get('/akun', { page: pageNumber }, { preserveScroll: true, preserveState: true });
    }
</script>

<CustomerLayout title="Nota Saya">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">Nota Saya</h1>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            Riwayat transaksi laundry yang tercatat atas nama akun Anda.
        </p>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <StatCard label="Total nota" value={number(ringkasan.total_nota)} hint="Seluruh transaksi Anda" icon="receipt" tone="orange" />
        <StatCard label="Total biaya" value={rupiah(ringkasan.total_biaya)} hint="Akumulasi nilai nota" icon="wallet" tone="teal" />
    </div>

    <section class="{cardClass} mt-6 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Riwayat transaksi</h2>
        </div>

        {#if notas.data.length === 0}
            <EmptyState
                title="Belum ada nota"
                description="Nota transaksi Anda akan muncul di sini setelah admin mencatat cucian."
                icon="receipt"
            />
        {:else}
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class={thClass}>No Nota</th>
                            <th class={thClass}>Waktu</th>
                            <th class={thClass}>Layanan</th>
                            <th class={thClass}>Jenis</th>
                            <th class="{thClass} text-right">Total</th>
                            <th class="{thClass} text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {#each notas.data as nota (nota.id)}
                            <tr>
                                <td class={tdClass}>
                                    <Link
                                        href={`/akun/nota/${nota.id}`}
                                        class="font-mono text-sm font-semibold text-orange-700 transition hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300"
                                    >
                                        {nota.no_nota}
                                    </Link>
                                </td>
                                <td class={tdClass}>{dateTime(nota.waktu)}</td>
                                <td class={tdClass}>
                                    <p class="font-medium text-slate-800 dark:text-slate-100">{layananLabel(nota)}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{detailLabel(nota)}</p>
                                </td>
                                <td class={tdClass}>
                                    <Badge tone={nota.jenis === 'Paket Kiloan' ? 'teal' : 'indigo'}>{nota.jenis}</Badge>
                                </td>
                                <td class="{tdClass} text-right font-semibold">{rupiah(nota.jumlah)}</td>
                                <td class="{tdClass} text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link href={`/akun/nota/${nota.id}`} class={buttonClass('secondary', 'px-2.5 py-1.5 text-xs')}>
                                            <Icon name="eye" class="size-3.5" />
                                            Lihat
                                        </Link>
                                        <a
                                            href={`/akun/nota/${nota.id}/pdf`}
                                            class={buttonClass('primary', 'px-2.5 py-1.5 text-xs')}
                                        >
                                            <Icon name="download" class="size-3.5" />
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 md:hidden">
                {#each notas.data as nota (nota.id)}
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-3">
                            <Link
                                href={`/akun/nota/${nota.id}`}
                                class="font-mono text-sm font-semibold text-orange-700 dark:text-orange-400"
                            >
                                {nota.no_nota}
                            </Link>
                            <Badge tone={nota.jenis === 'Paket Kiloan' ? 'teal' : 'indigo'}>{nota.jenis}</Badge>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{dateTime(nota.waktu)}</p>
                        <p class="mt-3 text-sm font-medium text-slate-800 dark:text-slate-100">{layananLabel(nota)}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{detailLabel(nota)}</p>
                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-800">
                            <p class="text-base font-bold text-orange-700 dark:text-orange-400">{rupiah(nota.jumlah)}</p>
                            <div class="flex gap-2">
                                <Link href={`/akun/nota/${nota.id}`} class={buttonClass('secondary', 'px-2.5 py-1.5 text-xs')}>
                                    Lihat
                                </Link>
                                <a href={`/akun/nota/${nota.id}/pdf`} class={buttonClass('primary', 'px-2.5 py-1.5 text-xs')}>
                                    PDF
                                </a>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800">
                <Pagination meta={notas.meta} onpage={goToPage} />
            </div>
        {/if}
    </section>
</CustomerLayout>
