# DEMO CREDENTIALS

Data demo dibuat oleh seeder yang dapat dijalankan ulang (idempoten) pada database proyek baru `laundry_svelte`.

## 1. Menyiapkan data demo

```bash
php artisan migrate:fresh --seed
```

Menjalankan ulang tanpa menghapus data:

```bash
php artisan db:seed
```

`db:seed` memakai `updateOrCreate`/`syncRoles` dan nomor nota deterministik, sehingga akun, master data, dan nota tidak berganda.

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

Sepuluh pelanggan demo digunakan sebagai pemilik nota dan pilihan pada form transaksi. Semua memakai password `password` dan dapat masuk ke portal pelanggan untuk melihat nota miliknya (daftar, detail, dan unduh PDF). Pelanggan tidak memiliki akses ke halaman staf.

| Nama | Email |
| --- | --- |
| Galang Ardiansyah | `galang@laundry.test` |
| Megawati | `mega@laundry.test` |
| Ayu Lestari | `ayu@laundry.test` |
| Budi Santoso | `budi@laundry.test` |
| Citra Dewi | `citra@laundry.test` |
| Eko Prasetyo | `eko@laundry.test` |
| Fitri Handayani | `fitri@laundry.test` |
| Hendra Wijaya | `hendra@laundry.test` |
| Indah Permata | `indah@laundry.test` |
| Joko Susilo | `joko@laundry.test` |

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
| `pengaturan.manage` | Ya | Tidak | Tidak |

Catatan: peran `pelanggan` memang tidak memiliki permission staf; akses pelanggan dibatasi oleh middleware `pelanggan` dan kepemilikan `pelanggan_id` pada route `/akun`. Pembatasan per peran adalah penyesuaian keamanan pada proyek baru. Aplikasi legacy hanya memeriksa status login, sehingga seluruh pengguna yang masuk dapat mengakses semua endpoint. Detail keputusan ada di `LEGACY_MIGRATION_REPORT.md` dan `SYSTEM_ARCHITECTURE.md`.

## 5. Ringkasan data demo

| Data | Jumlah | Contoh isi |
| --- | --- | --- |
| Pengguna | 15 | 1 admin, 4 karyawan, 10 pelanggan |
| Barang satuan | 10 | Selimut, Rok, Celana Jeans, Kemeja, Bed Cover, Jas, Gaun, Sepatu, Taplak Meja, Masker |
| Paket kiloan | 5 | SUPER DUPER 5kg, 4 Jam Selesai, 8 Jam Selesai, 24 Jam Selesai |
| Jenis layanan | 4 | Reguler (180 menit), Cepat (60 menit), Kilat (30 menit), Super Duper (10 menit) |
| Nota | 68 | Campuran Paket Kiloan dan Barang Satuan, tersebar pada tahun berjalan dan Desember tahun sebelumnya |

Jumlah nota mengikuti bulan berjalan saat seeder dijalankan (per November 2026: 68 nota).
Nomor nota deterministik mencegah duplikasi saat seeder diulang.

## 6. Peringatan

Kredensial ini hanya untuk demonstrasi dan pengembangan lokal. Sebelum dipakai di lingkungan nyata:

- Ganti seluruh password demo.
- Tinjau akun pelanggan yang masih memerlukan akses portal, lalu nonaktifkan/hapus sisanya.
- Jalankan `php artisan config:cache` dan pastikan `APP_DEBUG=false`.
