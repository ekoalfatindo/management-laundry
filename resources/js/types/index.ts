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

export type KategoriPelanggan = 'Reguler' | 'Member';

export type TipeHadiah = 'persen' | 'nominal' | 'gratis';

export type StatusHadiah = 'Tersedia' | 'Digunakan' | 'Kedaluwarsa' | 'Dibatalkan';

export type SumberHadiah = 'Loyalitas' | 'Promosi';

export type StatusNota = 'Diproses' | 'Selesai' | 'Dibatalkan';

export type StatusPembayaran = 'Belum Lunas' | 'Lunas';

export interface UserRecord {
    id: number;
    name: string;
    email: string;
    jenis_kelamin: string | null;
    alamat: string | null;
    kategori_pelanggan: KategoriPelanggan | null;
    member_sejak: string | null;
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

export interface NotaRewardRecord {
    id: number;
    label: string;
    sumber: SumberHadiah;
    tipe_hadiah: TipeHadiah;
    nilai_hadiah: number;
    nilai_terpakai: number;
}

export interface NotaRecord {
    id: number;
    no_nota: string;
    jenis: 'Paket Kiloan' | 'Barang Satuan';
    pelanggan_id: number | null;
    nama_pelanggan: string;
    pelanggan?: {
        id: number;
        name: string;
        email: string;
        kategori_pelanggan?: KategoriPelanggan | null;
        member_sejak?: string | null;
    } | null;
    berat: number | null;
    harga_per_kg: number | null;
    biaya_layanan: number;
    waktu: string | null;
    total_harga: number;
    diskon: number;
    diskon_nominal: number;
    potongan: number;
    reward_label: string | null;
    customer_reward_id: number | null;
    jumlah: number;
    status: StatusNota;
    status_pembayaran: StatusPembayaran;
    dibatalkan_at: string | null;
    is_selesai: boolean;
    is_dibatalkan: boolean;
    is_lunas: boolean;
    terbilang: string;
    reward?: NotaRewardRecord | null;
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
    kategori_pelanggan: KategoriPelanggan | null;
}

export interface RewardOption {
    id: number;
    user_id: number;
    user_name: string | null;
    label: string;
    tipe_hadiah: TipeHadiah;
    nilai_hadiah: number;
    nilai_label: string;
    kedaluwarsa_at: string | null;
}

export interface NotaFormOptions {
    barangs: BarangOption[];
    kiloans: KiloanRecord[];
    jenisLayanans: JenisLayananRecord[];
    pelanggans: PelangganOption[];
    hadiahs: RewardOption[];
}

export type NotaKind = 'kiloan' | 'satuan';

export interface RewardRecord {
    id: number;
    reward_key: string;
    label: string;
    sumber: SumberHadiah;
    tipe_hadiah: TipeHadiah;
    tipe_hadiah_label: string;
    nilai_hadiah: number;
    nilai_label: string;
    status: StatusHadiah;
    nilai_terpakai: number;
    promotion_id: number | null;
    promotion_nama: string | null;
    loyalty_program_id: number | null;
    loyalty_program_nama: string | null;
    siklus: number;
    diperoleh_at: string | null;
    kedaluwarsa_at: string | null;
    digunakan_at: string | null;
    nota_id: number | null;
}

export interface LoyaltyProgramRecord {
    id: number;
    nama: string;
    deskripsi: string | null;
    target_transaksi: number;
    tipe_hadiah: TipeHadiah;
    tipe_hadiah_label: string;
    nilai_hadiah: number;
    nilai_label: string;
    masa_berlaku_hari: number | null;
    is_active: boolean;
}

export interface LoyaltyProgress {
    program: LoyaltyProgramRecord;
    eligible: boolean;
    transaksi_selesai: number;
    target: number;
    sisa: number;
    siklus: number;
    hadiah_tersedia: number;
    hadiah_digunakan: number;
}

export interface PromotionRecord {
    id: number;
    nama: string;
    deskripsi: string | null;
    syarat: string | null;
    target_kategori: KategoriPelanggan | null;
    target_transaksi: number;
    tipe_hadiah: TipeHadiah;
    tipe_hadiah_label: string;
    nilai_hadiah: number;
    nilai_label: string;
    mulai: string | null;
    selesai: string | null;
    is_active: boolean;
    aktif_sekarang?: boolean;
    hadiah_tersedia?: number;
    hadiah_digunakan?: number;
    total_hadiah?: number;
}

export interface PromotionProgress {
    promotion: PromotionRecord;
    berlaku: boolean;
    aktif: boolean;
    transaksi_selesai: number;
    target: number;
    sisa: number;
    siklus: number;
    hadiah_tersedia: number;
    hadiah_digunakan: number;
}

export interface PelangganLoyalitas {
    loyalty: LoyaltyProgress | null;
    promotions: PromotionProgress[];
    rewards: RewardRecord[];
}
