<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import Badge from '../../Components/Ui/Badge.svelte';
    import StatCard from '../../Components/Ui/StatCard.svelte';
    import Pagination from '../../Components/Ui/Pagination.svelte';
    import EmptyState from '../../Components/Ui/EmptyState.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { buttonClass, cardClass, tdClass, thClass } from '../../lib/ui';
    import { dateOnly, dateTime, rupiah } from '../../lib/format';
    import type {
        NotaRecord,
        Paginated,
        PelangganLoyalitas,
        RewardRecord,
        StatusHadiah,
        StatusNota,
        StatusPembayaran,
        UserRecord,
    } from '../../types';

    let {
        pelanggan,
        notas,
        ringkasan,
        loyalitas,
    }: {
        pelanggan: UserRecord;
        notas: Paginated<NotaRecord>;
        ringkasan: { total_nota: number; transaksi_selesai: number; total_belanja: number };
        loyalitas: PelangganLoyalitas;
    } = $props();

    let isMember = $derived(pelanggan.kategori_pelanggan === 'Member');
    let loyalty = $derived(loyalitas.loyalty);

    let progressPercent = $derived.by(() => {
        if (!loyalty || loyalty.target < 1) {
            return 0;
        }

        const remainder = loyalty.transaksi_selesai % loyalty.target;

        return Math.round((remainder / loyalty.target) * 100);
    });

    function goToPage(page: number): void {
        router.get(`/pelanggan/${pelanggan.id}`, { page }, { preserveScroll: true, preserveState: true });
    }

    function statusTone(status: StatusNota): 'teal' | 'rose' | 'amber' {
        if (status === 'Selesai') {
            return 'teal';
        }

        return status === 'Dibatalkan' ? 'rose' : 'amber';
    }

    function pembayaranTone(status: StatusPembayaran): 'teal' | 'amber' {
        return status === 'Lunas' ? 'teal' : 'amber';
    }

    function rewardTone(status: StatusHadiah): 'teal' | 'indigo' | 'rose' | 'slate' {
        return status === 'Tersedia'
            ? 'teal'
            : status === 'Digunakan'
              ? 'indigo'
              : status === 'Dibatalkan'
                ? 'rose'
                : 'slate';
    }

    function promotionPercent(selesai: number, target: number): number {
        if (target < 1) {
            return 0;
        }

        return Math.round(((selesai % target) / target) * 100);
    }

    function rewardUsage(reward: RewardRecord): string {
        if (reward.status === 'Digunakan') {
            return reward.digunakan_at ? dateTime(reward.digunakan_at) : '-';
        }

        if (reward.status === 'Tersedia' && reward.kedaluwarsa_at) {
            return `s.d. ${dateOnly(reward.kedaluwarsa_at)}`;
        }

        return '-';
    }
</script>

