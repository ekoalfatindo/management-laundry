# ARCHITECTURE NOTES — Desain Awal (Superseded)

> **Status: dokumen desain Phase 2.** Arsitektur final dan peta route yang akurat ada di
> [`SYSTEM_ARCHITECTURE.md`](SYSTEM_ARCHITECTURE.md). Dokumen ini dipertahankan sebagai catatan
> sejarah keputusan desain, bukan referensi implementasi.

## Ringkasan desain awal

1. Laravel 13 + Inertia + Svelte 5 + Tailwind 4 + Spatie Permission, MySQL.
2. Controller tipis → Form Request → Service (`NotaService`, `PricingCalculator`,
   `DashboardService`) → Eloquent; DTO `NotaData`; action `GenerateNomorNota`.
3. Perhitungan harga selalu di server; klien hanya pratinjau.
4. Policy + permission middleware untuk otorisasi; role pelanggan tidak punya akses ke halaman
   staf (portal baca-saja `/akun` ditambahkan 2026-11-24, di luar desain awal).
5. Normalisasi ringan: hapus tabel wrapper `karyawans`/`pelanggans`, tambah `pelanggan_id` dan
   snapshot harga pada nota, FK master `nullOnDelete`.
6. UI Svelte 5 runes: modal untuk master, halaman penuh untuk transaksi, invoice cetak A4.

## Perbedaan desain awal vs implementasi final

| Desain awal | Implementasi final |
|---|---|
| Dashboard di `/dashboard` | Dashboard di `/` (name `dashboard`) |
| Satu route `POST /nota` | Dua route: `POST /nota/kiloan` dan `POST /nota/satuan` |
| `GET /nota-export` | `GET /nota/export` dengan filter daftar nota |
| `NotaExportController` terpisah | `NotaController@export` + `App\Support\NotaExcelExport` (workbook XLSX native) |
| Policy `KaryawanPolicy`, `PelangganPolicy` | Tidak ada; `UserPolicy` + permission middleware, guard bisnis di `UserService` |
| Komponen `Layouts/AppLayout`, `DataTable`, `FlashToast` | `Components/AppLayout` (toast SweetAlert2 via `lib/notify.ts`), tabel per halaman |
| Permission `pelanggan.view` + `manage` | Hanya `pelanggan.manage` |
| `dashboard.view` di route terpisah | Dashboard memakai `permission:dashboard.view` di `/` |

Alasan perubahan: menyederhanakan permukaan otorisasi (satu sumber kebenaran), memisahkan
validasi kiloan/satuan agar eksplisit, dan menghindari komponen/route yang tidak dipakai.
