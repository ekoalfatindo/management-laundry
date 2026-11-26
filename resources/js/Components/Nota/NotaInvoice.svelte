<script lang="ts">
    import Badge from '../Ui/Badge.svelte';
    import Icon from '../Ui/Icon.svelte';
    import { dateTime, number, rupiah } from '../../lib/format';
    import type { NotaRecord } from '../../types';

    let {
        nota,
        companyName,
        logoUrl,
    }: {
        nota: NotaRecord;
        companyName: string;
        logoUrl: string | null;
    } = $props();

    let totalPotongan = $derived(nota.total_harga - nota.jumlah);
    let isKiloan = $derived(nota.jenis === 'Paket Kiloan');
</script>

<section
    class="invoice-print mx-auto w-full max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 text-slate-800 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
>
    <header class="flex flex-wrap items-start justify-between gap-6 border-b-2 border-orange-600 pb-5">
        <div class="flex min-w-0 items-center gap-4">
            <span
                class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700"
            >
                {#if logoUrl}
                    <img src={logoUrl} alt={`Logo ${companyName}`} class="size-full object-contain p-1.5" />
                {:else}
                    <span class="text-orange-700 dark:text-orange-400">
                        <Icon name="shirt" class="size-8" />
                    </span>
                {/if}
            </span>
            <div class="min-w-0">
                <p class="truncate text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50" title={companyName}>
                    {companyName}
                </p>
                <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-[0.22em] text-orange-700 dark:text-orange-400">
                    Invoice
                </p>
            </div>
        </div>

        <div class="text-right">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Nomor Invoice</p>
            <p class="font-mono text-lg font-bold text-slate-900 dark:text-slate-50">{nota.no_nota}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{dateTime(nota.waktu)}</p>
            <div class="mt-2 flex flex-wrap justify-end gap-1.5">
                <Badge tone={nota.status === 'Selesai' ? 'teal' : nota.status === 'Dibatalkan' ? 'rose' : 'amber'}>
                    {nota.status}
                </Badge>
                <Badge tone={nota.is_lunas ? 'teal' : 'amber'}>{nota.status_pembayaran}</Badge>
            </div>
        </div>
    </header>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ditagihkan kepada</p>
            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-50">{nota.nama_pelanggan}</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                {nota.pelanggan?.email ?? 'Pelanggan umum'}
            </p>
            {#if nota.pelanggan?.kategori_pelanggan}
                <p class="mt-1">
                    <Badge tone={nota.pelanggan.kategori_pelanggan === 'Member' ? 'amber' : 'slate'}>
                        {nota.pelanggan.kategori_pelanggan}
                    </Badge>
                </p>
            {/if}
        </div>

        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/60">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Jenis Layanan</p>
            <div class="mt-1.5">
                <Badge tone={isKiloan ? 'teal' : 'indigo'}>{nota.jenis}</Badge>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                {isKiloan
                    ? `${nota.kiloan?.paket ?? 'Paket dihapus'} · ${number(nota.berat)} kg`
                    : (nota.jenis_layanan?.jenis_cuci ?? 'Layanan dihapus')}
            </p>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    <th class="pb-2 pr-3">Deskripsi</th>
                    <th class="pb-2 px-3 text-center">Qty</th>
                    <th class="pb-2 px-3 text-right">Harga</th>
                    <th class="pb-2 pl-3 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                {#if isKiloan}
                    <tr>
                        <td class="py-3 pr-3">
                            <p class="font-semibold text-slate-900 dark:text-slate-50">{nota.kiloan?.paket ?? 'Paket dihapus'}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                Layanan kiloan · {rupiah(nota.harga_per_kg)} / kg
                            </p>
                        </td>
                        <td class="px-3 py-3 text-center align-top">{number(nota.berat)} kg</td>
                        <td class="px-3 py-3 text-right align-top">{rupiah(nota.harga_per_kg)}</td>
                        <td class="py-3 pl-3 text-right align-top font-semibold">{rupiah(nota.total_harga)}</td>
                    </tr>
                {:else}
                    {#each nota.barangs ?? [] as barang (barang.id)}
                        <tr>
                            <td class="py-3 pr-3">
                                <p class="font-semibold text-slate-900 dark:text-slate-50">{barang.barang}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Harga satuan</p>
                            </td>
                            <td class="px-3 py-3 text-center align-top">{barang.jumlah_barang}</td>
                            <td class="px-3 py-3 text-right align-top">{rupiah(barang.harga)}</td>
                            <td class="py-3 pl-3 text-right align-top font-semibold">{rupiah(barang.total_harga)}</td>
                        </tr>
                    {/each}

                    {#if nota.biaya_layanan > 0}
                        <tr>
                            <td class="py-3 pr-3">
                                <p class="font-semibold text-slate-900 dark:text-slate-50">Biaya layanan</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {nota.jenis_layanan?.jenis_cuci ?? 'Layanan dihapus'}
                                </p>
                            </td>
                            <td class="px-3 py-3 text-center align-top">1</td>
                            <td class="px-3 py-3 text-right align-top">{rupiah(nota.biaya_layanan)}</td>
                            <td class="py-3 pl-3 text-right align-top font-semibold">{rupiah(nota.biaya_layanan)}</td>
                        </tr>
                    {/if}
                {/if}
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between sm:gap-8">
        <div class="max-w-sm flex-1 rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-500/30 dark:bg-orange-500/10">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Terbilang</p>
            <p class="mt-1 text-sm font-semibold leading-relaxed text-orange-900 dark:text-orange-100">{nota.terbilang}</p>
        </div>

        <dl class="w-full space-y-2 text-sm sm:w-72">
            <div class="flex justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">Subtotal</dt>
                <dd class="font-medium">{rupiah(nota.total_harga)}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">
                    {nota.reward_label ? `Hadiah (${nota.reward_label})` : `Diskon (${nota.diskon}%)`}
                </dt>
                <dd class="font-medium">- {rupiah(totalPotongan)}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-slate-300 pt-2 dark:border-slate-700">
                <dt class="text-base font-semibold text-slate-900 dark:text-slate-50">Total</dt>
                <dd class="text-xl font-bold text-orange-700 dark:text-orange-400">{rupiah(nota.jumlah)}</dd>
            </div>
        </dl>
    </div>

    <footer class="mt-8 space-y-1 border-t border-dashed border-slate-300 pt-4 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
        <p>Terima kasih telah menggunakan layanan {companyName}.</p>
        <p>Simpan invoice ini sebagai bukti transaksi.</p>
    </footer>
</section>
