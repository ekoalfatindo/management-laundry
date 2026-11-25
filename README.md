# Laundry Management System

Aplikasi manajemen laundry untuk mengelola transaksi dan operasional harian: master pelanggan/karyawan/pengguna, katalog barang satuan & paket kiloan, jenis layanan, nota transaksi (kiloan/satuan), invoice A4 dengan terbilang Bahasa Indonesia, identitas perusahaan (Setup), dashboard, dan ekspor laporan CSV. Staf menggunakan aplikasi internal, sedangkan pelanggan memiliki **portal sendiri** (`/akun`) untuk melihat riwayat nota miliknya dan mengunduh PDF.

Dibangun dengan:

- **Laravel 13** (PHP 8.3+, diuji pada PHP 8.4)
- **Inertia.js v3 + Svelte 5** (runes, TypeScript — tanpa Vue)
- **Tailwind CSS 4** + **Vite 8**
- **MySQL 8** + **Spatie Laravel Permission**
- **dompdf 3** untuk pembuatan PDF nota sisi server (tanpa layanan eksternal)
- **SweetAlert2 11** untuk dialog konfirmasi hapus dan toast notifikasi bertema aplikasi
- Arsitektur berlapis: Service layer, Form Request, Policy, DTO, Action, Enum, dan DB transaction

Data demo dihasilkan oleh seeder; tidak ada data produksi yang disertakan.

Panduan lengkap dalam format HTML (arsitektur, deploy VPS/shared hosting, dan fungsi menu):
[`docs/DOKUMENTASI_APLIKASI.html`](docs/DOKUMENTASI_APLIKASI.html) — buka langsung di browser.

## Fitur

- **Autentikasi & pengalihan peran**: login untuk staf dan pelanggan dengan rate limit serta toggle tampilkan/sembunyikan kata sandi; admin/karyawan diarahkan ke dashboard dan pelanggan ke portal `/akun`.
- **Portal pelanggan (`/akun`)**: ringkasan nota dan total biaya milik sendiri, daftar nota berpaginasi, detail invoice, dan unduh PDF nota — khusus untuk nota atas nama akun yang login.
- **PDF nota A4 (`dompdf`)**: dibuat di sisi server (memuat nama perusahaan, terbilang, dan rincian item) untuk diunduh pelanggan dari portal; staf dapat mencetak invoice langsung dari halaman detail nota.
- **Lupa kata sandi**: pengiriman tautan reset via email menggunakan Laravel password broker (token kedaluwarsa & sekali pakai) pada halaman reset terpisah, tanpa membocorkan status pendaftaran email.
- **Dashboard**: pendapatan total & bulan ini, jumlah nota, pengguna, paket, grafik 12 bulan, dan daftar nota terbaru.
- **Manajemen data master**: CRUD pengguna, karyawan, pelanggan, barang satuan, paket kiloan, dan jenis layanan, dengan label menu **Harga Per Barang** dan **Harga Paket Kiloan** (route dan tabel tetap).
- **Transaksi nota**: mendukung **Paket Kiloan** (`berat × harga/kg`) dan **Barang Satuan** (multi-baris + biaya layanan), diskon persen `0–100%`, kalkulasi ulang seluruh nominal di server, serta snapshot harga pada nota.
- **Setup identitas perusahaan (admin)**: pengaturan nama dan logo (`PNG`/`JPG`/`WEBP`, maks. 2 MB) di database dan public disk, diterapkan otomatis pada sidebar, halaman login, dan invoice.
- **Invoice A4**: hierarki informasi jelas, tabel item, ringkasan pembayaran, konversi terbilang Bahasa Indonesia otomatis, logo/nama perusahaan dinamis, serta CSS cetak A4 yang menyembunyikan navigasi.
- **Daftar nota & ekspor CSV**: pencarian, filter jenis/tanggal, pagination server-side, dan ekspor CSV (UTF-8 BOM, terlindungi dari formula injection) sesuai filter aktif.
- **UI, tema & notifikasi**: sidebar oranye hangat dengan tombol hamburger untuk menciutkan menu menjadi rail ikon di desktop (tersimpan di `localStorage`), ikon paket kiloan bergaya mesin cuci, tooltip Logout, dark mode, latar login bergradasi hangat, layout responsif, serta toast & konfirmasi hapus **SweetAlert2** bertema oranye/rose.
- **Footer konsisten**: bar ringkas `fixed` di dasar layar pada dashboard, halaman staf, dan portal pelanggan (mengikuti lebar sidebar), footer ringkas di bawah kartu login, serta bar `fixed` pada portal pelanggan — memuat kredit pengembang, `Free to Use · Voluntary Donation`, dan hak cipta. Konstanta teks footer dipusatkan di `resources/js/lib/footer.ts`.
- **Halaman error**: tampilan khusus untuk status `403`, `404`, `419`, `429`, `500`, dan `503`.

