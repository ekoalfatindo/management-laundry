# DATABASE ANALYSIS — Legacy vs Aplikasi Baru

## 1. Tabel Legacy (hasil verifikasi migration + model + query)

### 1.1 `users`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint unsigned | PK |
| name | varchar(255) | not null |
| email | varchar(255) | unique |
| password | varchar(255) | not null |
| role | varchar(255) | not null (nilai: `Admin`, `Karyawan`, `Pelanggan`) |
| jenis_kelamin | enum(`Laki-Laki`,`Perempuan`) | not null |
| alamat | varchar(255) | nullable |
| email_verified_at | timestamp | nullable |
| remember_token | varchar(100) | nullable |
| created_at / updated_at | timestamp | |

### 1.2 `karyawans`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint unsigned | PK |
| user_id | bigint unsigned | FK → users.id, cascade |
| created_at / updated_at | timestamp | |

Tidak menyimpan data tambahan apa pun (wrapper 1:1 untuk menandai user karyawan).

### 1.3 `pelanggans`
Struktur identik dengan `karyawans`, FK → users.id cascade. Juga wrapper 1:1.

### 1.4 `barangs`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint unsigned | PK |
| name | varchar(255) | unique |
| harga | int | not null (rupiah) |
| timestamps | | |

### 1.5 `kiloans`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint unsigned | PK |
| paket | varchar(255) | not null |
| harga | int | not null (rupiah/kg) |
| description | varchar(255) | nullable |
| timestamps | | |

### 1.6 `jenis_layanans`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint unsigned | PK |
| jenis_cuci | varchar(255) | unique |
| waktu | varchar(255) | not null (contoh "180 Menit") |
| harga | int | not null (biaya layanan) |
| timestamps | | |

### 1.7 `notas`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint unsigned | PK |
| no_nota | varchar(255) | unique |
| jenis | enum(`Barang Satuan`,`Paket Kiloan`) | not null |
| nama_pelanggan | varchar(255) | not null (teks bebas, bukan FK) |
| jenis_layanan_id | bigint unsigned | nullable, FK → jenis_layanans.id, cascade |
| kiloan_id | bigint unsigned | nullable, FK → kiloans.id, cascade |
| berat | int | nullable |
| waktu | datetime | not null |
| jumlah | float | not null (nilai bersih setelah diskon) |
| diskon | int | not null (persen) |
| total_harga | float | not null (nilai bruto sebelum diskon) |
| timestamps | | |

Catatan: `ON DELETE CASCADE` pada master menyebabkan nota historis ikut terhapus bila master dihapus.

### 1.8 `nota_barangs`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint unsigned | PK |
| nota_id | bigint unsigned | FK → notas.id, cascade |
| barang | varchar(255) | not null (nama snapshot, bukan FK) |
| harga | float | not null |
| jumlah_barang | int | not null |
| total_harga | float | not null |
| timestamps | | |

### 1.9 Tabel bawaan Laravel
`password_reset_tokens`, `failed_jobs`, `personal_access_tokens` (Sanctum).

## 2. Relationship Map (Legacy)

```
users 1 ──── 0..1 karyawans        (user_id, cascade)
users 1 ──── 0..1 pelanggans       (user_id, cascade)
users 1 ──── * notas?              (TIDAK ADA FK — hanya nama_pelanggan teks)

jenis_layanans 1 ──── * notas      (nullable, cascade)
kiloans        1 ──── * notas      (nullable, cascade)
notas          1 ──── * nota_barangs (cascade)

barangs        * ──── ? notas      (model salah: hasMany tanpa FK)
nota_barangs   * ──── 1 notas      (nota_id)
```

### ERD Legacy (Mermaid)

```mermaid
erDiagram
    USERS ||--o| KARYAWANS : "user_id"
    USERS ||--o| PELANGGANS : "user_id"
    JENIS_LAYANANS ||--o{ NOTAS : "jenis_layanan_id"
    KILOANS ||--o{ NOTAS : "kiloan_id"
    NOTAS ||--o{ NOTA_BARANGS : "nota_id"
    USERS {
      bigint id PK
      string name
      string email UK
      string password
      string role
      enum jenis_kelamin
      string alamat
    }
    NOTAS {
      bigint id PK
      string no_nota UK
      enum jenis
      string nama_pelanggan
      bigint jenis_layanan_id FK
      bigint kiloan_id FK
      int berat
      datetime waktu
      float jumlah
      int diskon
      float total_harga
    }
    NOTA_BARANGS {
      bigint id PK
      bigint nota_id FK
      string barang
      float harga
      int jumlah_barang
      float total_harga
    }
```

