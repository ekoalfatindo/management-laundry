<script lang="ts">
    import { Link, router, usePage } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import EmptyState from '../../Components/Ui/EmptyState.svelte';
    import Pagination from '../../Components/Ui/Pagination.svelte';
    import Badge from '../../Components/Ui/Badge.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { buttonClass, cardClass, inputClass, tdClass, thClass } from '../../lib/ui';
    import { confirmDelete } from '../../lib/notify';
    import { dateTime, rupiah } from '../../lib/format';
    import type { NotaRecord, Paginated, SharedProps } from '../../types';

    let {
        notas,
        filters,
        jenisOptions,
        statusOptions,
        statusPembayaranOptions,
    }: {
        notas: Paginated<NotaRecord>;
        filters: {
            search: string;
            jenis: string;
            status: string;
            status_pembayaran: string;
            dari: string;
            sampai: string;
            per_page: number;
        };
        jenisOptions: { value: string; label: string }[];
        statusOptions: { value: string; label: string }[];
        statusPembayaranOptions: { value: string; label: string }[];
    } = $props();

    const page = usePage<SharedProps>();

    let permissions = $derived(page.props.auth.user?.permissions ?? []);
    let can = $derived((permission: string) => permissions.includes(permission));

    let search = $state(untrack(() => filters.search));
    let jenis = $state(untrack(() => filters.jenis));
    let status = $state(untrack(() => filters.status));
    let statusPembayaran = $state(untrack(() => filters.status_pembayaran));
    let dari = $state(untrack(() => filters.dari));
    let sampai = $state(untrack(() => filters.sampai));
    let perPage = $state(untrack(() => filters.per_page));

    $effect(() => {
        search = filters.search;
        jenis = filters.jenis;
        status = filters.status;
        statusPembayaran = filters.status_pembayaran;
        dari = filters.dari;
        sampai = filters.sampai;
        perPage = filters.per_page;
    });

    let exportUrl = $derived(
        (() => {
            const params = new URLSearchParams();

            if (search) params.set('search', search);
            if (jenis) params.set('jenis', jenis);
            if (status) params.set('status', status);
            if (statusPembayaran) params.set('status_pembayaran', statusPembayaran);
            if (dari) params.set('dari', dari);
            if (sampai) params.set('sampai', sampai);

            const query = params.toString();

            return `/nota/export${query ? `?${query}` : ''}`;
        })(),
    );

    function queryParameters(extra: Record<string, string | number> = {}): Record<string, string | number> {
        const params: Record<string, string | number> = { per_page: perPage, ...extra };

        if (search) params.search = search;
        if (jenis) params.jenis = jenis;
        if (status) params.status = status;
        if (statusPembayaran) params.status_pembayaran = statusPembayaran;
        if (dari) params.dari = dari;
        if (sampai) params.sampai = sampai;

        return params;
    }

    function applyFilters(event?: Event): void {
        event?.preventDefault();

        router.get('/nota', queryParameters({ page: 1 }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function resetFilters(): void {
        search = '';
        jenis = '';
        status = '';
        statusPembayaran = '';
        dari = '';
        sampai = '';
        perPage = 10;

        router.get('/nota', { per_page: 10 }, { preserveScroll: true, preserveState: true });
    }

    function goToPage(pageNumber: number): void {
        router.get('/nota', queryParameters({ page: pageNumber }), { preserveScroll: true, preserveState: true });
    }

    function changePerPage(event: Event): void {
        perPage = Number((event.target as HTMLSelectElement).value);
        applyFilters();
    }

    function layananLabel(nota: NotaRecord): string {
        return nota.jenis === 'Paket Kiloan'
            ? (nota.kiloan?.paket ?? 'Paket dihapus')
            : (nota.jenis_layanan?.jenis_cuci ?? 'Layanan dihapus');
    }

    function statusTone(value: NotaRecord['status']): 'teal' | 'rose' | 'amber' {
        if (value === 'Selesai') {
            return 'teal';
        }

        return value === 'Dibatalkan' ? 'rose' : 'amber';
    }

    function pembayaranTone(value: NotaRecord['status_pembayaran']): 'teal' | 'amber' {
        return value === 'Lunas' ? 'teal' : 'amber';
    }

</script>

<AppLayout title="Nota Transaksi">
    <PageHeader title="Nota Transaksi" description="Riwayat nota kiloan dan barang satuan.">
        {#if can('nota.export')}
            <a href={exportUrl} class={buttonClass('secondary')} title="Unduh laporan Excel (.xlsx)">
                <Icon name="download" class="size-4" />
                Ekspor Excel
            </a>
        {/if}

        {#if can('nota.create')}
            <Link href="/nota/create" class={buttonClass('primary')}>
                <Icon name="plus" class="size-4" />
                Buat Nota
            </Link>
        {/if}
    </PageHeader>

    <section class="{cardClass} overflow-hidden">
        <form class="border-b border-slate-200 p-4 dark:border-slate-800" onsubmit={applyFilters}>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-12">
                <div class="relative xl:col-span-4">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <Icon name="search" class="size-4" />
                    </span>
                    <input
                        type="search"
                        class="{inputClass} pl-9"
                        placeholder="Cari nomor nota atau pelanggan…"
                        aria-label="Cari nota"
                        bind:value={search}
                    />
                </div>

                <select class="{inputClass} xl:col-span-2" aria-label="Filter jenis nota" bind:value={jenis}>
                    <option value="">Semua jenis</option>
                    {#each jenisOptions as option}
                        <option value={option.value}>{option.label}</option>
                    {/each}
                </select>

                <select class="{inputClass} xl:col-span-2" aria-label="Filter status nota" bind:value={status}>
                    <option value="">Semua status</option>
                    {#each statusOptions as option}
                        <option value={option.value}>{option.label}</option>
                    {/each}
                </select>

                <select class="{inputClass} xl:col-span-2" aria-label="Filter status pembayaran" bind:value={statusPembayaran}>
                    <option value="">Semua pembayaran</option>
                    {#each statusPembayaranOptions as option}
                        <option value={option.value}>{option.label}</option>
                    {/each}
                </select>

                <div class="flex gap-2 xl:col-span-2">
                    <Button type="submit">Terapkan</Button>
                    <Button variant="ghost" onclick={resetFilters}>Reset</Button>
                </div>

                <div class="xl:col-span-3">
                    <input type="date" class={inputClass} aria-label="Tanggal mulai" bind:value={dari} />
                </div>

                <div class="xl:col-span-3">
                    <input type="date" class={inputClass} aria-label="Tanggal akhir" bind:value={sampai} />
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between gap-3">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Tanggal kosong berarti tanpa batas. Ekspor mengikuti filter yang aktif.
                </p>

                <label class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    Baris
                    <select class="{inputClass} w-20" aria-label="Jumlah baris per halaman" value={perPage} onchange={changePerPage}>
                        <option value={10}>10</option>
                        <option value={25}>25</option>
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                    </select>
                </label>
            </div>
        </form>

        {#if notas.data.length === 0}
            <EmptyState icon="receipt" title="Nota tidak ditemukan" description="Ubah filter atau buat nota baru.">
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
                            <th class={thClass}>Status</th>
                            <th class="{thClass} hidden sm:table-cell">Pembayaran</th>
                            <th class={thClass}>Pelanggan</th>
                            <th class="{thClass} hidden xl:table-cell">Layanan</th>
                            <th class="{thClass} hidden lg:table-cell">Waktu</th>
                            <th class="{thClass} hidden text-right md:table-cell">Total Harga</th>
                            <th class="{thClass} hidden text-center md:table-cell">Diskon</th>
                            <th class="{thClass} text-right">Jumlah</th>
                            <th class="{thClass} text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {#each notas.data as nota (nota.id)}
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class={tdClass}>
                                    <Link
                                        href={`/nota/${nota.id}`}
                                        class="font-mono text-xs font-semibold text-orange-700 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300"
                                    >
                                        {nota.no_nota}
                                    </Link>
                                </td>
                                <td class={tdClass}>
                                    <Badge tone={nota.jenis === 'Paket Kiloan' ? 'teal' : 'indigo'}>{nota.jenis}</Badge>
                                </td>
                                <td class={tdClass}><Badge tone={statusTone(nota.status)}>{nota.status}</Badge></td>
                                <td class="{tdClass} hidden sm:table-cell">
                                    <Badge tone={pembayaranTone(nota.status_pembayaran)}>{nota.status_pembayaran}</Badge>
                                </td>
                                <td class="{tdClass} max-w-[12rem] truncate">{nota.nama_pelanggan}</td>
                                <td class="{tdClass} hidden max-w-[10rem] truncate text-slate-500 xl:table-cell dark:text-slate-400">
                                    {layananLabel(nota)}
                                </td>
                                <td class="{tdClass} hidden whitespace-nowrap text-slate-500 lg:table-cell dark:text-slate-400">
                                    {dateTime(nota.waktu)}
                                </td>
                                <td class="{tdClass} hidden text-right md:table-cell">{rupiah(nota.total_harga)}</td>
                                <td class="{tdClass} hidden text-center md:table-cell">
                                    {#if nota.reward_label}
                                        <span class="text-xs font-semibold text-teal-700 dark:text-teal-300">Hadiah</span>
                                    {:else}
                                        {nota.diskon}%
                                    {/if}
                                </td>
                                <td class="{tdClass} text-right font-semibold">{rupiah(nota.jumlah)}</td>
                                <td class={tdClass}>
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            href={`/nota/${nota.id}`}
                                            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-orange-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-orange-300"
                                            aria-label={`Lihat nota ${nota.no_nota}`}
                                        >
                                            <Icon name="eye" class="size-4" />
                                        </Link>

                                        {#if can('nota.update') && !nota.is_dibatalkan}
                                            <Link
                                                href={`/nota/${nota.id}/edit`}
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-orange-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-orange-300"
                                                aria-label={`Ubah nota ${nota.no_nota}`}
                                            >
                                                <Icon name="edit" class="size-4" />
                                            </Link>
                                        {/if}

                                        {#if can('nota.delete')}
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                aria-label={`Hapus nota ${nota.no_nota}`}
                                                onclick={() =>
                                                    confirmDelete({
                                                        url: `/nota/${nota.id}`,
                                                        title: 'Hapus Nota',
                                                        text: `Yakin ingin menghapus nota ${nota.no_nota}? Data barang pada nota ini juga akan dihapus.`,
                                                    })}
                                            >
                                                <Icon name="trash" class="size-4" />
                                            </button>
                                        {/if}
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800">
                <Pagination meta={notas.meta} onpage={goToPage} />
            </div>
        {/if}
    </section>
</AppLayout>
