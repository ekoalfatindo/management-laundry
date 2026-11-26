# TESTING REPORT — Laundry Management System

Tanggal eksekusi terakhir: **2026-11-27**
Lingkungan: Windows 10, PHP 8.4.27 (NTS), MySQL 8.0.30 (Laragon), Node 24.18.0, Vite 8.3.8.
Database aplikasi: `laundry_svelte`; database test: `laundry_svelte_test` (keduanya dibuat ulang
dari nol pada 27 November 2026).

Tidak ada klaim pengujian tanpa eksekusi. Seluruh hasil di bawah berasal dari perintah yang
benar-benar dijalankan pada sesi terkait. Angka ringkasan di §1 adalah hasil terakhir
(27 November 2026); walkthrough bertanda tanggal lebih lama dipertahankan sebagai riwayat.

## 1. Ringkasan Hasil

| Pemeriksaan | Perintah | Hasil |
|---|---|---|
| Type & aksesibilitas Svelte/TS | `npx svelte-check --tsgo --tsconfig ./tsconfig.json` | **0 error, 0 warning** |
| Build produksi | `npm run build` | **Sukses** (805 modul; JS 493,73 kB / gzip 131,45 kB; CSS 101,15 kB / gzip 17,20 kB — `app.css` 71,63 kB + `sweetalert2.min.css` 29,52 kB) |
| Test backend | `php artisan test` | **141 test lulus, 755 assertion**, 23,30 detik |
| Daftar route | `php artisan route:list --except-vendor` | **56 route aplikasi** (61 total bila `_inertia/devtools`, `storage`, dan `up` ikut dihitung; route vendor dikecualikan) |
| Migrasi + seeder | `php artisan migrate --seed --force` pada database baru | Sukses; **17 migrasi + 10 seeder** berjalan berurutan tanpa error |
| Storage publik | `php artisan storage:link` | Sukses (logo Setup dapat diakses lewat `/storage`) |
| Verifikasi data demo | query hitung via Tinker | 15 user (1 admin, 4 karyawan, 5 Member, 5 Reguler), 10 barang, 5 kiloan, 4 layanan, 1 program loyalitas, 3 promosi, 81 nota, 18 riwayat hadiah, 3 role, 14 permission, 1 baris pengaturan (baseline seeder; database dibuat ulang dari nol di §4.6) |
| Idempotensi seeder | `php artisan db:seed --force` dua kali berturut-turut | Jumlah nota dan hadiah identik (81 nota, 18 hadiah); tidak ada duplikasi |
| Walkthrough UI live | Chrome headless + CDP, viewport 1440 × 900, 390 × 844, dan 360 × 800 (thermal) | Lulus (detail §4) |
| Console browser | listener `Runtime.exceptionThrown` + `console.error` | **0 exception, 0 `console.error`**; satu entri network 404 berasal dari uji negatif URL tak dikenal |

## 2. Cakupan Test Otomatis

124 method test menjadi 141 test setelah data provider PHPUnit:

