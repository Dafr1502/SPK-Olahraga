<?php
session_start();
include 'config/db.php';
include 'includes/function.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_user = $_SESSION['user_id'];
$id_kuesioner = intval($_GET['id_kuesioner'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM kuesioner WHERE id_kuesioner = ? AND id_user = ?");
$stmt->bind_param("ii", $id_kuesioner, $id_user);
$stmt->execute();
$kuesioner = $stmt->get_result()->fetch_assoc();
if (!$kuesioner) {
    die("Data kuesioner tidak ditemukan atau bukan milik Anda.");
}

$usia = intval($kuesioner['usia']);
$tingkat_kebugaran = $kuesioner['tingkat_kebugaran'];
$kondisi_kesehatan = $kuesioner['kondisi_kesehatan'] ?? 'baik';
$preferensi_olahraga = $kuesioner['preferensi_olahraga'] ?? '';
$tujuan_kesehatan = $kuesioner['tujuan_kesehatan'] ?? '';
$waktu_luang = intval($kuesioner['waktu_luang'] ?? 5);

$max_intensity = 1.0;

// Filter berdasarkan kondisi kesehatan & kebugaran
if ($kondisi_kesehatan === 'kurang') {
    if ($tingkat_kebugaran === 'rendah') {
        $max_intensity = 0.3;
    } else {
        $max_intensity = 0.5;
    }
} elseif ($kondisi_kesehatan === 'cukup') {
    if ($tingkat_kebugaran === 'rendah') {
        $max_intensity = 0.5;
    } elseif ($tingkat_kebugaran === 'sedang') {
        $max_intensity = 0.7;
    }
}

// Filter berdasarkan usia
if ($usia >= 60) {
    $max_intensity = min($max_intensity, 0.5);
} elseif ($usia >= 50) {
    $max_intensity = min($max_intensity, 0.6);
} elseif ($usia >= 40) {
    $max_intensity = min($max_intensity, 0.7);
}


$olahraga_result = $conn->query("SELECT * FROM olahraga");
if (!$olahraga_result) {
    die("Query olahraga gagal: " . $conn->error);
}

$hasil = [];
while ($row = $olahraga_result->fetch_assoc()) {
    $intensitas_numeric = getIntensityNumeric($row);

    // FILTER: Skip olahraga yang melebihi max_intensity
    if ($intensitas_numeric > $max_intensity) {
        continue; 
    }
    
    $raw_fuzzy = fuzzyLogic($usia, $tingkat_kebugaran, $kondisi_kesehatan, $intensitas_numeric, $waktu_luang);

    
    $bonus_pref = bonusPreferensi($preferensi_olahraga, $row['lokasi_olahraga']);
    $bonus_tujuan = bonusTujuan($tujuan_kesehatan, $row['kategori'], $row['nama_olahraga']);

    $skor_final = max(0, min(1, $raw_fuzzy + $bonus_pref + $bonus_tujuan));

    $hasil[] = [
        'id_olahraga' => $row['id_olahraga'],
        'nama_olahraga' => $row['nama_olahraga'],
        'deskripsi' => $row['deskripsi'] ?? '',
        'rekomendasi_waktu' => $row['rekomendasi_waktu'] ?? '',
        'kategori' => $row['kategori'] ?? '',
        'tingkat_kesulitan' => $row['tingkat_kesulitan'] ?? '',
        'intensitas_numeric' => $intensitas_numeric,
        'raw_fuzzy' => $raw_fuzzy,
        'skor' => round($skor_final, 3),
        'bonus_preferensi' => round($bonus_pref, 3),
        'bonus_tujuan' => round($bonus_tujuan, 3)
    ];
}

// Sorting berdasarkan skor
usort($hasil, function($a, $b) {
    if ($b['skor'] == $a['skor']) {
        return $b['raw_fuzzy'] <=> $a['raw_fuzzy'];
    }
    return $b['skor'] <=> $a['skor'];
});

$top = array_slice($hasil, 0, 10);

// Simpan ke database dengan kolom bonus
$del = $conn->prepare("DELETE FROM hasil_rekomendasi WHERE id_user = ? AND id_kuesioner = ?");
$del->bind_param("ii", $id_user, $id_kuesioner);
$del->execute();

$insert = $conn->prepare("INSERT INTO hasil_rekomendasi (id_user, id_kuesioner, id_olahraga, output_fuzzy, skor, peringkat, bonus_preferensi, bonus_tujuan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

$peringkat = 1;
foreach ($top as $item) {
    $insert->bind_param("iiiddidd", $id_user, $id_kuesioner, $item['id_olahraga'], $item['raw_fuzzy'], $item['skor'], $peringkat, $item['bonus_preferensi'], $item['bonus_tujuan']);
    $insert->execute();
    $peringkat++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Rekomendasi Olahraga</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Montserrat', sans-serif;
    }
    .card-rekomendasi {
        background: rgba(255,255,255,0.96);
        border-radius: 18px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        padding: 2.5rem 2rem;
        max-width: 900px;
        margin: 40px auto;
    }
    h2.title-rekomendasi {
        color: #2196f3;
        font-weight: 700;
        text-align: center;
        margin-bottom: 2rem;
    }
    .btn-success {
        background: linear-gradient(90deg, #00c853 0%, #2196f3 100%);
        border: none;
        font-weight: bold;
        letter-spacing: 1px;
    }
    .btn-success:hover {
        background: linear-gradient(90deg, #2196f3 0%, #00c853 100%);
    }
    .btn-outline-secondary {
        font-weight: 500;
        border-radius: 8px;
    }
    .table thead th {
        background: #e3f2fd;
        color: #2196f3;
        font-weight: 600;
    }
    .table-bordered td, .table-bordered th {
        border-color: #b2dfdb;
    }
    .user-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #2196f3;
    }
    .skor-detail {
        font-size: 0.8em;
        color: #666;
    }
    .tingkat-kesulitan {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: 500;
    }
    .kesulitan-ringan { background-color: #e8f5e8; color: #2e7d32; }
    .kesulitan-sedang { background-color: #fff3e0; color: #f57c00; }
    .kesulitan-berat { background-color: #ffebee; color: #c62828; }
    
    @media (max-width: 767px) {
        .card-rekomendasi {
            padding: 1.5rem 1rem;
            margin: 15px 10px;
        }
        
        h2.title-rekomendasi {
            font-size: 1.5rem;
        }
        
        .user-info {
            padding: 10px;
        }
        
        .user-info h6 {
            font-size: 0.95rem;
        }
        
        .user-info table {
            font-size: 13px;
        }
        
        .user-info td {
            padding: 0.3rem !important;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .table-responsive table {
            min-width: 800px;
            margin-bottom: 0;
        }
        
        table {
            font-size: 12px;
            white-space: nowrap;
        }
        
        table th, table td {
            padding: 0.5rem;
            min-width: 100px;
            vertical-align: middle;
        }
        
        .table-responsive table thead th:first-child,
        .table-responsive table tbody td:first-child {
            position: sticky;
            left: 0;
            background: white;
            z-index: 2;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .table-responsive table thead th:first-child {
            z-index: 3;
            background: #e3f2fd;
        }
        
        .table th:nth-child(1), 
        .table td:nth-child(1) {
            min-width: 60px !important;
        }
        
        .table th:nth-child(2), 
        .table td:nth-child(2) {
            min-width: 140px !important;
        }
        
        .table th:nth-child(3), 
        .table td:nth-child(3) {
            min-width: 100px !important;
        }
        
        .table th:nth-child(4), 
        .table td:nth-child(4) {
            min-width: 100px !important;
        }
        
        .table th:nth-child(5), 
        .table td:nth-child(5) {
            min-width: 150px !important;
        }
        
        .table th:nth-child(6), 
        .table td:nth-child(6) {
            min-width: 180px !important;
        }
        
        .badge {
            font-size: 11px;
            padding: 3px 6px;
        }
        
        .tingkat-kesulitan {
            font-size: 10px;
            padding: 2px 5px;
        }
        
        .skor-detail {
            font-size: 10px;
            line-height: 1.3;
        }
        
        .btn-lg {
            font-size: 1rem;
            padding: 0.75rem 1.5rem;
            margin-bottom: 10px;
            width: 100%;
        }
        
        .alert {
            padding: 0.75rem;
            font-size: 14px;
        }
        
        .alert ul {
            padding-left: 1.2rem;
            font-size: 13px;
        }
    }
    
    @media (max-width: 575px) {
        .card-rekomendasi {
            padding: 1rem 0.75rem;
            margin: 10px 5px;
        }
        
        h2.title-rekomendasi {
            font-size: 1.3rem;
        }
        
        .table-responsive table {
            min-width: 750px;
        }
        
        table {
            font-size: 11px;
        }
        
        table th, table td {
            padding: 0.4rem;
            min-width: 80px;
        }
        
        .table th:nth-child(1), 
        .table td:nth-child(1) {
            min-width: 50px !important;
        }
        
        .skor-detail {
            font-size: 9px;
        }
        
        .user-info table {
            font-size: 12px;
        }
        
        .alert {
            padding: 0.75rem;
            font-size: 0.9rem;
        }
        
        .alert ul {
            font-size: 0.85rem;
        }
    }
    
    @media (max-width: 375px) {
        .card-rekomendasi {
            padding: 0.75rem 0.5rem;
        }
        
        h2.title-rekomendasi {
            font-size: 1.1rem;
        }
        
        .table-responsive table {
            min-width: 700px;
        }
        
        table {
            font-size: 10px;
        }
        
        table th, table td {
            padding: 0.3rem;
            min-width: 70px;
        }
        
        .user-info table {
            font-size: 11px;
        }
    }
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-3">
    <a href="dashboard.php" class="btn btn-outline-secondary mb-3">
        <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="card-rekomendasi">
    <h2 class="title-rekomendasi">
        <i class="fas fa-trophy"></i> Rekomendasi Olahraga Personal
    </h2>
    
   <!-- Info Profil User -->
<div class="user-info mb-4">
    <h6 class="mb-3" style="color:#2196f3; font-weight:700;">
        <i class="fas fa-user"></i> Profil Anda
    </h6>
    <div class="row">
        <div class="col-md-6 mb-2">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td style="width:120px;"><strong>Usia</strong></td>
                    <td>: <?= $usia ?> tahun</td>
                </tr>
                <tr>
                    <td><strong>Kebugaran</strong></td>
                    <td>: <?= ucfirst($tingkat_kebugaran) ?></td>
                </tr>
                <tr>
                    <td><strong>Kondisi</strong></td>
                    <td>: <?= ucfirst($kondisi_kesehatan) ?></td>
                </tr>
                <tr>
                    <td><strong>Waktu</strong></td>
                    <td>: <?= $waktu_luang ?> jam/minggu</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6 mb-2">
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <td style="width:120px;"><strong>Preferensi</strong></td>
                    <td>: <?= $preferensi_olahraga ?></td>
                </tr>
                <tr>
                    <td><strong>Tujuan</strong></td>
                    <td>: <?= $tujuan_kesehatan ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>



<?php
// Deteksi kondisi berisiko
$show_warning = false;
$warning_level = '';
$warning_message = '';
$warning_icon = 'warning';

if ($kondisi_kesehatan == 'kurang') {
    $show_warning = true;
    $warning_level = 'danger';
    $warning_icon = 'error';
    $warning_message = "
        <strong><i class='fas fa-exclamation-triangle'></i> Perhatian Khusus - Kondisi Kesehatan Kurang</strong><br>
        <ul style='text-align:left; margin-top:10px;'>
            <li><strong>WAJIB konsultasi dengan dokter</strong> sebelum memulai program olahraga</li>
            <li>Sistem hanya merekomendasikan aktivitas intensitas <strong>SANGAT RENDAH</strong> (≤0.3)</li>
            <li>Lakukan medical check-up untuk memastikan keamanan</li>
            <li>Hentikan aktivitas jika merasa tidak nyaman atau nyeri</li>
        </ul>
        <p style='color:#d32f2f; font-weight:bold; margin-top:10px;'>
            ⚠️ Rekomendasi ini BUKAN pengganti nasihat medis profesional.
        </p>
    ";
} elseif ($kondisi_kesehatan == 'cukup' && $tingkat_kebugaran == 'rendah') {
    $show_warning = true;
    $warning_level = 'warning';
    $warning_message = "
        <strong><i class='fas fa-info-circle'></i> Saran Keamanan</strong><br>
        <ul style='text-align:left; margin-top:10px;'>
            <li>Mulai dengan olahraga ringan (intensitas ≤0.5) dan tingkatkan secara bertahap</li>
            <li>Konsultasi dengan instruktur atau personal trainer dapat membantu</li>
            <li>Perhatikan sinyal tubuh dan jangan memaksakan diri</li>
            <li>Istirahat cukup antar sesi latihan (minimal 1-2 hari)</li>
        </ul>
    ";
} elseif ($usia >= 60) {
    $show_warning = true;
    $warning_level = 'info';
    $warning_message = "
        <strong><i class='fas fa-heartbeat'></i> Catatan untuk Usia 60+</strong><br>
        <ul style='text-align:left; margin-top:10px;'>
            <li>Sistem membatasi rekomendasi pada intensitas sedang (≤0.5)</li>
            <li>Fokus pada keseimbangan, fleksibilitas, dan latihan fungsional</li>
            <li>Disarankan warming-up 10-15 menit sebelum aktivitas</li>
            <li>Konsultasi dokter jika memiliki riwayat penyakit kronis</li>
        </ul>
    ";
}
?>

<?php if ($show_warning): ?>
<div class="alert alert-<?php echo $warning_level; ?> alert-dismissible fade show" role="alert" style="border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
    <?php echo $warning_message; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

    <!-- PERBAIKAN: Tambahkan class table-responsive -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th style="width: 8%">Peringkat</th>
                    <th style="width: 25%">Nama Olahraga</th>
                    <th style="width: 12%">Skor Akhir</th>
                    <th style="width: 15%">Kategori</th>
                    <th style="width: 20%">Waktu & Intensitas</th>
                    <th style="width: 20%">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($top as $item): ?>
                <tr>
                    <td class="text-center">
                        <span class="badge badge-primary"><?= $rank++ ?></span>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($item['nama_olahraga']) ?></strong>
                        <br>
                        <span class="tingkat-kesulitan kesulitan-<?= $item['tingkat_kesulitan'] ?>">
                            <?= ucfirst($item['tingkat_kesulitan']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <strong style="font-size: 1.1em; color: #2196f3;">
                            <?= number_format($item['skor'], 2) ?>
                        </strong>
                        <div class="skor-detail">
                            Fuzzy: <?= number_format($item['raw_fuzzy'], 2) ?><br>
                            <?php if ($item['bonus_preferensi'] > 0): ?>
                            +Pref: <?= number_format($item['bonus_preferensi'], 2) ?><br>
                            <?php endif; ?>
                            <?php if ($item['bonus_tujuan'] > 0): ?>
                            +Tujuan: <?= number_format($item['bonus_tujuan'], 2) ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-secondary">
                            <?= ucfirst($item['kategori']) ?>
                        </span>
                        <br>
                        <small class="text-muted">
                            Intensitas: <?= number_format($item['intensitas_numeric'], 1) ?>
                        </small>
                    </td>
                    <td>
                        <i class="fas fa-clock text-primary"></i>
                        <small><?= htmlspecialchars($item['rekomendasi_waktu']) ?></small>
                    </td>
                    <td>
                        <small><?= htmlspecialchars($item['deskripsi']) ?></small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="alert alert-info mt-4">
        <h6><i class="fas fa-info-circle"></i> Cara Membaca Rekomendasi:</h6>
        <ul class="mb-0 small">
            <li><strong>Skor Akhir:</strong> Nilai kesesuaian olahraga dengan profil Anda (0.00-1.00)</li>
            <li><strong>Fuzzy:</strong> Skor dari algoritma fuzzy logic berdasarkan kondisi fisik</li>
            <li><strong>Bonus:</strong> Tambahan skor dari preferensi lokasi dan tujuan kesehatan</li>
            <li><strong>Rekomendasi:</strong> Mulai dari peringkat 1-3 untuk hasil terbaik</li>
        </ul>
    </div>

    <div class="mt-4 text-center">
        <a href="feedback.php?id_kuesioner=<?= $id_kuesioner ?>" class="btn btn-success btn-lg mr-2 mb-2">
            <i class="fa-solid fa-star"></i> Beri Umpan Balik
        </a>
        <a href="kuesioner.php" class="btn btn-outline-primary btn-lg mb-2">
            <i class="fas fa-redo"></i> Isi Ulang Kuesioner
        </a>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
</body>
</html>