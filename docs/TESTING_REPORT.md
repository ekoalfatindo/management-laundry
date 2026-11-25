# TESTING REPORT — Laundry Management System

Tanggal eksekusi: **2026-11-24**
Lingkungan: Windows 10, PHP 8.4.27 (NTS), MySQL 8.0.30 (Laragon), Node 24.18.0, Vite 8.3.8.
Database aplikasi: `laundry_svelte`; database test: `laundry_svelte_test`.

Tidak ada klaim pengujian tanpa eksekusi. Seluruh hasil di bawah berasal dari perintah yang
benar-benar dijalankan pada sesi ini.

## 1. Ringkasan Hasil

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| Type & aksesibilitas Svelte/TS | `npx svelte-check --tsgo --tsconfig ./tsconfig.json` | **0 error, 0 warning** |
| Build produksi | `npm run build` | **Sukses** (800 modul; JS 431,59 kB / gzip 116,97 kB; CSS 97,20 kB / gzip 16,59 kB — `app.css` 67,68 kB + `sweetalert2.min.css` 29,52 kB) |
| Test backend | `php artisan test` | **93 test lulus, 437 assertion**, 14,48 detik |
| Daftar route | `php artisan route:list --except-vendor` | **46 route aplikasi** (di luar route vendor) |
| Migrasi + seeder baru | `php artisan migrate --force`, `db:seed --class=RolePermissionSeeder`, `db:seed --class=PengaturanSeeder` | Sukses |
| Storage publik | `php artisan storage:link` | Sukses (logo Setup dapat diakses lewat `/storage`) |
| Verifikasi data demo | query hitung via Tinker | 15 user, 10 barang, 5 kiloan, 4 layanan, 68 nota, 63 baris nota, 3 role, 12 permission, 1 baris pengaturan |
| Walkthrough UI live | Chrome headless + CDP, viewport 1440 × 900 dan 390 × 844 | Lulus (detail §4) |
| Console browser | listener `Runtime.exceptionThrown` + `console.error` | **0 error, 0 exception** |

## 2. Cakupan Test Otomatis

76 method test menjadi 93 test setelah data provider PHPUnit:

| File | Jumlah | Cakupan |
|---|---|---|
| `tests/Feature/AuthTest.php` | 9 | Halaman login, login admin/karyawan, pelanggan masuk ke portal `/akun`, password salah, rate limit 5 percobaan, guest redirect, logout, pelanggan login dialihkan dari `/login` |
| `tests/Feature/AkunTest.php` | 6 | Daftar hanya nota milik sendiri (urutan, meta, ringkasan total), detail milik sendiri, detail pelanggan lain 404, PDF milik sendiri `200` + `%PDF`, PDF pelanggan lain 404, staf di `/akun` dialihkan ke dashboard |
| `tests/Feature/AuthorizationTest.php` | 6 | Matriks akses karyawan/pelanggan, 403 Inertia merender `Error`, hanya admin boleh hapus nota, karyawan boleh kelola pelanggan |
| `tests/Feature/MasterDataTest.php` | 10 | CRUD barang/kiloan/jenis layanan, validasi, unique, user view-only, filter/pagination, flash prop Inertia |
| `tests/Feature/NotaTest.php` | 14 | Harga kiloan & satuan, validasi, update snapshot, ganti baris, cascade hapus, snapshot saat master dihapus, filter, pagination, CSV (BOM + formula guard), izin ekspor, resource detail + **terbilang** |
| `tests/Feature/UserManagementTest.php` | 8 | Buat/ubah user + role, email duplikat, password confirmation/minimum, larangan hapus akun sendiri, karyawan mengelola pelanggan, filter role |
| `tests/Feature/PengaturanTest.php` | 8 | Halaman Setup, ubah nama, unggah/ganti logo (logo lama dihapus), tolak PDF, nama wajib, karyawan 403, guest redirect, identitas dibagikan ke Inertia |
| `tests/Feature/ForgotPasswordTest.php` | 8 | Halaman lupa password, notifikasi terkirim, email tak terdaftar tetap pesan sama, halaman reset, reset + login password baru, token sekali pakai, token salah, validasi panjang/konfirmasi |
| `tests/Unit/PricingCalculatorTest.php` | 5 (+ data provider) | Pembulatan kiloan, diskon, biaya layanan satuan, clamp diskon 0–100 |
| `tests/Unit/TerbilangTest.php` | 16 | 15 nominal (0, 12, 25, 100, 125, 1.000, 10.000, 25.000, 125.000, 1.500.000, 2.000.000, 1 miliar, 999, 1.000.001) + normalisasi nilai negatif |