| File | Jumlah method | Cakupan |
|---|---|---|
| `tests/Feature/AuthTest.php` | 9 | Halaman login, login admin/karyawan, pelanggan masuk ke portal `/akun`, password salah, rate limit 5 percobaan, guest redirect, logout, pelanggan login dialihkan dari `/login` |
| `tests/Feature/AkunTest.php` | 7 | Daftar hanya nota milik sendiri (urutan, meta, ringkasan total), **total biaya mengecualikan nota batal**, detail milik sendiri, detail pelanggan lain 404, PDF milik sendiri `200` + `%PDF`, PDF pelanggan lain 404, staf di `/akun` dialihkan ke dashboard |
| `tests/Feature/AuthorizationTest.php` | 6 | Matriks akses karyawan/pelanggan, 403 Inertia merender `Error`, hanya admin boleh hapus nota, karyawan boleh kelola pelanggan |
| `tests/Feature/MasterDataTest.php` | 12 | CRUD barang/kiloan/jenis layanan, validasi, unique, user view-only, filter/pagination, flash prop Inertia, **hapus barang/kiloan/jenis layanan kembali ke daftar tanpa filter** |
| `tests/Feature/NotaTest.php` | 18 | Harga kiloan & satuan, validasi, **pelanggan harus berperan Pelanggan**, update snapshot, ganti baris, cascade hapus, snapshot saat master dihapus, **nota Dibatalkan tidak dapat diedit**, filter, pagination, ekspor XLSX rapi (8 bagian Open XML, header beku, auto-filter, total, format Rupiah/tanggal), izin ekspor, resource detail + **terbilang**, transisi status/pembayaran dan reward-aware totals |
| `tests/Feature/UserManagementTest.php` | 10 | Buat/ubah user + role, **admin terakhir tidak dapat diturunkan**, email duplikat, password confirmation/minimum, larangan hapus akun sendiri, karyawan mengelola pelanggan, filter role, **hapus karyawan/pelanggan/pengguna kembali ke daftar tanpa filter** |
| `tests/Feature/PengaturanTest.php` | 8 | Halaman Setup, ubah nama, unggah/ganti logo (logo lama dihapus), tolak PDF, nama wajib, karyawan 403, guest redirect, identitas dibagikan ke Inertia |
| `tests/Feature/ForgotPasswordTest.php` | 8 | Halaman lupa password, notifikasi terkirim, email tak terdaftar tetap pesan sama, halaman reset, reset + login password baru, token sekali pakai, token salah, validasi panjang/konfirmasi |
| `tests/Feature/LoyaltyPromotionTest.php` | 18 | Penerbitan hadiah loyalitas per siklus, promosi otomatis hanya dalam periode aktif, sekali pakai, kedaluwarsa, pembatalan nota (lepas/revoke), larangan pakai ulang, pemisahan hitungan loyalitas vs promosi, tipe persen/nominal/gratis, **penerapan gratis penuh (nilai 0) sampai total nota menjadi 0**, **pelepasan hadiah terpasang dan penggantian ke hadiah lain saat update nota**, progress pelanggan, **sisa target penuh sebelum transaksi pertama**, **status efektif kedaluwarsa pada payload** |
| `tests/Feature/LoyaltyPromotionHttpTest.php` | 14 | Halaman admin loyalitas/promosi, validasi CRUD program dan promosi, nilai 0 untuk hadiah Gratis Cuci, penolakan nilai 0 untuk nominal, akses karyawan 403, kategori pelanggan pada form, pemilihan hadiah pada nota, penolakan hadiah milik pelanggan lain, diskon manual dikunci saat hadiah dipakai, **hapus promosi kembali ke daftar tanpa filter** |
| `tests/Feature/RewardAccuracyTest.php` | 5 | Akurasi hadiah: jumlah hadiah loyalitas = siklus transaksi Selesai dan hanya untuk Member, promosi berkategori semua pelanggan diterima Member maupun Reguler, hitungan loyalitas dan promosi berdiri sendiri, promosi hanya menghitung nota Selesai di dalam periode (dibatalkan lalu aktif kembali), nilai hadiah tidak melebihi total dan `sync` berulang idempoten |
| `tests/Feature/DemoSeederTest.php` | 2 | Seeder demo 6 bulan: jumlah bulan unik, jumlah nota, tidak ada waktu masa depan, semua status/pembayaran, komposisi Member/Reguler, seluruh status hadiah, integritas hadiah terpakai, serta jumlah identik saat seeder diulang |
| `tests/Unit/PricingCalculatorTest.php` | 5 (+ data provider) | Pembulatan kiloan, diskon, biaya layanan satuan, clamp diskon 0–100, `potonganHadiah` untuk persen/nominal/gratis |
| `tests/Unit/TerbilangTest.php` | 2 (+ data provider) | 15 nominal (0, 12, 25, 100, 125, 1.000, 10.000, 25.000, 125.000, 1.500.000, 2.000.000, 1 miliar, 999, 1.000.001) + normalisasi nilai negatif |

Contoh perintah:

