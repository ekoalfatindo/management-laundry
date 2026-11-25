# Laundry Management System

Aplikasi laundry untuk mengelola transaksi: master pelanggan/karyawan, katalog barang
satuan & paket kiloan, jenis layanan, nota transaksi (kiloan/satuan), invoice A4 dengan terbilang,
identitas perusahaan (Setup), dashboard, dan ekspor laporan CSV. Staf memakai aplikasi
internal; pelanggan memiliki **portal sendiri** untuk melihat nota miliknya dan mengunduh PDF.

Aplikasi ini dibangun dengan:

- **Laravel 13** (PHP 8.3+, diuji pada PHP 8.4)
- **Inertia.js v3 + Svelte 5** (runes, TypeScript — tanpa Vue)
- **Tailwind CSS 4** + Vite 8
- **MySQL 8** + **Spatie Laravel Permission**
- **dompdf 3** untuk PDF nota sisi server (tanpa layanan eksternal)
- **SweetAlert2 11** untuk dialog konfirmasi hapus dan toast notifikasi bertema aplikasi
- Service layer, Form Request, Policy, DTO, Action, Enum, DB transaction

Data demo dibuat oleh seeder; tidak ada data produksi yang disertakan.

Panduan lengkap dalam format HTML (arsitektur, deploy VPS/shared hosting, dan fungsi menu):
[`docs/DOKUMENTASI_APLIKASI.html`](docs/DOKUMENTASI_APLIKASI.html) — buka langsung di browser.

## Fitur

- Login untuk staf dan pelanggan dengan rate limit dan toggle tampilkan/sembunyikan kata sandi;
  setelah masuk, admin/karyawan diarahkan ke dashboard dan pelanggan ke portal `/akun`.
- **Portal pelanggan** (`/akun`): ringkasan nota dan total biaya milik sendiri, daftar nota dengan
  pagination, detail invoice, dan **unduh PDF** nota — hanya untuk nota atas nama akun tersebut.
- **PDF nota A4** dibuat di server dengan dompdf (nama perusahaan, terbilang, rincian item) dan
  dapat diunduh pelanggan dari portal; staf mencetak invoice dari halaman detail nota.
- **Lupa kata sandi**: tautan reset via email (Laravel password broker, token sekali pakai),
  halaman reset terpisah, tanpa membocorkan status pendaftaran email.
- Dashboard: pendapatan total/bulan ini, jumlah nota, user, paket, grafik 12 bulan, nota terbaru.
- CRUD pengguna, karyawan, pelanggan, barang satuan, paket kiloan, jenis layanan.
- Menu **Harga Per Barang** dan **Harga Paket Kiloan** (label tampilan; route dan tabel tetap).
- Nota **Paket Kiloan** (`berat × harga/kg`) dan **Barang Satuan** (multi-baris + biaya layanan).
- Diskon persen 0–100; seluruh nominal dihitung ulang di server; snapshot harga pada nota.
- **Setup identitas perusahaan** (admin): nama dan logo (PNG/JPG/WEBP maks. 2 MB) disimpan di
  database + public disk; dipakai otomatis pada sidebar, halaman login, dan invoice.
- **Invoice A4 profesional**: hierarki informasi, tabel item, ringkasan pembayaran, terbilang
  Bahasa Indonesia otomatis, logo/nama perusahaan dinamis, dan CSS cetak A4 (navigasi disembunyikan).
- Daftar nota dengan pencarian, filter jenis/tanggal, dan pagination server-side.
- Ekspor CSV (UTF-8 BOM, aman dari formula injection) mengikuti filter daftar nota.
- Sidebar oranye hangat dengan tombol hamburger untuk merampingkan menu menjadi rail ikon di desktop
  (pilihan tersimpan di `localStorage`), ikon paket kiloan bergaya mesin cuci, dan tooltip Logout.
