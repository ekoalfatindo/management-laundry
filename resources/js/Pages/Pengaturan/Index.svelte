<script lang="ts">
    import { onDestroy, untrack } from 'svelte';
    import { useForm } from '@inertiajs/svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import FormField from '../../Components/Ui/FormField.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { cardClass, inputClass } from '../../lib/ui';
    import type { CompanyProfile } from '../../types';

    let { pengaturan }: { pengaturan: CompanyProfile } = $props();

    const initialName = untrack(() => pengaturan.nama_perusahaan);

    const form = useForm({
        nama_perusahaan: initialName,
        logo: null as File | null,
    });

    let previewUrl = $state<string | null>(null);
    let objectUrl: string | null = null;
    let fileName = $state('');

    let displayLogo = $derived(previewUrl ?? pengaturan.logo_url);

    function pickLogo(event: Event): void {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0] ?? null;

        form.logo = file;
        fileName = file?.name ?? '';

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        if (file) {
            objectUrl = URL.createObjectURL(file);
            previewUrl = objectUrl;
        } else {
            previewUrl = null;
        }
    }

    function clearLogo(): void {
        form.logo = null;
        fileName = '';

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        previewUrl = null;
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        form.post('/pengaturan', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => clearLogo(),
        });
    }

    onDestroy(() => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
    });
</script>

<AppLayout title="Setup">
    <PageHeader title="Setup" description="Kelola identitas perusahaan yang dipakai pada invoice dan dokumen transaksi." />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <form class="{cardClass} p-5 sm:p-6" onsubmit={submit} novalidate>
            <div class="border-b border-slate-200 pb-4 dark:border-slate-800">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Informasi Perusahaan</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Nama dan logo disimpan di database serta digunakan pada nota dan header aplikasi.
                </p>
            </div>

            <div class="mt-5 space-y-5">
                <FormField id="nama-perusahaan" label="Nama Perusahaan" error={form.errors.nama_perusahaan}>
                    <input
                        id="nama-perusahaan"
                        type="text"
                        class={inputClass}
                        placeholder="Contoh: Laundry Bersih Sejahtera"
                        bind:value={form.nama_perusahaan}
                        maxlength="120"
                        required
                    />
                </FormField>

                <div>
                    <span class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Logo Perusahaan</span>

                    <div class="flex flex-wrap items-center gap-4">
                        <div
                            class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800/60"
                        >
                            {#if displayLogo}
                                <img src={displayLogo} alt="Pratinjau logo perusahaan" class="size-full object-contain p-1.5" />
                            {:else}
                                <Icon name="shirt" class="size-7" />
                            {/if}
                        </div>

                        <div class="min-w-0 flex-1">
                            <input
                                id="logo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-orange-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-orange-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                                onchange={pickLogo}
                            />
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                PNG, JPG, JPEG, atau WEBP. Maksimal 2 MB. {fileName ? `Dipilih: ${fileName}` : 'Kosongkan jika tidak ingin mengganti logo.'}
                            </p>
                        </div>
                    </div>

                    {#if form.errors.logo}
                        <p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{form.errors.logo}</p>
                    {/if}
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Menyimpan…' : 'Simpan Pengaturan'}
                </Button>
            </div>
        </form>

        <aside class="space-y-4">
            <section class="{cardClass} p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Pratinjau Identitas</h2>
                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950">
                    <div class="flex items-center gap-3">
                        <span class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-700">
                            {#if displayLogo}
                                <img src={displayLogo} alt="Logo perusahaan" class="size-full object-contain p-1" />
                            {:else}
                                <span class="text-slate-300 dark:text-slate-600">
                                    <Icon name="shirt" class="size-6" />
                                </span>
                            {/if}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-50">
                                {form.nama_perusahaan || 'Nama Perusahaan'}
                            </p>
                            <p class="text-[11px] uppercase tracking-widest text-slate-400 dark:text-slate-500">Invoice</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2 border-t border-dashed border-slate-200 pt-3 dark:border-slate-700">
                        <div class="h-2 w-3/4 rounded bg-slate-100 dark:bg-slate-800"></div>
                        <div class="h-2 w-1/2 rounded bg-slate-100 dark:bg-slate-800"></div>
                        <div class="h-2 w-2/3 rounded bg-slate-100 dark:bg-slate-800"></div>
                    </div>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    Jika logo belum diunggah, aplikasi memakai ikon bawaan agar tata letak tetap rapi.
                </p>
            </section>

            <section class="{cardClass} p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">Catatan</h2>
                <ul class="mt-3 space-y-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    <li>Logo disimpan pada penyimpanan publik aplikasi.</li>
                    <li>Logo lama otomatis dihapus saat logo baru diunggah.</li>
                    <li>Perubahan langsung berlaku pada nota yang ditampilkan setelah disimpan.</li>
                </ul>
            </section>
        </aside>
    </div>
</AppLayout>
