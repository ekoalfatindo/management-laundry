const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];

const BELASAN = [
    'sepuluh',
    'sebelas',
    'dua belas',
    'tiga belas',
    'empat belas',
    'lima belas',
    'enam belas',
    'tujuh belas',
    'delapan belas',
    'sembilan belas',
];

const PULUHAN = [
    '',
    '',
    'dua puluh',
    'tiga puluh',
    'empat puluh',
    'lima puluh',
    'enam puluh',
    'tujuh puluh',
    'delapan puluh',
    'sembilan puluh',
];

const SKALA = ['', 'ribu', 'juta', 'miliar', 'triliun', 'kuadriliun', 'kuintiliun'];

function spell(number: number): string {
    if (number === 0) {
        return 'nol';
    }

    if (number < 10) {
        return SATUAN[number];
    }

    if (number < 20) {
        return BELASAN[number - 10];
    }

    if (number < 100) {
        const remainder = number % 10;

        return PULUHAN[Math.floor(number / 10)] + (remainder > 0 ? ` ${SATUAN[remainder]}` : '');
    }

    if (number < 200) {
        const remainder = number % 100;

        return `seratus${remainder > 0 ? ` ${spell(remainder)}` : ''}`;
    }

    if (number < 1000) {
        const remainder = number % 100;

        return `${SATUAN[Math.floor(number / 100)]} ratus${remainder > 0 ? ` ${spell(remainder)}` : ''}`;
    }

    if (number < 2000) {
        const remainder = number % 1000;

        return `seribu${remainder > 0 ? ` ${spell(remainder)}` : ''}`;
    }

    for (let scale = SKALA.length - 1; scale >= 1; scale--) {
        const value = 1000 ** scale;

        if (number < value) {
            continue;
        }

        const remainder = number % value;

        return `${spell(Math.floor(number / value))} ${SKALA[scale]}${remainder > 0 ? ` ${spell(remainder)}` : ''}`;
    }

    return String(number);
}

export function terbilang(value: number | null | undefined): string {
    const amount = Math.abs(Math.round(Number(value) || 0));
    const words = spell(amount);

    return `${words.charAt(0).toUpperCase()}${words.slice(1)} rupiah`;
}
