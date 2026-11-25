<script lang="ts">
    import { Link, useForm, usePage } from '@inertiajs/svelte';
    import AuthShell from '../../Components/Auth/AuthShell.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import { authInputClass, errorClass, labelClass } from '../../lib/ui';
    import type { SharedProps } from '../../types';

    const page = usePage<SharedProps>();
    let status = $derived(page.props.flash?.status ?? '');

    const form = useForm({ email: '' });

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        form.post('/forgot-password');
    }
</script>

<AuthShell
    title="Lupa kata sandi?"
    subtitle="Masukkan email terdaftar. Kami akan mengirim tautan untuk mengatur ulang kata sandi."
    headline="Kata sandi aman, operasional lancar."
    description="Minta tautan pengaturan ulang, buat kata sandi baru, lalu kembali masuk ke aplikasi."
>
    {#if status}
        <div
            class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
            role="status"
        >
            <Icon name="check" class="mt-0.5 size-4 shrink-0" />
            <p>{status}</p>
        </div>
    {/if}

    <form class="space-y-5" onsubmit={submit} novalidate>
        <div>
            <label class={labelClass} for="email">Email terdaftar</label>
            <input
                id="email"
                type="email"
                class={authInputClass}
                placeholder="nama@laundry.test"
                autocomplete="username"
                bind:value={form.email}
                required
            />
            {#if form.errors.email}
                <p class={errorClass}>{form.errors.email}</p>
            {/if}
        </div>

        <Button type="submit" variant="brand" class="w-full" disabled={form.processing}>
            {form.processing ? 'Mengirim…' : 'Kirim Link Reset Password'}
        </Button>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400">
            Ingat kata sandi?
            <Link href="/login" class="font-semibold text-orange-700 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">
                Kembali ke halaman masuk
            </Link>
        </p>
    </form>
</AuthShell>
