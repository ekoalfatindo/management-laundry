<script lang="ts">
    import { untrack } from 'svelte';
    import { Link, useForm } from '@inertiajs/svelte';
    import AuthShell from '../../Components/Auth/AuthShell.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import { authInputClass, errorClass, labelClass } from '../../lib/ui';

    let { email, token }: { email: string; token: string } = $props();

    const initial = untrack(() => ({ email, token }));

    const form = useForm({
        token: initial.token,
        email: initial.email,
        password: '',
        password_confirmation: '',
    });

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        form.post('/reset-password');
    }
</script>

<AuthShell
    title="Atur kata sandi baru"
    subtitle="Buat kata sandi baru minimal 8 karakter, lalu konfirmasi."
    headline="Satu langkah lagi."
    description="Kata sandi baru akan langsung berlaku setelah disimpan."
>
    <form class="space-y-5" onsubmit={submit} novalidate>
        <div>
            <label class={labelClass} for="email">Email</label>
            <input id="email" type="email" class={authInputClass} autocomplete="username" bind:value={form.email} required />
            {#if form.errors.email}
                <p class={errorClass}>{form.errors.email}</p>
            {/if}
            {#if form.errors.token}
                <p class={errorClass}>{form.errors.token}</p>
            {/if}
        </div>

        <div>
            <label class={labelClass} for="password">Kata sandi baru</label>
            <input
                id="password"
                type="password"
                class={authInputClass}
                placeholder="Minimal 8 karakter"
                autocomplete="new-password"
                bind:value={form.password}
                required
            />
            {#if form.errors.password}
                <p class={errorClass}>{form.errors.password}</p>
            {/if}
        </div>

        <div>
            <label class={labelClass} for="password_confirmation">Konfirmasi kata sandi baru</label>
            <input
                id="password_confirmation"
                type="password"
                class={authInputClass}
                placeholder="Ulangi kata sandi baru"
                autocomplete="new-password"
                bind:value={form.password_confirmation}
                required
            />
            {#if form.errors.password_confirmation}
                <p class={errorClass}>{form.errors.password_confirmation}</p>
            {/if}
        </div>

        <Button type="submit" variant="brand" class="w-full" disabled={form.processing}>
            {form.processing ? 'Menyimpan…' : 'Simpan Kata Sandi Baru'}
        </Button>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400">
            <Link href="/login" class="font-semibold text-orange-700 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300">
                Kembali ke halaman masuk
            </Link>
        </p>
    </form>
</AuthShell>
