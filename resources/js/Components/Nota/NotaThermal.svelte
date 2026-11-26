<script lang="ts">
    import { dateTime, number, rupiah } from '../../lib/format';
    import type { NotaRecord } from '../../types';

    let {
        nota,
        companyName,
        logoUrl,
        width,
    }: {
        nota: NotaRecord;
        companyName: string;
        logoUrl: string | null;
        width: '58' | '80';
    } = $props();

    let isKiloan = $derived(nota.jenis === 'Paket Kiloan');
    let totalPotongan = $derived(nota.total_harga - nota.jumlah);

    let widthClass = $derived(
        width === '58'
            ? 'w-[58mm] px-2 py-3 text-[9px] leading-snug'
            : 'w-[80mm] px-3 py-4 text-[10px] leading-snug',
    );
</script>

<section class="thermal-print invoice-print mx-auto max-w-full rounded-lg border border-slate-300 bg-white text-black shadow-sm {widthClass}">
    <header class="text-center">
        {#if logoUrl}
            <img src={logoUrl} alt={`Logo ${companyName}`} class="mx-auto mb-1 h-8 w-auto object-contain" />
        {/if}
        <p class="break-words text-sm font-bold uppercase tracking-wide">{companyName}</p>
        <p class="mt-0.5 text-[8px] font-semibold uppercase tracking-[0.2em]">Invoice</p>
    </header>

    <div class="my-2 border-t border-dashed border-black"></div>

    <dl class="space-y-0.5">
        <div class="flex justify-between gap-2">
            <dt class="shrink-0">No Nota</dt>
            <dd class="break-all text-right font-semibold">{nota.no_nota}</dd>
        </div>
        <div class="flex justify-between gap-2">
            <dt class="shrink-0">Tanggal</dt>
            <dd class="text-right">{dateTime(nota.waktu)}</dd>
        </div>
        <div class="flex justify-between gap-2">
            <dt class="shrink-0">Pelanggan</dt>
            <dd class="break-words text-right font-semibold">{nota.nama_pelanggan}</dd>
        </div>
        {#if nota.pelanggan?.kategori_pelanggan}
            <div class="flex justify-between gap-2">
                <dt class="shrink-0">Kategori</dt>
                <dd class="text-right">{nota.pelanggan.kategori_pelanggan}</dd>
            </div>
        {/if}
        <div class="flex justify-between gap-2">
            <dt class="shrink-0">Layanan</dt>
            <dd class="break-words text-right">
                {isKiloan ? (nota.kiloan?.paket ?? 'Paket dihapus') : (nota.jenis_layanan?.jenis_cuci ?? 'Layanan dihapus')}
            </dd>
        </div>
    </dl>

    <div class="my-2 border-t border-dashed border-black"></div>

    <div class="space-y-1.5">
        {#if isKiloan}
            <div>
                <p class="break-words font-semibold">{nota.kiloan?.paket ?? 'Paket dihapus'}</p>
                <div class="flex justify-between gap-2">
                    <span class="break-words">{number(nota.berat)} kg × {rupiah(nota.harga_per_kg)}</span>
                    <span class="shrink-0 font-semibold">{rupiah(nota.total_harga)}</span>
                </div>
            </div>
        {:else}
            {#each nota.barangs ?? [] as barang (barang.id)}
                <div>
                    <p class="break-words font-semibold">{barang.barang}</p>
                    <div class="flex justify-between gap-2">
                        <span class="break-words">{barang.jumlah_barang} × {rupiah(barang.harga)}</span>
                        <span class="shrink-0 font-semibold">{rupiah(barang.total_harga)}</span>
                    </div>
                </div>
            {/each}

            {#if nota.biaya_layanan > 0}
                <div>
                    <p class="break-words font-semibold">Biaya layanan</p>
                    <div class="flex justify-between gap-2">
                        <span class="break-words">{nota.jenis_layanan?.jenis_cuci ?? 'Layanan dihapus'}</span>
                        <span class="shrink-0 font-semibold">{rupiah(nota.biaya_layanan)}</span>
                    </div>
                </div>
            {/if}
        {/if}
    </div>

    <div class="my-2 border-t border-dashed border-black"></div>

    <dl class="space-y-0.5">
        <div class="flex justify-between gap-2">
            <dt>Subtotal</dt>
            <dd class="text-right font-semibold">{rupiah(nota.total_harga)}</dd>
        </div>
        <div class="flex justify-between gap-2">
            <dt class="break-words">{nota.reward_label ? `Hadiah: ${nota.reward_label}` : `Diskon (${nota.diskon}%)`}</dt>
            <dd class="shrink-0 text-right font-semibold">- {rupiah(totalPotongan)}</dd>
        </div>
        <div class="mt-1 flex items-end justify-between gap-2 border-t border-black pt-1">
            <dt class="text-xs font-bold uppercase">Total</dt>
            <dd class="text-base font-bold">{rupiah(nota.jumlah)}</dd>
        </div>
        <div class="flex justify-between gap-2">
            <dt>Pembayaran</dt>
            <dd class="text-right font-bold uppercase">{nota.status_pembayaran}</dd>
        </div>
        <div class="flex justify-between gap-2">
            <dt>Status</dt>
            <dd class="text-right font-semibold">{nota.status}</dd>
        </div>
    </dl>

    <p class="mt-2 break-words text-[8px] italic">Terbilang: {nota.terbilang}</p>

    <div class="my-2 border-t border-dashed border-black"></div>

    <footer class="break-words text-center text-[8px]">
        <p>Terima kasih telah menggunakan layanan {companyName}.</p>
        <p class="mt-0.5">Simpan struk ini sebagai bukti transaksi.</p>
    </footer>
</section>