Contoh perintah:

```bash
php artisan test --filter=TerbilangTest
# PASS — 16 test, 16 assertion
```

## 3. Bukti Perilaku Kritis

1. **Perhitungan server-side** — test nota kiloan: 3,5 kg × Rp20.000, diskon 10% menghasilkan
   `total_harga = 70.000` dan `jumlah = 63.000`; test nota satuan: subtotal baris + biaya
   layanan Rp15.000, diskon 10% menghasilkan `total_harga = 50.000` dan `jumlah = 45.000`.
   Nilai kiriman klien tidak dipakai (dihitung ulang dari master).
2. **Snapshot harga** — setelah harga master diubah/dihapus, struk lama tetap memakai
   `harga_per_kg`, `biaya_layanan`, nama, dan harga baris saat transaksi.
3. **Terbilang** — `App\Support\Terbilang` diuji untuk nol, belasan, seratus/seribu, juta,
   miliar, sisa di atas juta, dan nominal negatif; detail nota mengembalikan
   `terbilang = "Empat puluh lima ribu rupiah"` (assertion di `NotaTest`). Implementasi TypeScript
   (`resources/js/lib/terbilang.ts`) memakai aturan yang sama untuk pratinjau langsung di form.
4. **Otorisasi** — karyawan mendapat 403 pada `/users`, `/karyawan`, dan `/pengaturan`; pelanggan
   yang membuka halaman staf (mis. `/nota`) mendapat 403 dan diarahkan ke `/akun` saat membuka `/`;
   tombol hapus nota tidak dirender untuk karyawan (tidak punya `nota.delete`). Staf yang membuka
   `/akun` dialihkan ke dashboard.
5. **Reset password** — `Password::sendResetLink` mengirim notifikasi; email tak terdaftar tetap
   menerima pesan generik tanpa notifikasi; token hanya berlaku sekali; password baru langsung
   dapat dipakai login; password < 8 karakter atau konfirmasi berbeda ditolak.
6. **Upload logo** — `Storage::fake('public')` membuktikan file tersimpan di `logos`, logo lama
   dihapus saat diganti, PDF ditolak validasi, dan nama perusahaan wajib diisi.
7. **Ekspor CSV** — respons `200`, `Content-Type: text/csv; charset=UTF-8`,
   `Content-Disposition: attachment; filename=laporan-nota-20261124-130954.csv`,
   byte awal `EF BB BF` (BOM), 69 baris (1 header + 68 nota), formula berbahaya diberi prefix `'`.
8. **Error page** — akses `/users` sebagai karyawan menampilkan halaman “Akses ditolak”
   (Error 403) di dalam layout aplikasi; URL tidak dikenal menampilkan Error 404; guest yang
   membuka `/nota` diarahkan ke `/login`; pelanggan yang membuka `/nota` mendapat 403.
9. **Portal pelanggan & PDF** — login `ayu@laundry.test` diarahkan ke `/akun`; props hanya memuat
   7 nota miliknya dengan ringkasan 7 nota / Rp 1.287.375; detail menampilkan invoice tanpa tombol
   edit/hapus; `/akun/nota/{id}/pdf` mengembalikan `200`, `Content-Type: application/pdf`,
   `Content-Disposition: attachment; filename=nota-D2610-02.pdf`, 8.334 byte, diawali `%PDF`.
   Test otomatis membuktikan nota/PDF pelanggan lain menghasilkan 404.

