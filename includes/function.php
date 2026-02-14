<?php

function triangular($x, $a, $b, $c) {
    if ($x <= $a || $x >= $c) return 0;
    elseif ($x == $b) return 1;
    elseif ($x > $a && $x < $b) return ($x - $a) / ($b - $a);
    elseif ($x > $b && $x < $c) return ($c - $x) / ($c - $b);
    return 0;
}

function fuzzyLogic($usia, $tingkat_kebugaran, $kondisi_kesehatan, $intensitas, $waktu_luang) {

    // FUZZIFIKASI USIA
    $usia_muda   = triangular($usia, 15, 30, 45);
    $usia_dewasa = triangular($usia, 35, 45, 65);
    $usia_lansia = triangular($usia, 55, 70, 85);

    // Centroid
    $c_muda = 30;
    $c_dewasa = 45;
    $c_lansia = 70;

    $sum_membership = $usia_muda + $usia_dewasa + $usia_lansia;

    if ($sum_membership > 0) {
        $usia_raw = ($usia_muda * $c_muda + $usia_dewasa * $c_dewasa + $usia_lansia * $c_lansia) / $sum_membership;
    } else {
        $usia_raw = $usia;
    }

    // Normalisasi usia
    $usia_score = ($usia_raw - 15) / (85 - 15);
    $usia_score = max(0, min(1, $usia_score));


    // FUZZIFIKASI KEBUGARAN
    $kebugaran_map = [
        'rendah' => 0.3,
        'sedang' => 0.6,
        'tinggi' => 0.9
    ];
    $kebugaran_score = isset($kebugaran_map[strtolower($tingkat_kebugaran)]) 
                      ? $kebugaran_map[strtolower($tingkat_kebugaran)] 
                      : 0.5;


    // FUZZIFIKASI KESEHATAN
    $kesehatan_map = [
        'kurang' => 0.4,
        'cukup' => 0.7,
        'baik' => 0.9
    ];
    $kesehatan_score = isset($kesehatan_map[strtolower($kondisi_kesehatan)]) 
                      ? $kesehatan_map[strtolower($kondisi_kesehatan)] 
                      : 0.7;


    // FUZZIFIKASI WAKTU LUANG
    $waktu_sedikit = triangular($waktu_luang, 0, 2, 5);
    $waktu_sedang  = triangular($waktu_luang, 3, 6, 10);
    $waktu_banyak  = triangular($waktu_luang, 8, 12, 20);

    $c_sedikit = 2;
    $c_sedang  = 6;
    $c_banyak  = 12;

    $sum_waktu = $waktu_sedikit + $waktu_sedang + $waktu_banyak;

    if ($sum_waktu > 0) {
        $waktu_raw = ($waktu_sedikit * $c_sedikit + $waktu_sedang * $c_sedang + $waktu_banyak * $c_banyak) / $sum_waktu;
    } else {
        $waktu_raw = $waktu_luang;
    }

    // Normalisasi waktu luang
    $waktu_score = $waktu_raw / 20;
    $waktu_score = max(0, min(1, $waktu_score));


    // BOBOT
    $bobot_kebugaran = 0.40;
    $bobot_kesehatan = 0.35;
    $bobot_usia      = 0.15;
    $bobot_waktu     = 0.10;

    // WEIGHTED SUM
    $skor_fuzzy = ($kebugaran_score * $bobot_kebugaran) +
                  ($kesehatan_score * $bobot_kesehatan) +
                  ($usia_score * $bobot_usia) +
                  ($waktu_score * $bobot_waktu);


    // MATCHING FUNCTION
    $selisih_intensitas = abs($skor_fuzzy - $intensitas);

    if ($selisih_intensitas <= 0.1) {
        $final_score = $skor_fuzzy * 1.0;
    } elseif ($selisih_intensitas <= 0.2) {
        $final_score = $skor_fuzzy * 0.95;
    } elseif ($selisih_intensitas <= 0.3) {
        $final_score = $skor_fuzzy * 0.9;
    } else {
        $final_score = $skor_fuzzy * 0.8;
    }


    // SAFETY ADJUSTMENT
    if ($kondisi_kesehatan === 'kurang') {
        if ($intensitas > 0.5) {
            $final_score *= 0.1;
        } elseif ($intensitas > 0.3) {
            $final_score *= 0.3;
        } else {
            $final_score *= 0.7;
        }
    }

    if ($kondisi_kesehatan === 'cukup' && $tingkat_kebugaran === 'rendah') {
        if ($intensitas > 0.6) {
            $final_score *= 0.2;
        } elseif ($intensitas > 0.5) {
            $final_score *= 0.4;
        }
    }

    if ($tingkat_kebugaran === 'rendah' && $kondisi_kesehatan === 'kurang' && $intensitas > 0.3) {
        $final_score *= 0.05;
    }

    if ($usia >= 40 && $tingkat_kebugaran === 'rendah' && $intensitas > 0.6) {
        $final_score *= 0.3;
    }

    return min(max(round($final_score, 3), 0), 1);
}


// BONUS PREFERENSI
function bonusPreferensi($preferensi_user, $lokasi_olahraga) {
    $pref = strtolower(trim($preferensi_user ?? ''));
    $lokasi = strtolower(trim($lokasi_olahraga ?? ''));

    if ($pref === 'fleksibel') return 0.02;
    if ($lokasi === 'fleksibel') return 0.02;

    if ($pref === $lokasi) return 0.10;

    return -0.30;
}


// BONUS TUJUAN
function bonusTujuan($tujuan, $kategori, $nama_olahraga) {
    $t = strtolower(trim($tujuan ?? ''));
    $cat = strtolower(trim($kategori ?? ''));
    $nama = strtolower(trim($nama_olahraga ?? ''));

    $bonus = 0.0;

    if (strpos($t, 'menurunkan berat badan') !== false) {
        if ($cat === 'kardio') $bonus = 0.15;
        elseif ($cat === 'fleksibilitas') $bonus = -0.10;
    }

    elseif (strpos($t, 'meningkatkan stamina') !== false) {
        if ($cat === 'kardio') $bonus = 0.15;
        elseif ($cat === 'kekuatan') $bonus = 0.05;
        else $bonus = -0.05;
    }

    elseif (strpos($t, 'menjaga kebugaran') !== false) {
        $bonus = 0.05;
    }

    elseif (strpos($t, 'massa otot') !== false) {
        if ($cat === 'kekuatan') $bonus = 0.15;
        elseif ($cat === 'kardio') $bonus = -0.10;
        else $bonus = -0.05;
    }

    return $bonus;
}


// FUNGSI INTENSITAS — PHP 7 VERSION
function getIntensityNumeric($row) {

    $intensitas_fuzzy = isset($row['intensitas_fuzzy']) ? floatval($row['intensitas_fuzzy']) : 0.0;
    if ($intensitas_fuzzy > 0) return $intensitas_fuzzy;

    $tk = strtolower(isset($row['tingkat_kesulitan']) ? $row['tingkat_kesulitan'] : '');

    switch ($tk) {
        case 'ringan':
            return 0.3;
        case 'sedang':
            return 0.6;
        case 'berat':
            return 0.9;
        default:
            return 0.5;
    }
}

?>