## Kebutuhan

- PHP 8.3+ dengan ekstensi standar Laravel (`pdo_mysql`, `mbstring`, `openssl`, dll.)
- Composer 2
- Node.js 20+ dan npm
- MySQL 8

Contoh pengecekan versi PHP eksplisit pada Laragon (Windows):

```bash
C:/laragon/bin/php/php-8.4.27-nts-Win32-vs17-x64/php -v
```

## Instalasi

```bash
composer install
cp .env.example .env         # Windows: copy .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laundry_svelte
DB_USERNAME=root
DB_PASSWORD=
```

Buat database terlebih dahulu, lalu jalankan migrasi, seeder, dan tautkan storage publik (untuk logo perusahaan):

```bash
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
```

Seeder bersifat idempoten; `php artisan db:seed` dapat dijalankan ulang tanpa menggandakan data.

### Email untuk Lupa Kata Sandi

Pada lingkungan pengembangan, gunakan `MAIL_MAILER=log` agar tautan reset tercatat di `storage/logs/laravel.log`. Untuk produksi, lengkapi konfigurasi SMTP di `.env` lalu jalankan `php artisan config:clear`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.domainanda.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@domainanda.com
MAIL_PASSWORD=RAHASIA_SMTP
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@domainanda.com
MAIL_FROM_NAME="${APP_NAME}"
```

Token reset disimpan pada tabel `password_reset_tokens` (tersedia sejak migrasi awal), memiliki masa berlaku, dan hanya dapat digunakan sekali. Permintaan tautan dibatasi maksimal 6 percobaan per menit.

## Menjalankan

Jalankan backend dan frontend dev server pada dua terminal terpisah:

```bash
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

Akses aplikasi melalui `http://127.0.0.1:8000`.

Akun demo (seluruhnya menggunakan password `password`):

| Peran     | Email                 |
| --------- | --------------------- |
| Admin     | `admin@laundry.test`  |
| Karyawan  | `vavian@laundry.test` |
| Pelanggan | `ayu@laundry.test`    |

Pelanggan akan diarahkan ke portal `/akun`, bukan dashboard staf. Daftar akun demo lengkap tersedia di [`docs/DEMO_CREDENTIALS.md`](docs/DEMO_CREDENTIALS.md).

## Testing

Siapkan database pengujian terpisah (cukup sekali):

```sql
CREATE DATABASE laundry_svelte_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Jalankan rangkaian pengujian dan build:

```bash
php artisan test
npx svelte-check --tsgo --tsconfig ./tsconfig.json
npm run build
```

Ringkasan hasil terakhir:

- **Pengujian otomatis (24 November 2026)**: 93 test / 437 assertion lulus dalam 14,48 detik.
- **Type & Svelte check**: 0 error / 0 warning.
- **Build produksi (25 November 2026)**: 800 modul; JS `431,59 kB` (gzip `116,97 kB`), CSS `97,20 kB` (gzip `16,59 kB` — `app.css` `67,68 kB` + `sweetalert2.min.css` `29,52 kB`), 46 route aplikasi.

Detail lengkap tersedia di [`docs/TESTING_REPORT.md`](docs/TESTING_REPORT.md).

Cache alat pengembangan (`.svelte-check/`, `.phpunit.result.cache`) dibersihkan setelah pemeriksaan tanpa menyisakan file sumber yatim. File `public/hot` hanya muncul saat Vite dev server aktif.

> **Catatan:** `svelte-check` memerlukan TypeScript 6 beserta alias TypeScript 7 (`@typescript/native`) sesuai `package.json`; jangan menghapus salah satunya.

## Struktur Singkat

```text
app/
├── Actions/Nota/          GenerateNomorNota
├── DTOs/                  NotaData
├── Enums/                 JenisNota, UserRole
├── Http/Controllers/      auth (login, lupa/reset password), dashboard, master, nota, pengaturan, akun (portal pelanggan)
├── Http/Middleware/       HandleInertiaRequests, EnsureStaff, EnsurePelanggan (redirect per peran)
├── Http/Requests/         validasi + authorize per operasi
├── Http/Resources/        bentuk props Inertia (termasuk terbilang invoice)
├── Models/                User, Barang, Kiloan, JenisLayanan, Nota, NotaBarang, Pengaturan
├── Policies/              akses per resource
├── Services/              NotaService, PricingCalculator, DashboardService, UserService
└── Support/               Terbilang (konversi angka ke kata Bahasa Indonesia)

