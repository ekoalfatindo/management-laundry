# SYSTEM ARCHITECTURE — Laundry Management System

Dokumen ini menjelaskan arsitektur **final** aplikasi.

## 1. Tujuan dan Batasan

- Aplikasi laundry (Laravel 13 + Inertia + Svelte 5) tanpa menambah modul di luar daftar
  fitur yang ditetapkan. Portal pelanggan (lihat nota + unduh PDF) adalah fitur yang
  diminta pada 2026-11-24, bukan fitur karangan.
- Source legacy **READ-ONLY**: `D:\project\lav_svelte\_legacy_ref\Laundry`.
- Proyek baru: `D:\project\lav_svelte\laundry`.
- Tidak memakai Vue.js. Seluruh UI baru memakai Svelte 5.

## 2. Stack

| Komponen | Teknologi |
|---|---|
| PHP | 8.4.x (minimum Laravel 13: 8.3) |
| Backend | Laravel 13 (`laravel/framework ^13.17`) |
| Inertia | `inertiajs/inertia-laravel ^3.8`, `@inertiajs/svelte 3.11` |
| UI | Svelte 5 (runes), TypeScript, Vite 8 |
| Notifikasi | `sweetalert2 ^11.26` (dialog konfirmasi + toast, tema kustom di `app.css`) |
| Styling | Tailwind CSS 4 (`@tailwindcss/vite`) |
| Auth/RBAC | Session auth + `spatie/laravel-permission ^8.5` |
| PDF | `dompdf/dompdf ^3.1` (render server-side, tanpa layanan eksternal) |
| Database | MySQL 8 (Laragon) |
| Testing | PHPUnit 12 (Feature + Unit), MySQL test database |
| Package manager | Composer & npm |

Dependency tambahan di luar kebutuhan teknis di atas tidak dipakai (tidak ada library chart,
state manager, atau wrapper PDF); SweetAlert2 dipakai khusus untuk notifikasi/konfirmasi.

## 3. Struktur Backend

```
app/
├── Actions/Nota/GenerateNomorNota.php     nomor nota unik + retry
├── DTOs/NotaData.php                      data tervalidasi untuk service
├── Enums/
│   ├── JenisNota.php                      Barang Satuan | Paket Kiloan
│   └── UserRole.php                       admin | karyawan | pelanggan
├── Exceptions/DomainException.php         aturan bisnis → 422 / flash error
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthenticatedSessionController.php
│   │   ├── DashboardController.php
│   │   ├── UserController.php
│   │   ├── KaryawanController.php
│   │   ├── PelangganController.php
│   │   ├── BarangController.php
│   │   ├── KiloanController.php
│   │   ├── JenisLayananController.php
│   │   ├── NotaController.php             index/create/show/edit/update/delete/export
│   │   └── AkunController.php             portal pelanggan: index/show/pdf
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php
│   │   ├── EnsureStaff.php                pelanggan → /akun
│   │   └── EnsurePelanggan.php            staf → dashboard
│   ├── Requests/                          Form Request per operasi + authorize()
│   └── Resources/                         NotaResource, NotaBarangResource, UserResource
├── Models/                                User, Barang, Kiloan, JenisLayanan, Nota, NotaBarang
├── Policies/                              UserPolicy, NotaPolicy, BarangPolicy,
│                                          KiloanPolicy, JenisLayananPolicy
├── Providers/AppServiceProvider.php
└── Services/
    ├── DashboardService.php
    ├── NotaService.php
    ├── PricingCalculator.php
    └── UserService.php
```

Tanggung jawab layer:

| Layer | Tanggung jawab |
|---|---|
| Route | URL, middleware `auth` + `permission:*` untuk staf, `auth` + `pelanggan` untuk portal |
| Controller | Validasi lewat FormRequest, panggil service, kembalikan Inertia/redirect |
| FormRequest | Aturan validasi + `authorize()` berbasis policy |
| Service | Business logic, perhitungan, transaksi database, konsistensi |
| Action | Operasi spesifik (generate nomor nota) |
| DTO | Membawa data tervalidasi ke service |
| Policy | Otorisasi per resource |
| Resource | Bentuk data untuk props Inertia |
| Enum | Nilai domain tetap |