```bash
php artisan test --filter=DemoSeederTest
# PASS — 2 test, 32 assertion
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
7. **Ekspor Excel** — respons `200`,
   `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`,
   `Content-Disposition: attachment; filename=laporan-nota-YYYYMMDD-HHMMSS.xlsx`. Berkas diuji
   sebagai ZIP berisi 8 bagian Open XML; sheet memuat judul, ringkasan filter, header pada baris 4,
   panel beku, auto-filter `A4:M<n>`, baris `TOTAL (n nota)`, `inlineStr` untuk teks (nama
   pelanggan `=SUM(A1:A2)` tetap tersimpan sebagai teks), serta format `"Rp" #,##0` dan
   `dd/mm/yyyy hh:mm`.
8. **Error page** — akses `/users` sebagai karyawan menampilkan halaman “Akses ditolak”
   (Error 403) di dalam layout aplikasi; URL tidak dikenal menampilkan Error 404; guest yang
   membuka `/nota` diarahkan ke `/login`; pelanggan yang membuka `/nota` mendapat 403.
9. **Portal pelanggan & PDF** — login `ayu@laundry.test` diarahkan ke `/akun`; props hanya memuat
   7 nota miliknya dengan ringkasan 7 nota / Rp 1.287.375; detail menampilkan invoice tanpa tombol
   edit/hapus; `/akun/nota/{id}/pdf` mengembalikan `200`, `Content-Type: application/pdf`,
   `Content-Disposition: attachment; filename=nota-D2610-02.pdf`, 8.334 byte, diawali `%PDF`.
   Test otomatis membuktikan nota/PDF pelanggan lain menghasilkan 404.
10. **Penerbitan hadiah** — hadiah loyalitas dibuat per siklus `target` transaksi **Selesai**
    (`LOY-{program}-{user}-{siklus}`), promosi per periode aktif (`PRM-{promo}-{user}-{siklus}`).
    Test membuktikan hadiah hanya terbit saat ambang tercapai, tidak terbit di luar periode promo,
    dan `reward_key` deterministik mencegah duplikasi saat perhitungan diulang.
11. **Pemakaian dan integritas hadiah** — saat hadiah dipilih, `diskon` manual dikunci ke 0,
    `customer_reward_id` dan `reward_label` tercatat, `diskon_nominal` = nilai potongan sebenarnya,
    dan `jumlah` = `total_harga` − potongan. Test membuktikan hadiah milik pelanggan lain ditolak,
    hadiah yang sudah `Digunakan`/`Kedaluwarsa`/`Dibatalkan` tidak dapat dipakai ulang, serta
    pembatalan nota melepas hadiah bila belum terpakai atau memblokir pembatalan bila sudah
    dikonsumsi nota lain.
12. **Format cetak** — pada `/nota/{id}` modal format tampil sebelum mencetak. A4 memakai
    `@page size: A4`; thermal 80 mm dan 58 mm menyuntikkan `@page size: 80mm <tinggi>mm` /
    `58mm <tinggi>mm` lewat elemen `#nota-print-page-size` dan memakai `.no-print` untuk
    menyembunyikan sidebar, header, footer, serta tombol aksi. Chrome menolak nilai
    `size: <lebar> auto`, sehingga tinggi halaman dihitung dari tinggi konten struk ditambah
    dua margin halaman dan disinkronkan ulang pada `beforeprint`. Verifikasi computed CSS:
    lebar dokumen thermal 80 mm = 302,359 px (font 10 px), 58 mm = 219,203 px (font 9 px),
    tanpa overflow horizontal; `Page.printToPDF` dengan `preferCSSPageSize` menghasilkan
    halaman **57,83 × 104,06 mm** (58 mm) dan **80,09 × 107,95 mm** (80 mm), masing-masing
    satu halaman.

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

### 4.4 Kategori pelanggan, loyalitas, promosi, dan format cetak (26 November 2026)

Pemeriksaan dilakukan pada aplikasi yang berjalan setelah 5 migrasi baru diterapkan pada database
demo. Angka seeder di bawah berasal dari seeder demo dan diverifikasi ulang pada 27 November 2026
setelah database dibuat ulang dari nol.

