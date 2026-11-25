<script lang="ts">
    import { Link, usePage } from '@inertiajs/svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import NotaInvoice from '../../Components/Nota/NotaInvoice.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import Badge from '../../Components/Ui/Badge.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { buttonClass, cardClass, tdClass, thClass } from '../../lib/ui';
    import { confirmDelete } from '../../lib/notify';
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
</script>

<AppLayout title={`Nota ${nota.no_nota}`}>
    <div class="no-print">
        <PageHeader title={`Nota ${nota.no_nota}`} description={`Dibuat ${dateTime(nota.created_at)}`}>
            <Link href="/nota" class={buttonClass('secondary')}>
                <Icon name="arrow-left" class="size-4" />
                Kembali
            </Link>

            <Button variant="secondary" onclick={() => window.print()}>
                <Icon name="print" class="size-4" />
                Cetak Invoice
            </Button>

            {#if can('nota.update')}
                <Link href={`/nota/${nota.id}/edit`} class={buttonClass('primary')}>
                    <Icon name="edit" class="size-4" />
                    Ubah
                </Link>
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
        <NotaInvoice {nota} {companyName} {logoUrl} />

        <aside class="no-print space-y-4">
            <section class="{cardClass} p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Ringkasan</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Jenis nota</dt>
                        <dd><Badge tone={isKiloan ? 'teal' : 'indigo'}>{nota.jenis}</Badge></dd>
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
                        <dt class="text-slate-500 dark:text-slate-400">Potongan</dt>
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
</AppLayout>