Template PDF berada di `resources/views/pdf/nota.blade.php` dan dirender oleh
`AkunController@pdf` memakai `Dompdf\Dompdf` langsung (A4, font Helvetica), sehingga tidak
memerlukan Node/Chromium di server.

Aturan bisnis tambahan yang gagal (mis. master tidak ditemukan) melempar `DomainException`;
`bootstrap/app.php` mengubahnya menjadi `back()->with('error', ...)` untuk request web dan
JSON `422` untuk request yang meminta JSON.

## 4. Peta Route Aktual

| Method | URI | Name | Middleware permission |
|---|---|---|---|
| GET | `/login` | login | guest |
| POST | `/login` | – | guest + throttle |
| GET | `/forgot-password` | password.request | guest |
| POST | `/forgot-password` | password.email | guest + throttle 6/menit |
| GET | `/reset-password/{token}` | password.reset | guest |
| POST | `/reset-password` | password.store | guest |
| POST | `/logout` | logout | auth |
| GET | `/` | dashboard | staff + dashboard.view |
| GET | `/akun` | akun.index | auth + pelanggan |
| GET | `/akun/nota/{nota}` | akun.nota.show | auth + pelanggan |
| GET | `/akun/nota/{nota}/pdf` | akun.nota.pdf | auth + pelanggan |
| GET/POST/PUT/DELETE | `/users...` | users.* | users.manage |
| GET/POST/PUT/DELETE | `/karyawan...` | karyawan.* | karyawan.manage |
| GET/POST/PUT/DELETE | `/pelanggan...` | pelanggan.* | pelanggan.manage |
| GET | `/barang`, `/kiloan`, `/jenis-layanan` | *.index | katalog.view |
| POST/PUT/DELETE | `/barang...`, `/kiloan...`, `/jenis-layanan...` | *.store/update/destroy | katalog.manage |
| GET | `/pengaturan` | pengaturan.edit | pengaturan.manage |
| POST | `/pengaturan` | pengaturan.update | pengaturan.manage |
| GET | `/nota` | nota.index | nota.view |
| GET | `/nota/export` | nota.export | nota.export |
| GET | `/nota/create` | nota.create | nota.create |
| POST | `/nota/kiloan` | nota.kiloan.store | nota.create |
| POST | `/nota/satuan` | nota.satuan.store | nota.create |
| GET | `/nota/{nota}` | nota.show | nota.view |
| GET | `/nota/{nota}/edit` | nota.edit | nota.update |
| PUT | `/nota/{nota}` | nota.update | nota.update |
| DELETE | `/nota/{nota}` | nota.destroy | nota.delete |

Catatan penting:

- Dashboard berada di `/` (name `dashboard`), bukan `/dashboard`. Route ini memakai middleware
  `staff`, sehingga pelanggan yang membuka `/` dialihkan ke `/akun`.
- Portal pelanggan memakai prefix `/akun` dan middleware `pelanggan`; staf yang membuka `/akun`
  dialihkan ke dashboard. Nota dan PDF di portal difilter dengan `pelanggan_id` milik akun yang
  login, sehingga nota pelanggan lain menghasilkan 404.
- Nota baru memakai dua endpoint terpisah (`/nota/kiloan`, `/nota/satuan`) agar validasi tiap
  jenis transaksi eksplisit.
- Ekspor CSV adalah `GET /nota/export` dan menerima filter yang sama dengan daftar nota
  (`search`, `jenis`, `dari`, `sampai`).

## 5. Otorisasi

12 permission (Spatie, guard `web`):

| Permission | Admin | Karyawan | Pelanggan |
|---|---|---|---|
| dashboard.view | ✓ | ✓ | – |
| users.manage | ✓ | – | – |
| karyawan.manage | ✓ | – | – |
| pelanggan.manage | ✓ | ✓ | – |
| katalog.view | ✓ | ✓ | – |
| katalog.manage | ✓ | ✓ | – |
| nota.view | ✓ | ✓ | – |
| nota.create | ✓ | ✓ | – |
| nota.update | ✓ | ✓ | – |
| nota.delete | ✓ | – | – |
| nota.export | ✓ | ✓ | – |
| pengaturan.manage | ✓ | – | – |