## 3. Masalah Integritas Data Legacy

1. **Tidak ada relasi nota → pelanggan**; `nama_pelanggan` bebas teks sehingga ejaan bisa berbeda
   untuk orang yang sama dan histori per pelanggan tidak mungkin dihitung.
2. **`ON DELETE CASCADE` dari master ke nota** menghapus riwayat transaksi saat master dihapus.
3. **Harga tidak di-snapshot**: detail nota menampilkan harga master saat ini, bukan harga saat
   transaksi dibuat. Perubahan harga merusak struk lama.
4. **`nota_barangs.barang` berupa string**, bukan FK; rename barang memutus hubungan dan
   statistik pie dihitung berdasarkan string nama (`where('barang', $barang->name)`).
5. **Uang bertipe float** pada notas/nota_barangs, rawan pembulatan.
6. **Tidak ada index laporan** (created_at/waktu/jenis/nama_pelanggan).
7. **Diskon tidak dibatasi** 0–100.
8. **Tabel wrapper** `karyawans`/`pelanggans` menduplikasi `users.role`.
9. `users.role` berupa string bebas (tidak ada constraint/enum).
10. Tidak ada soft delete; penghapusan permanen.

## 4. Desain Database Aplikasi

Prinsip: mempertahankan seluruh informasi & perilaku bisnis, menormalkan relasi, menambah
snapshot historis, memperbaiki tipe uang, dan menambah index laporan.

### 4.1 Daftar Tabel Baru

| Tabel | Perubahan dari legacy |
|---|---|
| `users` | `role` dihapus → Spatie roles (`admin`, `karyawan`, `pelanggan`); `jenis_kelamin`, `alamat` dipertahankan |
| `karyawans` | **Dihapus** (redundan; karyawan = user dengan role `karyawan`) |
| `pelanggans` | **Dihapus** (pelanggan = user dengan role `pelanggan`) |
| `barangs` | Sama; `harga` menjadi unsigned int; index nama tetap unique |
| `kiloans` | Sama; `harga` unsigned int |
| `jenis_layanans` | Sama; `harga` unsigned int |
| `notas` | + `pelanggan_id` nullable FK → users (nullOnDelete); + snapshot `harga_per_kg`, `biaya_layanan`; `berat` decimal(8,2); uang unsigned bigint; diskon 0–100; index laporan |
| `nota_barangs` | + `barang_id` nullable FK → barangs (nullOnDelete); `barang` tetap sebagai snapshot nama; uang unsigned int |
| `roles/permissions/...` | Tabel Spatie Permission |
| `sessions`, `cache`, `jobs` | Tabel bawaan Laravel 13 (driver database) |

### 4.2 Detail Tabel Baru

#### `users`
`id`, `name`, `email` (unique), `password`, `jenis_kelamin` enum nullable,
`alamat` varchar nullable, `email_verified_at`, `remember_token`, timestamps.

#### `notas` (inti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint unsigned PK | |
| no_nota | varchar(20) unique | `#` + 3 huruf + 3 alfanumerik |
| jenis | enum(`Barang Satuan`,`Paket Kiloan`) | |
| pelanggan_id | bigint nullable FK users | nullOnDelete; opsional dipilih dari master |
| nama_pelanggan | varchar(255) | snapshot teks (kompatibel legacy) |
| jenis_layanan_id | bigint nullable FK jenis_layanans | nullOnDelete |
| kiloan_id | bigint nullable FK kiloans | nullOnDelete |
| berat | decimal(8,2) nullable | kg; boleh pecahan |
| harga_per_kg | unsigned int nullable | snapshot harga paket saat transaksi |
| biaya_layanan | unsigned int default 0 | snapshot biaya layanan saat transaksi |
| waktu | datetime | waktu transaksi (dipakai laporan) |
| subtotal? | — | Tidak ditambah; `total_harga` tetap bruto |
| total_harga | unsigned bigint | bruto sebelum diskon |
| diskon | unsigned tinyint | persen 0–100 |
| jumlah | unsigned bigint | netto setelah diskon (dasar pendapatan) |
| timestamps | | |