## 4. Walkthrough UI Live

Verifikasi antarmuka dilakukan di Chrome headless melalui Chrome DevTools Protocol (CDP) pada
aplikasi yang benar-benar berjalan (Laravel + Vite dev). Viewport utama **1440 × 900** (light),
tambahan **390 × 844** untuk mobile; media print diemulasikan dengan `Emulation.setEmulatedMedia`.
Aksi dilakukan di DOM asli (`click()`, event `input`, `DOM.setFileInputFiles`), bukan simulasi server.

| Skenario | Bukti / hasil |
|---|---|
| Tema login menyatu dengan sidebar | `.auth-shell` memakai radial orange + `linear-gradient(rgb(255,250,244), rgb(253,242,230), rgb(247,232,215))`; tombol `brand` bergradasi orange-500/600/amber-700; dark mode memakai panel kartu hangat; 0 console error; tanpa overflow horizontal pada 390 px |
| Judul & brand login | `Masuk ke akun · Laundry Management`; brand panel “Laundry Management” |
| Tautan “Lupa password?” | Ada dan menuju `/forgot-password` |
| Kirim tautan reset | Setelah submit `admin@laundry.test`, muncul “Jika email terdaftar, tautan pengaturan ulang kata sandi telah dikirim.” |
| Halaman reset password | `/reset-password/{token}?email=...` menampilkan email terisi, kata sandi baru + konfirmasi |
| Label menu sidebar | Dashboard, Nota Transaksi, **Harga Per Barang**, **Harga Paket Kiloan**, Jenis Layanan, Karyawan, Pelanggan, Pengguna, **Setup** |
| Akses Setup | Menu tampil untuk admin dan halaman `/pengaturan` terbuka |
| Ikon Paket Kiloan | `<svg>` pada menu kiloan memakai ikon `washer` (bukan emoji), ukuran/stroke konsisten |
| Tooltip Logout | Teks “Logout”, `visibility: visible`, `opacity: 1` saat hover dan saat fokus keyboard; posisi di atas tombol dan seluruh area berada di viewport (`top 809–833`, `left 216–271`) |
| Setup: upload logo | `DOM.setFileInputFiles` → pratinjau `blob:` 1024 × 1024 tampil; submit → flash “Informasi perusahaan berhasil disimpan.”; pratinjau server `/storage/logos/...png`; logo muncul di sidebar |
| Setup: persistensi | Muat ulang halaman menampilkan logo dari public disk; nama perusahaan tetap “Laundry Management” |
| Invoice | Header menampilkan logo + nama perusahaan dari Setup; nomor invoice, pelanggan, jenis layanan, tabel item, subtotal, diskon, total `Rp 97.000`, terbilang “Sembilan puluh tujuh ribu rupiah” |
| Media print | `.no-print` (`display: none`), lebar dokumen 808 px (A4), warna teks `rgb(17,17,17)`, latar transparan/putih |
| Mobile 390 px Setup | `scrollWidth = 390`, tidak ada overflow horizontal, tidak ada alert error |
| Toggle sidebar | Lebar 288 → 80 px berfungsi |
| Portal pelanggan: login | Login `ayu@laundry.test` → `/akun`; header “Portal Pelanggan”, “Halo, Ayu Lestari”, tombol Keluar |
| Portal pelanggan: daftar | 7 baris nota (milik Ayu), kartu ringkasan **7 nota** dan **Rp 1.287.375**, tombol Lihat + PDF per baris |
| Portal pelanggan: detail | `/akun/nota/59` menampilkan invoice `#D2610-02` dengan tombol “Unduh PDF”; tidak ada tombol edit/hapus |
| Portal pelanggan: PDF | Unduh `nota-D2610-02.pdf` (8.334 byte, `%PDF`); dirender di Chrome non-headless dan tampil rapi: kop perusahaan, rincian item, total, terbilang |
| Portal pelanggan: mobile 390 px | `scrollWidth = 390`, tidak ada overflow horizontal, kartu daftar tampil menggantikan tabel |
| Portal pelanggan: batas akses | Staf membuka `/akun` → dashboard; pelanggan membuka `/nota` → 403; nota pelanggan lain → 404 |
| Logout | Klik tombol → `/login`; akses `/` setelah logout kembali diarahkan ke `/login` |
| Console browser | 0 exception, 0 `console.error` di seluruh alur (login staf/pelanggan, lupa/reset, Setup, invoice, PDF, portal, print, logout) |

