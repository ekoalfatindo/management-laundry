<script lang="ts">
    import { useForm } from "@inertiajs/svelte";
    import { untrack } from "svelte";
    import AppLayout from "../../Components/AppLayout.svelte";
    import PageHeader from "../../Components/Ui/PageHeader.svelte";
    import StatCard from "../../Components/Ui/StatCard.svelte";
    import Button from "../../Components/Ui/Button.svelte";
    import FormField from "../../Components/Ui/FormField.svelte";
    import Icon from "../../Components/Ui/Icon.svelte";
    import { cardClass, inputClass } from "../../lib/ui";
    import type { LoyaltyProgramRecord, TipeHadiah } from "../../types";

    let {
        program,
        stats,
    }: {
        program: LoyaltyProgramRecord | null;
        stats: {
            members: number;
            hadiah_tersedia: number;
            hadiah_digunakan: number;
        };
    } = $props();

    const form = useForm({
        nama: untrack(() => program?.nama ?? "Loyalty Member"),
        deskripsi: untrack(
            () =>
                program?.deskripsi ??
                "Reward otomatis untuk pelanggan Member yang mencapai target transaksi selesai.",
        ),
        target_transaksi: untrack(() => program?.target_transaksi ?? 10),
        tipe_hadiah: untrack<TipeHadiah>(
            () => program?.tipe_hadiah ?? "persen",
        ),
        nilai_hadiah: untrack(() => program?.nilai_hadiah ?? 10),
        masa_berlaku_hari: untrack(() => program?.masa_berlaku_hari ?? 30),
        is_active: untrack(() => program?.is_active ?? true),
    });

    const tipeOptions: { value: TipeHadiah; label: string; hint: string }[] = [
        {
            value: "persen",
            label: "Diskon Persen",
            hint: "Contoh: 10 berarti potongan 10% dari total transaksi.",
        },
        {
            value: "nominal",
            label: "Potongan Nominal",
            hint: "Contoh: 5000 berarti potongan Rp 5.000.",
        },
        {
            value: "gratis",
            label: "Gratis Cuci",
            hint: "Nilai menjadi batas maksimal gratis. Isi 0 untuk gratis penuh.",
        },
    ];

    let tipeHint = $derived(
        tipeOptions.find((option) => option.value === form.tipe_hadiah)?.hint ??
            "",
    );

    let nilaiMin = $derived(form.tipe_hadiah === "gratis" ? 0 : 1);
    let nilaiMax = $derived(form.tipe_hadiah === "persen" ? 100 : 100000000);

    function changeTipeHadiah(event: Event): void {
        const tipe = (event.currentTarget as HTMLSelectElement)
            .value as TipeHadiah;

        form.tipe_hadiah = tipe;

        if (tipe === "persen" && (form.nilai_hadiah < 1 || form.nilai_hadiah > 100)) {
            form.nilai_hadiah = 10;
        } else if (tipe === "nominal" && form.nilai_hadiah < 1) {
            form.nilai_hadiah = 5000;
        }
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        form.put("/loyalitas", { preserveScroll: true });
    }
</script>

