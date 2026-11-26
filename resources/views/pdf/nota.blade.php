<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota {{ $nota->no_nota }}</title>
    <style>
        @page {
            margin: 30px 36px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1e293b;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .header-table,
        .summary-table,
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .header {
            border-bottom: 2px solid #ea580c;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .brand {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }

        .brand-tag {
            margin-top: 3px;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #c2410c;
        }

        .doc-label {
            text-align: right;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: #64748b;
        }

        .doc-number {
            text-align: right;
            font-family: "Courier New", monospace;
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }

        .doc-date {
            text-align: right;
            margin-top: 2px;
            color: #64748b;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-right: 6px;
        }

        .info-box-right {
            margin-right: 0;
            margin-left: 6px;
        }

        .info-label {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #64748b;
        }

        .info-value {
            margin-top: 3px;
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }

        .info-sub {
            margin-top: 2px;
            color: #64748b;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            background: #ccfbf1;
            color: #0f766e;
        }

        .badge-satuan {
            background: #e0e7ff;
            color: #3730a3;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .items thead th {
            background: #fff7ed;
            border-bottom: 1px solid #fdba74;
            color: #9a3412;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 7px 8px;
            text-align: left;
        }

        .items tbody td {
            border-bottom: 1px solid #e2e8f0;
            padding: 8px;
            vertical-align: top;
        }

        .items .right {
            text-align: right;
        }

        .items .center {
            text-align: center;
        }

        .item-name {
            font-weight: bold;
            color: #0f172a;
        }

        .item-sub {
            margin-top: 2px;
            color: #64748b;
        }

        .summary-table {
            margin-top: 16px;
        }

        .summary-table td {
            vertical-align: top;
            padding: 0;
        }

        .terbilang {
            width: 54%;
            padding-right: 26px;
        }

        .terbilang-box {
            border: 1px solid #fed7aa;
            background: #fff7ed;
            border-radius: 6px;
            padding: 11px 14px 12px;
        }

        .terbilang-label {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #c2410c;
        }

        .terbilang-value {
            margin-top: 3px;
            font-size: 11px;
            font-weight: bold;
            color: #7c2d12;
        }

        .totals {
            width: 46%;
        }

        .totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 4px 0;
        }

        .totals .label {
            color: #64748b;
            text-align: left;
        }

        .totals .value {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }

        .totals .grand td {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .totals .grand .value {
            color: #c2410c;
        }

        .footer {
            margin-top: 24px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            color: #64748b;
            font-size: 9px;
            line-height: 1.5;
        }

        .footer span {
            display: block;
            float: none;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand">{{ $company->nama_perusahaan }}</div>
                    <div class="brand-tag">Invoice</div>
                </td>
                <td>
                    <div class="doc-label">Nomor Invoice</div>
                    <div class="doc-number">{{ $nota->no_nota }}</div>
                    <div class="doc-date">{{ $nota->waktu?->format('d/m/Y H:i') }}</div>
                    <div class="doc-date">Status: {{ $nota->status->value }} &middot; {{ $nota->status_pembayaran->value }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-label">Ditagihkan kepada</div>
                    <div class="info-value">{{ $nota->nama_pelanggan }}</div>
                    <div class="info-sub">{{ $nota->pelanggan?->email ?? 'Pelanggan umum' }}</div>
                    @if ($nota->pelanggan?->kategori_pelanggan)
                        <div class="info-sub">Kategori: {{ $nota->pelanggan->kategori_pelanggan->value }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="info-box info-box-right">
                    <div class="info-label">Jenis Layanan</div>
                    <div style="margin-top: 4px;">
                        <span class="badge {{ $nota->jenis->value === 'Paket Kiloan' ? '' : 'badge-satuan' }}">
                            {{ $nota->jenis->value }}
                        </span>
                    </div>
                    <div class="info-sub">
                        @if ($nota->jenis->value === 'Paket Kiloan')
                            {{ $nota->kiloan?->paket ?? 'Paket dihapus' }}
                            &middot; {{ number_format((float) $nota->berat, 2, ',', '.') }} kg
                        @else
                            {{ $nota->jenisLayanan?->jenis_cuci ?? 'Layanan dihapus' }}
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="center">Qty</th>
                <th class="right">Harga</th>
                <th class="right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @if ($nota->jenis->value === 'Paket Kiloan')
                <tr>
                    <td>
                        <div class="item-name">{{ $nota->kiloan?->paket ?? 'Paket dihapus' }}</div>
                        <div class="item-sub">Layanan kiloan &middot; Rp {{ number_format((int) $nota->harga_per_kg, 0, ',', '.') }} / kg</div>
                    </td>
                    <td class="center">{{ number_format((float) $nota->berat, 2, ',', '.') }} kg</td>
                    <td class="right">Rp {{ number_format((int) $nota->harga_per_kg, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((int) $nota->total_harga, 0, ',', '.') }}</td>
                </tr>
            @else
                @foreach ($nota->barangs as $barang)
                    <tr>
                        <td>
                            <div class="item-name">{{ $barang->barang }}</div>
                            <div class="item-sub">Harga satuan</div>
                        </td>
                        <td class="center">{{ $barang->jumlah_barang }}</td>
                        <td class="right">Rp {{ number_format((int) $barang->harga, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format((int) $barang->total_harga, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                @if ($nota->biaya_layanan > 0)
                    <tr>
                        <td>
                            <div class="item-name">Biaya layanan</div>
                            <div class="item-sub">{{ $nota->jenisLayanan?->jenis_cuci ?? 'Layanan dihapus' }}</div>
                        </td>
                        <td class="center">1</td>
                        <td class="right">Rp {{ number_format((int) $nota->biaya_layanan, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format((int) $nota->biaya_layanan, 0, ',', '.') }}</td>
                    </tr>
                @endif
            @endif
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="terbilang">
                <div class="terbilang-box">
                    <div class="terbilang-label">Terbilang</div>
                    <div class="terbilang-value">{{ $terbilang }}</div>
                </div>
            </td>
            <td class="totals">
                <table>
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="value">Rp {{ number_format((int) $subtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">{{ $nota->reward_label ? 'Hadiah ('.$nota->reward_label.')' : 'Diskon ('.$nota->diskon.'%)' }}</td>
                        <td class="value">- Rp {{ number_format((int) $potongan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Pembayaran</td>
                        <td class="value">{{ $nota->status_pembayaran->value }}</td>
                    </tr>
                    <tr class="grand">
                        <td class="label">Total</td>
                        <td class="value">Rp {{ number_format((int) $nota->jumlah, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Terima kasih telah menggunakan layanan {{ $company->nama_perusahaan }}.
        <span>Simpan invoice ini sebagai bukti transaksi.</span>
    </div>
</body>
</html>
