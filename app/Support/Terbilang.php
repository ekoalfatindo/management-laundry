<?php

namespace App\Support;

class Terbilang
{
    /** @var array<int, string> */
    private const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];

    /** @var array<int, string> */
    private const BELASAN = [
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

    /** @var array<int, string> */
    private const PULUHAN = [
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

    /** @var array<int, string> */
    private const SKALA = ['', 'ribu', 'juta', 'miliar', 'triliun', 'kuadriliun', 'kuintiliun'];

    public static function rupiah(int|float $amount): string
    {
        return ucfirst(self::convert($amount)).' rupiah';
    }

    public static function convert(int|float $amount): string
    {
        return self::spell((int) round(abs($amount)));
    }

    private static function spell(int $number): string
    {
        if ($number === 0) {
            return 'nol';
        }

        if ($number < 10) {
            return self::SATUAN[$number];
        }

        if ($number < 20) {
            return self::BELASAN[$number - 10];
        }

        if ($number < 100) {
            $sisa = $number % 10;

            return self::PULUHAN[intdiv($number, 10)].($sisa > 0 ? ' '.self::SATUAN[$sisa] : '');
        }

        if ($number < 200) {
            $sisa = $number % 100;

            return 'seratus'.($sisa > 0 ? ' '.self::spell($sisa) : '');
        }

        if ($number < 1000) {
            $sisa = $number % 100;

            return self::SATUAN[intdiv($number, 100)].' ratus'.($sisa > 0 ? ' '.self::spell($sisa) : '');
        }

        if ($number < 2000) {
            $sisa = $number % 1000;

            return 'seribu'.($sisa > 0 ? ' '.self::spell($sisa) : '');
        }

        for ($skala = count(self::SKALA) - 1; $skala >= 1; $skala--) {
            $nilai = 1000 ** $skala;

            if ($number < $nilai) {
                continue;
            }

            $sisa = $number % $nilai;

            return self::spell(intdiv($number, $nilai)).' '.self::SKALA[$skala].($sisa > 0 ? ' '.self::spell($sisa) : '');
        }

        return (string) $number;
    }
}
