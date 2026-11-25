# FEATURE INVENTORY — Legacy → Aplikasi Baru

Status legacy: **Ada / Rusak / Tidak ada**.
Status fitur diperbarui **2026-11-24** berdasarkan implementasi dan pengujian final.

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
| F-14 | Nota Paket Kiloan | `NotaController@storeKiloan` | Ada | Form + `POST /nota/kiloan`, hitung server-side | Selesai |
| F-15 | Nota Barang Satuan | `NotaController@storeSatuan` | Ada | Form multi-baris + `POST /nota/satuan`, satu transaksi | Selesai |
| F-16 | Daftar Nota | `NotaController@index` | Ada (client-side) | Server-side pagination + cari + filter jenis/tanggal | Selesai |
| F-17 | Detail Nota | `NotaController@show` | Ada | Halaman invoice A4 + ringkasan layar | Selesai |
| F-18 | Cetak Nota | `ShowNota.vue` | Ada | Print CSS A4 (`.invoice-print`, `.no-print`, `@page size: A4`) | Selesai |
| F-19 | Hapus Nota | `NotaController@destroy` | Ada | Konfirmasi + policy (admin) | Selesai |
| F-20 | Edit Nota | `NotaController@update` | Rusak, tanpa UI | Edit header + baris, hitung ulang, snapshot baru | Selesai |
| F-21 | Ekspor daftar nota | SheetJS `.xlsx` | Ada | CSV UTF-8 BOM + formula guard, kolom legacy + snapshot | Selesai (CSV) |
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
| F-36 | PDF nota server-side | — | Tidak ada (hanya print browser) | `AkunController@pdf` + dompdf A4 (`resources/views/pdf/nota.blade.php`), nama file `nota-{no_nota}.pdf` | Baru (permintaan 2026-11-24) |

## Modul yang TIDAK Ada di Legacy (tidak diimplementasikan)

| Modul | Keterangan |
|---|---|
| Pembayaran / status lunas / piutang | Tidak ada tabel/field/UI |
| Status pengerjaan laundry | Tidak ada |
| Pengeluaran operasional | Tidak ada |
| Outlet / cabang | Tidak ada |
| Laporan laba/keuntungan | Tidak ada |
| Laporan pengeluaran | Tidak ada |
| Manajemen stok | Tidak ada |
| Multi-tenant / langganan | Tidak ada |

Portal pelanggan tidak ada di legacy dan **diimplementasikan** pada proyek baru atas permintaan
2026-11-24 (F-35), terbatas pada baca nota + unduh PDF milik sendiri.

## Catatan Perubahan yang Disengaja

1. Register publik dihapus; akun pelanggan kini dibuat admin/karyawan dan dapat masuk ke portal
   baca-saja (`/akun`), bukan ke aplikasi staf.
2. Ekspor `.xlsx` menjadi CSV (kolom setara + snapshot, tanpa dependency SheetJS).
3. Edit nota diimplementasikan benar (sebelumnya endpoint rusak).
4. Menu Pelanggan ditambahkan (sebelumnya route/pages ada tanpa tautan).
5. Perhitungan harga dipindah ke server (sebelumnya client).
6. Grafik memakai `waktu` transaksi (sebelumnya `created_at`).
7. Grid/list toggle & pemilih tipe tabel tidak dipertahankan (fitur kosmetik).
8. Hapus nota dibatasi untuk admin; karyawan dapat membuat, mengubah, dan mengekspor.
9. Otorisasi ditegakkan di server (legacy hanya menyembunyikan menu di frontend).
10. Setup identitas perusahaan ditambahkan sebagai fitur baru (legacy hardcoded); bukan perubahan
    alur bisnis, hanya sumber nama/logo untuk tampilan dan invoice.
