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
│   ├── KategoriPelanggan.php              Reguler | Member
│   ├── StatusNota.php                     Diproses | Selesai | Dibatalkan
│   ├── StatusPembayaran.php               Belum Lunas | Lunas
│   ├── StatusHadiah.php                   Tersedia | Digunakan | Kedaluwarsa | Dibatalkan
│   ├── SumberHadiah.php                   loyalitas | promosi
│   ├── TipeHadiah.php                     persen | nominal | gratis
│   └── UserRole.php                       admin | karyawan | pelanggan
├── Exceptions/DomainException.php         aturan bisnis → 422 / flash error
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                          login, lupa password, reset password
│   │   ├── DashboardController.php
│   │   ├── UserController.php
│   │   ├── KaryawanController.php
│   │   ├── PelangganController.php        index/show/store/update/destroy (kategori)
│   │   ├── LoyaltyProgramController.php   edit/update program loyalitas
│   │   ├── PromotionController.php        index/store/update/destroy promosi
│   │   ├── BarangController.php
│   │   ├── KiloanController.php
│   │   ├── JenisLayananController.php
│   │   ├── PengaturanController.php
│   │   ├── NotaController.php             index/create/show/edit/update/delete/export,
│   │   │                                  complete/cancel/setPembayaran
│   │   └── AkunController.php             portal pelanggan: index/show/pdf
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php
│   │   ├── EnsureStaff.php                pelanggan → /akun
│   │   └── EnsurePelanggan.php            staf → dashboard
│   ├── Requests/                          Form Request per operasi + authorize()
│   │   └── ValidatesNotaReward.php        validasi hadiah pada nota kiloan/satuan
│   └── Resources/                         NotaResource, NotaBarangResource, UserResource
├── Models/                                User, Barang, Kiloan, JenisLayanan, Nota,
│                                          NotaBarang, Pengaturan, LoyaltyProgram,
│                                          Promotion, CustomerReward
├── Policies/                              UserPolicy, NotaPolicy, BarangPolicy,
│                                          KiloanPolicy, JenisLayananPolicy
├── Providers/AppServiceProvider.php
├── Services/
│   ├── DashboardService.php
│   ├── NotaService.php                    create/update/delete/complete/cancel/pembayaran
│   ├── PricingCalculator.php              kiloan, satuan, potonganHadiah
│   ├── RewardService.php                  ledger hadiah: sync, reserve, release, progress
│   └── UserService.php
└── Support/
    ├── Terbilang.php                      angka → kata Bahasa Indonesia
    └── NotaExcelExport.php                workbook XLSX rapi untuk laporan nota
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
| GET | `/pelanggan/{user}` | pelanggan.show | pelanggan.manage |
| GET/PUT | `/loyalitas` | loyalitas.edit/update | loyalitas.manage |
| GET/POST/PUT/DELETE | `/promosi...` | promosi.* | promosi.manage |
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
| POST | `/nota/{nota}/selesai` | nota.complete | nota.update |
| POST | `/nota/{nota}/batal` | nota.cancel | nota.update |
| PATCH | `/nota/{nota}/pembayaran` | nota.pembayaran | nota.update |
| GET | `/nota/{nota}/edit` | nota.edit | nota.update |
| PUT | `/nota/{nota}` | nota.update | nota.update |
| DELETE | `/nota/{nota}` | nota.destroy | nota.delete |

Total **56 route aplikasi** (61 bila route internal `_inertia/devtools`, `storage`, dan `up` ikut
dihitung; route vendor dikecualikan).

Catatan penting:

- Dashboard berada di `/` (name `dashboard`), bukan `/dashboard`. Route ini memakai middleware
  `staff`, sehingga pelanggan yang membuka `/` dialihkan ke `/akun`.
- Portal pelanggan memakai prefix `/akun` dan middleware `pelanggan`; staf yang membuka `/akun`
  dialihkan ke dashboard. Nota dan PDF di portal difilter dengan `pelanggan_id` milik akun yang
  login, sehingga nota pelanggan lain menghasilkan 404.