- Dark mode, latar login bergradasi hangat selaras dengan sidebar dan tombol oranye senada, serta layout responsif.
- **Notifikasi profesional dengan SweetAlert2**: toast sukses/gagal melayang di kanan atas (progres
  4,5 detik, dapat ditutup) dan dialog konfirmasi hapus dengan fokus awal pada tombol Batal;
  seluruh warna mengikuti tema oranye/rose termasuk mode gelap.
- **Footer konsisten**: bar ringkas **fixed di dasar layar** pada dashboard dan seluruh halaman staf
  (selalu terlihat saat konten digeser, mengikuti lebar sidebar); footer ringkas di bawah kartu login;
  serta bar fixed yang sama di portal pelanggan — kredit pengembang, Free to Use · Voluntary Donation,
  dan hak cipta. Teks footer dipusatkan di `resources/js/lib/footer.ts` sebagai konstanta terenkode,
  bukan ditulis langsung di template halaman.
- Halaman error 403/404/419/429/500/503.

## Kebutuhan

- PHP 8.3+ dengan ekstensi standar Laravel (pdo_mysql, mbstring, openssl, dll.)
- Composer 2
- Node.js 20+ dan npm
- MySQL 8

Contoh pada Laragon (Windows), PHP eksplisit:

```bash
C:/laragon/bin/php/php-8.4.27-nts-Win32-vs17-x64/php -v
```

## Instalasi

```bash
composer install
cp .env.example .env         # Windows: copy .env.example .env
php artisan key:generate
```

Sesuaikan `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laundry_svelte
DB_USERNAME=root
DB_PASSWORD=
```

Buat database, lalu jalankan migrasi + seeder, tautkan storage publik (untuk logo perusahaan):

```bash
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
```

Seeder idempoten; `php artisan db:seed` dapat diulang tanpa menggandakan data.

### Email untuk Lupa Kata Sandi

Pengembangan memakai `MAIL_MAILER=log`: tautan reset tertulis di `storage/logs/laravel.log`.
Untuk produksi, isi SMTP di `.env` lalu jalankan `php artisan config:clear`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.domainanda.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@domainanda.com
MAIL_PASSWORD=RAHASIA_SMTP
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@domainanda.com
MAIL_FROM_NAME="${APP_NAME}"
```

Token reset disimpan pada tabel `password_reset_tokens` (sudah ada sejak migrasi awal), kedaluwarsa,
dan hanya dapat dipakai sekali. Permintaan tautan dibatasi 6 percobaan per menit.

## Menjalankan

Dua proses terpisah:

```bash
php artisan serve --host=127.0.0.1 --port=8000
npm run dev
```

Buka `http://127.0.0.1:8000`.

Akun demo (password `password`):

| Peran | Email |
|---|---|
| Admin | `admin@laundry.test` |
| Karyawan | `vavian@laundry.test` |
| Pelanggan | `ayu@laundry.test` |

Seluruh akun memakai password `password`. Pelanggan masuk ke portal `/akun`, bukan dashboard staf.
Daftar lengkap tersedia di [`docs/DEMO_CREDENTIALS.md`](docs/DEMO_CREDENTIALS.md).

## Testing

Siapkan database test terpisah (sekali saja):

```sql
CREATE DATABASE laundry_svelte_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Jalankan:

```bash
php artisan test
npx svelte-check --tsgo --tsconfig ./tsconfig.json
npm run build
```

Hasil terakhir (pemeriksaan final 24 November 2026): 93 test / 437 assertion lulus (14,48 detik),
svelte-check 0 error / 0 warning, build terakhir 25 November 2026 (800 modul; JS 431,59 kB / gzip 116,97 kB;
CSS 97,20 kB / gzip 16,59 kB — `app.css` 67,68 kB + `sweetalert2.min.css` 29,52 kB), 46 route aplikasi.
Detail ada di [`docs/TESTING_REPORT.md`](docs/TESTING_REPORT.md).

Cache alat pengembangan (`.svelte-check/`, `.phpunit.result.cache`) dibersihkan setelah pemeriksaan;
tidak ada file sumber yatim yang tersisa. `public/hot` hanya ada saat Vite dev server berjalan.

Catatan: `svelte-check` membutuhkan TypeScript 6 + alias TypeScript 7 (`@typescript/native`)
sesuai `package.json`; jangan menghapus salah satunya.

## Struktur Singkat

```
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
└── Support/               Terbilang (angka → kata Bahasa Indonesia)

