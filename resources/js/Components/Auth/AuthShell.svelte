<script lang="ts">
    import type { Snippet } from "svelte";
    import { usePage } from "@inertiajs/svelte";
    import Icon from "../Ui/Icon.svelte";
    import AppFooter from "../Ui/AppFooter.svelte";
    import type { SharedProps } from "../../types";

    let {
        title,
        subtitle,
        headline = "Kelola bisnis lebih rapi.",
        description = "Kelola pelanggan, cetak nota, serta pantau pendapatan dalam satu aplikasi.",
        children,
    }: {
        title: string;
        subtitle: string;
        headline?: string;
        description?: string;
        children: Snippet;
    } = $props();

    const page = usePage<SharedProps>();

    let companyName = $derived(
        page.props.company?.nama_perusahaan ||
            page.props.appName ||
            "Laundry Management",
    );
    let logoUrl = $derived(page.props.company?.logo_url ?? null);
</script>

<svelte:head>
    <title>{title} · {companyName}</title>
</svelte:head>

<div class="auth-shell flex min-h-screen flex-col items-center px-4 py-8">
    <div class="my-auto flex w-full flex-col items-center">
        <div
            class="grid w-full max-w-4xl overflow-hidden rounded-[28px] border border-orange-100/70 bg-white/95 shadow-[0_32px_80px_-36px_rgba(67,20,7,0.45)] ring-1 ring-white/70 backdrop-blur-md md:grid-cols-2 dark:border-orange-100/10 dark:bg-[#1b130d]/95 dark:ring-orange-100/5"
        >
            <div
                class="auth-panel relative hidden flex-col overflow-hidden p-10 text-orange-50 md:flex"
            >
                <div
                    class="flex min-w-0 items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 pr-4 backdrop-blur-sm"
                >
                    <span
                        class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white/15 ring-1 ring-white/15"
                    >
                        {#if logoUrl}
                            <img
                                src={logoUrl}
                                alt={`Logo ${companyName}`}
                                class="size-full bg-white object-contain p-1"
                            />
                        {:else}
                            <Icon name="shirt" class="size-6" />
                        {/if}
                    </span>
                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-bold text-white"
                            title={companyName}
                        >
                            {companyName}
                        </p>
                        <p class="text-xs text-orange-100/75">
                            Manajemen Laundry
                        </p>
                    </div>
                </div>

                <div class="my-auto pt-10">
                    <h1
                        class="max-w-sm text-[2rem] font-bold leading-[1.15] text-white"
                    >
                        {headline}
                    </h1>
                    <p
                        class="mt-3 max-w-sm text-sm leading-relaxed text-orange-100/85"
                    >
                        {description}
                    </p>
                </div>
            </div>

            <div class="flex flex-col p-8 sm:p-10">
                <div class="mb-8 md:hidden">
                    <span
                        class="inline-flex size-11 items-center justify-center overflow-hidden rounded-xl bg-linear-to-br from-orange-500 via-orange-600 to-amber-700 text-white shadow-lg shadow-orange-900/20 dark:from-orange-400 dark:via-orange-500 dark:to-amber-500 dark:text-slate-950"
                    >
                        {#if logoUrl}
                            <img
                                src={logoUrl}
                                alt={`Logo ${companyName}`}
                                class="size-full bg-white object-contain p-1"
                            />
                        {:else}
                            <Icon name="shirt" class="size-6" />
                        {/if}
                    </span>
                </div>

                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900 dark:text-orange-50"
                    >
                        {title}
                    </h2>
                    <p
                        class="mt-1.5 text-sm text-slate-500 dark:text-orange-100/60"
                    >
                        {subtitle}
                    </p>
                </div>

                <div class="mt-7">
                    {@render children()}
                </div>

                <p
                    class="mt-8 border-t border-slate-100 pt-5 text-center text-xs text-slate-400 dark:border-orange-100/10 dark:text-orange-100/45"
                >
                    Untuk administrator, karyawan, dan pelanggan.
                </p>
            </div>
        </div>

        <AppFooter variant="auth" />
    </div>
</div>
