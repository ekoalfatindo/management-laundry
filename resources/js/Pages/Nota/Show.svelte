<script lang="ts">
    import { Link, router, usePage } from '@inertiajs/svelte';
    import { onDestroy } from 'svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import NotaInvoice from '../../Components/Nota/NotaInvoice.svelte';
    import NotaThermal from '../../Components/Nota/NotaThermal.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import Badge from '../../Components/Ui/Badge.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import Modal from '../../Components/Ui/Modal.svelte';
    import { buttonClass, cardClass, tdClass, thClass } from '../../lib/ui';
    import { confirmAction, confirmDelete } from '../../lib/notify';
    import { dateTime, dateOnly, rupiah } from '../../lib/format';
    import type { NotaRecord, SharedProps } from '../../types';

    let { nota }: { nota: NotaRecord } = $props();

    const page = usePage<SharedProps>();

    let permissions = $derived(page.props.auth.user?.permissions ?? []);
    let can = $derived((permission: string) => permissions.includes(permission));

    let companyName = $derived(page.props.company?.nama_perusahaan || page.props.appName || 'Laundry Management');
    let logoUrl = $derived(page.props.company?.logo_url ?? null);

    let totalPotongan = $derived(nota.total_harga - nota.jumlah);
    let isKiloan = $derived(nota.jenis === 'Paket Kiloan');

    type PrintFormat = 'a4' | 'thermal80' | 'thermal58';

    let format = $state<PrintFormat>('a4');
    let printOpen = $state(false);

    const printFormats: { value: PrintFormat; label: string; description: string; icon: 'receipt' | 'print' | 'tag' }[] = [
        { value: 'a4', label: 'A4 Profesional', description: 'Invoice lengkap untuk arsip atau email.', icon: 'receipt' },
        { value: 'thermal80', label: 'Thermal 80 mm', description: 'Struk kasir lebar 80 mm.', icon: 'print' },
        { value: 'thermal58', label: 'Thermal 58 mm', description: 'Struk kasir ringkas 58 mm.', icon: 'tag' },
    ];

    function openPrint(): void {
        printOpen = true;
    }

    function printNow(): void {
        printOpen = false;
        window.setTimeout(() => window.print(), 80);
    }

    function closePrint(): void {
        printOpen = false;
    }

    function thermalPageHeightMm(): number {
        const el = document.querySelector<HTMLElement>('.thermal-print');

        if (!el) {
            return 0;
        }

        const style = getComputedStyle(el);
        const contentHeight =
            el.getBoundingClientRect().height -
            parseFloat(style.paddingTop) -
            parseFloat(style.paddingBottom) -
            parseFloat(style.borderTopWidth) -
            parseFloat(style.borderBottomWidth);

        return Math.ceil((contentHeight * 25.4) / 96);
    }

    function syncPrintPageSize(): void {
        const id = 'nota-print-page-size';
        let style = document.getElementById(id) as HTMLStyleElement | null;

        if (format === 'a4') {
            style?.remove();

            return;
        }

        if (!style) {
            style = document.createElement('style');
            style.id = id;
            document.head.appendChild(style);
        }

        const width = format === 'thermal80' ? 80 : 58;
        const margin = format === 'thermal80' ? 3 : 2;
        const height = thermalPageHeightMm();

        // Chrome rejects `size: <width> auto`; height must be explicit. Page margins sit
        // inside the page box, so include them (plus a 2 mm cushion) to avoid spill pages.
        const pageHeight = height > 0 ? ` ${height + margin * 2 + 2}mm` : '';

        style.textContent = `@media print { @page { size: ${width}mm${pageHeight}; margin: ${margin}mm; } }`;
    }

    $effect(() => {
        format;
        syncPrintPageSize();
    });

    window.addEventListener('beforeprint', syncPrintPageSize);

    onDestroy(() => {
        window.removeEventListener('beforeprint', syncPrintPageSize);
        document.getElementById('nota-print-page-size')?.remove();
    });

    function statusTone(): 'teal' | 'rose' | 'amber' {
        return nota.status === 'Selesai' ? 'teal' : nota.status === 'Dibatalkan' ? 'rose' : 'amber';
    }

    function markSelesai(): void {
        router.post(`/nota/${nota.id}/selesai`, {}, { preserveScroll: true });
    }

    function togglePembayaran(): void {
        router.patch(
            `/nota/${nota.id}/pembayaran`,
            { status_pembayaran: nota.is_lunas ? 'Belum Lunas' : 'Lunas' },
            { preserveScroll: true },
        );
    }

    async function cancelNota(): Promise<void> {
        const confirmed = await confirmAction({
            title: 'Batalkan Nota',
            text: `Yakin ingin membatalkan nota ${nota.no_nota}? Hadiah yang terpakai akan dilepas dan progres loyalitas dihitung ulang.`,
            confirmText: 'Ya, batalkan',
            danger: true,
        });

        if (confirmed) {
            router.post(`/nota/${nota.id}/batal`, {}, { preserveScroll: true });
        }
    }
</script>

