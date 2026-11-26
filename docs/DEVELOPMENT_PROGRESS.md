# DEVELOPMENT PROGRESS

Legenda: `[ ]` Not Started · `[~]` In Progress · `[x]` Completed · `[!]` Blocked

Status per **2026-11-27**.

## Phase 1 — Legacy Audit

- [x] Identifikasi sumber acuan dan direktori kerja proyek
- [x] Ekstrak arsip legacy ke folder referensi read-only
- [x] Audit struktur backend (controller, model, migration, resource, route, seeder)
- [x] Audit frontend (layout, halaman, store, perhitungan)
- [x] Petakan database & relasi
- [x] Petakan workflow bisnis
- [x] Dokumentasi: `LEGACY_SYSTEM_AUDIT.md`, `DATABASE_ANALYSIS.md`, `FEATURE_INVENTORY.md`, `BUSINESS_WORKFLOW.md`

## Phase 2 — Arsitektur & Scaffold

- [x] Proyek Laravel 13 di direktori baru (`D:\project\lav_svelte\laundry`)
- [x] Inertia + Svelte 5 + Vite + TypeScript
- [x] Tailwind CSS 4
- [x] Spatie Laravel Permission
- [x] Konfigurasi MySQL + `.env`
- [x] Inertia middleware, root view, entrypoint
- [x] Dokumentasi arsitektur: `ARCHITECTURE_NOTES.md` (desain awal) dan
      `SYSTEM_ARCHITECTURE.md` (final)

## Phase 3 — Backend

- [x] Migration & model (users, barangs, kiloans, jenis_layanans, notas, nota_barangs)
- [x] Enum, DTO, Action, Service (`JenisNota`, `UserRole`, `NotaData`, `GenerateNomorNota`,
      `NotaService`, `PricingCalculator`, `DashboardService`, `UserService`)
- [x] Form Request & Policy
- [x] Controller & route (56 route aplikasi, di luar route internal/vendor)
- [x] Seeder role/permission (14 permission, 3 role)
- [x] Autentikasi & otorisasi (session, rate limit, middleware staf/pelanggan)
- [x] Perhitungan harga server-side + snapshot
- [x] Ekspor Excel `.xlsx` (judul, ringkasan filter, header beku, auto-filter, total,
      format Rupiah/tanggal)
- [x] Portal pelanggan (`/akun`) + PDF nota server-side (dompdf)
- [x] Error page Inertia (403/404/419/429/500/503) untuk web dan XHR

## Phase 4 — Frontend

- [x] Layout & komponen UI (Button, Modal, FormField, Badge, Pagination, dll.)
- [x] Halaman login
- [x] Dashboard + grafik SVG + nota terbaru
- [x] CRUD user/karyawan/pelanggan/barang/kiloan/jenis layanan
- [x] Nota: index, create (kiloan/satuan), edit, show/print
- [x] Portal pelanggan: daftar, detail invoice, unduh PDF (layout terpisah)
- [x] Responsive & dark mode
- [x] `svelte-check` 0 error / 0 warning dan `npm run build` sukses

## Phase 5 — Seeder & Demo Data

- [x] `RolePermissionSeeder`
- [x] `UserSeeder` (1 admin, 4 karyawan, 10 pelanggan)
- [x] `BarangSeeder` (10), `KiloanSeeder` (5), `JenisLayananSeeder` (4)
- [x] `NotaSeeder` (81 nota pada 6 bulan + 18 riwayat hadiah, nomor deterministik, rebuild dalam transaksi)
- [x] `LoyaltyProgramSeeder` (1 program), `PromotionSeeder` (3 promosi)
- [x] Seeder idempoten — `db:seed` ulang tidak menggandakan akun/master/nota/hadiah
- [x] `docs/DEMO_CREDENTIALS.md`

## Phase 6 — Testing

- [x] Feature test auth (9), otorisasi (6), portal pelanggan & PDF (6)
- [x] Feature test CRUD master (12), nota (14), user management (9)
- [x] Feature test pengaturan (8), lupa/reset password (8)
- [x] Feature test loyalitas/promosi (13), HTTP loyalitas/promosi (14), seeder demo (2),
      akurasi hadiah Member & promosi semua pelanggan (5)
- [x] Unit test `PricingCalculator` (5 + data provider), `Terbilang` (16)
- [x] Total 141 test / 755 assertion lulus (23,30 detik pada pemeriksaan terakhir)
- [x] `npm run build` tanpa warning (805 modul; JS 493,73 kB / gzip 131,45 kB;
      CSS 101,15 kB / gzip 17,20 kB termasuk `sweetalert2.min.css`)
- [x] Walkthrough UI live per role (admin, karyawan, pelanggan)
- [x] Pemeriksaan final 24 November 2026: matriks status per peran, batas kepemilikan nota/PDF,
      filter valid vs input berlebih, 0 error console, dan 0 error log server
- [x] Verifikasi live notifikasi SweetAlert2 (dialog hapus, toast simpan/hapus, mode gelap,
      tombol Batal) — `TESTING_REPORT.md` §4.2
- [x] Footer global: bar fixed ringkas di dasar layar untuk dashboard/staf, footer ringkas di login,
      dan bar fixed yang sama di portal pelanggan (light/dark, layar kecil, `.no-print`); teks dipusatkan
      di `resources/js/lib/footer.ts` sebagai konstanta terenkode — `TESTING_REPORT.md` §4.3
- [x] `docs/TESTING_REPORT.md`

## Phase 7 — Cleanup

- [x] Hapus `AGENTS.md`, `CLAUDE.md`, `resources/views/welcome.blade.php`
- [x] Inventaris seluruh file sumber: tidak ada komponen/lib/request/resource/seeder yatim;
      `UserFactory` dipakai model `User`, policy ditemukan otomatis
