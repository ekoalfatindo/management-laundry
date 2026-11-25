<script lang="ts">
    import { Link, usePage } from '@inertiajs/svelte';
    import CustomerLayout from '../../Components/CustomerLayout.svelte';
    import NotaInvoice from '../../Components/Nota/NotaInvoice.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { buttonClass } from '../../lib/ui';
    import { dateTime } from '../../lib/format';
    import type { NotaRecord, SharedProps } from '../../types';

    let { nota }: { nota: NotaRecord } = $props();

    const page = usePage<SharedProps>();

    let companyName = $derived(page.props.company?.nama_perusahaan || page.props.appName || 'Laundry Management');
    let logoUrl = $derived(page.props.company?.logo_url ?? null);
</script>

<CustomerLayout title={`Nota ${nota.no_nota}`}>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <Link
                href="/akun"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-700 transition hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-300"
            >
                <Icon name="arrow-left" class="size-4" />
                Kembali ke daftar nota
            </Link>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">Nota {nota.no_nota}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Dibuat {dateTime(nota.created_at)}</p>
        </div>

        <div class="flex flex-col items-end gap-1.5">
            <a href={`/akun/nota/${nota.id}/pdf`} class={buttonClass('primary')}>
                <Icon name="download" class="size-4" />
                Unduh PDF
            </a>
            <p class="text-xs text-slate-500 dark:text-slate-400">PDF dapat disimpan dan dicetak.</p>
        </div>
    </div>

    <NotaInvoice {nota} {companyName} {logoUrl} />
</CustomerLayout>