- Nota baru memakai dua endpoint terpisah (`/nota/kiloan`, `/nota/satuan`) agar validasi tiap
  jenis transaksi eksplisit.
- Ekspor Excel adalah `GET /nota/export` dan menerima filter yang sama dengan daftar nota
  (`search`, `jenis`, `status`, `status_pembayaran`, `dari`, `sampai`). Workbook `.xlsx`
  dibangun server-side oleh `App\Support\NotaExcelExport` (Open XML: ZIP + `XMLWriter`) tanpa
  dependency tambahan.

## 5. Otorisasi

14 permission (Spatie, guard `web`):

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
| loyalitas.manage | ✓ | – | – |
| promosi.manage | ✓ | – | – |
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
- Pembatasan per peran menjadi lapisan keamanan tambahan: route staf dijaga permission, data
  pelanggan dijaga kepemilikan, dan seluruh endpoint memerlukan autentikasi.

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

potonganHadiah(total, tipe, nilai):
  persen  → round(total × min(100, nilai) / 100)
  nominal → min(total, nilai)
  gratis  → nilai > 0 ? min(total, nilai) : total
```

`RewardService` (ledger hadiah, idempoten):

1. `sync(user)` menjalankan `expire()` → `syncLoyalty()` → `syncPromotions()`.
2. Siklus loyalitas = `intdiv(jumlah nota Selesai, target)`; hanya diproses bila pelanggan
   `isMember()` dan ada program aktif.
3. Siklus promosi = `intdiv(jumlah nota Selesai dalam periode, target)` dan hanya terbit bila
   promosi aktif serta kategori pelanggan memenuhi `target_kategori`.
4. Setiap siklus membuat satu baris `customer_rewards` dengan `reward_key` deterministik
   (`LOY-…`/`PRM-…`), sehingga sinkronisasi berulang tidak menggandakan hadiah.
5. `reserve()` memakai `lockForUpdate`, memvalidasi kepemilikan dan status `Tersedia`, menghitung
   potongan, lalu menandai `Digunakan` + `nota_id` + `nilai_terpakai`.
6. `releaseFor()` mengembalikan hadiah ke `Tersedia`/`Kedaluwarsa` saat nota diubah/dibatalkan/dihapus.
7. `assertCancelable()` menolak pembatalan bila hadiah dari transaksi sumber sudah dikonsumsi nota
   lain (siklus akan turun).
8. `revokeExcess()` menandai `Dibatalkan` hadiah `Tersedia` pada siklus yang tidak lagi valid
   (mis. target dinaikkan atau transaksi dibatalkan).
9. `progress()` menyusun data progress loyalitas, seluruh promosi, dan riwayat hadiah untuk UI.

`NotaService`:

1. `create()` membuka `DB::transaction`.
2. Kiloan: ambil paket dari master, wajib `berat > 0`, snapshot `harga_per_kg`.
3. Satuan: ambil jenis layanan, minimal satu baris, ambil harga barang dari master, snapshot
   nama + harga per baris.
4. Nomor nota dibuat `#` + 3 huruf kapital + 3 alfanumerik, retry maksimal 20 kali.
5. `update()` menolak nota berstatus `Dibatalkan`, lalu melepas hadiah lama, menghitung ulang
   seluruh nominal dari master saat ini, mengganti baris satuan, dan menerapkan hadiah yang
   dipilih kembali (atau tanpa hadiah bila dikosongkan) dalam satu transaksi.
6. `delete()` menghapus header; baris ikut cascade; hadiah dilepas lebih dulu.
7. `complete()` mengubah status ke `Selesai` lalu menyinkronkan hadiah pelanggan.
8. `cancel()` menolak bila hadiah sumber sudah dikonsumsi, melepas hadiah, mencatat
   `dibatalkan_at`, dan menyinkronkan ulang hadiah.
