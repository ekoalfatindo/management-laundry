# DEVELOPMENT PROGRESS

Legenda: `[ ]` Not Started · `[~]` In Progress · `[x]` Completed · `[!]` Blocked

Status per **2026-11-24**.

## Phase 1 — Legacy Audit

- [x] Identifikasi lokasi legacy dan proyek baru
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
- [x] Controller & route (46 route aplikasi, di luar route vendor)
- [x] Seeder role/permission (12 permission, 3 role)
- [x] Autentikasi & otorisasi (session, rate limit, middleware staf/pelanggan)
- [x] Perhitungan harga server-side + snapshot
- [x] Ekspor CSV (BOM, formula guard, filter)
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
- [x] `NotaSeeder` (68 nota + 63 baris, tanggal/jenis bervariasi, nomor deterministik)
- [x] Seeder idempoten — `db:seed` ulang tidak menggandakan data
- [x] `docs/DEMO_CREDENTIALS.md`

## Phase 6 — Testing

- [x] Feature test auth (9), otorisasi (6), portal pelanggan & PDF (6)
- [x] Feature test CRUD master (10), nota (14), user management (8)
- [x] Feature test pengaturan (8), lupa/reset password (8)
- [x] Unit test `PricingCalculator` (5 + data provider), `Terbilang` (16)
- [x] Total 93 test / 437 assertion lulus (14,48 detik pada pemeriksaan final)
- [x] `npm run build` tanpa warning (800 modul; JS 431,59 kB / gzip 116,97 kB;
      CSS 97,20 kB / gzip 16,59 kB termasuk `sweetalert2.min.css`)
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
      robots, `lang/id`); `public/hot` hanya artefak Vite dev yang aktif
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
