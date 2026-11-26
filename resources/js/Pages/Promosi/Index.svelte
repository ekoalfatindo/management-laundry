<script lang="ts">
    import { router, useForm } from "@inertiajs/svelte";
    import { untrack } from "svelte";
    import AppLayout from "../../Components/AppLayout.svelte";
    import PageHeader from "../../Components/Ui/PageHeader.svelte";
    import Modal from "../../Components/Ui/Modal.svelte";
    import EmptyState from "../../Components/Ui/EmptyState.svelte";
    import Pagination from "../../Components/Ui/Pagination.svelte";
    import Badge from "../../Components/Ui/Badge.svelte";
    import Button from "../../Components/Ui/Button.svelte";
    import FormField from "../../Components/Ui/FormField.svelte";
    import Icon from "../../Components/Ui/Icon.svelte";
    import { cardClass, inputClass, tdClass, thClass } from "../../lib/ui";
    import { confirmDelete } from "../../lib/notify";
    import { dateOnly } from "../../lib/format";
    import type { Paginated, PromotionRecord, TipeHadiah } from "../../types";

    let {
        promotions,
        filters,
        kategoriOptions,
        tipeOptions,
    }: {
        promotions: Paginated<PromotionRecord>;
        filters: { search: string; status: string; per_page: number };
        kategoriOptions: { value: string; label: string }[];
        tipeOptions: { value: string; label: string }[];
    } = $props();

    let search = $state(untrack(() => filters.search));
    let status = $state(untrack(() => filters.status));
    let perPage = $state(untrack(() => filters.per_page));

    $effect(() => {
        search = filters.search;
        status = filters.status;
        perPage = filters.per_page;
    });

    let modalOpen = $state(false);
    let editing = $state<PromotionRecord | null>(null);

    const form = useForm({
        nama: "",
        deskripsi: "",
        syarat: "",
        target_kategori: "",
        target_transaksi: 10,
        tipe_hadiah: "nominal" as TipeHadiah,
        nilai_hadiah: 5000,
        mulai: "",
        selesai: "",
        is_active: true,
    });

    let nilaiHint = $derived(
        form.tipe_hadiah === "gratis"
            ? "Isi 0 untuk gratis penuh, atau nominal batas maksimal gratis."
            : form.tipe_hadiah === "persen"
              ? "Persen dari total nota (1–100)."
              : "Nominal rupiah, contoh 5000 berarti Rp 5.000.",
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

    function queryParameters(
        extra: Record<string, string | number> = {},
    ): Record<string, string | number> {
        const params: Record<string, string | number> = {
            per_page: perPage,
            ...extra,
        };

        if (search) params.search = search;
        if (status) params.status = status;

        return params;
    }

    function applyFilters(event?: Event): void {
        event?.preventDefault();

        router.get("/promosi", queryParameters({ page: 1 }), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function resetFilters(): void {
        search = "";
        status = "";
        perPage = 10;

        router.get(
            "/promosi",
            { per_page: 10 },
            { preserveScroll: true, preserveState: true },
        );
    }

    function goToPage(page: number): void {
        router.get("/promosi", queryParameters({ page }), {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function changePerPage(event: Event): void {
        perPage = Number((event.target as HTMLSelectElement).value);
        applyFilters();
    }

    function openCreate(): void {
        editing = null;
        form.reset();
        form.clearErrors();
        form.target_kategori = "";
        form.tipe_hadiah = "nominal";
        form.target_transaksi = 10;
        form.nilai_hadiah = 5000;
        form.is_active = true;
        modalOpen = true;
    }

    function openEdit(promotion: PromotionRecord): void {
        editing = promotion;
        form.clearErrors();
        form.nama = promotion.nama;
        form.deskripsi = promotion.deskripsi ?? "";
        form.syarat = promotion.syarat ?? "";
        form.target_kategori = promotion.target_kategori ?? "";
        form.target_transaksi = promotion.target_transaksi;
        form.tipe_hadiah = promotion.tipe_hadiah;
        form.nilai_hadiah = promotion.nilai_hadiah;
        form.mulai = promotion.mulai ?? "";
        form.selesai = promotion.selesai ?? "";
        form.is_active = promotion.is_active;
        modalOpen = true;
    }

    function closeModal(): void {
        if (form.processing) {
            return;
        }

        modalOpen = false;
        editing = null;
        form.clearErrors();
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                modalOpen = false;
                editing = null;
                form.reset();
            },
        };

        if (editing) {
            form.put(`/promosi/${editing.id}`, options);
        } else {
            form.post("/promosi", options);
        }
    }

    function statusLabel(promotion: PromotionRecord): string {
        if (!promotion.is_active) {
            return "Nonaktif";
        }

        return promotion.aktif_sekarang ? "Aktif" : "Di luar periode";
    }

    function statusTone(
        promotion: PromotionRecord,
    ): "teal" | "slate" | "amber" {
        if (!promotion.is_active) {
            return "slate";
        }

        return promotion.aktif_sekarang ? "teal" : "amber";
    }

    function kategoriLabel(promotion: PromotionRecord): string {
        return promotion.target_kategori ?? "Semua pelanggan";
    }
</script>

<AppLayout title="Promosi">
    <PageHeader
        title="Promosi"
        description="Pengaturan promosi untuk semua pelanggan."
    >
        <Button onclick={openCreate}>
            <Icon name="plus" class="size-4" />
            Tambah Promosi
        </Button>
    </PageHeader>

    <section class="{cardClass} overflow-hidden">
        <form
            class="border-b border-slate-200 p-4 dark:border-slate-800"
            onsubmit={applyFilters}
        >
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div class="relative flex-1">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"
                    >
                        <Icon name="search" class="size-4" />
                    </span>
                    <input
                        type="search"
                        class="{inputClass} pl-9"
                        placeholder="Cari nama atau deskripsi promosi…"
                        aria-label="Cari promosi"
                        bind:value={search}
                    />
                </div>

                <select
                    class="{inputClass} md:w-44"
                    aria-label="Filter status promosi"
                    bind:value={status}
                >
                    <option value="">Semua status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>

                <div class="flex gap-2">
                    <Button type="submit">Terapkan</Button>
                    <Button variant="ghost" onclick={resetFilters}>Reset</Button
                    >
                </div>

                <label
                    class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400"
                >
                    Baris
                    <select
                        class="{inputClass} w-20"
                        aria-label="Jumlah baris per halaman"
                        value={perPage}
                        onchange={changePerPage}
                    >
                        <option value={10}>10</option>
                        <option value={25}>25</option>
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                    </select>
                </label>
            </div>
        </form>

        {#if promotions.data.length === 0}
            <EmptyState
                icon="tag"
                title="Promosi tidak ditemukan"
                description="Ubah filter atau tambah promosi baru."
            >
                <Button onclick={openCreate}>Tambah Promosi</Button>
            </EmptyState>
        {:else}
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-slate-200 dark:divide-slate-800"
                >
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class={thClass}>Promosi</th>
                            <th class={thClass}>Target</th>
                            <th class={thClass}>Hadiah</th>
                            <th class="{thClass} hidden lg:table-cell"
                                >Periode</th
                            >
                            <th class={thClass}>Status</th>
                            <th
                                class="{thClass} hidden text-center xl:table-cell"
                                >Riwayat Hadiah</th
                            >
                            <th class="{thClass} text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 dark:divide-slate-800"
                    >
                        {#each promotions.data as promotion (promotion.id)}
                            <tr
                                class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50"
                            >
                                <td class={tdClass}>
                                    <p
                                        class="font-semibold text-slate-800 dark:text-slate-100"
                                    >
                                        {promotion.nama}
                                    </p>
                                    <p
                                        class="mt-0.5 max-w-xs truncate text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {promotion.deskripsi ||
                                            promotion.syarat ||
                                            "-"}
                                    </p>
                                </td>
                                <td class={tdClass}>
                                    <p class="font-medium">
                                        {promotion.target_transaksi} transaksi
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {kategoriLabel(promotion)}
                                    </p>
                                </td>
                                <td class={tdClass}>
                                    <p class="font-medium">
                                        {promotion.nilai_label}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {promotion.tipe_hadiah_label}
                                    </p>
                                </td>
                                <td
                                    class="{tdClass} hidden whitespace-nowrap text-slate-500 lg:table-cell dark:text-slate-400"
                                >
                                    {promotion.mulai
                                        ? dateOnly(promotion.mulai)
                                        : "Tanpa batas"}
                                    {#if promotion.selesai}
                                        <span class="block text-xs"
                                            >s.d. {dateOnly(
                                                promotion.selesai,
                                            )}</span
                                        >
                                    {/if}
                                </td>
                                <td class={tdClass}>
                                    <Badge tone={statusTone(promotion)}
                                        >{statusLabel(promotion)}</Badge
                                    >
                                </td>
                                <td
                                    class="{tdClass} hidden text-center xl:table-cell"
                                >
                                    <span
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {promotion.hadiah_tersedia ?? 0} tersedia
                                        · {promotion.hadiah_digunakan ?? 0} dipakai
                                    </span>
                                </td>
                                <td class={tdClass}>
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-orange-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-orange-300"
                                            aria-label={`Ubah promosi ${promotion.nama}`}
                                            onclick={() => openEdit(promotion)}
                                        >
                                            <Icon name="edit" class="size-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                            aria-label={`Hapus promosi ${promotion.nama}`}
                                            onclick={() =>
                                                confirmDelete({
                                                    url: `/promosi/${promotion.id}`,
                                                    title: "Hapus Promosi",
                                                    text: `Yakin ingin menghapus promosi ${promotion.nama}? Promosi dengan riwayat hadiah tidak dapat dihapus.`,
                                                })}
                                        >
                                            <Icon name="trash" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800">
                <Pagination meta={promotions.meta} onpage={goToPage} />
            </div>
        {/if}
    </section>

    <Modal
        open={modalOpen}
        title={editing ? "Ubah Promosi" : "Tambah Promosi"}
        onclose={closeModal}
        size="lg"
    >
        <form class="space-y-4" onsubmit={submit} novalidate>
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField
                    id="promo-nama"
                    label="Nama Promosi"
                    error={form.errors.nama}
                >
                    <input
                        id="promo-nama"
                        type="text"
                        class={inputClass}
                        bind:value={form.nama}
                        placeholder="Contoh: Beli 15 Gratis 1"
                    />
                </FormField>

                <FormField
                    id="promo-kategori"
                    label="Target Pelanggan"
                    error={form.errors.target_kategori}
                >
                    <select
                        id="promo-kategori"
                        class={inputClass}
                        bind:value={form.target_kategori}
                    >
                        {#each kategoriOptions as option}
                            <option value={option.value}>{option.label}</option>
                        {/each}
                    </select>
                </FormField>

                <FormField
                    id="promo-target"
                    label="Target Transaksi"
                    error={form.errors.target_transaksi}
                    hint="Jumlah transaksi Selesai untuk memicu hadiah."
                >
                    <input
                        id="promo-target"
                        type="number"
                        min="1"
                        max="1000"
                        step="1"
                        class={inputClass}
                        bind:value={form.target_transaksi}
                    />
                </FormField>

                <FormField
                    id="promo-tipe"
                    label="Jenis Hadiah"
                    error={form.errors.tipe_hadiah}
                >
                    <select
                        id="promo-tipe"
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
                    id="promo-nilai"
                    label="Nilai Hadiah"
                    error={form.errors.nilai_hadiah}
                    hint={nilaiHint}
                >
                    <input
                        id="promo-nilai"
                        type="number"
                        min={nilaiMin}
                        max={nilaiMax}
                        step="1"
                        class={inputClass}
                        bind:value={form.nilai_hadiah}
                    />
                </FormField>

                <FormField
                    id="promo-status"
                    label="Status"
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
                        Promosi aktif
                    </label>
                </FormField>

                <FormField
                    id="promo-mulai"
                    label="Mulai"
                    error={form.errors.mulai}
                    hint="Kosongkan jika tanpa batas awal."
                >
                    <input
                        id="promo-mulai"
                        type="date"
                        class={inputClass}
                        bind:value={form.mulai}
                    />
                </FormField>

                <FormField
                    id="promo-selesai"
                    label="Selesai"
                    error={form.errors.selesai}
                    hint="Kosongkan jika tanpa batas akhir."
                >
                    <input
                        id="promo-selesai"
                        type="date"
                        class={inputClass}
                        bind:value={form.selesai}
                    />
                </FormField>

                <div class="sm:col-span-2">
                    <FormField
                        id="promo-deskripsi"
                        label="Deskripsi"
                        error={form.errors.deskripsi}
                    >
                        <textarea
                            id="promo-deskripsi"
                            rows="2"
                            class={inputClass}
                            bind:value={form.deskripsi}></textarea>
                    </FormField>
                </div>

                <div class="sm:col-span-2">
                    <FormField
                        id="promo-syarat"
                        label="Syarat dan Ketentuan"
                        error={form.errors.syarat}
                    >
                        <textarea
                            id="promo-syarat"
                            rows="2"
                            class={inputClass}
                            bind:value={form.syarat}></textarea>
                    </FormField>
                </div>
            </div>

            <p
                class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
            >
                Promosi nonaktif atau di luar periode tidak menerbitkan hadiah
                baru. Menyimpan promosi akan menghitung ulang hadiah seluruh
                pelanggan.
            </p>

            <div
                class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800"
            >
                <Button
                    variant="secondary"
                    onclick={closeModal}
                    disabled={form.processing}>Batal</Button
                >
                <Button type="submit" disabled={form.processing}>
                    {form.processing
                        ? "Menyimpan…"
                        : editing
                          ? "Simpan Perubahan"
                          : "Tambah Promosi"}
                </Button>
            </div>
        </form>
    </Modal>
</AppLayout>
