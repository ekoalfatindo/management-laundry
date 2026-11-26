# LEGACY SYSTEM AUDIT — Laundry Management System

Tanggal audit: 2026-11-24
Auditor: Qoder (Senior Software Architect / Legacy Migration Specialist)

## 1. Ringkasan Eksekutif

Aplikasi lama adalah **Laundry Management System** internal (staff-only) yang dibangun dengan
**Laravel 10 + Sanctum** sebagai API dan **Quasar v2 (Vue 3) + Pinia** sebagai SPA.
Aplikasi berfokus pada:

1. Master data operasional: pelanggan, karyawan, user, barang satuan, paket kiloan, jenis layanan.
2. Pencatatan transaksi (nota) dengan dua model: **Barang Satuan** dan **Paket Kiloan**.
3. Perhitungan harga, diskon persen, dan total bersih.
4. Dashboard ringkas: total pendapatan, jumlah nota, jumlah user/paket, grafik pendapatan bulanan.
5. Cetak nota (struk 80 mm) dan ekspor daftar nota ke Excel.

Tidak ditemukan modul pembayaran/piutang, status pengerjaan, pengeluaran, outlet, atau laporan
keuntungan. Modul-modul tersebut **tidak boleh dikarang** pada aplikasi baru; akan didokumentasikan
sebagai ketiadaan fitur.

## 2. Lokasi Source Code

| Peran | Lokasi | Status |
|---|---|---|
| Arsip asli (legacy) | `D:\Programmer\code_for_learn\laravel-vue-dari-pembelian\laundry.zip` | READ-ONLY, tidak disentuh |
| Salinan referensi hasil ekstrak | `D:\project\lav_svelte\_legacy_ref\Laundry\` | READ-ONLY (hanya dibaca) |
| Proyek baru | `D:\project\lav_svelte\laundry\` | Direktori kerja proyek baru |

Backend lama: `_legacy_ref/Laundry/laundry-be`.
Frontend lama: `_legacy_ref/Laundry/laundry-fe` (Quasar).

## 3. Stack Legacy

| Komponen | Versi / Keterangan |
|---|---|
| PHP | ^8.1 |
| Laravel | ^10.10 |
| Auth API | Laravel Sanctum (personal access token) |
| Database | MySQL (nama DB tidak disertakan di arsip) |
| Frontend | Quasar v2, Vue 3, Pinia, vue-router (hash mode) |
| Chart | ECharts + vue-echarts |
| Export | SheetJS `xlsx` (client-side) |
| HTTP | Axios, `withCredentials`, memanggil `https://be.camelia.laundry.winecoders.com` |

## 4. Struktur Aplikasi Lama

### 4.1 Backend (ringkas)

```
app/
├── Http/
│   ├── Controllers/  Auth, User, Karyawan, Pelanggan, Barang, Kiloan, JenisLayanan, Nota, NotaBarang(empty)
│   └── Resources/    User, Karyawan, Pelanggan, Barang, Kiloan, JenisLayanan, Nota, NotaBarang, Chart, Pie
├── Models/           User, Karyawan, Pelanggan, Barang, Kiloan, JenisLayanan, Nota, NotaBarang
routes/api.php        seluruh endpoint (prefix /api)
database/migrations/  4 tabel bawaan Laravel + 7 tabel domain
database/seeders/     User, Karyawan, Pelanggan, Barang, Kiloan, JenisLayanan, Nota(kosong)
```

Tidak ada Service layer, Form Request, Policy, enum, event, job, scheduler, atau middleware
otorisasi. Controller memegang validasi inline (`$request->validate`) dan query langsung.

### 4.2 Frontend (ringkas)

```
src/
├── boot/        auth.js (token), role.js (role), axios.js, rupiah.js
├── layouts/     MainLayout (landing), AuthLayout, DashboardLayout (sidebar + dark mode)
├── pages/
│   ├── auth/AuthPage.vue                  login + register (animasi)
│   ├── dashboard/IndexDashboardPage.vue   KPI + grafik + tabel nota terbaru
│   └── dashboard/{user,karyawan,pelanggan,barang,kiloan,jenislayanan,nota}/
├── stores/      Pinia store per entitas (memanggil API)
└── router/      route guard token + role (frontend-only)
```

## 5. Modul dan Fitur yang Ditemukan

