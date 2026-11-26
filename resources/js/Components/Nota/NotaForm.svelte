<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import Icon from '../Ui/Icon.svelte';
    import Badge from '../Ui/Badge.svelte';
    import Button from '../Ui/Button.svelte';
    import FormField from '../Ui/FormField.svelte';
    import PelangganCombobox from './PelangganCombobox.svelte';
    import { cardClass, inputClass } from '../../lib/ui';
    import { rupiah } from '../../lib/format';
    import { terbilang } from '../../lib/terbilang';
    import type {
        NotaFormOptions,
        NotaKind,
        NotaRecord,
        NotaRewardRecord,
        PelangganOption,
        RewardOption,
        TipeHadiah,
    } from '../../types';

    let {
        mode,
        jenis,
        options,
        defaultWaktu,
        nota = null,
    }: {
        mode: 'create' | 'edit';
        jenis: NotaKind;
        options: NotaFormOptions;
        defaultWaktu: string;
        nota?: NotaRecord | null;
    } = $props();

    let activeKind = $state<NotaKind>(untrack(() => jenis));

    const initial = untrack(() => ({
        nama_pelanggan: nota?.nama_pelanggan ?? '',
        pelanggan_id: nota?.pelanggan_id ?? '',
        kiloan_id: nota?.kiloan?.id ?? '',
        berat: nota?.berat ?? '',
        jenis_layanan_id: nota?.jenis_layanan?.id ?? '',
        waktu: defaultWaktu,
        diskon: nota?.diskon ?? 0,
        customer_reward_id: nota?.customer_reward_id ?? '',
        barangs:
            nota?.barangs && nota.barangs.length > 0
                ? nota.barangs.map((barang) => ({
                      barang_id: barang.barang_id ?? '',
                      jumlah_barang: barang.jumlah_barang,
                  }))
                : [{ barang_id: '', jumlah_barang: 1 }],
    }));

    const kiloanForm = useForm({
        nama_pelanggan: initial.nama_pelanggan,
        pelanggan_id: initial.pelanggan_id,
        kiloan_id: initial.kiloan_id,
        berat: initial.berat,
        waktu: initial.waktu,
        diskon: initial.diskon,
        customer_reward_id: initial.customer_reward_id,
    });

    const satuanForm = useForm({
        nama_pelanggan: initial.nama_pelanggan,
        pelanggan_id: initial.pelanggan_id,
        jenis_layanan_id: initial.jenis_layanan_id,
        waktu: initial.waktu,
        diskon: initial.diskon,
        customer_reward_id: initial.customer_reward_id,
        barangs: initial.barangs,
    });

    let kiloanSelected = $derived(options.kiloans.find((kiloan) => kiloan.id === Number(kiloanForm.kiloan_id)) ?? null);
    let kiloanBerat = $derived(Number(kiloanForm.berat) || 0);
    let kiloanDiskon = $derived(Number(kiloanForm.diskon) || 0);
    let kiloanGross = $derived(Math.round(kiloanBerat * (kiloanSelected?.harga ?? 0)));
    let kiloanPotongan = $derived(Math.round((kiloanGross * kiloanDiskon) / 100));

    let kiloanExistingReward: NotaRewardRecord | null = $derived.by(() => {
        if (mode !== 'edit' || !nota?.reward || nota.pelanggan_id === null) {
            return null;
        }

        return Number(nota.pelanggan_id) === Number(kiloanForm.pelanggan_id) ? nota.reward : null;
    });
    let kiloanRewardChoices = $derived(rewardChoices(kiloanForm.pelanggan_id, kiloanExistingReward));
    let kiloanSelectedReward = $derived(
        kiloanRewardChoices.find((reward) => reward.id === Number(kiloanForm.customer_reward_id)) ?? null,
    );
    let kiloanHadiah: RewardOption | null = $derived(kiloanSelectedReward);
    let kiloanRewardPotongan = $derived(
        kiloanHadiah ? potonganHadiah(kiloanHadiah.tipe_hadiah, kiloanHadiah.nilai_hadiah, kiloanGross) : 0,
    );
    let kiloanNet = $derived(kiloanGross - (kiloanHadiah ? kiloanRewardPotongan : kiloanPotongan));

    let satuanLayanan = $derived(
        options.jenisLayanans.find((layanan) => layanan.id === Number(satuanForm.jenis_layanan_id)) ?? null,
    );
    let satuanDiskon = $derived(Number(satuanForm.diskon) || 0);
    let satuanLines = $derived(
        satuanForm.barangs.map((row) => {
            const barang = options.barangs.find((option) => option.id === Number(row.barang_id)) ?? null;
            const quantity = Number(row.jumlah_barang) || 0;

            return {
                barang,
                quantity,
                total: barang ? Math.round(barang.harga * quantity) : 0,
            };
        }),
    );
    let satuanSubtotal = $derived(satuanLines.reduce((sum, line) => sum + line.total, 0));
    let satuanGross = $derived(satuanSubtotal + (satuanLayanan?.harga ?? 0));
    let satuanPotongan = $derived(Math.round((satuanGross * satuanDiskon) / 100));

    let satuanExistingReward: NotaRewardRecord | null = $derived.by(() => {
        if (mode !== 'edit' || !nota?.reward || nota.pelanggan_id === null) {
            return null;
        }

        return Number(nota.pelanggan_id) === Number(satuanForm.pelanggan_id) ? nota.reward : null;
    });
    let satuanRewardChoices = $derived(rewardChoices(satuanForm.pelanggan_id, satuanExistingReward));
    let satuanSelectedReward = $derived(
        satuanRewardChoices.find((reward) => reward.id === Number(satuanForm.customer_reward_id)) ?? null,
    );
    let satuanHadiah: RewardOption | null = $derived(satuanSelectedReward);
    let satuanRewardPotongan = $derived(
        satuanHadiah ? potonganHadiah(satuanHadiah.tipe_hadiah, satuanHadiah.nilai_hadiah, satuanGross) : 0,
    );
    let satuanNet = $derived(satuanGross - (satuanHadiah ? satuanRewardPotongan : satuanPotongan));

    function potonganHadiah(tipe: TipeHadiah, nilai: number, gross: number): number {
        const value = Math.max(0, nilai);

        if (tipe === 'persen') {
            return Math.round((gross * Math.min(100, value)) / 100);
        }

        const potongan = tipe === 'gratis' ? (value > 0 ? value : gross) : value;

        return Math.max(0, Math.min(gross, potongan));
    }

    function nilaiLabel(tipe: TipeHadiah, nilai: number): string {
        if (tipe === 'persen') {
            return `${nilai}%`;
        }

        if (tipe === 'nominal') {
            return rupiah(nilai);
        }

        return nilai > 0 ? `Gratis s.d. ${rupiah(nilai)}` : 'Gratis penuh';
    }

    function rewardsFor(pelangganId: string | number): RewardOption[] {
        if (!pelangganId) {
            return [];
        }

        return options.hadiahs.filter((reward) => reward.user_id === Number(pelangganId));
    }

    function rewardChoices(pelangganId: string | number, existing: NotaRewardRecord | null): RewardOption[] {
        const available = rewardsFor(pelangganId);

        if (!existing || available.some((reward) => reward.id === existing.id)) {
            return available;
        }

        return [
            {
                id: existing.id,
                user_id: Number(pelangganId),
                user_name: nota?.nama_pelanggan ?? null,
                label: existing.label,
                tipe_hadiah: existing.tipe_hadiah,
                nilai_hadiah: existing.nilai_hadiah,
                nilai_label: nilaiLabel(existing.tipe_hadiah, existing.nilai_hadiah),
                kedaluwarsa_at: null,
            },
            ...available,
        ];
    }

    function applyRewardChoice(
        form: { customer_reward_id: string | number },
        pelangganId: string | number,
        choices: RewardOption[],
    ): void {
        if (!form.customer_reward_id) {
            return;
        }

        const reward = choices.find((option) => option.id === Number(form.customer_reward_id));

        if (!reward || reward.user_id !== Number(pelangganId)) {
            form.customer_reward_id = '';
        }
    }

    function pickPelangganKiloan(pelanggan: PelangganOption | null): void {
        const sebelumnya = kiloanForm.pelanggan_id;
        kiloanForm.pelanggan_id = pelanggan?.id ?? '';

        if (pelanggan) {
            kiloanForm.nama_pelanggan = pelanggan.name;
        } else if (sebelumnya) {
            kiloanForm.nama_pelanggan = '';
        }

        applyRewardChoice(kiloanForm, kiloanForm.pelanggan_id, kiloanRewardChoices);
    }

    function pickPelangganSatuan(pelanggan: PelangganOption | null): void {
        const sebelumnya = satuanForm.pelanggan_id;
        satuanForm.pelanggan_id = pelanggan?.id ?? '';

        if (pelanggan) {
            satuanForm.nama_pelanggan = pelanggan.name;
        } else if (sebelumnya) {
            satuanForm.nama_pelanggan = '';
        }

        applyRewardChoice(satuanForm, satuanForm.pelanggan_id, satuanRewardChoices);
    }

    function chooseRewardKiloan(): void {
        if (kiloanForm.customer_reward_id) {
            kiloanForm.diskon = 0;
        }
    }

    function chooseRewardSatuan(): void {
        if (satuanForm.customer_reward_id) {
            satuanForm.diskon = 0;
        }
    }

    function addRow(): void {
        satuanForm.barangs.push({ barang_id: '', jumlah_barang: 1 });
    }

    function removeRow(index: number): void {
        if (satuanForm.barangs.length <= 1) {
            return;
        }

        satuanForm.barangs.splice(index, 1);
    }

    function rowError(index: number, field: string): string {
        const errors = satuanForm.errors as Record<string, string>;

        return errors[`barangs.${index}.${field}`] ?? '';
    }

    function submitKiloan(event: SubmitEvent): void {
        event.preventDefault();

        if (mode === 'edit' && nota) {
            kiloanForm.put(`/nota/${nota.id}`, { preserveScroll: true });
        } else {
            kiloanForm.post('/nota/kiloan', { preserveScroll: true });
        }
    }

    function submitSatuan(event: SubmitEvent): void {
        event.preventDefault();

        if (mode === 'edit' && nota) {
            satuanForm.put(`/nota/${nota.id}`, { preserveScroll: true });
        } else {
            satuanForm.post('/nota/satuan', { preserveScroll: true });
        }
    }
