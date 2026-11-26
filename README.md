# Laundry Management System

Aplikasi manajemen laundry untuk mengelola transaksi dan operasional harian: master pelanggan/karyawan/pengguna dengan kategori Reguler/Member, katalog barang satuan & paket kiloan, jenis layanan, nota transaksi (kiloan/satuan) berstatus proses/selesai/batal, program loyalitas Member, promosi berulang, pemakaian hadiah, invoice A4 dan struk thermal 58/80 mm dengan terbilang Bahasa Indonesia, identitas perusahaan (Setup), dashboard, dan ekspor laporan Excel (.xlsx). Staf menggunakan aplikasi internal, sedangkan pelanggan memiliki **portal sendiri** (`/akun`) untuk melihat riwayat nota miliknya dan mengunduh PDF.

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

## Dukungan & Donasi

Aplikasi ini **gratis untuk digunakan (Free to Use)** dan dapat dimanfaatkan untuk kebutuhan operasional bisnis maupun pembelajaran.

Jika aplikasi ini memberikan manfaat bagi Anda, dukungan melalui donasi sukarela akan sangat berarti untuk membantu pengembangan, pemeliharaan, serta penyempurnaan fitur aplikasi di masa mendatang.

Donasi sepenuhnya bersifat sukarela dan tidak diwajibkan untuk menggunakan aplikasi ini.

**Dukung pengembangan proyek ini melalui Saweria:**

☕ **Saweria:** https://saweria.co/masykuro075

Terima kasih atas dukungan dan apresiasi Anda. Setiap kontribusi sangat berarti bagi keberlangsungan pengembangan proyek ini.

## Fitur

- **Autentikasi & pengalihan peran**: login untuk staf dan pelanggan dengan rate limit serta toggle tampilkan/sembunyikan kata sandi; admin/karyawan diarahkan ke dashboard dan pelanggan ke portal `/akun`.
- **Portal pelanggan (`/akun`)**: ringkasan nota dan total biaya milik sendiri, daftar nota berpaginasi, detail invoice, dan unduh PDF nota — khusus untuk nota atas nama akun yang login.
- **PDF nota A4 (`dompdf`)**: dibuat di sisi server (memuat nama perusahaan, terbilang, dan rincian item) untuk diunduh pelanggan dari portal; staf dapat mencetak invoice langsung dari halaman detail nota.
- **Lupa kata sandi**: pengiriman tautan reset via email menggunakan Laravel password broker (token kedaluwarsa & sekali pakai) pada halaman reset terpisah, tanpa membocorkan status pendaftaran email.
- **Dashboard**: pendapatan total & bulan ini, jumlah nota, pengguna, paket, grafik 12 bulan, dan daftar nota terbaru.
- **Manajemen data master**: CRUD pengguna, karyawan, pelanggan, barang satuan, paket kiloan, dan jenis layanan, dengan label menu **Harga Per Barang** dan **Harga Paket Kiloan** (route dan tabel tetap).
- **Kategori pelanggan**: setiap pelanggan diklasifikasikan sebagai **Reguler** atau **Member** (dengan tanggal mulai member), dapat difilter, ditampilkan sebagai badge, serta memiliki halaman detail berisi riwayat transaksi, total belanja, dan riwayat hadiah. Perubahan kategori tidak mengubah riwayat nota.
- **Program loyalitas Member**: target transaksi, jenis hadiah (diskon persen, potongan nominal, gratis cuci), nilai hadiah, masa berlaku, dan status aktif dapat diatur admin. Hadiah diterbitkan otomatis saat transaksi **Selesai** mencapai target, hanya dapat dipakai sekali pada transaksi berikutnya, dan tidak dapat digabung dengan diskon persen manual.
- **Promosi berulang**: promosi untuk Reguler, Member, atau semua pelanggan dengan periode berlaku, syarat, target transaksi, dan hadiah. Promosi aktif menerbitkan hadiah secara otomatis, sedangkan promosi nonaktif atau kedaluwarsa tidak menerbitkan hadiah. Loyalitas dan promosi dihitung terpisah tanpa duplikasi.
- **Status nota & pembayaran**: status **Diproses / Selesai / Dibatalkan** dan pembayaran **Belum Lunas / Lunas**. Pembatalan melepas atau mencabut hadiah terkait dan dicegah jika hadiah dari transaksi tersebut sudah terpakai di nota lain.
- **Transaksi nota**: mendukung **Paket Kiloan** (`berat × harga/kg`) dan **Barang Satuan** (multi-baris + biaya layanan), diskon persen `0–100%`, pemakaian hadiah pelanggan, kalkulasi ulang seluruh nominal di server, serta snapshot harga pada nota. Pelanggan terdaftar dipilih lewat **combobox pencarian** (default **Pelanggan umum**) yang mengisi nama dan membuka pilihan hadiah secara otomatis; beralih kembali ke **Pelanggan umum** mengosongkan nama pelanggan sebelumnya. Saat mengubah nota, hadiah terpasang dapat dilepas atau diganti tanpa harus mengganti pelanggan.
- **Setup identitas perusahaan (admin)**: pengaturan nama dan logo (`PNG`/`JPG`/`WEBP`, maks. 2 MB) di database dan public disk, diterapkan otomatis pada sidebar, halaman login, dan invoice.
- **Pilihan format cetak**: sebelum mencetak, pilih **A4 Profesional**, **Thermal 80 mm**, atau **Thermal 58 mm**. Template A4 menampilkan invoice lengkap (identitas, pelanggan, nomor, tanggal, layanan, berat/qty, harga, diskon/hadiah, status pembayaran, total, terbilang), sedangkan template thermal tampil ringkas tanpa angka atau nama terpotong. Seluruh format memakai data nota yang sama sehingga total selalu konsisten, dan CSS cetak menyembunyikan sidebar, header, tombol, serta footer.
- **Daftar nota & ekspor Excel**: pencarian, filter jenis/status/pembayaran/tanggal, pagination server-side, dan ekspor `.xlsx` rapi sesuai filter aktif — judul perusahaan, ringkasan filter, header beku, auto-filter, baris zebra, baris total, format Rupiah/tanggal, serta tata cetak lanskap A4.
- **UI, tema & notifikasi**: sidebar oranye hangat dengan tombol hamburger untuk menciutkan menu menjadi rail ikon di desktop (tersimpan di `localStorage`), ikon paket kiloan bergaya mesin cuci, tooltip Logout, dark mode, latar login bergradasi hangat, layout responsif, serta toast & konfirmasi hapus **SweetAlert2** bertema oranye/rose.
- **Footer konsisten**: bar ringkas `fixed` di dasar layar pada dashboard, halaman staf, dan portal pelanggan (mengikuti lebar sidebar), footer ringkas di bawah kartu login, serta bar `fixed` pada portal pelanggan — memuat kredit pengembang, `Free to Use · Voluntary Donation`, dan hak cipta. Konstanta teks footer dipusatkan di `resources/js/lib/footer.ts`.
- **Halaman error**: tampilan khusus untuk status `403`, `404`, `419`, `429`, `500`, dan `503`.

