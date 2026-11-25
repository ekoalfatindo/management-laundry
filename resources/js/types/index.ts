export interface AuthUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
}

export interface FlashMessages {
    success: string | null;
    error: string | null;
    status: string | null;
}

export interface CompanyProfile {
    nama_perusahaan: string;
    logo_url: string | null;
}

export interface SharedProps {
    appName: string;
    company: CompanyProfile;
    auth: {
        user: AuthUser | null;
    };
    flash: FlashMessages;
    [key: string]: unknown;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
    links: {
        prev: string | null;
        next: string | null;
    };
}

export interface UserRecord {
    id: number;
    name: string;
    email: string;
    jenis_kelamin: string | null;
    alamat: string | null;
    roles: string[];
    role: string | null;
    created_at: string | null;
}

export interface NotaBarangRecord {
    id: number;
    barang_id: number | null;
    barang: string;
    harga: number;
    jumlah_barang: number;
    total_harga: number;
}

export interface KiloanRecord {
    id: number;
    paket: string;
    harga: number;
    description: string | null;
}

export interface JenisLayananRecord {
    id: number;
    jenis_cuci: string;
    waktu: string;
    harga: number;
}

export interface NotaRecord {
    id: number;
    no_nota: string;
    jenis: 'Paket Kiloan' | 'Barang Satuan';
    pelanggan_id: number | null;
    nama_pelanggan: string;
    pelanggan?: { id: number; name: string; email: string } | null;
    berat: number | null;
    harga_per_kg: number | null;
    biaya_layanan: number;
    waktu: string | null;
    total_harga: number;
    diskon: number;
    jumlah: number;
    terbilang: string;
    kiloan?: KiloanRecord | null;
    jenis_layanan?: JenisLayananRecord | null;
    barangs?: NotaBarangRecord[];
    created_at: string | null;
}

export interface BarangOption {
    id: number;
    name: string;
    harga: number;
}

export interface PelangganOption {
    id: number;
    name: string;
}

export interface NotaFormOptions {
    barangs: BarangOption[];
    kiloans: KiloanRecord[];
    jenisLayanans: JenisLayananRecord[];
    pelanggans: PelangganOption[];
}

export type NotaKind = 'kiloan' | 'satuan';
