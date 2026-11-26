# LEGACY MIGRATION REPORT — Laundry Management System

Tanggal: 2026-11-24
Legacy: Laravel 10 + Sanctum + Quasar/Vue 3 (`D:\project\lav_svelte\_legacy_ref\Laundry`, read-only)
Aplikasi baru: Laravel 13 + Inertia + Svelte 5 + Tailwind 4 (`D:\project\lav_svelte\laundry`)

## 1. Batasan yang Dipatuhi

- Source legacy tidak diubah, tidak dipindah, dan tidak ditimpa. Hanya dibaca.
- Tidak ada operasi database destruktif pada database legacy maupun database produksi.
- Tidak ada fitur/business logic yang dikarang di luar temuan audit. Portal pelanggan
  (lihat nota + unduh PDF) ditambahkan atas permintaan eksplisit 2026-11-24.
- Tidak memakai Vue.js atau package tanpa alasan teknis.

## 2. Pemetaan Modul

| Modul legacy | Implementasi aplikasi baru |
|---|---|
| `AuthController` (Sanctum token) | `AuthenticatedSessionController` + session Inertia + rate limit untuk semua peran; staf diarahkan ke dashboard, pelanggan ke portal `/akun` |
| `UserController` + tabel `karyawans`/`pelanggans` | `UserController`, `KaryawanController`, `PelangganController`; wrapper table dihapus, peran Spatie `admin/karyawan/pelanggan` |
| `BarangController`, `KiloanController`, `JenisLayananController` | Controller + FormRequest + Policy yang setara |
| `NotaController@storeKiloan/@storeSatuan` | `NotaController@storeKiloan` (`POST /nota/kiloan`) dan `@storeSatuan` (`POST /nota/satuan`) |
| `NotaController@update` (rusak) | `@update` + `UpdateNotaRequest` + `NotaService::update` (hitung ulang, ganti baris) |
| `NotaController@destroy` | `@destroy` + `NotaPolicy::delete`; baris cascade |
| `NotaController@chart/@pie` | `DashboardService` (chart bulanan); endpoint pie tidak dipakai dan tidak dibuat ulang |
| Ekspor SheetJS di browser | `NotaController@export` + `NotaExcelExport` — XLSX native, filter server-side, tata letak laporan |
| Halaman Quasar + Pinia | Halaman Svelte 5 + Inertia props; state form lokal (`useForm`) |
| Token di localStorage | Session cookie + CSRF Inertia (token tidak disimpan di JS) |

## 3. Fidelity Business Rules

| Aturan legacy | Status di aplikasi baru | Bukti |
|---|---|---|
| Dua jenis nota: `Paket Kiloan`, `Barang Satuan` | Dipertahankan (enum `JenisNota`) | `NotaTest`, UI Nota/Create |
| Kiloan: `total = berat × harga_per_kg` | Dipertahankan, dibulatkan rupiah | `PricingCalculatorTest`, `NotaTest` |
| Satuan: `total = Σ(harga × jumlah) + biaya_layanan` | Dipertahankan | `PricingCalculatorTest`, `NotaTest` |
| Diskon persen dari total bruto | Dipertahankan; kini divalidasi 0–100 dan di-clamp | `PricingCalculatorTest` |
| Nomor nota `#` + 6 karakter acak, unik | Dipertahankan (3 huruf kapital + 3 alfanumerik, retry 20×) | `NotaTest`, `GenerateNomorNota` |
| Nama pelanggan teks bebas | Dipertahankan sebagai snapshot; ditambah relasi opsional `pelanggan_id` | `notas` migration, form nota |
| Barang per nota menyimpan nama + harga | Dipertahankan sebagai snapshot; ditambah `barang_id` nullable | `nota_barangs` migration |
| Hapus barang/paket/layanan | Nota tidak ikut terhapus (legacy cascade; kini `nullOnDelete`) | `NotaTest` |
| Dashboard: pendapatan = Σ `jumlah`, penjualan = jumlah nota, user, paket | Dipertahankan | `DashboardService` |
| Grafik bulanan | Dipertahankan; grup memakai `waktu` (legacy `created_at`) | `DashboardService` |
| Cetak nota | Invoice A4 dengan terbilang dan kop perusahaan di halaman staf; PDF server-side (dompdf) untuk portal pelanggan | `Nota/Show.svelte`, `AkunController@pdf` |
| Pencarian & filter daftar nota | Ditingkatkan ke server-side (cari nomor/nama, jenis, rentang tanggal) | `NotaController@index`, `NotaTest` |
| Format Rupiah id-ID | Dipertahankan | `lib/format.ts` |

## 4. Perubahan yang Disengaja

1. **Register publik dihapus.** Legacy membuat akun `Pelanggan` yang tidak punya halaman;
   sekarang pelanggan dibuat admin/karyawan dan dapat masuk ke portal baca-saja. → `FEATURE_INVENTORY` F-03, F-35.
2. **Otorisasi server-side.** Legacy membuka seluruh endpoint CRUD tanpa auth dan hanya
   menyembunyikan menu di frontend. Aplikasi baru memakai session auth + 14 permission + policy.