<AppLayout title={`Nota ${nota.no_nota}`}>
    <div class="no-print">
        <PageHeader title={`Nota ${nota.no_nota}`} description={`Dibuat ${dateTime(nota.created_at)}`}>
            <Link href="/nota" class={buttonClass('secondary')}>
                <Icon name="arrow-left" class="size-4" />
                Kembali
            </Link>

            <Button variant="secondary" onclick={openPrint}>
                <Icon name="print" class="size-4" />
                Cetak Invoice
            </Button>

            {#if can('nota.update')}
                {#if !nota.is_dibatalkan && !nota.is_selesai}
                    <Button variant="secondary" onclick={markSelesai}>
                        <Icon name="check" class="size-4" />
                        Tandai Selesai
                    </Button>
                {/if}

                {#if !nota.is_dibatalkan}
                    <Button variant="secondary" onclick={togglePembayaran}>
                        <Icon name="wallet" class="size-4" />
                        {nota.is_lunas ? 'Tandai Belum Lunas' : 'Tandai Lunas'}
                    </Button>
                {/if}

                {#if !nota.is_dibatalkan}
                    <Link href={`/nota/${nota.id}/edit`} class={buttonClass('primary')}>
                        <Icon name="edit" class="size-4" />
                        Ubah
                    </Link>
                {/if}

                {#if !nota.is_dibatalkan}
                    <Button variant="danger" onclick={cancelNota}>
                        <Icon name="close" class="size-4" />
                        Batalkan
                    </Button>
                {/if}
            {/if}

            {#if can('nota.delete')}
                <Button
                    variant="danger"
                    onclick={() =>
                        confirmDelete({
                            url: `/nota/${nota.id}`,
                            title: 'Hapus Nota',
                            text: `Yakin ingin menghapus nota ${nota.no_nota}? Data barang pada nota ini juga akan dihapus.`,
                            preserveScroll: false,
                        })}
                >
                    <Icon name="trash" class="size-4" />
                    Hapus
                </Button>
            {/if}
        </PageHeader>
    </div>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        {#if format === 'a4'}
            <NotaInvoice {nota} {companyName} {logoUrl} />
        {:else}
            <NotaThermal {nota} {companyName} {logoUrl} width={format === 'thermal80' ? '80' : '58'} />
        {/if}

        <aside class="no-print space-y-4">
            <section class="{cardClass} p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Ringkasan</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Jenis nota</dt>
                        <dd><Badge tone={isKiloan ? 'teal' : 'indigo'}>{nota.jenis}</Badge></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                        <dd><Badge tone={statusTone()}>{nota.status}</Badge></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Pembayaran</dt>
                        <dd><Badge tone={nota.is_lunas ? 'teal' : 'amber'}>{nota.status_pembayaran}</Badge></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Pelanggan terdaftar</dt>
                        <dd class="text-right font-medium">{nota.pelanggan?.name ?? 'Tidak terhubung'}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Subtotal</dt>
                        <dd class="font-medium">{rupiah(nota.total_harga)}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">
                            {nota.reward_label ? `Hadiah (${nota.reward_label})` : `Diskon (${nota.diskon}%)`}
                        </dt>
                        <dd class="font-medium">{rupiah(totalPotongan)}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-3 dark:border-slate-800">
                        <dt class="font-semibold">Total bayar</dt>
                        <dd class="text-lg font-bold text-orange-700 dark:text-orange-400">{rupiah(nota.jumlah)}</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-3 dark:border-orange-500/30 dark:bg-orange-500/10">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Terbilang</p>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-orange-900 dark:text-orange-100">{nota.terbilang}</p>
                </div>
            </section>

            <section class="{cardClass} p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Informasi</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Dibuat pada</dt>
                        <dd class="font-medium">{dateOnly(nota.created_at)}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Sumber harga</dt>
                        <dd class="text-right font-medium">Tersimpan di nota</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    Harga paket, biaya layanan, dan harga barang disimpan saat transaksi sehingga nota lama tidak berubah ketika master data diperbarui.
                </p>
            </section>

            {#if (nota.barangs?.length ?? 0) > 0}
                <section class="{cardClass} overflow-hidden">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Rincian barang</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-800/60">
                                <tr>
                                    <th class={thClass}>Barang</th>
                                    <th class="{thClass} text-center">Qty</th>
                                    <th class="{thClass} text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                {#each nota.barangs ?? [] as barang (barang.id)}
                                    <tr>
                                        <td class="{tdClass} font-medium">{barang.barang}</td>
                                        <td class="{tdClass} text-center">{barang.jumlah_barang}</td>
                                        <td class="{tdClass} text-right font-semibold">{rupiah(barang.total_harga)}</td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </section>
            {/if}
        </aside>
    </div>

    <Modal open={printOpen} title="Pilih Format Cetak" onclose={closePrint}>
        <div class="space-y-3">
            {#each printFormats as option}
                <button
                    type="button"
                    class="flex w-full items-start gap-3 rounded-xl border p-3 text-left transition {format === option.value
                        ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-500/20 dark:border-orange-400 dark:bg-orange-500/10'
                        : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600'}"
                    aria-pressed={format === option.value}
                    onclick={() => (format = option.value)}
                >
                    <span class="rounded-lg bg-orange-600 p-2 text-white dark:bg-orange-500 dark:text-slate-950">
                        <Icon name={option.icon} class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-900 dark:text-slate-50">{option.label}</span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{option.description}</span>
                    </span>
                    {#if format === option.value}
                        <span class="text-orange-700 dark:text-orange-400"><Icon name="check" class="size-5" /></span>
                    {/if}
                </button>
            {/each}
        </div>

        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            Pratinjau di belakang mengikuti format yang dipilih. Menu, tombol, dan sidebar tidak ikut tercetak.
        </p>

        <div class="mt-5 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
            <Button variant="secondary" onclick={closePrint}>Batal</Button>
            <Button onclick={printNow}>
                <Icon name="print" class="size-4" />
                Cetak Sekarang
            </Button>
        </div>
    </Modal>
</AppLayout>