## Kebutuhan

- PHP 8.3+ dengan ekstensi standar Laravel (`pdo_mysql`, `mbstring`, `openssl`, `zip`, `xmlwriter`, dll.)
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

Seeder bersifat idempoten. `php artisan db:seed` mengatur ulang **data transaksi demo** (nota, item nota, dan hadiah) lalu membangun ulang riwayat 6 bulan terakhir, sekaligus melengkapi data master (pengguna, kategori pelanggan, katalog, program loyalitas, dan promosi) tanpa menggandakan data.

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

- **Pengujian otomatis (27 November 2026)**: 141 test / 755 assertion lulus dalam 23,30 detik, termasuk kategori pelanggan, loyalitas, promosi, akurasi hadiah (Member & promosi semua pelanggan), integritas pembatalan, format cetak, ekspor XLSX, dan seeder demo 6 bulan.
- **Type & Svelte check**: 0 error / 0 warning.
- **Build produksi (27 November 2026)**: 805 modul; JS `493,73 kB` (gzip `131,45 kB`), CSS `101,15 kB` (gzip `17,20 kB` — `app.css` `71,63 kB` + `sweetalert2.min.css` `29,52 kB`), 56 route aplikasi.

Detail lengkap tersedia di [`docs/TESTING_REPORT.md`](docs/TESTING_REPORT.md).

Cache alat pengembangan (`.svelte-check/`, `.phpunit.result.cache`), view terkompilasi, log, dan dev marker `public/hot` dibersihkan setelah pemeriksaan (27 November 2026) tanpa menyisakan file sumber yatim; semuanya dibuat ulang otomatis dan sudah masuk `.gitignore`. Build produksi di `public/build` dipertahankan.

> **Catatan:** `svelte-check` memerlukan TypeScript 6 beserta alias TypeScript 7 (`@typescript/native`) sesuai `package.json`; jangan menghapus salah satunya.

## Struktur Singkat