resources/
├── js/
│   ├── Components/        AppLayout, CustomerLayout, Auth/AuthShell, Nota/NotaForm, Nota/NotaInvoice, komponen Ui
│   ├── Pages/             Auth (login, lupa/reset), Dashboard, master, Nota, Pengaturan, Akun (portal), Error
│   └── lib/               format rupiah/tanggal, terbilang, class UI, notifikasi SweetAlert2
└── views/pdf/nota.blade.php  template PDF A4 (dompdf)
```

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [`docs/DOKUMENTASI_APLIKASI.html`](docs/DOKUMENTASI_APLIKASI.html) | **Panduan HTML** (buka di browser): arsitektur, deploy VPS/shared hosting, fungsi menu |
| [`docs/SYSTEM_ARCHITECTURE.md`](docs/SYSTEM_ARCHITECTURE.md) | Arsitektur final, layer, route, otorisasi, business logic |
| [`docs/DATABASE_ANALYSIS.md`](docs/DATABASE_ANALYSIS.md) | Skema legacy vs aplikasi baru, ERD, integritas data |
| [`docs/FEATURE_INVENTORY.md`](docs/FEATURE_INVENTORY.md) | Status setiap fitur legacy → aplikasi baru |
| [`docs/BUSINESS_WORKFLOW.md`](docs/BUSINESS_WORKFLOW.md) | Alur login, master, transaksi, cetak, laporan |
| [`docs/LEGACY_MIGRATION_REPORT.md`](docs/LEGACY_MIGRATION_REPORT.md) | Keputusan migrasi, perbaikan, perubahan sengaja |
| [`docs/TESTING_REPORT.md`](docs/TESTING_REPORT.md) | Bukti pengujian otomatis dan walkthrough UI |
| [`docs/DEMO_CREDENTIALS.md`](docs/DEMO_CREDENTIALS.md) | Akun demo dan ringkasan data seed |
| [`docs/DEVELOPMENT_PROGRESS.md`](docs/DEVELOPMENT_PROGRESS.md) | Checklist fase pengembangan |
| [`docs/LEGACY_SYSTEM_AUDIT.md`](docs/LEGACY_SYSTEM_AUDIT.md) | Audit aplikasi legacy (snapshot) |
| [`docs/ARCHITECTURE_NOTES.md`](docs/ARCHITECTURE_NOTES.md) | Catatan desain awal (superseded) |

## Keamanan

- Jangan gunakan kredensial demo di lingkungan nyata; ganti seluruh password.
- Set `APP_DEBUG=false` dan jalankan `php artisan config:cache` saat produksi.
- Reset password memakai token acak Laravel yang kedaluwarsa dan sekali pakai; respons permintaan
  selalu sama agar keberadaan email tidak dapat ditebak.
- Logo Setup divalidasi tipe (PNG/JPG/WEBP) dan ukuran (maks. 2 MB), disimpan di public disk;
  unggahan lama dihapus saat diganti. Hanya admin dengan `pengaturan.manage` yang dapat mengubahnya.
- Portal pelanggan memfilter nota berdasarkan `pelanggan_id` akun yang login; membuka nota atau PDF
  milik pelanggan lain menghasilkan 404, dan staf diarahkan ke dashboard bila membuka `/akun`.
- Aplikasi ini tidak menangani pembayaran; nilai transaksi adalah catatan operasional.