9. `setPembayaran()` mengubah `status_pembayaran` tanpa memengaruhi hitungan hadiah (hanya
   `Selesai` yang dihitung).
10. Bila `customer_reward_id` diisi, `diskon` manual dipaksa 0 dan
    `jumlah = max(0, total_harga − potongan)`; `diskon_nominal` menyimpan potongan aktual.

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
│   ├── Nota/NotaForm.svelte     form kiloan/satuan + pratinjau total + terbilang + pilih hadiah
│   ├── Nota/PelangganCombobox.svelte  combobox pencarian pelanggan (default Pelanggan umum)
│   ├── Nota/NotaInvoice.svelte  invoice A4 dipakai staf & portal pelanggan
│   ├── Nota/NotaThermal.svelte  struk thermal 58/80 mm
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
    ├── Pelanggan/Index.svelte   daftar + filter kategori + badge
    ├── Pelanggan/Show.svelte    profil, riwayat nota, progress loyalitas & hadiah
    ├── Loyalitas/Index.svelte   konfigurasi program + ringkasan progress pelanggan
    ├── Promosi/Index.svelte     CRUD promosi + status periode/kategori
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
  untuk menyembunyikan navigasi/tombol saat dicetak. Detail nota menampilkan modal pilihan format
  sebelum mencetak: **A4**, **Thermal 80 mm**, atau **Thermal 58 mm**.
- Format thermal memakai `NotaThermal.svelte` (font 10 px/9 px, lebar 80/58 mm) dan menyuntikkan
  `@page size: 80mm <tinggi>mm` / `58mm <tinggi>mm` melalui elemen `<style id="nota-print-page-size">`
  yang dibersihkan saat komponen dilepas. Karena Chrome menolak `size: <lebar> auto`, tinggi
  halaman dihitung dari tinggi konten struk + dua margin halaman + 2 mm, lalu disinkronkan ulang
  pada `beforeprint`. Nama barang panjang membungkus; angka total tidak terpotong.
- Invoice web, A4, thermal, PDF, dan Excel memakai sumber data `NotaResource` yang sama sehingga
  total dan terbilang konsisten.
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
  `pengaturan`, `loyalty_programs`, `promotions`, `customer_rewards`.
- `users.kategori_pelanggan` (`Reguler`/`Member`) + `member_sejak`; index kategori untuk filter.
- `notas` menyimpan `status`, `status_pembayaran`, `dibatalkan_at`, `diskon_nominal`,
  `reward_label`, dan `customer_reward_id` (FK nullable ke `customer_rewards`).
- `customer_rewards` adalah ledger hadiah: `reward_key` unique, `sumber`, relasi opsional ke
  program/promosi, `siklus`, `status`, `nilai_terpakai`, `diperoleh_at`, `kedaluwarsa_at`,
  `digunakan_at`, dan `nota_id` pemakaian.
- `pengaturan` menyimpan satu baris `nama_perusahaan` + `logo_path` (public disk `logos`).
- Tabel wrapper legacy `karyawans`/`pelanggans` dihapus; peran memakai Spatie.
- `notas.pelanggan_id` opsional ke `users`; `nama_pelanggan` tetap snapshot teks.
- Index laporan: `waktu`, `jenis`, `nama_pelanggan`, `pelanggan_id`, `status`,
  `status_pembayaran`, `no_nota` unique.

## 10. Seed dan Demo Data

- Seeder akun/master idempoten (`updateOrCreate`, `syncRoles`); `NotaSeeder` **membangun ulang**
  data transaksi 6 bulan terakhir dalam satu transaksi database dengan nomor deterministik
  `#D{YY}{MM}-{NN}`, lalu menyinkronkan ledger hadiah.
- `php artisan migrate:fresh --seed` menyiapkan 15 user (5 Member, 5 Reguler), 10 barang,
  5 paket kiloan, 4 jenis layanan, 1 program loyalitas, 3 promosi, 81 nota, dan 18 riwayat hadiah
  (13 Tersedia, 2 Digunakan, 3 Kedaluwarsa) per November 2026.
