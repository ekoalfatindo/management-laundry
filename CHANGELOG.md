# Changelog

Semua perubahan penting pada aplikasi Manajemen Laundry dicatat di file ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/) dan
[Semantic Versioning](https://semver.org/lang/id/).

## [1.1.1] — 2026-11-27

Rilis pemeliharaan: audit kebersihan repositori, rebuild database demo/test dari nol, dan
verifikasi ulang seluruh fitur. Tidak ada perubahan skema; delapan cacat perilaku pada fitur
yang sudah ada diperbaiki (lihat bagian Diperbaiki).

### Diubah

- **Database demo dan test dibangun ulang dari nol**: `laundry_svelte` dan
  `laundry_svelte_test` di-`DROP`/`CREATE` (utf8mb4/utf8mb4_unicode_ci), lalu diisi lewat
  `php artisan migrate --seed --force` (17 migrasi + 10 seeder). Hanya kedua database proyek
  yang disentuh; database proyek lain, legacy, dan produksi tidak diubah.
- **Baseline demo terverifikasi ulang**: 15 pengguna (1 admin, 4 karyawan, 5 Member,
  5 Reguler), 10 barang, 5 paket kiloan, 4 jenis layanan, 1 program loyalitas (diskon 10%,
  target 5), 3 promosi, 81 nota (ID 1–81, `#D2606-01` s.d. `#D2611-12`), 18 riwayat hadiah,
  3 role, dan 14 permission.
- **Dokumentasi diselaraskan** ke kondisi 27 November 2026: README, arsitektur, database,
  inventaris fitur, laporan pengujian (§4.5 walkthrough, §4.6 rebuild), kredensial demo,
  progres pengembangan, dan panduan HTML.

### Diperbaiki

- **Admin terakhir tidak dapat diturunkan perannya**: sebelumnya admin terakhir bisa
  diubah menjadi Karyawan/Pelanggan lewat `PUT /users/{user}`, sehingga aplikasi bisa
  kehilangan seluruh admin. `UserService::update` kini menolak dengan pesan
  "Admin terakhir tidak dapat diubah perannya.", setara dengan perlindungan pada aksi hapus.
- **Progres loyalitas/promosi sebelum transaksi pertama**: saat pelanggan belum memiliki
  transaksi Selesai, `sisa` bernilai 0 sehingga halaman detail pelanggan menampilkan
  "Target tercapai. Hadiah siklus ke-0 sudah diterbitkan." Kini `sisa` sama dengan target
  penuh dan pesan tersebut hanya muncul setelah siklus benar-benar selesai.
- **Total biaya portal pelanggan**: ringkasan `/akun` sebelumnya menjumlahkan nota
  Dibatalkan, berbeda dari halaman detail pelanggan milik staf. Total biaya kini hanya
  menjumlahkan nota yang tidak dibatalkan; jumlah nota tetap menghitung seluruh riwayat.
- **Status hadiah kedaluwarsa**: daftar hadiah pada detail pelanggan menampilkan
  "Tersedia" untuk hadiah yang sudah lewat masa berlaku sampai ada event berikutnya.
  Payload progres kini melaporkan status efektif "Kedaluwarsa" secara langsung.
- **Pelanggan terdaftar divalidasi perannya**: form nota kiloan, satuan, dan edit sebelumnya
  menerima ID pengguna mana pun (`exists:users,id`), sehingga akun admin/karyawan bisa
  dijadikan pelanggan lewat request manual. Rule `PelangganTerdaftar` kini menolak akun
  non-pelanggan dengan pesan "Akun yang dipilih bukan pelanggan terdaftar."
- **Nota Dibatalkan bersifat final**: nota batal sebelumnya masih bisa dibuka halaman
  editnya dan diperbarui. Kini halaman edit dan `PUT /nota/{nota}` ditolak dengan pesan
  "Nota yang dibatalkan tidak dapat diubah.", dan tombol Ubah disembunyikan pada daftar
  serta detail nota.
- **Nama pelanggan walk-in tidak tertinggal**: beralih dari pelanggan terdaftar ke
  "Pelanggan umum" sebelumnya membiarkan nama pelanggan sebelumnya di kolom Nama Pelanggan,
  sehingga nota walk-in bisa tersimpan dengan nama pelanggan lain. Nama kini dikosongkan saat
  beralih ke walk-in (kiloan dan satuan), sekaligus melepas pilihan hadiahnya.
- **Hadiah terpasang dapat dilepas atau diganti saat edit**: halaman edit sebelumnya
  menampilkan kartu "Hadiah terpakai" tanpa pilihan, sehingga hadiah hanya bisa dilepas dengan
  mengganti pelanggan. Select hadiah kini tetap tampil berisi hadiah terpasang, hadiah lain
  yang tersedia, dan opsi "Tanpa hadiah"; melepas hadiah mengaktifkan kembali diskon persen.

### Dihapus

- **Artefak generasi**: view Blade terkompilasi, `storage/logs/laravel.log`, dev marker
  `public/hot`, cache `.svelte-check/`, dan `.phpunit.result.cache`; bootstrap cache
  dibersihkan dengan `php artisan optimize:clear`. Semuanya dibuat ulang otomatis dan sudah
  masuk `.gitignore`.
- **Tidak ada file sumber yang dihapus**: inventaris ulang memastikan seluruh 17 migrasi dan
  10 seeder direferensikan, semua komponen/lib/request/resource/policy dipakai atau
  ditemukan otomatis, dan `UserFactory` dipakai model `User`. Scaffolding framework yang
  dipakai (migrasi cache/jobs, favicon, `robots.txt`, `lang/id`) tetap dipertahankan,
  begitu pula `public/build` (3 aset yang dirujuk `manifest.json`).

### Catatan Pembaruan

```bash
# Menyiapkan ulang lingkungan demo dari nol (database demo)
php artisan migrate:fresh --seed
php artisan storage:link
npm run build

# Database test dibuat ulang otomatis oleh PHPUnit (RefreshDatabase)
php artisan test
```

> `migrate:fresh` menghapus seluruh data pada database demo. Jangan jalankan pada database
> produksi. Untuk produksi cukup `php artisan migrate`.

### Verifikasi

- `php artisan test`: **141 test lulus, 755 assertion** (23,30 detik) pada database
  `laundry_svelte_test` yang baru dimigrasi.
- `npx svelte-check --tsgo --tsconfig ./tsconfig.json`: **0 error, 0 warning**.
- `npm run build`: **805 modul**, JS 493,73 kB (gzip 131,45 kB), CSS 101,15 kB (gzip 17,20 kB).
- Walkthrough live final (27 November 2026): matriks status admin/karyawan/pelanggan, ekspor
  XLSX (9.750 byte), PDF portal (8.557 byte), pemisahan nota/PDF pelanggan lain (404), modal
  format cetak A4/thermal 58/80 mm, dan combobox pelanggan 11 opsi — **0 error console** dan
  0 `local.ERROR`; satu 404 jaringan berasal dari uji negatif URL tak dikenal.
- Rincian bukti ada di `docs/TESTING_REPORT.md` §4.5–§4.6.

## [1.1.0] — 2026-11-26

### Ditambahkan

- **Kategori pelanggan** Reguler/Member beserta tanggal `member_sejak`, filter kategori,
  badge pada daftar pelanggan, dan halaman detail pelanggan berisi riwayat transaksi,
  total belanja, serta riwayat hadiah.
- **Program loyalitas Member** yang dapat dikonfigurasi admin: nama, deskripsi, target
  transaksi, jenis hadiah (diskon persen, potongan nominal, gratis cuci), nilai hadiah,
  masa berlaku, dan status aktif.
- **Penerbitan hadiah otomatis** saat transaksi berstatus Selesai mencapai target, dengan
  kunci deterministik `LOY-{programId}-{userId}-{siklus}` agar tidak terjadi duplikasi.
- **Riwayat dan progres hadiah** per pelanggan: status Tersedia/Digunakan/Kedaluwarsa/Dibatalkan,
  sisa target siklus berjalan, dan tanggal kedaluwarsa.
- **Promosi berulang** untuk kategori Reguler, Member, atau semua pelanggan, lengkap dengan
  periode berlaku, syarat, target transaksi, dan hadiah; kunci `PRM-{promotionId}-{userId}-{siklus}`.
- **Aturan promosi**: promosi nonaktif atau di luar periode tidak menerbitkan hadiah, dan
  perhitungan loyalitas serta promosi berjalan terpisah tanpa pemotongan ganda.
- **Status nota** Diproses/Selesai/Dibatalkan dan **status pembayaran** Belum Lunas/Lunas,
  termasuk aksi Selesaikan, Batalkan, dan Ubah Pembayaran pada halaman detail nota.
- **Filter status dan pembayaran** pada daftar nota, badge status pada tabel, serta ringkasan
  status pada halaman invoice.
- **Snapshot potongan hadiah** pada nota (`diskon_nominal`, `reward_label`,
  `customer_reward_id`) sehingga riwayat nota tetap konsisten meskipun program berubah.
- **Pilihan format cetak** A4 Profesional, Thermal 80 mm, dan Thermal 58 mm melalui dialog
  sebelum mencetak, dengan ukuran halaman dinamis dan komponen `NotaThermal`.
- **Permission baru** `loyalitas.manage` dan `promosi.manage` (total 14 permission); menu
  Loyalitas dan Promosi hanya tersedia untuk admin.
- **Seeder demo 6 bulan** (Mei–November 2026): 81 nota, 18 riwayat hadiah, 1 program loyalitas,
  3 promosi, 5 pelanggan Member dan 5 Reguler, dengan nomor nota deterministik.
- **Pengujian baru**: `LoyaltyPromotionTest` (13 test), `LoyaltyPromotionHttpTest` (14 test),
  `DemoSeederTest` (2 test / 32 assertion), dan `RewardAccuracyTest` (5 test / 35 assertion)
  untuk akurasi hadiah Member dan promosi semua pelanggan.
- **Dokumentasi**: `CHANGELOG.md`, pembaruan README, panduan HTML, arsitektur, database,
  alur bisnis, inventaris fitur, laporan pengujian, kredensial demo, dan progres pengembangan.

### Diubah

- **Kolom Pelanggan Terdaftar pada form nota** menjadi combobox pencarian (komponen
  `PelangganCombobox`): default menampilkan **Pelanggan umum**, daftar difilter saat mengetik,
  mendukung navigasi keyboard (panah, Enter, Escape) dan badge kategori; memilih pelanggan
  mengisi nama serta membuka pilihan hadiah seperti sebelumnya.
- **Perhitungan nota** memakai potongan hadiah di server: `persen = round(total × min(100, nilai)/100)`,
  `nominal = min(total, nilai)`, dan `jumlah = total_harga − potongan`.
- **Edit nota** melepas hadiah lama lalu menerapkan ulang hadiah sesuai kondisi baru.
- **Manajemen pelanggan** menampilkan kategori, tanggal member, filter, dan aksi menuju
  halaman detail.
- **`db:seed`** kini membangun ulang data transaksi demo (nota, item nota, dan hadiah) dalam
  satu transaksi agar idempoten; data master tetap memakai `updateOrCreate`/`syncRoles`.
- **CSS cetak** menyembunyikan navigasi, tombol, dan footer (`.no-print`) serta memakai
  `@page` dinamis sesuai format yang dipilih.
- **Ekspor nota** kini menghasilkan workbook `.xlsx` native (Open XML) yang dibangun server-side
  oleh `App\Support\NotaExcelExport` tanpa dependency baru: judul perusahaan, ringkasan filter
  aktif, header beku, auto-filter, baris zebra, baris total, format Rupiah `Rp #,##0`, tanggal
  `dd/mm/yyyy hh:mm`, serta pengaturan cetak lanskap A4 fit-to-width.
- **Dokumentasi teknis** diselaraskan dengan kondisi final: 56 route aplikasi, 14 permission,
  131 test / 705 assertion, dan build 805 modul.

### Diperbaiki

- **Ukuran kertas thermal**: Chrome menolak nilai `@page size: <lebar> auto` sehingga pratinjau
  cetak jatuh ke A4. Kini `@page` memakai lebar 80/58 mm dengan tinggi eksplisit yang dihitung
  dari tinggi struk + dua margin halaman, disinkronkan ulang pada `beforeprint`; pratinjau dan
  PDF memakai ukuran kertas thermal yang benar tanpa halaman spil.
- **Integritas pembatalan**: pembatalan nota melepas hadiah terkait, tetapi ditolak jika
  hadiah tersebut sudah dipakai pada nota lain.
- **Diskon manual** dipaksa menjadi 0 ketika nota memakai hadiah, sehingga potongan tidak
  dihitung dua kali.
- **Satu hadiah per nota** ditegakkan di backend melalui aturan validasi dan penguncian baris.
- **Seeder** tidak lagi menggandakan nota atau meninggalkan nota yatim saat dijalankan ulang.
- **Validasi hadiah Gratis Cuci** menerima nilai 0 (gratis penuh) pada promosi dan loyalitas;
  nilai 0 tetap ditolak untuk hadiah nominal dan persen.
- **Batas input form** diselaraskan dengan validasi server: nilai hadiah dinamis (0 hanya untuk
  Gratis Cuci, maksimal 100 untuk persen, reset otomatis saat tipe berubah), serta `max` untuk
  target transaksi, masa berlaku, harga master, berat cucian, dan jumlah barang.
- **Hapus data dari daftar berfilter** kembali ke daftar tanpa filter/pencarian dan ke halaman
  pertama, sehingga semua data yang tersisa langsung tampil. Berlaku untuk Harga Per Barang,
  Harga Paket Kiloan, Jenis Layanan, Karyawan, Pelanggan, Pengguna, Promosi, dan Nota; kolom
  cari, filter status/kategori/role, serta jumlah baris ikut disinkronkan dengan kondisi server.

### Keamanan

- Otorisasi loyalitas dan promosi ditegakkan di server melalui permission; karyawan mendapat
  `403` pada `/loyalitas` dan `/promosi`.
- Batas kepemilikan nota dan PDF pelanggan tetap berlaku: pelanggan lain mendapat `404`.
- Tidak ada perubahan pada source code sistem lama dan tidak ada operasi destruktif terhadap
  database lama maupun database produksi.

### Catatan Pembaruan

```bash
# Database produksi: hanya migrasi
php artisan migrate

# Demo/pelatihan: bangun ulang database dan data demo
php artisan migrate:fresh --seed

# Menyegarkan data demo tanpa menghapus struktur
php artisan db:seed
php artisan storage:link
npm run build
```

> `db:seed` menghapus seluruh isi `notas` dan `customer_rewards` pada database demo lalu
> membangun ulang riwayat 6 bulan. Jangan jalankan `migrate:fresh` pada database produksi.
> Ganti seluruh kredensial demo sebelum aplikasi dipakai di lingkungan nyata.

### Verifikasi

- `php artisan test`: **131 test lulus, 705 assertion** (28,87 detik, pengukuran 26 November 2026).
- `npx svelte-check --tsgo --tsconfig ./tsconfig.json`: **0 error, 0 warning**.
- `npm run build`: **805 modul**, JS 493,83 kB (gzip 131,41 kB), CSS 101,64 kB (gzip 17,28 kB).
- Walkthrough live final (26 November 2026): matriks status admin/karyawan/pelanggan sesuai,
  combobox pelanggan berfungsi di form kiloan/satuan, ekspor XLSX dan PDF portal valid,
  format thermal 58 mm memakai `@page size: 58mm 104mm`, **0 error console** dan 0 `local.ERROR`.
- Database demo dikembalikan ke baseline seeder pada 26 November 2026 (15 pengguna, 4 jenis
  layanan, loyalitas diskon 10%, 81 nota, 18 hadiah) dan diverifikasi ulang. Database demo
  kemudian dibangun ulang dari nol pada 27 November 2026 — lihat [1.1.1].
