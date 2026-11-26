# FEATURE INVENTORY — Legacy → Aplikasi Baru

Status legacy: **Ada / Rusak / Tidak ada**.
Status fitur diperbarui **2026-11-27** berdasarkan implementasi dan pengujian final.

| ID | Fitur | Lokasi Legacy | Status Legacy | Implementasi | Status |
|---|---|---|---|---|---|
| F-01 | Login | `AuthController@login`, `AuthPage.vue` | Ada | Session Inertia + rate limit untuk semua peran; staf ke dashboard, pelanggan ke portal `/akun`; toggle tampilkan/sembunyikan kata sandi | Selesai |
| F-02 | Logout | `AuthController@logout` | Ada | Invalidate session + redirect login | Selesai |
| F-03 | Register publik | `AuthController@register` | Ada (tanpa area pelanggan) | Dihapus; akun dibuat admin/karyawan | Tidak diimplementasikan (sengaja) |
| F-04 | Profil user | `AuthController@profile` | Ada | Shared prop `auth.user` (id, name, email, roles, permissions) | Selesai |
| F-05 | Dashboard KPI | `IndexDashboardPage.vue` | Ada | 4 kartu KPI dari `DashboardService` | Selesai |
| F-06 | Grafik pendapatan bulanan | `NotaController@chart`, ECharts | Ada (pakai created_at) | Grafik SVG `MonthlyChart` berbasis `waktu` | Selesai |
| F-07 | Penjualan terbaru | `IndexDashboardPage.vue` | Ada | 6 nota terbaru + tautan detail | Selesai |
| F-08 | CRUD User | `UserController` | Ada (store rusak) | CRUD + role Spatie (admin) | Selesai |
| F-09 | CRUD Karyawan | `KaryawanController` | Ada (store rusak) | CRUD user role `karyawan` (admin) | Selesai |
| F-10 | CRUD Pelanggan | `PelangganController` | Ada (tanpa menu, store rusak) | CRUD + menu untuk admin & karyawan | Selesai |
| F-11 | CRUD Barang Satuan | `BarangController` | Ada | CRUD; hapus master tidak menghapus nota (snapshot) | Selesai |
| F-12 | CRUD Paket Kiloan | `KiloanController` | Ada | CRUD paket + harga/kg + deskripsi | Selesai |
| F-13 | CRUD Jenis Layanan | `JenisLayananController` | Ada | CRUD layanan + waktu + biaya | Selesai |
| F-14 | Nota Paket Kiloan | `NotaController@storeKiloan` | Ada | Form + `POST /nota/kiloan`, hitung server-side; pelanggan terdaftar dipilih lewat combobox pencarian (default **Pelanggan umum**, beralih ke walk-in mengosongkan nama) dan divalidasi harus berperan Pelanggan | Selesai |
| F-15 | Nota Barang Satuan | `NotaController@storeSatuan` | Ada | Form multi-baris + `POST /nota/satuan`, satu transaksi; pelanggan terdaftar dipilih lewat combobox pencarian (default **Pelanggan umum**, beralih ke walk-in mengosongkan nama) dan divalidasi harus berperan Pelanggan | Selesai |
| F-16 | Daftar Nota | `NotaController@index` | Ada (client-side) | Server-side pagination + cari + filter jenis/tanggal/status/pembayaran | Selesai |
| F-17 | Detail Nota | `NotaController@show` | Ada | Halaman invoice A4 + ringkasan layar + badge status/kategori + aksi Selesai/Lunas/Batal | Selesai |
| F-18 | Cetak Nota | `ShowNota.vue` | Ada | Modal pilih format: A4 (`.invoice-print`, `@page size: A4`), thermal 80 mm, thermal 58 mm; `.no-print` | Selesai |
| F-19 | Hapus Nota | `NotaController@destroy` | Ada | Konfirmasi + policy (admin) | Selesai |
| F-20 | Edit Nota | `NotaController@update` | Rusak, tanpa UI | Edit header + baris, hitung ulang, snapshot baru; hadiah terpasang dapat dilepas/diganti; nota **Dibatalkan** bersifat final (edit ditolak, tombol Ubah disembunyikan) | Selesai |
| F-21 | Ekspor daftar nota | SheetJS `.xlsx` | Ada | XLSX native (Open XML, server-side): judul + ringkasan filter, header beku, auto-filter, zebra, baris total, format Rupiah/tanggal; kolom legacy + snapshot | Selesai (XLSX) |
| F-22 | Pencarian Nota | client-side | Ada | Server-side `no_nota`/`nama_pelanggan` | Selesai |
| F-23 | Pagination Nota | client-side 10/20/30 | Ada | Server-side 10/25/50/100 | Selesai |
| F-24 | Grid/List & tipe tabel | `IndexNota.vue` | Ada (kosmetik) | Tidak dipertahankan; tabel responsif | Tidak dipertahankan |
| F-25 | Dark mode | `DashboardLayout.vue` | Ada (localStorage) | Tailwind dark + localStorage + skrip anti-flicker | Selesai |
| F-26 | Role guard | `boot/role.js` (frontend) | Ada (frontend saja) | Middleware permission + Policy + menu per permission | Selesai |
| F-27 | Format Rupiah | `boot/rupiah.js` | Ada | `rupiah()` id-ID di `lib/format.ts` | Selesai |
| F-28 | Grafik pie barang | `NotaController@pie` | Ada (endpoint mati) | Tidak dibuat ulang | Tidak dipertahankan |
| F-29 | Landing page | `MainLayout.vue` | Ada | `/` = dashboard; guest diarahkan ke login | Selesai |
| F-30 | Responsive layout | `DashboardLayout.vue` | Ada | Sidebar drawer + tabel responsif Tailwind | Selesai |
| F-31 | Setup identitas perusahaan | — | Tidak ada (hardcoded) | Menu Setup: nama + logo (PNG/JPG/WEBP ≤ 2 MB), public disk, admin only, dipakai di login/sidebar/invoice | Baru (permintaan 2026-11-24) |
| F-32 | Terbilang invoice | — | Tidak ada | `App\Support\Terbilang` + `lib/terbilang.ts`; otomatis dari total, akhiran “rupiah” | Baru (permintaan 2026-11-24) |
| F-33 | Lupa & reset password | — | Tidak ada | Laravel password broker, email token sekali pakai, halaman reset terpisah | Baru (permintaan 2026-11-24) |
| F-34 | Penyempurnaan UI | — | — | Label “Harga Per Barang”/“Harga Paket Kiloan”, ikon washer, tooltip Logout, tema login oranye selaras sidebar | Baru (permintaan 2026-11-24) |
| F-35 | Portal pelanggan | — | Tidak ada | Pelanggan login lalu membuka `/akun`: ringkasan + daftar nota milik sendiri, detail invoice, unduh PDF; data difilter `pelanggan_id` | Baru (permintaan 2026-11-24) |
| F-36 | PDF nota server-side | — | Tidak ada (hanya print browser) | `AkunController@pdf` + dompdf A4 (`resources/views/pdf/nota.blade.php`), nama file `nota-{no_nota}.pdf` | Baru (permintaan 2026-11-26) |
| F-37 | Kategori pelanggan | — | Tidak ada | `users.kategori_pelanggan` (`Reguler`/`Member`) + `member_sejak`; badge, filter, dan form ubah kategori | Baru (permintaan 2026-11-26) |
| F-38 | Detail pelanggan | — | Tidak ada | `/pelanggan/{id}`: profil, kategori, riwayat nota, progress loyalitas, dan daftar hadiah | Baru (permintaan 2026-11-26) |
| F-39 | Program loyalitas Member | — | Tidak ada | `loyalty_programs` + `RewardService`: target transaksi Selesai, tipe/nilai hadiah, masa berlaku, aktif; hadiah otomatis per siklus | Baru (permintaan 2026-11-26) |
| F-40 | Promosi umum | — | Tidak ada | `promotions`: target kategori, target transaksi, periode, syarat, aktif; hadiah otomatis per siklus | Baru (permintaan 2026-11-26) |
| F-41 | Riwayat & pemakaian hadiah | — | Tidak ada | `customer_rewards` (Tersedia/Digunakan/Kedaluwarsa/Dibatalkan), sekali pakai, pemilihan hadiah pada nota, potongan otomatis | Baru (permintaan 2026-11-26) |
| F-42 | Status nota & pembayaran | — | Tidak ada | `Diproses`/`Selesai`/`Dibatalkan` + `Belum Lunas`/`Lunas`; aksi Selesai/Lunas/Batal di detail nota; filter daftar | Baru (permintaan 2026-11-26) |
| F-43 | Format cetak thermal 58/80 mm | — | Tidak ada (hanya A4) | Modal pilih format sebelum cetak; `NotaThermal.svelte`, `@page` dinamis, `.no-print` | Baru (permintaan 2026-11-26) |
| F-44 | Seeder demo 6 bulan | — | Data lama lebih sedikit | 81 nota, 18 hadiah, 5 Member/5 Reguler, seluruh status, idempoten dalam satu transaksi | Baru (permintaan 2026-11-26) |