Index: `no_nota` unique, `created_at`, `waktu`, `jenis`, `nama_pelanggan`, `pelanggan_id`.

#### `nota_barangs`
`id`, `nota_id` FK cascade, `barang_id` nullable FK barangs nullOnDelete,
`barang` varchar snapshot, `harga` unsigned int, `jumlah_barang` unsigned int,
`total_harga` unsigned bigint, timestamps. Index `nota_id`.

### 4.3 ERD Aplikasi (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ NOTAS : "pelanggan_id (nullable)"
    USERS }o--o{ ROLES : "model_has_roles"
    ROLES }o--o{ PERMISSIONS : "role_has_permissions"
    JENIS_LAYANANS |o--o{ NOTAS : "jenis_layanan_id"
    KILOANS |o--o{ NOTAS : "kiloan_id"
    NOTAS ||--o{ NOTA_BARANGS : "nota_id"
    BARANGS |o--o{ NOTA_BARANGS : "barang_id"
    USERS {
      bigint id PK
      string name
      string email UK
      string password
      enum jenis_kelamin
      string alamat
    }
    NOTAS {
      bigint id PK
      string no_nota UK
      enum jenis
      bigint pelanggan_id FK
      string nama_pelanggan
      bigint jenis_layanan_id FK
      bigint kiloan_id FK
      decimal berat
      int harga_per_kg
      int biaya_layanan
      datetime waktu
      bigint total_harga
      tinyint diskon
      bigint jumlah
    }
    NOTA_BARANGS {
      bigint id PK
      bigint nota_id FK
      bigint barang_id FK
      string barang
      int harga
      int jumlah_barang
      bigint total_harga
    }
```

### 4.4 Aturan Integritas Baru

1. Semua nilai uang dihitung ulang di server; input klien diabaikan.
2. Diskon divalidasi 0–100 dengan pembulatan rupiah `round()`.
3. Jenis nota menentukan field wajib: `Paket Kiloan` ⇒ `kiloan_id` + `berat`; `Barang Satuan`
   ⇒ `jenis_layanan_id` + minimal 1 baris barang.
4. Harga satuan, harga per kg, dan biaya layanan diambil dari master saat penyimpanan dan
   disimpan sebagai snapshot.
5. Transaksi berjalan dalam `DB::transaction` (header + baris).
6. Master yang dihapus tidak menghapus nota (nullOnDelete).
7. Soft delete master tidak dipakai (legacy tidak memakainya). Master yang sudah dipakai transaksi
   tetap boleh dihapus; FK `nullOnDelete` dan snapshot pada nota menjaga riwayat tetap utuh.
8. `pelanggan_id` boleh kosong untuk kompatibilitas transaksi walk-in.

## 5. Verifikasi Implementasi (2026-11-24)

Skema di atas diverifikasi langsung terhadap migration dan database berjalan:

| Migration | Tabel |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_11_24_121457_create_permission_tables` | 5 tabel Spatie (roles, permissions, pivot) |
| `2026_11_24_130001_create_barangs_table` | `barangs` |
| `2026_11_24_130002_create_kiloans_table` | `kiloans` |
| `2026_11_24_130003_create_jenis_layanans_table` | `jenis_layanans` |
| `2026_11_24_130004_create_notas_table` | `notas` |
| `2026_11_24_130005_create_nota_barangs_table` | `nota_barangs` |

Fakta yang dicek:

- `notas.no_nota` varchar(20) unique; `jenis` enum dua nilai; `pelanggan_id`,
  `jenis_layanan_id`, `kiloan_id` nullable dengan `nullOnDelete`.
- `notas.berat` decimal(8,2); `total_harga`/`jumlah` unsigned bigint; `diskon` unsigned tinyint.
- Index `waktu`, `jenis`, `nama_pelanggan` ada.
- `nota_barangs.nota_id` cascade; `barang_id` nullable `nullOnDelete`; snapshot `barang` + `harga`.
- `users` menyimpan `jenis_kelamin` enum nullable dan `alamat`; peran ada di tabel Spatie.
- Hasil `migrate:fresh --seed`: 15 user, 10 barang, 5 kiloan, 4 layanan, 68 nota, 63 baris nota,
  3 role, 12 permission. `db:seed` ulang tidak mengubah jumlah (idempoten).
- Test database terpisah `laundry_svelte_test` dipakai oleh PHPUnit (`RefreshDatabase`).