Kolom pelanggan berarti "tidak memiliki permission staf"; aksesnya ke data sendiri diatur oleh
middleware `pelanggan` + filter kepemilikan `pelanggan_id` di `AkunController`.

- Reset password memakai password broker Laravel: token acak di `password_reset_tokens`,
  kedaluwarsa, sekali pakai, notifikasi `ResetPassword`, dan pesan permintaan yang generik.

- Middleware `permission:*` melindungi route staf; FormRequest/Policies memeriksa aksi spesifik.
- Role `pelanggan` tidak memiliki permission staf. Pelanggan hanya memakai route `/akun`
  (middleware `pelanggan`), dan kepemilikan data ditegakkan dengan memfilter `pelanggan_id`.
- Login tunggal untuk semua peran: staf diarahkan ke dashboard, pelanggan ke `/akun`;
  pelanggan yang sudah login dan membuka `/login` dialihkan ke `/akun`, staf ke dashboard.
- `UserService` menolak penghapusan akun sendiri dan penghapusan admin terakhir.
- Pembatasan ini adalah penyesuaian keamanan: legacy hanya memeriksa login di frontend dan
  endpoint API-nya terbuka. Detail ada di `LEGACY_MIGRATION_REPORT.md`.

## 6. Business Logic

`PricingCalculator` (server-side, integer rupiah):

```
kiloan(berat, hargaPerKg, diskon):
  total    = round(berat × hargaPerKg)
  diskon   = clamp(diskon, 0, 100)
  potongan = round(total × diskon / 100)
  jumlah   = total − potongan

satuan(baris[], biayaLayanan, diskon):
  subtotal = Σ baris.total_harga        // per baris: round(harga × jumlah)
  total    = subtotal + biayaLayanan
  jumlah   = total − round(total × diskon / 100)
```

`NotaService`:

1. `create()` membuka `DB::transaction`.
2. Kiloan: ambil paket dari master, wajib `berat > 0`, snapshot `harga_per_kg`.
3. Satuan: ambil jenis layanan, minimal satu baris, ambil harga barang dari master, snapshot
   nama + harga per baris.
4. Nomor nota dibuat `#` + 3 huruf kapital + 3 alfanumerik, retry maksimal 20 kali.
5. `update()` menghitung ulang seluruh nominal dari master saat ini dan mengganti baris satuan.
6. `delete()` menghapus header; baris ikut cascade.

Snapshot & integritas:

- `notas.harga_per_kg` dan `notas.biaya_layanan` menyimpan harga saat transaksi.
- `nota_barangs.barang` + `harga` menyimpan snapshot nama/harga; `barang_id` nullable.
- FK master memakai `nullOnDelete`, sehingga menghapus master tidak menghapus riwayat nota.
- Uang disimpan sebagai `unsigned bigint`/int; berat `decimal(8,2)`.
- `App\Support\Terbilang::rupiah()` mengubah total final menjadi kata Bahasa Indonesia
  (skala ribu/juta/miliar/triliun); nilai ini ikut di `NotaResource` sebagai `terbilang`.

## 7. Frontend