## Modul di Luar Cakupan

| Modul | Keterangan |
|---|---|
| Gateway pembayaran / piutang | Tidak ada integrasi pembayaran; status Lunas hanya catatan operasional |
| Pengeluaran operasional | Tidak ada |
| Outlet / cabang | Tidak ada |
| Laporan laba/keuntungan | Tidak ada |
| Laporan pengeluaran | Tidak ada |
| Manajemen stok | Tidak ada |
| Multi-tenant / langganan | Tidak ada |

Status nota dan status pembayaran **tersedia** sebagai fitur F-42; yang tidak diimplementasikan
adalah integrasi pembayaran/piutang. Portal pelanggan tersedia sebagai fitur F-35, terbatas pada
baca nota + unduh PDF milik sendiri.

## Catatan Perubahan yang Disengaja

1. Register publik dihapus; akun pelanggan kini dibuat admin/karyawan dan dapat masuk ke portal
   baca-saja (`/akun`), bukan ke aplikasi staf.
2. Ekspor `.xlsx` dibuat native di server (Open XML melalui `NotaExcelExport`, tanpa dependency
   SheetJS); kolom setara + snapshot dipertahankan dengan tata letak laporan yang rapi.
3. Edit nota diimplementasikan benar (sebelumnya endpoint rusak).
4. Menu Pelanggan ditambahkan (sebelumnya route/pages ada tanpa tautan).
5. Perhitungan harga dipindah ke server (sebelumnya client).
6. Grafik memakai `waktu` transaksi (sebelumnya `created_at`).
7. Grid/list toggle & pemilih tipe tabel tidak dipertahankan (fitur kosmetik).
8. Hapus nota dibatasi untuk admin; karyawan dapat membuat, mengubah, dan mengekspor.
9. Otorisasi ditegakkan di server (legacy hanya menyembunyikan menu di frontend).
10. Setup identitas perusahaan ditambahkan sebagai fitur baru (legacy hardcoded); bukan perubahan
    alur bisnis, hanya sumber nama/logo untuk tampilan dan invoice.
11. Kategori pelanggan, loyalitas Member, dan promosi menambah tabel/kolom baru tanpa mengubah
    struktur nota lama; nota historis tetap memakai snapshot harga.
12. Status nota/pembayaran dan ledger hadiah memakai nilai default aman (`Diproses`/`Belum Lunas`,
    `Tersedia`) sehingga data lama tetap valid setelah migrasi.
13. Seeder nota membangun ulang data transaksi demo 6 bulan; akun dan master data tetap idempoten.