Catatan: dialog cetak fisik tidak dibuka; verifikasi print dilakukan pada media emulasi CSS.
PDF diperiksa dengan mengunduh berkas dari aplikasi dan membukanya di Chrome non-headless
(headless tidak menyediakan penampil PDF). Pengiriman email nyata tidak dilakukan karena
`MAIL_MAILER=log`; alur notifikasi diuji otomatis dengan notification fake, sedangkan tautan reset
diverifikasi melalui halaman reset.

### 4.1 Pemeriksaan final semua fitur (24 November 2026)

Pemeriksaan ulang dilakukan pada aplikasi yang berjalan (PHP dev server + Vite dev) memakai
otomasi browser setelah build produksi dan seluruh test otomatis lulus.

| Peran | Hasil status |
|---|---|
| Admin | `/`, `/nota`, `/nota/create`, `/nota/65`, `/nota/65/edit`, `/users`, `/karyawan`, `/pelanggan`, `/barang`, `/kiloan`, `/jenis-layanan`, `/pengaturan`, `/nota/export` → **200**; `/akun` dan `/akun/nota/65` → redirect ke dashboard |
| Karyawan | `/`, `/nota`, `/nota/create`, `/nota/65`, `/nota/65/edit`, `/pelanggan`, `/barang`, `/kiloan`, `/jenis-layanan`, `/nota/export` → **200**; `/users`, `/karyawan`, `/pengaturan` → **403**; seluruh `/akun*` → redirect ke dashboard |
| Pelanggan (`ayu@laundry.test`) | `/akun` dan `/akun/nota/59` → **200**; `/akun/nota/59/pdf` → **200**, `application/pdf`, `attachment; filename="nota-D2610-02.pdf"`, 8.334 byte, diawali `%PDF-1.7`; nota/PDF pelanggan lain (`/akun/nota/65`, `/akun/nota/65/pdf`) → **404**; `/` → redirect ke `/akun`; seluruh halaman staf → **403** |
| Guest | `/` dan `/akun` → redirect ke `/login`; `/login` dan `/forgot-password` → **200**; URL tidak dikenal → **404** |

- Daftar nota menampilkan 10 baris per halaman; filter valid (`search` ≤ 100 karakter,
  `Paket Kiloan`, rentang tanggal, `per_page=50`) → **200**. Input `search` 120 karakter dan
  `jenis` yang tidak valid ditolak validasi (redirect), bukan error 500.
- Login salah menampilkan pesan inline “Email atau kata sandi yang dimasukkan salah.”
- Console browser hanya berisi pesan debug Vite (`vite connecting/connected`), **0 error**;
  tidak ada respons 500 pada log server setelah pemeriksaan dimulai (18:00).
- Setelah pemeriksaan, build produksi dijalankan ulang (25 November 2026): **800 modul**, JS 431,59 kB (gzip 116,97 kB),
  CSS 97,20 kB (gzip 16,59 kB; `app.css` 67,68 kB + `sweetalert2.min.css` 29,52 kB), selesai dalam 1,79 detik.

### 4.2 Notifikasi SweetAlert2 (24 November 2026)