<AppLayout title={`Pelanggan ${pelanggan.name}`}>
    <PageHeader title={pelanggan.name} description={pelanggan.email}>
        <Link href="/pelanggan" class={buttonClass('secondary')}>
            <Icon name="arrow-left" class="size-4" />
            Kembali
        </Link>
    </PageHeader>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Total Nota" value={String(ringkasan.total_nota)} icon="receipt" tone="orange" />
        <StatCard label="Transaksi Selesai" value={String(ringkasan.transaksi_selesai)} icon="check" tone="teal" />
        <StatCard label="Total Belanja" value={rupiah(ringkasan.total_belanja)} icon="wallet" tone="indigo" />
        <StatCard
            label="Kategori"
            value={pelanggan.kategori_pelanggan ?? 'Reguler'}
            hint={isMember && pelanggan.member_sejak ? `Member sejak ${dateOnly(pelanggan.member_sejak)}` : ''}
            icon="customer"
            tone={isMember ? 'amber' : 'slate'}
        />
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="{cardClass} overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Riwayat Transaksi</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Semua nota pelanggan ini, termasuk yang dibatalkan.</p>
                </div>

                {#if notas.data.length === 0}
                    <EmptyState icon="receipt" title="Belum ada transaksi" description="Nota pelanggan ini akan tampil di sini." />
                {:else}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-800/60">
                                <tr>
                                    <th class={thClass}>No Nota</th>
                                    <th class={thClass}>Waktu</th>
                                    <th class={thClass}>Status</th>
                                    <th class={thClass}>Pembayaran</th>
                                    <th class="{thClass} text-right">Jumlah</th>
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
                                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{nota.jenis}</p>
                                        </td>
                                        <td class="{tdClass} whitespace-nowrap text-slate-500 dark:text-slate-400">{dateTime(nota.waktu)}</td>
                                        <td class={tdClass}><Badge tone={statusTone(nota.status)}>{nota.status}</Badge></td>
                                        <td class={tdClass}><Badge tone={pembayaranTone(nota.status_pembayaran)}>{nota.status_pembayaran}</Badge></td>
                                        <td class="{tdClass} text-right font-semibold">{rupiah(nota.jumlah)}</td>
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

            <section class="{cardClass} overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Riwayat Hadiah</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Hadiah dari program loyalitas maupun promosi.</p>
                </div>

                {#if loyalitas.rewards.length === 0}
                    <EmptyState icon="gift" title="Belum ada hadiah" description="Hadiah muncul otomatis setelah target transaksi tercapai." />
                {:else}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-800/60">
                                <tr>
                                    <th class={thClass}>Hadiah</th>
                                    <th class={thClass}>Sumber</th>
                                    <th class={thClass}>Nilai</th>
                                    <th class={thClass}>Status</th>
                                    <th class={thClass}>Keterangan</th>
                                    <th class="{thClass} text-right">Nota</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                {#each loyalitas.rewards as reward (reward.id)}
                                    <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                        <td class={tdClass}>
                                            <p class="font-semibold">{reward.label}</p>
                                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Siklus {reward.siklus}</p>
                                        </td>
                                        <td class={tdClass}>
                                            <Badge tone={reward.sumber === 'Loyalitas' ? 'amber' : 'indigo'}>{reward.sumber}</Badge>
                                        </td>
                                        <td class={tdClass}>{reward.nilai_label}</td>
                                        <td class={tdClass}><Badge tone={rewardTone(reward.status)}>{reward.status}</Badge></td>
                                        <td class="{tdClass} text-slate-500 dark:text-slate-400">{rewardUsage(reward)}</td>
                                        <td class="{tdClass} text-right">
                                            {#if reward.nota_id}
                                                <Link
                                                    href={`/nota/${reward.nota_id}`}
                                                    class="text-xs font-semibold text-orange-700 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300"
                                                >
                                                    Lihat nota
                                                </Link>
                                            {:else}
                                                <span class="text-xs text-slate-400 dark:text-slate-500">-</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                {/if}
            </section>
        </div>

        <aside class="space-y-4">
            <section class="{cardClass} p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Profil Pelanggan</h2>
                    <Badge tone={isMember ? 'amber' : 'slate'}>{pelanggan.kategori_pelanggan ?? 'Reguler'}</Badge>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Jenis kelamin</dt>
                        <dd class="font-medium">{pelanggan.jenis_kelamin ?? '-'}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Alamat</dt>
                        <dd class="text-right font-medium">{pelanggan.alamat || '-'}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">Terdaftar</dt>
                        <dd class="font-medium">{dateOnly(pelanggan.created_at)}</dd>
                    </div>
                </dl>
            </section>

            {#if loyalty}
                <section class="{cardClass} p-5">
                    <div class="flex items-center gap-2">
                        <span class="rounded-xl bg-orange-50 p-2 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">
                            <Icon name="gift" class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-50">{loyalty.program.nama}</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Hadiah {loyalty.program.nilai_label}</p>
                        </div>
                    </div>

                    {#if !isMember}
                        <p class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400">
                            Program loyalitas hanya berlaku untuk pelanggan Member. Ubah kategori pelanggan untuk mengaktifkan.
                        </p>
                    {:else}
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                                <span>Progres transaksi selesai</span>
                                <span>{loyalty.transaksi_selesai} / {loyalty.target}</span>
                            </div>
                            <div
                                class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                role="progressbar"
                                aria-label="Progres loyalitas"
                                aria-valuemin="0"
                                aria-valuemax={loyalty.target}
                                aria-valuenow={loyalty.transaksi_selesai % loyalty.target}
                            >
                                <div class="h-full rounded-full bg-orange-500 transition-all" style={`width: ${progressPercent}%`}></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {loyalty.sisa === 0
                                    ? `Target tercapai. Hadiah siklus ke-${loyalty.siklus} sudah diterbitkan.`
                                    : `Kurang ${loyalty.sisa} transaksi selesai lagi untuk hadiah berikutnya.`}
                            </p>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-center text-xs">
                            <div class="rounded-xl bg-teal-50 px-3 py-2 dark:bg-teal-500/10">
                                <dt class="text-teal-700 dark:text-teal-300">Tersedia</dt>
                                <dd class="mt-1 text-lg font-bold text-teal-800 dark:text-teal-200">{loyalty.hadiah_tersedia}</dd>
                            </div>
                            <div class="rounded-xl bg-indigo-50 px-3 py-2 dark:bg-indigo-500/10">
                                <dt class="text-indigo-700 dark:text-indigo-300">Digunakan</dt>
                                <dd class="mt-1 text-lg font-bold text-indigo-800 dark:text-indigo-200">{loyalty.hadiah_digunakan}</dd>
                            </div>
                        </dl>
                    {/if}
                </section>
            {/if}

            {#if loyalitas.promotions.length > 0}
                <section class="{cardClass} p-5">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Progres Promosi</h2>
                    <ul class="mt-3 space-y-3">
                        {#each loyalitas.promotions as item (item.promotion.id)}
                            <li class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{item.promotion.nama}</p>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {item.promotion.nilai_label} · target {item.target} transaksi
                                        </p>
                                    </div>
                                    <Badge tone={item.aktif && item.berlaku ? 'teal' : 'slate'}>
                                        {item.aktif && item.berlaku ? 'Aktif' : item.aktif ? 'Tidak berlaku' : 'Nonaktif'}
                                    </Badge>
                                </div>
                                <div
                                    class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                    role="progressbar"
                                    aria-label={`Progres promosi ${item.promotion.nama}`}
                                    aria-valuemin="0"
                                    aria-valuemax={item.target}
                                    aria-valuenow={item.transaksi_selesai % item.target}
                                >
                                    <div class="h-full rounded-full bg-orange-500" style={`width: ${promotionPercent(item.transaksi_selesai, item.target)}%`}></div>
                                </div>
                                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                    {item.transaksi_selesai} transaksi selesai · {item.hadiah_tersedia} hadiah tersedia · {item.hadiah_digunakan} digunakan
                                </p>
                            </li>
                        {/each}
                    </ul>
                </section>
            {/if}
        </aside>
    </div>
</AppLayout>
