<script lang="ts">
    import { Link, useForm, usePage } from '@inertiajs/svelte';
    import AuthShell from '../../Components/Auth/AuthShell.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import Button from '../../Components/Ui/Button.svelte';
    import { authInputClass, errorClass, labelClass } from '../../lib/ui';
    import type { SharedProps } from '../../types';

    const page = usePage<SharedProps>();
    let status = $derived(page.props.flash?.status ?? '');

    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    let showPassword = $state(false);

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        form.post('/login', {
            onFinish: () => form.reset('password'),
        });
    }
</script>

<AuthShell title="Masuk ke akun" subtitle="Kelola operasional laundry atau lihat nota atas nama Anda.">
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
            <label class={labelClass} for="email">Email</label>
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

        <div>
            <div class="flex items-center justify-between gap-3">
                <label class="{labelClass} mb-0" for="password">Kata sandi</label>
                <Link
                    href="/forgot-password"
                    class="text-xs font-semibold text-orange-700 transition hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300"
                >
                    Lupa password?
                </Link>
            </div>
            <div class="relative mt-1.5">
                <input
                    id="password"
                    type={showPassword ? 'text' : 'password'}
                    class="{authInputClass} pr-11"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    bind:value={form.password}
                    required
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-3 text-slate-400 transition hover:text-orange-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 dark:text-orange-100/40 dark:hover:text-orange-300"
                    aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                    aria-pressed={showPassword}
                    onclick={() => (showPassword = !showPassword)}
                >
                    <Icon name={showPassword ? 'eye-off' : 'eye'} class="size-4" />
                </button>
            </div>
            {#if form.errors.password}
                <p class={errorClass}>{form.errors.password}</p>
            {/if}
        </div>

        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 dark:text-slate-300">
            <input
                type="checkbox"
                class="size-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500 dark:border-orange-100/20 dark:bg-[#1f160f]"
                bind:checked={form.remember}
            />
            Ingat saya
        </label>

        <Button type="submit" variant="brand" class="w-full" disabled={form.processing}>
            {form.processing ? 'Memproses…' : 'Masuk'}
        </Button>
    </form>
</AuthShell>
