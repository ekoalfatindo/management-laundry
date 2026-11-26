# DEMO CREDENTIALS

Data demo dibuat oleh seeder yang dapat dijalankan ulang (idempoten) pada database `laundry_svelte`.
Database demo dan test dibuat ulang dari nol pada **27 November 2026**; seluruh 17 migrasi dan
10 seeder dijalankan ulang sehingga ID nota kembali mulai dari 1 (`#D2606-01` s.d. `#D2611-12`).

## 1. Menyiapkan data demo

```bash
php artisan migrate:fresh --seed
```

Menjalankan ulang tanpa menghapus data:

```bash
php artisan db:seed
```

`db:seed` memakai `updateOrCreate`/`syncRoles` untuk akun dan master data. **Data transaksi dibangun
ulang**: `NotaSeeder` menghapus seluruh isi `notas` dan `customer_rewards` di dalam satu transaksi,
lalu menyusun ulang 6 bulan terakhir dengan nomor nota deterministik (`#D{YY}{MM}-{NN}`), sehingga
tidak ada duplikasi maupun nota yatim saat seeder dijalankan berulang. Nota/reward yang Anda buat
manual di database demo akan hilang ketika `php artisan db:seed` dijalankan.

## 2. Akun staf (dapat login ke aplikasi)

| Peran | Nama | Email | Password |
| --- | --- | --- | --- |
| Admin | Octavyan Putra Ramadhan | `admin@laundry.test` | `password` |
| Karyawan | Vavian Ega Ramadhan | `vavian@laundry.test` | `password` |
| Karyawan | Sidiq Kurniawan | `sidiq@laundry.test` | `password` |
| Karyawan | Fadly Rizky Maulana | `fadly@laundry.test` | `password` |
| Karyawan | Puan Maharani | `puan@laundry.test` | `password` |

Hanya pengguna dengan peran `admin` atau `karyawan` yang diarahkan ke dashboard setelah masuk. Akun pelanggan juga dapat masuk, tetapi diarahkan ke portal `/akun`. Ketiga peran memakai satu halaman login yang sama.

Menu **Setup** (nama + logo perusahaan) hanya tersedia untuk admin. Pada mode pengembangan
(`MAIL_MAILER=log`), tautan **Lupa password?** dapat diambil dari `storage/logs/laravel.log`
setelah mengirim permintaan reset; token hanya berlaku sekali dan tidak mengubah akun demo
sampai kata sandi baru benar-benar disimpan.

## 3. Akun pelanggan (portal `/akun`)

Sepuluh pelanggan demo digunakan sebagai pemilik nota dan pilihan pada form transaksi. Lima di antaranya
berstatus **Member** (dengan tanggal `member_sejak`) dan lima **Reguler**, sehingga badge kategori,
filter pelanggan, serta program loyalitas/promosi dapat diuji tanpa mengubah data. Semua memakai
password `password` dan dapat masuk ke portal pelanggan untuk melihat nota miliknya (daftar, detail,
dan unduh PDF). Pelanggan tidak memiliki akses ke halaman staf.

| Nama | Email | Kategori | Member sejak |
| --- | --- | --- | --- |
| Galang Ardiansyah | `galang@laundry.test` | Reguler | — |
| Megawati | `mega@laundry.test` | Member | 10 bulan lalu |
| Ayu Lestari | `ayu@laundry.test` | Member | 9 bulan lalu |
| Budi Santoso | `budi@laundry.test` | Reguler | — |
| Citra Dewi | `citra@laundry.test` | Member | 8 bulan lalu |
| Eko Prasetyo | `eko@laundry.test` | Reguler | — |
| Fitri Handayani | `fitri@laundry.test` | Member | 7 bulan lalu |
| Hendra Wijaya | `hendra@laundry.test` | Reguler | — |
| Indah Permata | `indah@laundry.test` | Member | 6 bulan lalu |
| Joko Susilo | `joko@laundry.test` | Reguler | — |

## 4. Matriks peran dan permission

