<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
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
    import { dateOnly } from '../../lib/format';
    import type { Paginated, UserRecord } from '../../types';

    let {
        pelanggans,
        filters,
    }: {
        pelanggans: Paginated<UserRecord>;
        filters: { search: string; per_page: number };
    } = $props();

    let search = $state(untrack(() => filters.search));
    let perPage = $state(untrack(() => filters.per_page));

    let modalOpen = $state(false);
    let editing = $state<UserRecord | null>(null);

    const form = useForm({
        name: '',
        email: '',
        jenis_kelamin: 'Laki-Laki',
        alamat: '',
        password: '',
        password_confirmation: '',
    });

    function applyFilters(event?: Event): void {
        event?.preventDefault();

        router.get(
            '/pelanggan',
            { search, per_page: perPage, page: 1 },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    }

    function goToPage(page: number): void {
        router.get('/pelanggan', { search, per_page: perPage, page }, { preserveScroll: true, preserveState: true });
    }

    function changePerPage(event: Event): void {
        perPage = Number((event.target as HTMLSelectElement).value);
        applyFilters();
    }

    function openCreate(): void {
        editing = null;
        form.reset();
        form.clearErrors();
        form.jenis_kelamin = 'Laki-Laki';
        modalOpen = true;
    }

    function openEdit(user: UserRecord): void {
        editing = user;
        form.clearErrors();
        form.name = user.name;
        form.email = user.email;
        form.jenis_kelamin = user.jenis_kelamin ?? 'Laki-Laki';
        form.alamat = user.alamat ?? '';
        form.password = '';
        form.password_confirmation = '';
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
            form.put(`/pelanggan/${editing.id}`, options);
        } else {
            form.post('/pelanggan', options);
        }
    }

    function initials(name: string): string {
        return name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0]?.toUpperCase() ?? '')
            .join('');
    }
</script>

<AppLayout title="Pelanggan">
    <PageHeader title="Pelanggan" description="Kelola data pelanggan yang dapat dikaitkan ke nota.">
        <Button onclick={openCreate}>
            <Icon name="plus" class="size-4" />
            Tambah Pelanggan
        </Button>
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
                        placeholder="Cari nama atau email…"
                        aria-label="Cari pelanggan"
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

        {#if pelanggans.data.length === 0}
            <EmptyState icon="customer" title="Pelanggan tidak ditemukan" description="Ubah kata kunci atau tambah pelanggan baru.">
                <Button onclick={openCreate}>Tambah Pelanggan</Button>
            </EmptyState>
        {:else}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class={thClass}>Nama</th>
                            <th class="{thClass} hidden md:table-cell">Jenis Kelamin</th>
                            <th class="{thClass} hidden lg:table-cell">Alamat</th>
                            <th class="{thClass} hidden sm:table-cell">Dibuat</th>
                            <th class="{thClass} text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        {#each pelanggans.data as pelanggan (pelanggan.id)}
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class={tdClass}>
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white dark:bg-indigo-500">
                                            {initials(pelanggan.name)}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-800 dark:text-slate-100">{pelanggan.name}</p>
                                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{pelanggan.email}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="{tdClass} hidden md:table-cell">{pelanggan.jenis_kelamin ?? '-'}</td>
                                <td class="{tdClass} hidden max-w-xs truncate lg:table-cell">{pelanggan.alamat || '-'}</td>
                                <td class="{tdClass} hidden text-slate-500 sm:table-cell dark:text-slate-400">{dateOnly(pelanggan.created_at)}</td>
                                <td class="{tdClass}">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-orange-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-orange-300"
                                            aria-label={`Ubah ${pelanggan.name}`}
                                            onclick={() => openEdit(pelanggan)}
                                        >
                                            <Icon name="edit" class="size-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 dark:text-slate-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                            aria-label={`Hapus ${pelanggan.name}`}
                                            onclick={() =>
                                                confirmDelete({
                                                    url: `/pelanggan/${pelanggan.id}`,
                                                    title: 'Hapus Pelanggan',
                                                    text: `Yakin ingin menghapus ${pelanggan.name}? Nota yang pernah dibuat tetap tersimpan.`,
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
                <Pagination meta={pelanggans.meta} onpage={goToPage} />
            </div>
        {/if}
    </section>

    <Modal open={modalOpen} title={editing ? 'Ubah Pelanggan' : 'Tambah Pelanggan'} onclose={closeModal}>
        <form class="space-y-4" onsubmit={submit} novalidate>
            <div class="grid gap-4 sm:grid-cols-2">
                <FormField id="pelanggan-name" label="Nama" error={form.errors.name}>
                    <input id="pelanggan-name" type="text" class={inputClass} bind:value={form.name} />
                </FormField>

                <FormField id="pelanggan-email" label="Email" error={form.errors.email}>
                    <input id="pelanggan-email" type="email" class={inputClass} bind:value={form.email} />
                </FormField>

                <FormField id="pelanggan-gender" label="Jenis Kelamin" error={form.errors.jenis_kelamin}>
                    <select id="pelanggan-gender" class={inputClass} bind:value={form.jenis_kelamin}>
                        <option value="Laki-Laki">Laki-Laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </FormField>

                <FormField id="pelanggan-alamat" label="Alamat" error={form.errors.alamat}>
                    <input id="pelanggan-alamat" type="text" class={inputClass} bind:value={form.alamat} />
                </FormField>

                <FormField
                    id="pelanggan-password"
                    label={editing ? 'Kata Sandi (opsional)' : 'Kata Sandi'}
                    error={form.errors.password}
                    hint={editing ? 'Biarkan kosong jika tidak ingin mengubah.' : 'Minimal 8 karakter.'}
                >
                    <input id="pelanggan-password" type="password" class={inputClass} bind:value={form.password} autocomplete="new-password" />
                </FormField>

                <FormField
                    id="pelanggan-password-confirmation"
                    label="Konfirmasi Kata Sandi"
                    error={form.errors.password_confirmation}
                >
                    <input
                        id="pelanggan-password-confirmation"
                        type="password"
                        class={inputClass}
                        bind:value={form.password_confirmation}
                        autocomplete="new-password"
                    />
                </FormField>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <Button variant="secondary" onclick={closeModal} disabled={form.processing}>Batal</Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Menyimpan…' : editing ? 'Simpan Perubahan' : 'Tambah Pelanggan'}
                </Button>
            </div>
        </form>
    </Modal>
</AppLayout>