<AppLayout title="Program Loyalitas">
    <PageHeader
        title="Program Loyalitas"
        description="Pengaturan reward bagi pelanggan Member."
    />

    <div class="mb-5 grid gap-4 sm:grid-cols-3">
        <StatCard
            label="Pelanggan Member"
            value={String(stats.members)}
            icon="customer"
            tone="amber"
        />
        <StatCard
            label="Reward Tersedia"
            value={String(stats.hadiah_tersedia)}
            icon="gift"
            tone="teal"
        />
        <StatCard
            label="Reward Digunakan"
            value={String(stats.hadiah_digunakan)}
            icon="check"
            tone="indigo"
        />
    </div>

    <form class="grid gap-6 xl:grid-cols-3" onsubmit={submit} novalidate>
        <section class="{cardClass} p-5 xl:col-span-2">
            <div
                class="flex items-start justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800"
            >
                <div>
                    <p
                        class="text-[11px] font-semibold uppercase tracking-wider text-orange-700 dark:text-orange-400"
                    >
                        Pengaturan Reward
                    </p>
                    <h2
                        class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-50"
                    >
                        Detail Program Loyalitas
                    </h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Hanya satu program aktif yang dipakai pada satu waktu.
                    </p>
                </div>
                <span
                    class="rounded-xl bg-orange-50 p-2 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400"
                >
                    <Icon name="gift" class="size-5" />
                </span>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <FormField
                    id="loyalty-nama"
                    label="Nama Program"
                    error={form.errors.nama}
                >
                    <input
                        id="loyalty-nama"
                        type="text"
                        class={inputClass}
                        bind:value={form.nama}
                    />
                </FormField>

                <FormField
                    id="loyalty-target"
                    label="Target Transaksi"
                    error={form.errors.target_transaksi}
                    hint="Dihitung dari nota berstatus Selesai."
                >
                    <input
                        id="loyalty-target"
                        type="number"
                        min="1"
                        max="1000"
                        step="1"
                        class={inputClass}
                        bind:value={form.target_transaksi}
                    />
                </FormField>

                <FormField
                    id="loyalty-tipe"
                    label="Jenis Reward"
                    error={form.errors.tipe_hadiah}
                >
                    <select
                        id="loyalty-tipe"
                        class={inputClass}
                        value={form.tipe_hadiah}
                        onchange={changeTipeHadiah}
                    >
                        {#each tipeOptions as option}
                            <option value={option.value}>{option.label}</option>
                        {/each}
                    </select>
                </FormField>

                <FormField
                    id="loyalty-nilai"
                    label="Nilai Reward"
                    error={form.errors.nilai_hadiah}
                    hint={tipeHint}
                >
                    <input
                        id="loyalty-nilai"
                        type="number"
                        min={nilaiMin}
                        max={nilaiMax}
                        step="1"
                        class={inputClass}
                        bind:value={form.nilai_hadiah}
                    />
                </FormField>

                <FormField
                    id="loyalty-masa"
                    label="Masa Berlaku Reward (hari)"
                    error={form.errors.masa_berlaku_hari}
                    hint="Kosongkan agar reward tidak kedaluwarsa."
                >
                    <input
                        id="loyalty-masa"
                        type="number"
                        min="1"
                        max="3650"
                        step="1"
                        class={inputClass}
                        bind:value={form.masa_berlaku_hari}
                    />
                </FormField>

                <FormField
                    id="loyalty-status"
                    label="Status Program"
                    error={form.errors.is_active}
                >
                    <label
                        class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"
                    >
                        <input
                            type="checkbox"
                            class="size-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                            bind:checked={form.is_active}
                        />
                        Program aktif
                    </label>
                </FormField>

                <div class="sm:col-span-2">
                    <FormField
                        id="loyalty-deskripsi"
                        label="Deskripsi"
                        error={form.errors.deskripsi}
                    >
                        <textarea
                            id="loyalty-deskripsi"
                            rows="3"
                            class={inputClass}
                            bind:value={form.deskripsi}></textarea>
                    </FormField>
                </div>
            </div>

            <div
                class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800"
            >
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? "Menyimpan…" : "Simpan Program"}
                </Button>
            </div>
        </section>

        <aside class="{cardClass} h-fit overflow-hidden xl:sticky xl:top-6">
            <div
                class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/60"
            >
                <h2
                    class="text-sm font-semibold text-slate-900 dark:text-slate-50"
                >
                    Cara Kerja
                </h2>
            </div>
            <div
                class="space-y-3 p-5 text-sm text-slate-600 dark:text-slate-300"
            >
                <p>
                    Setiap transaksi pelanggan berstatus <span
                        class="font-semibold">Selesai</span
                    > dihitung sebagai satu progres.
                </p>
                <p>
                    Setiap kali jumlah transaksi mencapai target, sistem
                    otomatis menerbitkan satu reward untuk pelanggan Member.
                </p>
                <p>
                    Reward hanya dapat dipakai satu kali pada transaksi
                    berikutnya dan tidak dapat digabung dengan diskon persen
                    manual.
                </p>
                <p>
                    Jika transaksi dibatalkan dan menyebabkan target tidak lagi
                    terpenuhi, reward yang belum dipakai akan dibatalkan
                    otomatis.
                </p>
                <p
                    class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                >
                    Menyimpan program akan menghitung ulang reward untuk seluruh
                    pelanggan.
                </p>
            </div>
        </aside>
    </form>
</AppLayout>