| Modul | Fitur | Implementasi |
|---|---|---|
| Autentikasi | Login, register, profil, logout (token Sanctum) | `AuthController`, `AuthPage.vue`, `boot/auth.js` |
| Otorisasi | Role string `Admin` / `Karyawan` / `Pelanggan`, guard di frontend | `boot/role.js`, `DashboardLayout.vue` |
| Dashboard | KPI total pendapatan, total penjualan, total user, total paket; grafik pendapatan bulanan; tabel nota terbaru | `IndexDashboardPage.vue`, `NotaController@chart` |
| User | CRUD user (admin) | `UserController`, `pages/dashboard/user/*` |
| Karyawan | CRUD karyawan (admin), membuat user + baris `karyawans` | `KaryawanController`, `pages/dashboard/karyawan/*` |
| Pelanggan | CRUD pelanggan, membuat user + baris `pelanggans` | `PelangganController`, `pages/dashboard/pelanggan/*` (tanpa menu) |
| Barang Satuan | CRUD barang + harga satuan | `BarangController`, `pages/dashboard/barang/*` |
| Paket Kiloan | CRUD paket + harga per kg + deskripsi | `KiloanController`, `pages/dashboard/kiloan/*` |
| Jenis Layanan | CRUD layanan (Reguler/Cepat/Kilat/…) + biaya layanan + estimasi waktu | `JenisLayananController`, `pages/dashboard/jenislayanan/*` |
| Nota | Buat nota kiloan, buat nota satuan (multi baris), daftar + cari + pagination + grid/list + ekspor Excel + hapus, detail + cetak struk | `NotaController`, `pages/dashboard/nota/*` |
| Statistik | Grafik garis pendapatan per bulan; endpoint pie jumlah barang terjual (tidak dipakai UI) | `NotaController@chart`, `@pie` |

## 6. Business Logic Aktual

### 6.1 Nota Paket Kiloan
- Input: nama pelanggan (teks bebas), paket kiloan, berat (kg), waktu, diskon (%).
- `total_harga = berat × kiloan.harga`
- `jumlah = total_harga − (total_harga × diskon / 100)`
- Disimpan dengan `jenis = "Paket Kiloan"`, `jenis_layanan_id = null`, `kiloan_id = paket`.

### 6.2 Nota Barang Satuan
- Input: nama pelanggan (teks bebas), jenis layanan, waktu, baris barang (barang, jumlah), diskon (%).
- Harga baris diambil dari master `barangs.harga`; `total baris = harga × jumlah_barang`.
- `total_harga = Σ(total baris) + jenis_layanan.harga` (biaya layanan selalu ditambahkan).
- `jumlah = total_harga − (total_harga × diskon / 100)`
- Disimpan dengan `jenis = "Barang Satuan"`, `kiloan_id = null`; baris disimpan ke `nota_barangs`
  (nama barang berupa string, bukan foreign key).

### 6.3 Nomor Nota
- Format `#` + 3 huruf kapital acak + 3 karakter alfanumerik acak (contoh `#ABC1x2`).
- Dicek unik dengan loop retry.

### 6.4 Dashboard
- Total pendapatan = `SUM(notas.jumlah)` (memakai nilai bersih setelah diskon).
- Grafik pendapatan bulanan = `SUM(jumlah)` dikelompokkan berdasarkan **bulan `created_at`**, bukan `waktu`.
- Total penjualan = jumlah baris nota.
- Total user = jumlah user; total paket = jumlah paket kiloan.
- Tabel "Penjualan Terbaru" = seluruh nota (komponen `IndexNota`).

### 6.5 Nota List & Cetak
- Daftar nota tanpa pagination server (semua baris diambil `latest()`), pencarian/pagination di client.
- Ekspor Excel berisi: No, Nomor Nota, Waktu, Jenis, Jumlah Awal (nilai `jumlah`), Diskon %, Total Harga.
- Detail nota menampilkan struk 300 px dan tombol print yang membuka window baru dengan CSS `80mm`.

## 7. Hak Akses Lama

| Halaman | Admin | Karyawan | Pelanggan |
|---|---|---|---|
| Landing `/` | ya | ya | ya |
| Login/Register `/auth` | ya | ya | ya |
| Dashboard `/dashboard/*` | ya | ya | tidak |
| Menu User | ya | tidak | tidak |
| Menu Karyawan | ya | tidak | tidak |
| Menu Barang/Paket/Layanan/Nota | ya | ya | tidak |
| Menu Pelanggan | **tidak ada menu** (route ada) | tidak ada menu | tidak |

Catatan: register publik menghasilkan akun role `Pelanggan` yang **tidak memiliki halaman dashboard**,
sehingga fitur register tidak memiliki kegunaan operasional.

## 8. Integrasi

- **Sanctum SPA**: frontend memanggil `GET /sanctum/csrf-cookie` sebelum request, token Bearer
  disimpan di `localStorage`.
- **Base URL** di-hardcode ke domain produksi lama (`https://be.camelia.laundry.winecoders.com`).
- **ECharts** untuk grafik dashboard; **SheetJS** untuk ekspor nota.
- **Seluruh resource API tidak memakai middleware auth** (hanya profil/logout yang memakai
  `auth:sanctum`) — endpoint CRUD dapat diakses tanpa login.

## 9. Temuan dan Potensi Masalah (Legacy)