</script>

{#if mode === 'create'}
    <div class="mb-5 grid gap-3 sm:grid-cols-2">
        <button
            type="button"
            class="flex items-start gap-3 rounded-2xl border p-4 text-left transition {activeKind === 'kiloan'
                ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-500/20 dark:border-orange-400 dark:bg-orange-500/10'
                : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900'}"
            onclick={() => (activeKind = 'kiloan')}
        >
            <span class="rounded-xl bg-orange-600 p-2 text-white dark:bg-orange-500 dark:text-slate-950">
                <Icon name="washer" class="size-5" />
            </span>
            <span>
                <span class="block text-sm font-semibold text-slate-900 dark:text-slate-50">Nota Kiloan</span>
                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Hitung dari berat cucian × harga per kg.</span>
            </span>
        </button>

        <button
            type="button"
            class="flex items-start gap-3 rounded-2xl border p-4 text-left transition {activeKind === 'satuan'
                ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-500/20 dark:border-orange-400 dark:bg-orange-500/10'
                : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900'}"
            onclick={() => (activeKind = 'satuan')}
        >
            <span class="rounded-xl bg-indigo-600 p-2 text-white dark:bg-indigo-500">
                <Icon name="box" class="size-5" />
            </span>
            <span>
                <span class="block text-sm font-semibold text-slate-900 dark:text-slate-50">Nota Barang Satuan</span>
                <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Hitung per item ditambah biaya layanan.</span>
            </span>
        </button>
    </div>
{:else}
    <div class="mb-5 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900">
        <span class="text-slate-500 dark:text-slate-400">Jenis nota:</span>
        <Badge tone={jenis === 'kiloan' ? 'teal' : 'indigo'}>
            {jenis === 'kiloan' ? 'Paket Kiloan' : 'Barang Satuan'}
        </Badge>
        <span class="ml-auto text-xs text-slate-400 dark:text-slate-500">Jenis nota tidak dapat diubah.</span>
    </div>
{/if}

{#if activeKind === 'kiloan'}
    <form class="grid gap-6 xl:grid-cols-3" onsubmit={submitKiloan} novalidate>
        <section class="{cardClass} p-5 xl:col-span-2">
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-orange-700 dark:text-orange-400">Form Invoice</p>
                    <h2 class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-50">Detail Nota Kiloan</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Isi pelanggan, paket, berat cucian, dan diskon.</p>
                </div>
                <span class="rounded-xl bg-orange-50 p-2 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">
                    <Icon name="washer" class="size-5" />
                </span>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <FormField id="kiloan-nama" label="Nama Pelanggan" error={kiloanForm.errors.nama_pelanggan}>
                    <input id="kiloan-nama" type="text" class={inputClass} bind:value={kiloanForm.nama_pelanggan} />
                </FormField>

                <FormField id="kiloan-pelanggan" label="Pelanggan Terdaftar (opsional)" error={kiloanForm.errors.pelanggan_id}>
                    <PelangganCombobox
                        id="kiloan-pelanggan"
                        value={kiloanForm.pelanggan_id}
                        options={options.pelanggans}
                        onselect={pickPelangganKiloan}
                    />
                </FormField>

                <FormField id="kiloan-paket" label="Paket Kiloan" error={kiloanForm.errors.kiloan_id}>
                    <select id="kiloan-paket" class={inputClass} bind:value={kiloanForm.kiloan_id}>
                        <option value="">Pilih paket…</option>
                        {#each options.kiloans as kiloan}
                            <option value={kiloan.id}>{kiloan.paket} — {rupiah(kiloan.harga)}/kg</option>
                        {/each}
                    </select>
                </FormField>

                <FormField id="kiloan-berat" label="Berat Cucian (kg)" error={kiloanForm.errors.berat}>
                    <input
                        id="kiloan-berat"
                        type="number"
                        min="0.1"
                        max="1000"
                        step="0.01"
                        class={inputClass}
                        bind:value={kiloanForm.berat}
                    />
                </FormField>

                <FormField id="kiloan-waktu" label="Waktu Transaksi" error={kiloanForm.errors.waktu}>
                    <input id="kiloan-waktu" type="datetime-local" class={inputClass} bind:value={kiloanForm.waktu} />
                </FormField>

                <FormField
                    id="kiloan-diskon"
                    label="Diskon (%)"
                    error={kiloanForm.errors.diskon}
                    hint={kiloanHadiah ? 'Dinonaktifkan karena menggunakan hadiah.' : ''}
                >
                    <input
                        id="kiloan-diskon"
                        type="number"
                        min="0"
                        max="100"
                        step="1"
                        class="{inputClass} disabled:cursor-not-allowed disabled:opacity-60"
                        bind:value={kiloanForm.diskon}
                        disabled={kiloanHadiah !== null}
                    />
                </FormField>

                <div class="sm:col-span-2">
                    <FormField
                        id="kiloan-hadiah"
                        label="Gunakan Hadiah (opsional)"
                        error={kiloanForm.errors.customer_reward_id}
                        hint={!kiloanForm.pelanggan_id
                            ? 'Pilih pelanggan terdaftar untuk melihat hadiah yang tersedia.'
                            : kiloanRewardChoices.length === 0
                              ? 'Tidak ada hadiah tersedia untuk pelanggan ini.'
                              : kiloanExistingReward && Number(kiloanForm.customer_reward_id) === kiloanExistingReward.id
                                ? 'Hadiah ini terpasang pada nota. Pilih "Tanpa hadiah" untuk melepasnya.'
                                : 'Hadiah tidak dapat digabung dengan diskon persen manual.'}
                    >
                        <select
                            id="kiloan-hadiah"
                            class={inputClass}
                            bind:value={kiloanForm.customer_reward_id}
                            onchange={chooseRewardKiloan}
                            disabled={!kiloanForm.pelanggan_id || kiloanRewardChoices.length === 0}
                        >
                            <option value="">Tanpa hadiah</option>
                            {#each kiloanRewardChoices as reward}
                                <option value={reward.id}>{reward.label} — {reward.nilai_label}</option>
                            {/each}
                        </select>
                    </FormField>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <Link href={mode === 'edit' && nota ? `/nota/${nota.id}` : '/nota'} class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    Batal
                </Link>
                <Button type="submit" disabled={kiloanForm.processing}>
                    {kiloanForm.processing ? 'Menyimpan…' : mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Nota Kiloan'}
                </Button>
            </div>
        </section>

        <aside class="{cardClass} h-fit overflow-hidden xl:sticky xl:top-6">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/60">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Ringkasan Pembayaran</h2>
            </div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Harga per kg</dt>
                        <dd class="font-medium">{rupiah(kiloanSelected?.harga ?? 0)}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Berat</dt>
                        <dd class="font-medium">{kiloanBerat.toLocaleString('id-ID', { maximumFractionDigits: 2 })} kg</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Subtotal</dt>
                        <dd class="font-medium">{rupiah(kiloanGross)}</dd>
                    </div>
                    {#if kiloanHadiah}
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-slate-400">Hadiah: {kiloanHadiah.label}</dt>
                            <dd class="font-medium text-rose-600 dark:text-rose-400">- {rupiah(kiloanRewardPotongan)}</dd>
                        </div>
                    {:else}
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-slate-400">Diskon ({kiloanDiskon}%)</dt>
                            <dd class="font-medium text-rose-600 dark:text-rose-400">- {rupiah(kiloanPotongan)}</dd>
                        </div>
                    {/if}
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 dark:border-slate-800">
                        <dt class="font-semibold">Total bayar</dt>
                        <dd class="text-lg font-bold text-orange-700 dark:text-orange-400">{rupiah(kiloanNet)}</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-3 dark:border-orange-500/30 dark:bg-orange-500/10">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Terbilang</p>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-orange-900 dark:text-orange-100">{terbilang(kiloanNet)}</p>
                </div>

                <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">Perhitungan akhir diproses ulang oleh server saat disimpan.</p>
            </div>
        </aside>
    </form>
{:else}
    <form class="grid gap-6 xl:grid-cols-3" onsubmit={submitSatuan} novalidate>
        <section class="{cardClass} p-5 xl:col-span-2">
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-orange-700 dark:text-orange-400">Form Invoice</p>
                    <h2 class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-50">Detail Nota Barang Satuan</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Isi pelanggan, layanan, barang, jumlah, dan diskon.</p>
                </div>
                <span class="rounded-xl bg-orange-50 p-2 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">
                    <Icon name="box" class="size-5" />
                </span>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <FormField id="satuan-nama" label="Nama Pelanggan" error={satuanForm.errors.nama_pelanggan}>
                    <input id="satuan-nama" type="text" class={inputClass} bind:value={satuanForm.nama_pelanggan} />
                </FormField>

                <FormField id="satuan-pelanggan" label="Pelanggan Terdaftar (opsional)" error={satuanForm.errors.pelanggan_id}>
                    <PelangganCombobox
                        id="satuan-pelanggan"
                        value={satuanForm.pelanggan_id}
                        options={options.pelanggans}
                        onselect={pickPelangganSatuan}
                    />
                </FormField>

                <FormField id="satuan-layanan" label="Jenis Layanan" error={satuanForm.errors.jenis_layanan_id}>
                    <select id="satuan-layanan" class={inputClass} bind:value={satuanForm.jenis_layanan_id}>
                        <option value="">Pilih layanan…</option>
                        {#each options.jenisLayanans as layanan}
                            <option value={layanan.id}>{layanan.jenis_cuci} — {rupiah(layanan.harga)}</option>
                        {/each}
                    </select>
                </FormField>

                <FormField id="satuan-waktu" label="Waktu Transaksi" error={satuanForm.errors.waktu}>
                    <input id="satuan-waktu" type="datetime-local" class={inputClass} bind:value={satuanForm.waktu} />
                </FormField>

                <FormField
                    id="satuan-diskon"
                    label="Diskon (%)"
                    error={satuanForm.errors.diskon}
                    hint={satuanHadiah ? 'Dinonaktifkan karena menggunakan hadiah.' : ''}
                >
                    <input
                        id="satuan-diskon"
                        type="number"
                        min="0"
                        max="100"
                        step="1"
                        class="{inputClass} disabled:cursor-not-allowed disabled:opacity-60"
                        bind:value={satuanForm.diskon}
                        disabled={satuanHadiah !== null}
                    />
                </FormField>

                <div class="sm:col-span-2">
                    <FormField
                        id="satuan-hadiah"
                        label="Gunakan Hadiah (opsional)"
                        error={satuanForm.errors.customer_reward_id}
                        hint={!satuanForm.pelanggan_id
                            ? 'Pilih pelanggan terdaftar untuk melihat hadiah yang tersedia.'
                            : satuanRewardChoices.length === 0
                              ? 'Tidak ada hadiah tersedia untuk pelanggan ini.'
                              : satuanExistingReward && Number(satuanForm.customer_reward_id) === satuanExistingReward.id
                                ? 'Hadiah ini terpasang pada nota. Pilih "Tanpa hadiah" untuk melepasnya.'
                                : 'Hadiah tidak dapat digabung dengan diskon persen manual.'}
                    >
                        <select
                            id="satuan-hadiah"
                            class={inputClass}
                            bind:value={satuanForm.customer_reward_id}
                            onchange={chooseRewardSatuan}
                            disabled={!satuanForm.pelanggan_id || satuanRewardChoices.length === 0}
                        >
                            <option value="">Tanpa hadiah</option>
                            {#each satuanRewardChoices as reward}
                                <option value={reward.id}>{reward.label} — {reward.nilai_label}</option>
                            {/each}
                        </select>
                    </FormField>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Daftar Barang</h3>
                    <Button variant="secondary" onclick={addRow}>
                        <Icon name="plus" class="size-4" />
                        Tambah Baris
                    </Button>
                </div>

                {#if satuanForm.errors.barangs}
                    <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">
                        {satuanForm.errors.barangs as string}
                    </p>
                {/if}

                <div class="mt-3 space-y-3">
                    {#each satuanForm.barangs as row, index (index)}
                        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                            <div class="grid gap-3 sm:grid-cols-[1fr_120px_140px_auto] sm:items-end">
                                <FormField id={`satuan-barang-${index}`} label="Barang" error={rowError(index, 'barang_id')}>
                                    <select id={`satuan-barang-${index}`} class={inputClass} bind:value={row.barang_id}>
                                        <option value="">Pilih barang…</option>
                                        {#each options.barangs as barang}
                                            <option value={barang.id}>{barang.name} — {rupiah(barang.harga)}</option>
                                        {/each}
                                    </select>
                                </FormField>

                                <FormField id={`satuan-qty-${index}`} label="Jumlah" error={rowError(index, 'jumlah_barang')}>
                                    <input
                                        id={`satuan-qty-${index}`}
                                        type="number"
                                        min="1"
                                        max="100000"
                                        step="1"
                                        class={inputClass}
                                        bind:value={row.jumlah_barang}
                                    />
                                </FormField>

                                <div>
                                    <span class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Subtotal</span>
                                    <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-100">
                                        {rupiah(satuanLines[index]?.total ?? 0)}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="mb-0.5 rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 disabled:cursor-not-allowed disabled:opacity-40 dark:text-slate-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                    aria-label={`Hapus baris ${index + 1}`}
                                    disabled={satuanForm.barangs.length <= 1}
                                    onclick={() => removeRow(index)}
                                >
                                    <Icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <Link href={mode === 'edit' && nota ? `/nota/${nota.id}` : '/nota'} class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    Batal
                </Link>
                <Button type="submit" disabled={satuanForm.processing}>
                    {satuanForm.processing ? 'Menyimpan…' : mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Nota Satuan'}
                </Button>
            </div>
        </section>

        <aside class="{cardClass} h-fit overflow-hidden xl:sticky xl:top-6">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/60">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Ringkasan Pembayaran</h2>
            </div>
            <div class="p-5">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Subtotal barang</dt>
                        <dd class="font-medium">{rupiah(satuanSubtotal)}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Biaya layanan</dt>
                        <dd class="font-medium">{rupiah(satuanLayanan?.harga ?? 0)}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500 dark:text-slate-400">Subtotal</dt>
                        <dd class="font-medium">{rupiah(satuanGross)}</dd>
                    </div>
                    {#if satuanHadiah}
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-slate-400">Hadiah: {satuanHadiah.label}</dt>
                            <dd class="font-medium text-rose-600 dark:text-rose-400">- {rupiah(satuanRewardPotongan)}</dd>
                        </div>
                    {:else}
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500 dark:text-slate-400">Diskon ({satuanDiskon}%)</dt>
                            <dd class="font-medium text-rose-600 dark:text-rose-400">- {rupiah(satuanPotongan)}</dd>
                        </div>
                    {/if}
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-3 dark:border-slate-800">
                        <dt class="font-semibold">Total bayar</dt>
                        <dd class="text-lg font-bold text-orange-700 dark:text-orange-400">{rupiah(satuanNet)}</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 p-3 dark:border-orange-500/30 dark:bg-orange-500/10">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Terbilang</p>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-orange-900 dark:text-orange-100">{terbilang(satuanNet)}</p>
                </div>

                <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">Perhitungan akhir diproses ulang oleh server saat disimpan.</p>
            </div>
        </aside>
    </form>
{/if}