- Nota demo mencakup kedua jenis, seluruh status (74 Selesai, 5 Diproses, 2 Dibatalkan), kedua
  status pembayaran, transaksi walk-in, dan dua contoh pemakaian hadiah (loyalitas + promosi).
- Kredensial ada di `DEMO_CREDENTIALS.md`.

## 11. Testing

- 141 test PHPUnit (755 assertion) pada database MySQL terpisah `laundry_svelte_test`, termasuk
  pengujian ledger hadiah, pembatalan, kedaluwarsa, pemakaian ulang, pelepasan/penggantian hadiah
  saat update nota, akurasi hadiah Member & promosi semua pelanggan, otorisasi loyalitas/promosi,
  ekspor XLSX, serta `DemoSeederTest` untuk konsistensi dan idempotensi data 6 bulan. Pemeriksaan
  terakhir 27 November 2026: 23,30 detik.
- `svelte-check` (tsgo) 0 error / 0 warning.
- `npm run build` sukses (805 modul; JS 493,73 kB / gzip 131,45 kB; CSS 101,15 kB / gzip 17,20 kB —
  `app.css` 71,63 kB + `sweetalert2.min.css` 29,52 kB).
- Walkthrough UI live per peran (admin, karyawan, pelanggan) dan pemeriksaan status/console.
  Matriks akhir: staf 403 pada halaman terlarang (termasuk loyalitas/promosi untuk karyawan),
  pelanggan hanya `/akun` miliknya, nota/PDF pelanggan lain 404; modal format cetak A4/thermal
  diverifikasi melalui computed CSS (lebar 58 mm = 219,203 px, 80 mm = 302,359 px).
- Database demo dan test dibangun ulang dari nol pada 27 November 2026 (`DROP`/`CREATE`,
  `migrate --seed`, 17 migrasi + 10 seeder) hingga baseline seeder; hanya kedua database proyek
  tersebut yang disentuh.
- Bukti lengkap ada di `TESTING_REPORT.md` (§4.5 walkthrough terakhir, §4.6 rebuild database).

## 12. Non-Goals

Tidak diimplementasikan: gateway pembayaran/piutang, pengeluaran operasional, multi-outlet,
laporan laba-rugi, manajemen stok, multi-tenant, register publik, dan toggle grid/list kosmetik.

Fitur yang tersedia sebagai bagian inti aplikasi: kategori pelanggan, program loyalitas Member,
promosi umum, status nota/pembayaran, kupon hadiah sekali pakai, Setup identitas perusahaan,
invoice A4, thermal 58/80 mm, dan portal pelanggan (lihat nota sendiri + unduh PDF). Portal
pelanggan tidak menyediakan pembuatan nota, pembayaran, atau perubahan data.

## 13. Keamanan

- Session auth + CSRF Inertia; password bcrypt; rate limit login 5 percobaan.
- Seluruh input divalidasi FormRequest; perhitungan harga tidak pernah mempercayai klien.
- Mass assignment dibatasi atribut `#[Fillable]`.
- Otorisasi server-side berlapis (route permission + policy).
- Svelte meng-escape data; tidak ada `{@html}` untuk data pengguna.
- Ekspor Excel: workbook `.xlsx` native; seluruh teks ditulis sebagai `inlineStr` sehingga nilai
  seperti `=`, `+`, `-`, atau `@` tidak dieksekusi sebagai formula; query diproses chunk 500 baris.
- PDF portal memakai query ter-scope `pelanggan_id` akun login; membuka nota/PDF pelanggan lain
  menghasilkan 404. Nama file di-sanitasi sebelum dipakai pada `Content-Disposition`.
- Hadiah divalidasi server-side: kepemilikan pelanggan, status `Tersedia`, dan satu hadiah per nota.
  `reserve()` memakai `lockForUpdate` agar dua transaksi bersamaan tidak memakai hadiah yang sama.
- Kredensial demo hanya untuk lokal; `.env` tidak di-commit.
