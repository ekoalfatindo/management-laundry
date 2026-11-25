const decimal = new Intl.NumberFormat('id-ID');

const decimalWithFraction = new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
});

const dateTimeFormat = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const dateFormat = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
});

export function rupiah(value: number | null | undefined): string {
    return value === null || value === undefined ? '-' : `Rp ${decimal.format(value)}`;
}

export function number(value: number | null | undefined): string {
    return value === null || value === undefined ? '-' : decimal.format(value);
}

export function berat(value: number | null | undefined): string {
    return value === null || value === undefined ? '-' : `${decimalWithFraction.format(value)} kg`;
}

export function dateTime(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? '-' : dateTimeFormat.format(parsed);
}

export function dateOnly(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? '-' : dateFormat.format(parsed);
}

export function toDateTimeLocal(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return '';
    }

    const pad = (part: number): string => String(part).padStart(2, '0');

    return `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}T${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`;
}

export function jenisLabel(jenis: string): string {
    return jenis === 'Paket Kiloan' ? 'Paket Kiloan' : 'Barang Satuan';
}