resources/
├── js/
│   ├── Components/        AppLayout, CustomerLayout, Auth/AuthShell, Nota/NotaForm, Nota/NotaInvoice, komponen Ui
│   ├── Pages/             Auth (login, lupa/reset), Dashboard, master, Nota, Pengaturan, Akun (portal), Error
│   └── lib/               format rupiah/tanggal, terbilang, class UI, notifikasi SweetAlert2
└── views/pdf/nota.blade.php  template PDF A4 (dompdf)
```

## Dokumentasi

| Dokumen                                                              | Isi                                                                                    |
| -------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| [`docs/DOKUMENTASI_APLIKASI.html`](docs/DOKUMENTASI_APLIKASI.html)   | **Panduan HTML** (buka di browser): arsitektur, deploy VPS/shared hosting, fungsi menu |
| [`docs/SYSTEM_ARCHITECTURE.md`](docs/SYSTEM_ARCHITECTURE.md)         | Arsitektur final, layer, route, otorisasi, business logic                              |
| [`docs/DATABASE_ANALYSIS.md`](docs/DATABASE_ANALYSIS.md)             | Skema legacy vs aplikasi baru, ERD, integritas data                                    |
| [`docs/FEATURE_INVENTORY.md`](docs/FEATURE_INVENTORY.md)             | Status setiap fitur legacy ke aplikasi baru                                            |
| [`docs/BUSINESS_WORKFLOW.md`](docs/BUSINESS_WORKFLOW.md)             | Alur login, master, transaksi, cetak, laporan                                          |
| [`docs/LEGACY_MIGRATION_REPORT.md`](docs/LEGACY_MIGRATION_REPORT.md) | Keputusan migrasi, perbaikan, perubahan sengaja                                        |
| [`docs/TESTING_REPORT.md`](docs/TESTING_REPORT.md)                   | Bukti pengujian otomatis dan walkthrough UI                                            |
| [`docs/DEMO_CREDENTIALS.md`](docs/DEMO_CREDENTIALS.md)               | Akun demo dan ringkasan data seed                                                      |
| [`docs/DEVELOPMENT_PROGRESS.md`](docs/DEVELOPMENT_PROGRESS.md)       | Checklist fase pengembangan                                                            |
| [`docs/LEGACY_SYSTEM_AUDIT.md`](docs/LEGACY_SYSTEM_AUDIT.md)         | Audit aplikasi legacy (snapshot)                                                       |
| [`docs/ARCHITECTURE_NOTES.md`](docs/ARCHITECTURE_NOTES.md)           | Catatan desain awal (superseded)                                                       |

## Keamanan

- Jangan gunakan kredensial demo di lingkungan produksi; ganti seluruh kata sandi.
- Atur `APP_DEBUG=false` dan jalankan `php artisan config:cache` saat deploy ke produksi.
- Reset kata sandi menggunakan token acak Laravel yang kedaluwarsa dan sekali pakai, dengan respons permintaan yang konsisten agar keberadaan email tidak dapat ditebak.
- Unggahan logo pada menu Setup divalidasi tipe (`PNG`/`JPG`/`WEBP`) dan ukuran (maks. 2 MB), disimpan di public disk, serta otomatis menghapus file lama saat diganti. Hanya admin dengan izin `pengaturan.manage` yang dapat mengubahnya.
- Portal pelanggan membatasi akses berdasarkan `pelanggan_id` akun yang login; mengakses nota atau PDF milik pelanggan lain mengembalikan `404`, dan staf akan diarahkan ke dashboard jika membuka `/akun`.
- Aplikasi ini tidak menangani pemrosesan pembayaran; seluruh nilai transaksi berfungsi sebagai catatan operasional.
