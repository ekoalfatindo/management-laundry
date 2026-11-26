<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { untrack } from 'svelte';
    import AppLayout from '../../Components/AppLayout.svelte';
    import PageHeader from '../../Components/Ui/PageHeader.svelte';
    import NotaForm from '../../Components/Nota/NotaForm.svelte';
    import Icon from '../../Components/Ui/Icon.svelte';
    import { buttonClass } from '../../lib/ui';
    import type { NotaFormOptions, NotaKind, NotaRecord } from '../../types';

    let {
        nota,
        barangs,
        kiloans,
        jenisLayanans,
        pelanggans,
        hadiahs,
        defaultWaktu,
    }: {
        nota: NotaRecord;
        barangs: NotaFormOptions['barangs'];
        kiloans: NotaFormOptions['kiloans'];
        jenisLayanans: NotaFormOptions['jenisLayanans'];
        pelanggans: NotaFormOptions['pelanggans'];
        hadiahs: NotaFormOptions['hadiahs'];
        defaultWaktu: string;
    } = $props();

    let jenis: NotaKind = untrack(() => (nota.jenis === 'Paket Kiloan' ? 'kiloan' : 'satuan'));
</script>

<AppLayout title="Ubah Nota">
    <PageHeader title={`Ubah Nota ${nota.no_nota}`} description="Perbarui detail transaksi. Nomor nota tidak berubah.">
        <Link href={`/nota/${nota.id}`} class={buttonClass('secondary')}>
            <Icon name="arrow-left" class="size-4" />
            Kembali
        </Link>
    </PageHeader>

    <NotaForm
        mode="edit"
        {jenis}
        options={{ barangs, kiloans, jenisLayanans, pelanggans, hadiahs }}
        {defaultWaktu}
        {nota}
    />
</AppLayout>