- [x] Hapus cache alat pengembangan `.svelte-check/` dan `.phpunit.result.cache` setelah
      pemeriksaan final (dibuat ulang otomatis, sudah masuk `.gitignore`)
- [x] Pertahankan scaffolding yang dipakai (migration cache/jobs, cache bootstrap, favicon,
      robots, `lang/id`); `public/hot` dihapus karena build produksi memakai `manifest.json`
- [x] Pembersihan ulang 27 November 2026: view terkompilasi, log, `public/hot`, `.svelte-check/`,
      `.phpunit.result.cache` dihapus; `public/build` (3 aset) dipertahankan; 17 migrasi + 10
      seeder diverifikasi semuanya direferensikan
- [x] Rapikan `composer.json` (nama, deskripsi, script setup MySQL)
- [x] Verifikasi `migrate:fresh --seed`, `db:seed` ulang, `npm run build`

## Phase 8 — Dokumentasi Final

- [x] `README.md`
- [x] `docs/SYSTEM_ARCHITECTURE.md`
- [x] `docs/DATABASE_ANALYSIS.md` (skema final + verifikasi implementasi)
- [x] `docs/FEATURE_INVENTORY.md` (status akhir)
- [x] `docs/LEGACY_MIGRATION_REPORT.md`
- [x] `docs/TESTING_REPORT.md`
- [x] `docs/DEMO_CREDENTIALS.md`
- [x] Verifikasi legacy tetap utuh (tidak ada penulisan ke folder read-only)

## Phase 9 — Kategori, Loyalitas, Promosi, Status, dan Format Cetak (2026-11-26)

- [x] Audit ulang source code sebelum perubahan; komponen/tabel lama dipakai ulang, tidak ada
      fitur yang dihapus
- [x] Migration baru: `users.kategori_pelanggan`/`member_sejak`, `loyalty_programs`, `promotions`,
      `customer_rewards`, kolom status/hadiah pada `notas`
- [x] Enum domain: `KategoriPelanggan`, `StatusNota`, `StatusPembayaran`, `StatusHadiah`,
      `SumberHadiah`, `TipeHadiah`
- [x] `RewardService` (sinkronisasi, reserve/release, cancel guard, progress) + `PricingCalculator::potonganHadiah`
- [x] `NotaService` mendukung status Selesai/Batal, pembayaran, dan pemakaian hadiah dalam transaksi database
- [x] Controller/route/permission `loyalitas.manage` dan `promosi.manage`; detail pelanggan
- [x] Frontend: badge/filter kategori, halaman detail pelanggan, Loyalitas, Promosi, pemilihan
      hadiah pada form nota, aksi status/pembayaran, modal format cetak
- [x] Invoice A4 diperbarui (status, kategori, hadiah) + template thermal 58/80 mm
- [x] Seeder demo 6 bulan: kategori Member/Reguler, loyalitas/promosi, riwayat hadiah, dua
      contoh pemakaian hadiah; idempoten
- [x] Standardisasi istilah UI menjadi **Pelanggan** tanpa mengubah nama tabel/kolom internal
- [x] Test baru: `LoyaltyPromotionTest`, `LoyaltyPromotionHttpTest`, `DemoSeederTest`
- [x] Verifikasi live per peran, modal format cetak, console 0 error; `svelte-check` 0/0;
      build 805 modul tanpa warning
- [x] Combobox pencarian **Pelanggan Terdaftar** pada form nota kiloan & satuan (default
      Pelanggan umum, navigasi keyboard, filter nama, edit nota memuat pelanggan tersimpan)
- [x] Pemeriksaan final 26 November 2026: matriks status admin/karyawan/pelanggan, filter nota &
      kategori pelanggan, kalkulasi kiloan/satuan, ekspor XLSX, PDF portal, thermal 58 mm,
      0 error console, dan 0 `local.ERROR`
- [x] Sinkronisasi dokumentasi: README, DEMO_CREDENTIALS, TESTING_REPORT, BUSINESS_WORKFLOW,
      DATABASE_ANALYSIS, SYSTEM_ARCHITECTURE, FEATURE_INVENTORY, DEVELOPMENT_PROGRESS,
      dan DOKUMENTASI_APLIKASI.html

## Phase 10 — Rebuild Database & Audit Final (2026-11-27)

- [x] Inventaris ulang seluruh file sumber: 17 migrasi dan 10 seeder semuanya direferensikan;
      tidak ada komponen/lib/request/resource/seeder yatim — tidak ada file sumber dihapus
- [x] Bersihkan artefak generasi: view terkompilasi, `storage/logs/laravel.log`, `public/hot`,
      `.svelte-check/`, `.phpunit.result.cache`; `php artisan optimize:clear`
- [x] `DROP`/`CREATE` database `laundry_svelte` dan `laundry_svelte_test` (utf8mb4), lalu
      `php artisan migrate --seed --force`; database proyek lain/legacy/produksi tidak disentuh
- [x] Baseline terverifikasi: 15 user, 10 barang, 5 kiloan, 4 layanan, 1 loyalitas, 3 promosi,
      81 nota (ID 1–81), 18 hadiah, 3 role, 14 permission
- [x] Pemeriksaan final semua fitur: 141 test / 755 assertion (23,30 detik), `svelte-check` 0/0,
      build 805 modul, matriks status per peran, ekspor XLSX, PDF portal, format cetak thermal,
      console 0 error — `TESTING_REPORT.md` §4.5
- [x] Sinkronisasi dokumentasi 27 November 2026 (MD + HTML) dan entri changelog `1.1.1`
