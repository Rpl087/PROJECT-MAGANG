<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Mengamankan data dari Google Sheets Formula Injection (CSV Injection).
     * Jika string diawali dengan karakter formula (=, +, -, @),
     * kita tambahkan kutip satu (') di awal agar Google Sheets menganggapnya sebagai teks biasa.
     */
    protected function escapeFormula(mixed $value): mixed
    {
        if (is_string($value) && $value !== '') {
            $firstChar = $value[0];
            if (in_array($firstChar, ['=', '+', '-', '@'])) {
                return "'" . $value;
            }
        }
        return $value;
    }
}
