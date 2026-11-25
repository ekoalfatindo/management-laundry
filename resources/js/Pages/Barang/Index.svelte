<script lang="ts">
    import { router, useForm, usePage } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import Modal from '../../Components/Ui/Modal.svelte';
    import EmptyState from '../../Components/Ui/EmptyState.svelte';
    import Pagination from '../../Components/Ui/Pagination.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import FormField from '../../Components/Ui/FormField.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { cardClass, inputClass, tdClass, thClass } from '../../lib/ui';
    import { confirmDelete } from '../../lib/notify';
    import { rupiah } from '../../lib/format';
    import type { Paginated, SharedProps } from '../../types';

    interface BarangRow {
        id: number;
        name: string;
        harga: number;
    }

    let {
        barangs,
        filters,
    }: {
        barangs: Paginated<BarangRow>;
        filters: { search: string; per_page: number };
    } = $props();

    const page = usePage<SharedProps>();
    let canManage = $derived(page.props.auth.user?.permissions.includes('katalog.manage') ?? false);

    let search = $state(untrack(() => filters.search));
    let perPage = $state(untrack(() => filters.per_page));

    let modalOpen = $state(false);
    let editing = $state<BarangRow | null>(null);

    const form = useForm({ name: '', harga: 0 });

    function applyFilters(event?: Event): void {
        event?.preventDefault();

        router.get(
            '/barang',
            { search, per_page: perPage, page: 1 },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    }

    function goToPage(pageNumber: number): void {
        router.get('/barang', { search, per_page: perPage, page: pageNumber }, { preserveScroll: true, preserveState: true });
    }

    function changePerPage(event: Event): void {
        perPage = Number((event.target as HTMLSelectElement).value);
        applyFilters();
    }

    function openCreate(): void {
        editing = null;
        form.reset();
        form.clearErrors();
        form.harga = 0;
        modalOpen = true;
    }

    function openEdit(barang: BarangRow): void {
        editing = barang;
        form.clearErrors();
        form.name = barang.name;
        form.harga = barang.harga;
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
            form.put(`/barang/${editing.id}`, options);
        } else {
            form.post('/barang', options);
        }
    }

</script>

<AppLayout title="Harga Per Barang">
    <PageHeader title="Harga Per Barang" description="Daftar harga barang satuan beserta harga per item.">
        {#if canManage}
            <Button onclick={openCreate}>
                <Icon name="plus" class="size-4" />
                Tambah Harga Barang
            </Button>
        {/if}
    </PageHeader>

    <section class="{cardClass} overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <form class="flex flex-1 gap-2" onsubmit={applyFilters}>
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <Icon name="search" class="size-4" />
                    </span>
                    <input
                        type="search"
                        class="{inputClass} pl-9"
                        placeholder="Cari nama barang…"
                        aria-label="Cari barang"
                        bind:value={search}
                    />
                </div>
                <Button type="submit" variant="secondary">Cari</Button>
            </form>

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

        {#if barangs.data.length === 0}
            <EmptyState icon="box" title="Barang tidak ditemukan" description="Ubah kata kunci atau tambah harga barang baru.">
                {#if canManage}
                    <Button onclick={openCreate}>Tambah Harga Barang</Button>
                {/if}
            </EmptyState>
        {:else}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class={thClass}>Nama Barang</th>
                            <th class="{thClass} text-right">Harga</th>
                            {#if canManage}
                                <th class="{thClass} text-right">Aksi</th>
                            {/if}
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {#each barangs.data as barang (barang.id)}
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="{tdClass} font-medium">{barang.name}</td>
                                <td class="{tdClass} text-right font-semibold">{rupiah(barang.harga)}</td>
                                {#if canManage}
                                    <td class={tdClass}>
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-orange-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-orange-300"
                                                aria-label={`Ubah ${barang.name}`}
                                                onclick={() => openEdit(barang)}
                                            >
                                                <Icon name="edit" class="size-4" />
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                aria-label={`Hapus ${barang.name}`}
                                                onclick={() =>
                                                    confirmDelete({
                                                        url: `/barang/${barang.id}`,
                                                        title: 'Hapus Barang',
                                                        text: `Yakin ingin menghapus ${barang.name}? Nota lama tetap menyimpan nama dan harga barang.`,
                                                    })}
                                            >
                                                <Icon name="trash" class="size-4" />
                                            </button>
                                        </div>
                                    </td>
                                {/if}
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800">
                <Pagination meta={barangs.meta} onpage={goToPage} />
            </div>
        {/if}
    </section>

    <Modal open={modalOpen} title={editing ? 'Ubah Harga Barang' : 'Tambah Harga Barang'} onclose={closeModal}>
        <form class="space-y-4" onsubmit={submit} novalidate>
            <FormField id="barang-name" label="Nama Barang" error={form.errors.name}>
                <input id="barang-name" type="text" class={inputClass} bind:value={form.name} />
            </FormField>

            <FormField id="barang-harga" label="Harga Satuan" error={form.errors.harga} hint="Masukkan angka tanpa titik atau koma.">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">Rp</span>
                    <input
                        id="barang-harga"
                        type="number"
                        min="0"
                        step="1"
                        class="{inputClass} pl-10"
                        bind:value={form.harga}
                    />
                </div>
            </FormField>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <Button variant="secondary" onclick={closeModal} disabled={form.processing}>Batal</Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Menyimpan…' : editing ? 'Simpan Perubahan' : 'Tambah Harga Barang'}
                </Button>
            </div>
        </form>
    </Modal>
</AppLayout>