| Permission | Admin | Karyawan | Pelanggan |
| --- | --- | --- | --- |
| `dashboard.view` | Ya | Ya | Tidak |
| `users.manage` | Ya | Tidak | Tidak |
| `karyawan.manage` | Ya | Tidak | Tidak |
| `pelanggan.manage` | Ya | Ya | Tidak |
| `katalog.view` | Ya | Ya | Tidak |
| `katalog.manage` | Ya | Ya | Tidak |
| `nota.view` | Ya | Ya | Tidak |
| `nota.create` | Ya | Ya | Tidak |
| `nota.update` | Ya | Ya | Tidak |
| `nota.delete` | Ya | Tidak | Tidak |
| `nota.export` | Ya | Ya | Tidak |
| `loyalitas.manage` | Ya | Tidak | Tidak |
| `promosi.manage` | Ya | Tidak | Tidak |
| `pengaturan.manage` | Ya | Tidak | Tidak |

Catatan: peran `pelanggan` memang tidak memiliki permission staf; akses pelanggan dibatasi oleh middleware `pelanggan` dan kepemilikan `pelanggan_id` pada route `/akun`. Pembatasan per peran menjadi lapisan keamanan tambahan di samping pemeriksaan status login. Detail keputusan ada di `SYSTEM_ARCHITECTURE.md`.

## 5. Ringkasan data demo

| Data | Jumlah | Contoh isi |
| --- | --- | --- |
| Pengguna | 15 | 1 admin, 4 karyawan, 10 pelanggan (5 Member, 5 Reguler) |
| Barang satuan | 10 | Selimut, Rok, Celana Jeans, Kemeja, Bed Cover, Jas, Gaun, Sepatu, Taplak Meja, Masker |
| Paket kiloan | 5 | SUPER DUPER 5kg, 4 Jam Selesai, 8 Jam Selesai, 24 Jam Selesai |
| Jenis layanan | 4 | Reguler (180 menit), Cepat (60 menit), Kilat (30 menit), Super Duper (10 menit) |
| Program loyalitas | 1 | Member Laundry — target 5 transaksi Selesai, hadiah diskon 10%, berlaku 90 hari, aktif |
| Promosi | 3 | Promo Hemat Reguler (5%), Bonus Member Setia (Rp10.000), Promo Lebaran Berkah (nonaktif) |
| Nota | 81 | Campuran Paket Kiloan dan Barang Satuan, tersebar pada 6 bulan terakhir |
| Riwayat hadiah | 18 | 13 Tersedia, 2 Digunakan, 3 Kedaluwarsa (loyalitas dan promosi) |

Komposisi nota per November 2026 (81 nota): 74 **Selesai**, 5 **Diproses**, 2 **Dibatalkan**;
41 nota Barang Satuan dan 40 nota Paket Kiloan; 65 **Lunas** dan 16 **Belum Lunas**. Dua nota
Selesai memakai hadiah — promo pelanggan Mega pada `#D2611-02` (Rp125.000 → Rp115.000) dan hadiah
loyalitas pelanggan Fitri pada `#D2611-07` (Rp150.000 → Rp135.000) — sebagai contoh alur pemakaian
hadiah beserta `diskon = 0` dan pencatatan `nilai_terpakai`.

Jumlah nota mengikuti bulan berjalan saat seeder dijalankan; seeder selalu menjaga 6 bulan terakhir.
Nomor nota deterministik mencegah duplikasi saat seeder diulang dan `NotaSeeder` membangun ulang data
transaksi dalam satu transaksi database.

## 6. Peringatan

Kredensial ini hanya untuk demonstrasi dan pengembangan lokal. Sebelum dipakai di lingkungan nyata:

- Ganti seluruh password demo.
- Tinjau akun pelanggan yang masih memerlukan akses portal, lalu nonaktifkan/hapus sisanya.
- Nonaktifkan atau sesuaikan program loyalitas dan promosi demo sebelum dipakai nyata; hadiah hanya
  berupa potongan catatan operasional, bukan saldo uang atau saluran pembayaran.
- Jalankan `php artisan config:cache` dan pastikan `APP_DEBUG=false`.