| # | Severity | Temuan | Dampak |
|---|---|---|---|
| 1 | Kritis | Endpoint CRUD (`user`, `karyawan`, `pelanggan`, `barang`, `kiloan`, `jenislayanan`, `nota`) tanpa middleware autentikasi/otorisasi | Data dapat dibaca/diubah siapa pun yang tahu URL |
| 2 | Kritis | Total harga, diskon, dan jumlah dihitung di client lalu dipercaya server | Manipulasi harga/nota |
| 3 | Tinggi | `Nota::barangs()` = `hasMany(Barang::class)` tanpa foreign key; `update()` memakai `sync()` pada relasi yang tidak ada | Relasi salah, edit nota rusak |
| 4 | Tinggi | `UserController@store`, `KaryawanController@store`, `PelangganController@store` mengirim `passwordConfirmation` (dan `role`) ke `User::create` dengan `$guarded = ['id']` | Kemungkinan besar error SQL kolom tidak ada |
| 5 | Tinggi | `NotaController@update` memvalidasi `barangs` wajib ada dan mencari jenis layanan tanpa null-check | Update nota kiloan gagal / error 500 |
| 6 | Tinggi | Perhitungan diskon tidak dibatasi 0–100; angka negatif/berlebih diterima | Total bersih tidak valid |
| 7 | Sedang | `karyawans` dan `pelanggans` hanya berisi `user_id` (tabel wrapper 1:1) | Duplikasi informasi role |
| 8 | Sedang | Nota tidak terhubung ke master pelanggan (hanya teks `nama_pelanggan`) | Tidak ada histori per pelanggan |
| 9 | Sedang | Dashboard memakai `created_at`, bukan `waktu` transaksi | Pendapatan bulanan bisa salah saat input backdate |
| 10 | Sedang | Tidak ada pagination server-side | Performa menurun saat data besar |
| 11 | Sedang | Register publik menghasilkan akun Pelanggan tanpa halaman | Akun tidak terpakai |
| 12 | Rendah | `removeRow()` di form satuan memakai `pop()` (menghapus baris terakhir, bukan baris dipilih) | Salah hapus baris |
| 13 | Rendah | Seeder `KiloanSeeder` membuat paket kembar; angka "Total Paket" sempat hardcode 82 | Data/UI tidak konsisten |
| 14 | Rendah | Tidak ada index tambahan (hanya unique/FK bawaan) | Query laporan melambat |
| 15 | Rendah | Tidak ada rate limit login, tidak ada verifikasi email | Risiko brute force |
| 16 | Rendah | `no_nota` hanya 6 karakter acak; keunikan dicek retry | Risiko tabrakan rendah tetapi ruang kecil |
| 17 | Rendah | Frontend menyimpan token & role di `localStorage` | Rentan XSS |
| 18 | Info | Modal cetak mengambil CSS Quasar dari CDN | Cetak bergantung internet |

## 10. Fitur yang TIDAK Ada di Legacy (tidak akan dikarang)

- Pembayaran/record pembayaran, status lunas/belum lunas, piutang, pelunasan.
- Status pengerjaan laundry (diterima/dicuci/disetrika/selesai/diambil).
- Pengeluaran operasional.
- Outlet/cabang.
- Laporan laba/keuntungan, laporan pengeluaran.
- Pengaturan aplikasi (nama toko, alamat, logo, pajak).
- Manajemen stok.
- Portal pelanggan.
- Multi-tenant / subscription.

Aplikasi baru mempertahankan fitur aktual dan **tidak** menambahkan modul di atas. Ketiadaan ini
dicatat pada `FEATURE_INVENTORY.md` dan `LEGACY_MIGRATION_REPORT.md`.

## 11. Keputusan Pengembangan (ringkas)

1. Backend Laravel 13, frontend Svelte 5 + Inertia + Tailwind 4, MySQL, Spatie Permission.
2. Service layer untuk seluruh business logic; controller tipis; Form Request; Policy; enum status jenis nota.
3. Seluruh perhitungan harga/diskono dihitung ulang di server (client hanya pratinjau).
4. Otorisasi nyata di server: session auth Inertia + permission per modul.
5. Normalisasi ringan: hapus tabel wrapper `karyawans`/`pelanggans` (diganti role Spatie);
   `nota_barangs.barang_id` ditambahkan (nama disimpan sebagai snapshot); `notas` mendapat relasi
   pelanggan opsional + index laporan.
6. Pertahankan dua jenis nota, diskon persen, format nomor nota, struk cetak, dashboard, dan ekspor.
7. Register publik dihapus karena legacy tidak menyediakan area pelanggan; akun dibuat oleh admin.
8. Ekspor nota memakai XLSX native (Open XML) alih-alih SheetJS; tetap menyertakan kolom yang
   sama dengan tata letak laporan.