```
resources/js/
├── app.ts                       createInertiaApp + mount Svelte 5
├── types/index.ts               SharedProps, entity, pagination types
├── lib/format.ts                rupiah(), number(), tanggal
├── lib/terbilang.ts             angka → kata untuk pratinjau invoice
├── lib/ui.ts                    cardClass, inputClass, thClass, buttonClass
├── lib/notify.ts                toast SweetAlert2 + dialog konfirmasi hapus
├── lib/footer.ts                teks footer global (konstanta terenkode)
├── Components/
│   ├── AppLayout.svelte         sidebar, topbar, dark mode, flash toast (SweetAlert2), tooltip logout
│   ├── CustomerLayout.svelte    header portal pelanggan + tombol keluar + flash toast
│   ├── Auth/AuthShell.svelte    latar gradient hangat + panel brand + footer kredit
│   ├── Nota/NotaForm.svelte     form kiloan/satuan + pratinjau total + terbilang
│   ├── Nota/NotaInvoice.svelte  invoice A4 dipakai staf & portal pelanggan
│   └── Ui/                      Button, Modal, FormField, Badge,
│                                Pagination, EmptyState, StatCard, MonthlyChart,
│                                PageHeader, AppFooter, Icon
└── Pages/
    ├── Auth/Login.svelte
    ├── Auth/ForgotPassword.svelte
    ├── Auth/ResetPassword.svelte
    ├── Akun/Index.svelte        ringkasan + daftar nota pelanggan
    ├── Akun/Show.svelte         detail invoice + tautan unduh PDF
    ├── Dashboard.svelte
    ├── Error.svelte             403/404/419/429/500/503
    ├── Users/Index.svelte
    ├── Karyawan/Index.svelte
    ├── Pelanggan/Index.svelte
    ├── Barang/Index.svelte
    ├── Kiloan/Index.svelte
    ├── JenisLayanan/Index.svelte
    ├── Pengaturan/Index.svelte
    └── Nota/{Index,Create,Edit,Show}.svelte
```

Prinsip UI:

- Svelte 5 runes (`$state`, `$derived`, `$effect`, `$props`); nilai awal dari props yang
  memang disalin ke state dibungkus `untrack()` agar tidak memicu warning.
- Form master memakai modal dengan error inline dari Laravel.
- Transaksi memakai halaman penuh; total klien hanya pratinjau, server menghitung ulang.
- Pencarian & pagination server-side (`10/25/50/100`).
- Sidebar responsif (drawer di layar kecil), dark mode Tailwind dengan preferensi localStorage
  dan skrip pra-render anti-flicker.
- Toast sukses/gagal dibaca dari shared prop `flash` melalui `lib/notify.ts` (SweetAlert2):
  muncul di kanan atas, progres 4,5 detik, dan menunggu dialog konfirmasi tertutup lebih dulu.
- Hapus data memakai dialog SweetAlert2 (`Hapus …`, tombol `Ya, hapus`/`Batal`, fokus awal Batal)
  yang bertema oranye/rose dan mendukung mode gelap; seluruh halaman memakai helper yang sama.
- Footer global: `AppLayout` memakai bar ringkas **fixed di dasar viewport** (selalu terlihat saat
  konten digeser, kiri mengikuti lebar sidebar dan z-index di bawah drawer/toast) berisi kredit
  pengembang, label Free to Use · Voluntary Donation, dan hak cipta; `main` diberi padding bawah
  (`pb-24`/`sm:pb-16`, `print:pb-0`) agar konten terakhir tidak tertutup.
- Teks footer (brand, tagline, kredit, label, hak cipta) hanya didefinisikan di `lib/footer.ts`
  sebagai konstanta terenkode (XOR + base64, didekode saat modul dimuat, lalu `Object.freeze`),
  sehingga tidak ditulis langsung di template dan tidak muncul sebagai string biasa di bundle.
- `CustomerLayout` memakai `Ui/AppFooter.svelte` varian `bar` yang sama dengan dashboard
  (tanpa offset sidebar, kontainer `max-w-5xl`), sehingga portal pelanggan juga memiliki footer
  fixed di dasar layar; `main` portal diberi `pb-24`/`sm:pb-16`/`print:pb-0`. `AuthShell` memakai
  varian `auth` ringkas di bawah kartu login. Varian bar memakai `.no-print` agar tidak ikut tercetak.
- Invoice A4 memakai `@page { size: A4; margin: 14mm }`, kelas `.invoice-print`, dan `.no-print`
  untuk menyembunyikan navigasi/tombol saat dicetak.
- Portal pelanggan memakai `CustomerLayout` (header ringkas tanpa sidebar staf), kartu ringkasan,
  tabel desktop + kartu mobile, dan `NotaInvoice` yang sama dengan halaman staf.
- PDF portal memakai tautan unduh biasa (`/akun/nota/{id}/pdf`) sehingga dapat disimpan/dicetak
  langsung dari penampil PDF tanpa JavaScript tambahan.
- Latar halaman login memakai gradasi hangat (radial + linear) yang selaras dengan sidebar; panel brand, fokus input, dan tombol memakai aksen oranye pada light/dark.