| Skenario | Bukti / hasil |
|---|---|
| Kategori pelanggan | `/pelanggan` menampilkan badge **Member**/**Reguler**; 5 Member dan 5 Reguler pada data demo; filter kategori dan halaman detail `/pelanggan/{id}` (profil + riwayat nota + progress loyalitas) terbuka untuk admin dan karyawan |
| Menu baru | Sidebar admin memuat **Loyalitas** (ikon gift) dan **Promosi** (ikon tag); menu tidak dirender untuk karyawan |
| Akses admin | `/loyalitas`, `/promosi`, `/pelanggan/{id}` → **200**; perubahan program/promosi tersimpan dan muncul pada form nota |
| Combobox Pelanggan Terdaftar | `/nota/create` memakai `role="combobox"` dengan label default **Pelanggan umum**; daftar 11 entri (umum + 10 pelanggan) terbuka saat fokus; mengetik `ayu` menyaring ke **Ayu Lestari**, memilihnya mengisi Nama Pelanggan dan mengaktifkan pilihan hadiah (3 opsi); kembali ke walk-in mengosongkan pelanggan dan menonaktifkan hadiah; navigasi panah/Enter/Escape, klik di luar, dan halaman edit (`/nota/72/edit` → **Ayu Lestari**) berfungsi; berlaku pada form kiloan dan satuan; **0 error console** |
| Akses karyawan | `/loyalitas` dan `/promosi` → **403**; menu tidak tampil; halaman pelanggan tetap **200** |
| Akses pelanggan | Seluruh halaman staf (termasuk loyalitas/promosi/pelanggan) → **403**; `/akun` tetap **200** |
| Program loyalitas demo | “Member Laundry”: target 5 transaksi Selesai, diskon 10%, berlaku 90 hari, aktif |
| Promosi demo | Promo Hemat Reguler (5%), Bonus Member Setia (Rp10.000), Promo Lebaran Berkah (nonaktif, di luar periode) |
| Riwayat hadiah | 18 baris: **13 Tersedia**, **2 Digunakan**, **3 Kedaluwarsa**; mencakup sumber loyalitas dan promosi |
| Contoh pemakaian promo | Nota `#D2611-02` (Mega): total Rp125.000 → **Rp115.000**, label hadiah promo, `diskon = 0` |
| Contoh pemakaian loyalitas | Nota `#D2611-07` (Fitri): total Rp150.000 → **Rp135.000**, label hadiah loyalitas, `diskon = 0` |
| Integritas nota berhadiah | Kedua nota berstatus **Selesai**, `customer_reward_id` terisi, dan hadiah menunjuk balik ke nota yang sama |
| Akurasi hadiah (audit data demo, read-only) | Seluruh 10 pelanggan: jumlah hadiah loyalitas = `floor(transaksi Selesai / target 5)` dan hanya untuk 5 Member; promosi semua pelanggan (Bonus Member Setia, target 2) diterima Member maupun Reguler; **0** temuan selisih, **0** `reward_key` duplikat; 2 hadiah Digunakan cocok dengan nota, diskon, dan nilai terpakai |
| Seeder 6 bulan | 81 nota pada 6 bulan berbeda; 74 Selesai / 5 Diproses / 2 Dibatalkan; 41 Barang Satuan / 40 Paket Kiloan; 65 Lunas / 16 Belum Lunas; tidak ada `waktu` di masa depan |
| Idempotensi | `db:seed --force` dijalankan ulang: tetap 81 nota dan 18 hadiah, tanpa duplikasi nomor nota |
| Modal format cetak | Tombol cetak membuka pilihan **A4**, **Thermal 80 mm**, **Thermal 58 mm** sebelum `window.print()` |
| Thermal 80 mm | Lebar dokumen terhitung **302,359 px**, font 10 px, `@page size: 80mm 108mm` (tinggi dinamis: konten + 2×margin 3 mm + 2 mm), margin 3 mm; PDF **80,09 × 107,95 mm**, **1 halaman** |
| Thermal 58 mm | Lebar dokumen terhitung **219,203 px**, font 9 px, `@page size: 58mm 104mm` (tinggi dinamis), margin 2 mm; PDF **57,83 × 104,06 mm**, **1 halaman**; nama barang panjang membungkus tanpa memotong angka total |
| Thermal nota terpanjang | Nota `#D2606-06` (3 baris barang): halaman 58 mm → **120 mm**, 80 mm → **126 mm**, keduanya **1 halaman** (tinggi mengikuti konten) |
| Ukuran kertas Chrome | `@page size: 58mm auto` / `80mm auto` **ditolak** Chrome 158 (deklarasi `size` dibuang, pratinjau jatuh ke A4); nilai dua panjang `58mm 104mm` diterima dan `Page.printToPDF` memakai ukuran tersebut |
| A4 | `@page size: A4`; PDF **209,89 × 297,01 mm**; kop perusahaan, tabel item, ringkasan, dan terbilang tampil; `.no-print` menyembunyikan sidebar/header/footer/tombol |
| Console browser | 0 exception, 0 `console.error` pada alur login admin/karyawan/pelanggan, halaman loyalitas/promosi, detail pelanggan, nota berhadiah, dan pratinjau format cetak |

- Test otomatis terakhir (27 November 2026): **141 test / 755 assertion lulus dalam 23,30 detik** (termasuk 5 test akurasi hadiah dan 2 test pembaruan hadiah nota baru).
- `svelte-check --tsgo`: **0 error / 0 warning**; `npm run build`: **805 modul** tanpa warning.

### 4.5 Pemeriksaan final semua fitur (27 November 2026)

Pemeriksaan ulang menyeluruh pada aplikasi yang berjalan dengan build produksi (tanpa Vite dev)
setelah database demo dan test dibuat ulang dari nol. Semua pengecekan di bawah read-only
kecuali login/logout per peran dan pembatalan dialog konfirmasi.

| Skenario | Bukti / hasil |
|---|---|
| Admin | `/`, `/nota`, `/nota/create`, `/nota/72`, `/nota/72/edit`, `/users`, `/karyawan`, `/pelanggan`, `/barang`, `/kiloan`, `/jenis-layanan`, `/loyalitas`, `/promosi`, `/pengaturan`, `/nota/export` → **200**; `/akun` dan `/akun/nota/72` → redirect ke dashboard |
| Karyawan (`vavian@laundry.test`) | `/`, `/nota`, `/nota/create`, `/nota/72`, `/nota/72/edit`, `/pelanggan`, `/barang`, `/kiloan`, `/jenis-layanan`, `/nota/export` → **200**; `/users`, `/karyawan`, `/pengaturan`, `/loyalitas`, `/promosi` → **403**; `/akun` → redirect; tombol hapus nota tidak dirender |
| Pelanggan (`ayu@laundry.test`) | `/akun` dan `/akun/nota/72` → **200**; `/akun/nota/72/pdf` → **200** `application/pdf`, `attachment; filename="nota-D2611-03.pdf"`, **8.557 byte**, `%PDF-1.7`; nota/PDF pelanggan lain (`/akun/nota/79`, `/akun/nota/79/pdf`) → **404**; `/` → redirect ke `/akun`; seluruh halaman staf → **403** |
| Portal pelanggan | Label invoice **INVOICE** (tanpa “Invoice Laundry”), nomor `#D2611-03`, rincian item, total, terbilang; tombol Unduh PDF ada; tidak ada tombol ubah/hapus |
| Filter daftar nota | `/nota?search=D2611&status=Selesai` → **7 baris**, semuanya berstatus Selesai dan cocok kata kunci; nominal `Rp 65.000` (pemisah ribuan id-ID) |
| Filter kategori pelanggan | `/pelanggan?kategori=Member` → **5 baris**, seluruhnya berlabel Member; detail `/pelanggan/8` → **200** |
| Kalkulasi nota kiloan | Paket **SUPER DUPER 5kg Rp 50.000/kg** × **3,5 kg** → Total bayar **Rp 175.000**; terbilang tampil |
| Kalkulasi nota satuan | Barang Selimut + layanan Cepat (Rp 15.000) → Total bayar **Rp 30.000**; terbilang tampil |
| Ekspor XLSX | `/nota/export` → **200**, `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, **9.750 byte**, diawali `PK` |
| Modal format cetak | Tombol “Cetak Invoice” membuka pilihan **A4**, **Thermal 80 mm**, **Thermal 58 mm** |
| Ukuran thermal 58 mm | Memilih thermal 58 mm menyuntik `@page { size: 58mm 104mm; margin: 2mm; }` (tinggi eksplisit, tanpa `auto`) |
| Nota Dibatalkan | `/nota?status=Dibatalkan` → **2 baris**, aksi hanya Lihat + Hapus (tanpa Ubah); `/nota/36` tanpa tombol Ubah/Tandai Selesai/Tandai Lunas/Batalkan; akses langsung `/nota/36/edit` → redirect kembali ke `/nota/36` dengan pesan error; console 0 error |
| Nama pelanggan walk-in | `/nota/create` (kiloan): memilih **Fitri Handayani** mengisi Nama Pelanggan; memilih **Pelanggan umum** mengosongkan kolom nama, mereset hadiah ke "Tanpa hadiah", dan menonaktifkan select hadiah; skenario sama pada form **Barang Satuan** |
| Hadiah saat edit | `/nota/76/edit` (nota berhadiah loyalitas): select hadiah tampil dengan opsi **Tanpa hadiah**, hadiah terpasang **Member Laundry — 10%** (terpilih), dan hadiah tersedia **Bonus Member Setia — Rp 10.000**; memilih Tanpa hadiah mengaktifkan diskon; memilih hadiah lain mengubah ringkasan menjadi potongan Rp 10.000 dan total Rp 140.000 (perubahan tidak disimpan) |
| Dashboard | Kartu ringkasan, grafik (29 elemen SVG), tabel aktivitas 6 baris, 11 menu admin tampil |
| Master data | `/barang` 10 baris, `/kiloan` 5 baris, `/jenis-layanan` **4 baris**, `/users` 15 baris, `/karyawan` 4 baris, `/pengaturan` memuat nama perusahaan + unggah logo |
| Console browser | 0 exception JavaScript / 0 `console.error` pada alur admin, karyawan, pelanggan, dan guest; satu entri network 404 berasal dari uji negatif `/tidak-ada-halaman` |
| Log server | 0 `local.ERROR` selama pemeriksaan final; entri `testing.ERROR` berasal dari test negatif PHPUnit |
| Route & permission | `route:list --except-vendor` → **56 route**; 3 role, 14 permission |

Tidak ada drift data pada pemeriksaan ini: database pengembangan dan test dibuat ulang dari nol
sebelum pengujian (§4.6), sehingga sisa sesi uji manual sebelumnya (akun seed lama, “Cuci Kilat
Tes”, program loyalitas yang diubah) tidak ada.

### 4.6 Rebuild database demo dan test dari nol (27 November 2026)

Kedua database proyek dihapus lalu dibuat ulang dengan `DROP DATABASE` + `CREATE DATABASE`
(utf8mb4/utf8mb4_unicode_ci), kemudian database demo diisi lewat
`php artisan migrate --seed --force`. Seluruh 17 migrasi dan 10 seeder berjalan berurutan tanpa
error (RolePermission, Pengaturan, User, Barang, Kiloan, JenisLayanan, LoyaltyProgram, Promotion,
Nota). Hanya `laundry_svelte` dan `laundry_svelte_test` yang disentuh; database proyek lain,
database legacy, dan database produksi tidak diubah.

| Objek | Baseline terverifikasi |
|---|---|
| Pengguna | **15** total — 1 admin, 4 karyawan, 10 pelanggan (5 Member, 5 Reguler); akun seed lama `sugeng/bejo/herman/putri` tidak ada |
| Master data | 10 barang, 5 paket kiloan, **4 jenis layanan** — Cepat Rp 15.000, Kilat Rp 30.000, Reguler Rp 0, Super Duper Rp 50.000 (tanpa “Cuci Kilat Tes”) |
| Loyalitas | **Member Laundry** — target **5**, tipe **persen**, nilai **10**, masa berlaku **90** hari, status aktif |
| Promosi | 3 program — Promo Hemat Reguler (5%), Bonus Member Setia (Rp10.000), Promo Lebaran Berkah (nonaktif) |
| Nota | **81** (ID 1–81, `#D2606-01` s.d. `#D2611-12`) — Selesai 74, Diproses 5, Dibatalkan 2; Lunas 65, Belum Lunas 16; Paket Kiloan 40, Barang Satuan 41 |
| Riwayat hadiah | **18** — Tersedia 13, Digunakan 2, Kedaluwarsa 3 |
| Role & permission | 3 role, 14 permission |
| Pengaturan | Nama perusahaan “Laundry Management”, `logo_path` kosong, folder `storage/app/public/logos` bersih |
| UI pasca-rebuild | Dashboard **Total Nota 79** (di luar 2 nota batal), 11 menu admin, grafik 29 elemen SVG, 6 baris aktivitas; `/karyawan` 4 baris; `/jenis-layanan` 4 baris; form Loyalitas menampilkan nilai baseline; combobox `/nota/create` berisi 11 opsi dengan default **Pelanggan umum**; console 0 exception JavaScript |

Database test dibuat kosong lalu dimigrasi otomatis oleh PHPUnit (`RefreshDatabase`) saat suite
dijalankan. Karena tabel dikosongkan, ID nota dimulai dari 1; seluruh nomor nota dan data demo
dibangun ulang oleh seeder.

## 5. Pembersihan Data Uji dan Artefak

- Inventaris ulang 27 November 2026: **17 migrasi** dan **10 seeder** semuanya direferensikan
  (tidak ada migrasi/seeder yatim), seluruh komponen Svelte dan modul `lib` diimpor, seluruh Form
  Request/Resource/Policy dirujuk atau ditemukan otomatis oleh Laravel, dan `UserFactory` dipakai
  model `User`. Tidak ada file sumber yang dihapus karena tidak ada yang tidak terpakai.
- Artefak generasi dibersihkan: view Blade terkompilasi (`storage/framework/views/*.php`), log
  `storage/logs/laravel.log`, dev marker `public/hot`, cache `.svelte-check/` dan
  `.phpunit.result.cache` (semuanya dibuat ulang otomatis dan sudah masuk `.gitignore`).
  Bootstrap cache dibersihkan dengan `php artisan optimize:clear` setelah verifikasi.
- Scaffolding framework yang dipakai dipertahankan: migrasi `cache`/`jobs` (driver cache, queue,
  dan session memakai database), `favicon.ico`, `robots.txt`, `lang/id`, serta `UserFactory`.
- `public/build` dipertahankan sebagai hasil build produksi terakhir (805 modul, 3 aset yang
  seluruhnya dirujuk `manifest.json`); `public/storage` tetap tertaut ke `storage/app/public`.
- Tidak ada logo uji atau berkas unggahan tersisa di `storage/app/public/logos`; data demo
  seluruhnya dibangun ulang oleh seeder dan tidak ada nota uji baru dari sesi ini.

## 6. Keterbatasan dan Saran Lanjutan

1. Cetak diuji lewat media emulasi CSS, bukan dialog cetak fisik atau PDF printer; PDF nota
   diuji sebagai berkas unduhan yang dibuka di penampil Chrome.
2. Email reset diuji dengan `MAIL_MAILER=log`/notification fake; konfigurasi SMTP produksi perlu
   diuji sekali dengan kotak surat nyata sebelum rilis.
3. Rate limit login diuji otomatis (5 percobaan) dan tidak diulang manual di browser.
4. Verifikasi mobile terbatas pada 390 px; tablet 768 px mengandalkan kelas responsif Tailwind.
5. Disarankan menjalankan `php artisan test`, `npx svelte-check`, dan `npm run build` pada CI
   sebelum deploy, serta mengganti seluruh password demo pada lingkungan nyata.