3. **Harga dihitung server-side.** Legacy menerima total/diskon dari klien; aplikasi baru mengabaikan
   nominal kiriman dan mengambil harga master saat simpan.
4. **Snapshot harga.** Ditambahkan `harga_per_kg`, `biaya_layanan`, dan `barang/harga` per baris
   agar struk lama tidak berubah saat master diedit.
5. **FK master `nullOnDelete`.** Menghapus master tidak lagi menghapus riwayat nota.
6. **Edit nota diperbaiki.** Legacy rusak (`hasMany` tanpa FK + `sync()`); aplikasi baru mengganti
   baris dalam transaksi dan menghitung ulang.
7. **Wrapper table dihapus.** `karyawans`/`pelanggans` diganti role Spatie; data identitas tetap
   di `users` (`jenis_kelamin`, `alamat`).
8. **Ekspor XLSX native menggantikan SheetJS.** Kolom legacy dipertahankan dan diperluas dengan
   snapshot (berat, harga/kg, biaya layanan) agar laporan setara; workbook dibangun server-side
   tanpa dependency.
9. **Pagination & pencarian server-side** (10/25/50/100) menggantikan pemrosesan client.
10. **Dashboard "Penjualan Terbaru"** menampilkan 6 nota terbaru (legacy menampilkan seluruh
    daftar di komponen yang sama); daftar lengkap tersedia di menu Nota.
11. **Hapus nota hanya admin.** Karyawan dapat membuat/mengubah/mengekspor tetapi tidak
    menghapus (permission `nota.delete` hanya untuk admin).
12. **Pemisahan route nota** (`/nota/kiloan`, `/nota/satuan`) agar validasi tiap jenis eksplisit.
13. **Grid/list toggle & pemilih tipe tabel** tidak dipertahankan (kosmetik legacy).
14. **Laravel Boost / scaffold** (AGENTS.md, CLAUDE.md, welcome page) dihapus dari proyek baru.
15. **Portal pelanggan + PDF nota** (permintaan 2026-11-24, tidak ada di legacy). Route `/akun`
    memakai middleware `pelanggan`; data difilter `pelanggan_id` akun login; PDF A4 dibuat
    server-side dengan dompdf. Pelanggan tidak dapat membuat/mengubah/menghapus nota.

## 5. Perbaikan Temuan Legacy

| Temuan audit | Perbaikan |
|---|---|
| Endpoint CRUD tanpa auth | Semua route di balik `auth` + `permission:*` |
| Perhitungan di client | `PricingCalculator` di server |
| `Nota::barangs()` salah + `sync()` | Relasi `hasMany(NotaBarang)` + `barangs()->delete()`/`createMany()` |
| Store user mengirim `passwordConfirmation`/`role` ke `$guarded=['id']` | FormRequest memisahkan field; `UserService` mengatur role/password |
| Diskon tak dibatasi | Validasi 0–100 + clamp |
| Dashboard memakai `created_at` | Memakai `waktu` |
| Tanpa pagination server | Pagination server-side |
| Query laporan tanpa index | Index `waktu`, `jenis`, `nama_pelanggan`, `pelanggan_id` |
| Nomor nota/content uang float | Uang integer/unsigned bigint; berat decimal(8,2) |
| Tanpa rate limit login | 5 percobaan / throttle, pesan Indonesia |
| Token di localStorage | Session + CSRF |

## 6. Data Legacy

Tidak ada dump database legacy yang disertakan pada arsip. Karena itu:

- Aplikasi baru **tidak** mengimpor data produksi legacy.
- Skema baru dibuat lewat migration; data demo realistis dibuat lewat seeder idempoten.
- Jika migrasi data nyata dibutuhkan, langkah yang disarankan: ekspor tabel legacy →
  petakan `users.role` ke role Spatie → buat `users`, `barangs`, `kiloans`, `jenis_layanans`,
  `notas` (+ isi snapshot dari master saat ini), `nota_barangs` (+ `barang_id` bila nama cocok).
  Nomor nota legacy dapat dipertahankan karena kolomnya kompatibel (`varchar(20)`).

## 7. Fitur Legacy yang Tidak Dibawa

Pembayaran/piutang/status lunas, status pengerjaan, pengeluaran, outlet, laporan laba,
pengaturan toko, stok, multi-tenant, register publik, endpoint pie, toggle grid/list.
Semua dicatat di `FEATURE_INVENTORY.md` dan tidak dibuat ulang.

Portal pelanggan tidak ada di legacy, tetapi **diimplementasikan** pada proyek baru atas
permintaan 2026-11-24 (F-35/F-36) dengan cakupan baca nota + unduh PDF milik sendiri.

## 8. Verifikasi Legacy Tetap Utuh

- Selama pengembangan, tidak ada perintah tulis yang dijalankan terhadap
  `D:\project\lav_svelte\_legacy_ref\Laundry` maupun arsip ZIP asal.
- Seluruh pekerjaan file berada di `D:\project\lav_svelte\laundry`.
- Database legacy/produksi tidak disentuh; pengujian memakai `laundry_svelte_test` dan
  pengembangan memakai `laundry_svelte`.
