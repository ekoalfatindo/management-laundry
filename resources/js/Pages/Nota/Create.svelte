<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import NotaForm from '../../Components/Nota/NotaForm.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { buttonClass } from '../../lib/ui';
    import type { NotaFormOptions, NotaKind } from '../../types';

    let {
        barangs,
        kiloans,
        jenisLayanans,
        pelanggans,
        defaultWaktu,
        defaultJenis,
    }: {
        barangs: NotaFormOptions['barangs'];
        kiloans: NotaFormOptions['kiloans'];
        jenisLayanans: NotaFormOptions['jenisLayanans'];
        pelanggans: NotaFormOptions['pelanggans'];
        defaultWaktu: string;
        defaultJenis: NotaKind;
    } = $props();
</script>

<AppLayout title="Buat Nota">
    <PageHeader title="Buat Nota" description="Pilih jenis nota, isi detail transaksi, lalu simpan.">
        <Link href="/nota" class={buttonClass('secondary')}>
            <Icon name="arrow-left" class="size-4" />
            Kembali
        </Link>
    </PageHeader>

    <NotaForm
        mode="create"
        jenis={defaultJenis}
        options={{ barangs, kiloans, jenisLayanans, pelanggans }}
        {defaultWaktu}
    />
</AppLayout>