Verifikasi live di Chrome pada aplikasi build produksi (`http://127.0.0.1:8000`), memakai
MutationObserver untuk memastikan elemen benar-benar masuk DOM.

| Skenario | Hasil |
|---|---|
| Dialog konfirmasi hapus | `role="dialog"`, `aria-modal="true"`, `aria-describedby` terisi; judul “Hapus Barang/Pelanggan/Nota”, pesan kontekstual, tombol `Ya, hapus` + `Batal` dengan fokus awal **Batal** |
| Batal | Dialog tertutup, tidak ada request hapus, tidak ada toast, baris tetap ada |
| Konfirmasi hapus (Barang, Pelanggan) | Request DELETE 200 → redirect 200; toast sukses “Barang/Pelanggan berhasil dihapus.” muncul ±0,8 detik setelah konfirmasi; baris hilang; tetap tampil walau halaman berganti ke EmptyState |
| Simpan pelanggan baru | Toast sukses “Pelanggan berhasil ditambahkan.” tampil di kanan atas dengan ikon centang dan progress bar 4,5 detik |
| Mode gelap | Toast gelap (`slate-900`), aksen kiri hijau (sukses), teks terang — kontras terbaca |
| Toaster saat dialog masih menutup | Toast menunggu dialog hilang (retry maks. 40 × 150 ms) sebelum tampil, tidak bertumpuk dengan dialog |
| Console browser | 0 error / 0 exception selama seluruh alur dialog, toast, dan hapus |
| Data uji | Seluruh record sementara (`Uji Toast …`) dihapus kembali; seed data tidak berubah |

### 4.3 Footer global (diperbarui 25 November 2026)

`AppLayout` (dashboard dan seluruh halaman staf) dan `CustomerLayout` (portal pelanggan) sama-sama
memakai bar footer `Ui/AppFooter.svelte` varian `bar` yang **fixed di dasar viewport**; di dashboard
kiri bar mengikuti lebar sidebar, sedangkan portal memakai kontainer `max-w-5xl` tanpa offset.
`AuthShell` (login, lupa/reset password) memakai varian `auth` di bawah kartu. Seluruh teks footer
dibaca dari konstanta `FOOTER` di `resources/js/lib/footer.ts`
(disimpan XOR + base64, didekode saat modul dimuat, lalu dibekukan dengan `Object.freeze`), sehingga
tidak ditulis langsung di template maupun muncul sebagai string biasa di bundle. Verifikasi live
dilakukan pada aplikasi yang berjalan (PHP dev server + Vite dev); build produksi tetap dijalankan
ulang dan hasil ukurannya dicatat di §1.