```text
app/
├── Actions/Nota/          GenerateNomorNota
├── DTOs/                  NotaData
├── Enums/                 JenisNota, UserRole, KategoriPelanggan, StatusNota, StatusPembayaran, TipeHadiah, SumberHadiah, StatusHadiah
├── Http/Controllers/      auth (login, lupa/reset password), dashboard, master, nota, loyalitas, promosi, pengaturan, akun (portal pelanggan)
├── Http/Middleware/       HandleInertiaRequests, EnsureStaff, EnsurePelanggan (redirect per peran)
├── Http/Requests/         validasi + authorize per operasi (termasuk hadiah nota)
├── Http/Resources/        bentuk props Inertia (termasuk terbilang, status, kategori, reward)
├── Models/                User, Barang, Kiloan, JenisLayanan, Nota, NotaBarang, Pengaturan, LoyaltyProgram, Promotion, CustomerReward
├── Policies/              akses per resource
├── Services/              NotaService, RewardService, PricingCalculator, DashboardService, UserService
└── Support/               Terbilang (konversi angka ke kata Bahasa Indonesia)

resources/
├── js/
│   ├── Components/        AppLayout, CustomerLayout, Auth/AuthShell, Nota/NotaForm, Nota/PelangganCombobox, Nota/NotaInvoice, Nota/NotaThermal, komponen Ui
│   ├── Pages/             Auth (login, lupa/reset), Dashboard, master, Nota, Pelanggan, Loyalitas, Promosi, Pengaturan, Akun (portal), Error
│   └── lib/               format rupiah/tanggal, terbilang, class UI, notifikasi SweetAlert2
└── views/pdf/nota.blade.php  template PDF A4 (dompdf)
```

## Dokumentasi

| Dokumen                                                              | Isi                                                                                    |
| -------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| [`CHANGELOG.md`](CHANGELOG.md)                                       | Riwayat perubahan fitur, perbaikan, dan catatan pembaruan                              |
| [`docs/DOKUMENTASI_APLIKASI.html`](docs/DOKUMENTASI_APLIKASI.html)   | **Panduan HTML** (buka di browser): arsitektur, deploy VPS/shared hosting, fungsi menu |
| [`docs/SYSTEM_ARCHITECTURE.md`](docs/SYSTEM_ARCHITECTURE.md)         | Arsitektur final, layer, route, otorisasi, business logic                              |
| [`docs/DATABASE_ANALYSIS.md`](docs/DATABASE_ANALYSIS.md)             | Skema final, ERD, relasi, dan integritas data                                          |
| [`docs/FEATURE_INVENTORY.md`](docs/FEATURE_INVENTORY.md)             | Daftar fitur dan status implementasi                                                   |
| [`docs/BUSINESS_WORKFLOW.md`](docs/BUSINESS_WORKFLOW.md)             | Alur login, master, transaksi, cetak, laporan                                          |
| [`docs/LEGACY_MIGRATION_REPORT.md`](docs/LEGACY_MIGRATION_REPORT.md) | Arsip audit, keputusan, dan perubahan historis                                         |
| [`docs/TESTING_REPORT.md`](docs/TESTING_REPORT.md)                   | Bukti pengujian otomatis dan walkthrough UI                                            |
| [`docs/DEMO_CREDENTIALS.md`](docs/DEMO_CREDENTIALS.md)               | Akun demo dan ringkasan data seed                                                      |
| [`docs/DEVELOPMENT_PROGRESS.md`](docs/DEVELOPMENT_PROGRESS.md)       | Checklist fase pengembangan                                                            |
| [`docs/LEGACY_SYSTEM_AUDIT.md`](docs/LEGACY_SYSTEM_AUDIT.md)         | Arsip audit sistem (snapshot historis)                                                 |
| [`docs/ARCHITECTURE_NOTES.md`](docs/ARCHITECTURE_NOTES.md)           | Catatan desain awal (superseded)                                                       |

## Keamanan

- Jangan gunakan kredensial demo di lingkungan produksi; ganti seluruh kata sandi.
- Atur `APP_DEBUG=false` dan jalankan `php artisan config:cache` saat deploy ke produksi.
- Reset kata sandi menggunakan token acak Laravel yang kedaluwarsa dan sekali pakai, dengan respons permintaan yang konsisten agar keberadaan email tidak dapat ditebak.
- Unggahan logo pada menu Setup divalidasi tipe (`PNG`/`JPG`/`WEBP`) dan ukuran (maks. 2 MB), disimpan di public disk, serta otomatis menghapus file lama saat diganti. Hanya admin dengan izin `pengaturan.manage` yang dapat mengubahnya.
- Portal pelanggan membatasi akses berdasarkan `pelanggan_id` akun yang login; mengakses nota atau PDF milik pelanggan lain mengembalikan `404`, dan staf akan diarahkan ke dashboard jika membuka `/akun`.
- Aplikasi ini tidak menangani pemrosesan pembayaran; seluruh nilai transaksi berfungsi sebagai catatan operasional.

## Saran & Masukan

Kami sangat terbuka terhadap saran, masukan, maupun laporan kendala untuk pengembangan dan penyempurnaan aplikasi ini. Silakan kirimkan pesan Anda melalui email ke [eko.alfatindo@gmail.com](mailto:eko.alfatindo@gmail.com).