## 8. Shared Props dan Error Handling

`HandleInertiaRequests::share()`:

- `appName`
- `auth.user` → id, name, email, roles, permissions
- `company` → `nama_perusahaan`, `logo_url` (dari tabel `pengaturan`)
- `flash.success`, `flash.error`, `flash.status`

`bootstrap/app.php` merender komponen `Error` (status 403/404/419/429/500/503) untuk request
web penuh maupun Inertia XHR. `Error.svelte` menampilkan layout aplikasi bila pengguna
terautentikasi, atau kartu mandiri dengan tautan ke beranda bila tidak.

## 9. Database

Skema final dan perbandingan dengan legacy ada di `DATABASE_ANALYSIS.md`. Ringkas:

- Tabel domain: `users`, `barangs`, `kiloans`, `jenis_layanans`, `notas`, `nota_barangs`,
  `pengaturan`.
- `pengaturan` menyimpan satu baris `nama_perusahaan` + `logo_path` (public disk `logos`).
- Tabel wrapper legacy `karyawans`/`pelanggans` dihapus; peran memakai Spatie.
- `notas.pelanggan_id` opsional ke `users`; `nama_pelanggan` tetap snapshot teks.
- Index laporan: `waktu`, `jenis`, `nama_pelanggan`, `pelanggan_id`, `no_nota` unique.

## 10. Seed dan Demo Data

- Seeder idempoten (`updateOrCreate`, `syncRoles`, nomor nota deterministik).
- `php artisan migrate:fresh --seed` menyiapkan 15 user, 10 barang, 5 paket kiloan,
  4 jenis layanan, dan 68 nota (November 2026; jumlah bulan berjalan mengikuti tanggal seed).
- Kredensial ada di `DEMO_CREDENTIALS.md`.

## 11. Testing

- 93 test PHPUnit (437 assertion) pada database MySQL terpisah `laundry_svelte_test`, termasuk
  6 test portal pelanggan (daftar nota sendiri, detail, PDF, penolakan nota pelanggan lain).
  Pemeriksaan final 24 November 2026: 14,48 detik.
- `svelte-check` (tsgo) 0 error / 0 warning.
- `npm run build` sukses (800 modul; JS 431,59 kB / gzip 116,97 kB; CSS 97,20 kB / gzip 16,59 kB —
  `app.css` 67,68 kB + `sweetalert2.min.css` 29,52 kB).
- Walkthrough UI live per peran (admin, karyawan, pelanggan) dan pemeriksaan status/console.
  Matriks akhir: staf 403 pada halaman terlarang, pelanggan hanya `/akun` miliknya, nota/PDF
  pelanggan lain 404.
- Bukti lengkap ada di `TESTING_REPORT.md` (§4.1 untuk pemeriksaan final).

## 12. Non-Goals

Tidak diimplementasikan karena tidak ada di legacy: pembayaran/piutang, status pengerjaan,
pengeluaran, outlet, laporan laba, pengaturan toko, stok, multi-tenant, register publik, dan
toggle grid/list kosmetik.

Portal pelanggan tersedia sebagai fitur yang diminta eksplisit (2026-11-24), dengan cakupan
terbatas: melihat nota milik sendiri dan mengunduh PDF-nya. Tidak ada pembuatan nota, pembayaran,
atau perubahan data dari sisi pelanggan.

## 13. Keamanan

- Session auth + CSRF Inertia; password bcrypt; rate limit login 5 percobaan.
- Seluruh input divalidasi FormRequest; perhitungan harga tidak pernah mempercayai klien.
- Mass assignment dibatasi atribut `#[Fillable]`.
- Otorisasi server-side berlapis (route permission + policy).
- Svelte meng-escape data; tidak ada `{@html}` untuk data pengguna.
- CSV export: BOM UTF-8, escaping formula (`=`, `+`, `-`, `@`), chunk 200 baris.
- PDF portal memakai query ter-scope `pelanggan_id` akun login; membuka nota/PDF pelanggan lain
  menghasilkan 404. Nama file di-sanitasi sebelum dipakai pada `Content-Disposition`.
- Kredensial demo hanya untuk lokal; `.env` tidak di-commit.