| Skenario | Hasil |
|---|---|
| Login mode terang | Footer tampil di bawah kartu pada latar gradasi hangat: ikon + brand “Management Laundry”, tagline, “Developed with ❤️ by Eko Subianto”, pill `Free to Use` · `Voluntary Donation`, dan “© 2026 Management Laundry. All Rights Reserved.” |
| Login mode gelap | Teks orange-100 dengan pill beraksen oranye; kontras terbaca |
| Login layar kecil (531 px) | `scrollHeight` 773 px dan footer dapat digulir penuh — wrapper `my-auto` mencegah konten terpotong |
| Dashboard (fixed) | `position: fixed`, `bottom: 0`, `z-index: 20`, tinggi 83 px; `getBoundingClientRect()` identik (top 398 / bottom 481) pada `scrollY` 0, 1.101, dan 1.721 — tidak ikut bergeser saat digulir |
| Dashboard (teks) | Hanya “Developed with ❤️ by Eko Subianto · Free to Use · Voluntary Donation · © 2026 Management Laundry. All Rights Reserved.” (brand/tagline dipindahkan keluar dari bar) |
| Dashboard (clearance) | Saat `scrollY` maksimum, `main.bottom` = 481 px dengan `padding-bottom` 96 px sehingga konten terakhir berakhir ±385 px, di atas tepi footer 398 px |
| Halaman staf lain (`/barang`) | Footer fixed juga tampil konsisten; konten pagination tetap dapat digulir penuh tanpa tertutup |
| Perlindungan teks | Tidak ada literal footer di template; pencarian “Voluntary Donation”, “Eko Subianto”, “All Rights Reserved”, “Solusi digital”, dan “Developed with” pada `public/build/assets/*.js`/`*.css` → **0 hasil**; hasil dekode di DOM persis sesuai teks yang diminta |
| Mode gelap | Latar `slate-900/95`, border atas slate-800, teks/pill terbaca |
| Sidebar | `lg:left-72` / `lg:left-20` mengikuti lebar sidebar; transisi `left` 200 ms, di bawah overlay drawer (z-30) dan sidebar (z-40) |
| Cetak invoice | Footer berkelas `.no-print`, dan `main` memakai `print:pb-0` sehingga tidak menyisakan ruang kosong saat dicetak |
| Portal pelanggan `/akun` | Bar fixed yang **sama dengan dashboard**: `position: fixed`, `bottom: 0`, tinggi 82,63 px; `getBoundingClientRect()` identik (top 398,38 / bottom 481) pada `scrollY` 0 dan 1.603 (maksimum); `main` ber-`padding-bottom` 96 px sehingga konten terakhir 385 px, di atas tepi footer 398 px; kontainer `max-w-5xl` selaras dengan header portal |
| Overflow | Tidak ada overflow horizontal pada layar 531 px |

- Regresi backend dijalankan ulang setelah perubahan: **93 test / 437 assertion lulus**.
- `svelte-check --tsgo` tetap **0 error / 0 warning**; build produksi **800 modul** tanpa warning.

## 5. Pembersihan Data Uji dan Artefak

- Logo uji yang diunggah melalui UI dihapus dari `storage/app/public/logos`, baris `pengaturan`
  dihapus lalu di-seed ulang sehingga kembali ke “Laundry Management” tanpa logo.
- Berkas gambar uji di folder proyek (`vibe_images/`) dihapus.
- Data master demo tidak diubah; tidak ada nota uji baru yang tersisa dari sesi ini.
- Inventaris akhir tidak menemukan file sumber yatim: seluruh komponen Svelte dan modul `lib`
  diimpor, seluruh Form Request/Resource/Seeder dirujuk, policy ditemukan otomatis oleh Laravel,
  dan `UserFactory` dipakai oleh model `User`. Scaffolding framework (migration cache/jobs,
  cache bootstrap, `favicon.ico`, `robots.txt`, `lang/id`) dipertahankan karena dipakai.
- Cache hasil alat pengembangan `.svelte-check/` dan `.phpunit.result.cache` dihapus setelah
  pemeriksaan final (keduanya dibuat ulang otomatis saat alat dijalankan dan sudah masuk
  `.gitignore`). `public/hot` dipertahankan karena sedang dipakai Vite dev server; `public/build`
  berisi hasil build produksi.

## 6. Keterbatasan dan Saran Lanjutan

1. Cetak diuji lewat media emulasi CSS, bukan dialog cetak fisik atau PDF printer; PDF nota
   diuji sebagai berkas unduhan yang dibuka di penampil Chrome.
2. Email reset diuji dengan `MAIL_MAILER=log`/notification fake; konfigurasi SMTP produksi perlu
   diuji sekali dengan kotak surat nyata sebelum rilis.
3. Rate limit login diuji otomatis (5 percobaan) dan tidak diulang manual di browser.
4. Verifikasi mobile terbatas pada 390 px; tablet 768 px mengandalkan kelas responsif Tailwind.
5. Disarankan menjalankan `php artisan test`, `npx svelte-check`, dan `npm run build` pada CI
   sebelum deploy, serta mengganti seluruh password demo pada lingkungan nyata.
