<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_user = $_SESSION['user_id'];

// HANDLE HAPUS (dijalankan sebelum output apapun)
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id_kuesioner = (int) $_GET['hapus'];

    // Hapus hasil_rekomendasi terkait
    $ok1 = false; $ok2 = false;
    $stmt1 = $conn->prepare("DELETE FROM hasil_rekomendasi WHERE id_kuesioner = ? AND id_user = ?");
    if ($stmt1) {
        $stmt1->bind_param("ii", $id_kuesioner, $id_user);
        $ok1 = $stmt1->execute();
        $stmt1->close();
    }

    // Hapus kuesioner
    $stmt2 = $conn->prepare("DELETE FROM kuesioner WHERE id_kuesioner = ? AND id_user = ?");
    if ($stmt2) {
        $stmt2->bind_param("ii", $id_kuesioner, $id_user);
        $ok2 = $stmt2->execute();
        $stmt2->close();
    }

    if ($ok2) {
        header("Location: riwayat.php?deleted=1");
        exit();
    } else {
        header("Location: riwayat.php?deleted=0");
        exit();
    }
}

// Ambil data dengan informasi kuesioner yang lebih lengkap
$query = "SELECT hr.*, o.nama_olahraga, o.deskripsi, o.rekomendasi_waktu, o.kategori, o.tingkat_kesulitan,
          k.tanggal_pengisian, k.usia, k.tingkat_kebugaran, k.kondisi_kesehatan, k.waktu_luang, 
          k.preferensi_olahraga, k.tujuan_kesehatan,
          hr.bonus_preferensi, hr.bonus_tujuan
          FROM hasil_rekomendasi hr
          JOIN olahraga o ON hr.id_olahraga = o.id_olahraga
          JOIN kuesioner k ON hr.id_kuesioner = k.id_kuesioner
          WHERE hr.id_user = ?
          ORDER BY k.tanggal_pengisian DESC, hr.peringkat ASC";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query gagal dipersiapkan: " . $conn->error);
}
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

$riwayat = [];
$profil_kuesioner = []; 
while ($row = $result->fetch_assoc()) {
    $riwayat[$row['id_kuesioner']][] = $row;
    
    // Simpan profil kuesioner (hanya sekali per id_kuesioner)
    if (!isset($profil_kuesioner[$row['id_kuesioner']])) {
        $profil_kuesioner[$row['id_kuesioner']] = [
            'usia' => $row['usia'],
            'tingkat_kebugaran' => $row['tingkat_kebugaran'],
            'kondisi_kesehatan' => $row['kondisi_kesehatan'] ?? 'baik',
            'waktu_luang' => $row['waktu_luang'],
            'preferensi_olahraga' => $row['preferensi_olahraga'],
            'tujuan_kesehatan' => $row['tujuan_kesehatan'],
            'tanggal_pengisian' => $row['tanggal_pengisian']
        ];
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Rekomendasi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Montserrat', sans-serif;
        }
        .card-riwayat {
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            margin-bottom: 2rem;
        }
        .card-header {
            background: linear-gradient(90deg, #2196f3 0%, #00c853 100%);
            color: #fff;
            font-weight: 600;
            border-radius: 18px 18px 0 0 !important;
        }
        h2.title-riwayat {
            color: #2196f3;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            font-family: 'Montserrat', sans-serif;
        }
        .btn-danger {
            background: linear-gradient(90deg, #f44336 0%, #2196f3 100%);
            border: none;
            font-weight: bold;
        }
        .btn-danger:hover {
            background: linear-gradient(90deg, #2196f3 0%, #f44336 100%);
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
        .profil-info {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 0.9em;
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
        .skor-detail {
            font-size: 0.8em;
            color: #666;
        }
        
    </style>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container mt-3">
    <a href="dashboard.php" class="btn btn-outline-secondary mb-3">
        <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="container mb-5">
    <h2 class="title-riwayat">Riwayat Rekomendasi Anda</h2>

    <?php if (isset($_GET['deleted'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['deleted'] == '1'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Riwayat rekomendasi berhasil dihapus.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                <?php else: ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat menghapus riwayat.',
                    });
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

    <?php if (empty($riwayat)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> 
            Belum ada riwayat rekomendasi. 
            <a href="kuesioner.php" class="btn btn-primary btn-sm ml-2">
                <i class="fas fa-plus"></i> Buat Rekomendasi Pertama
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($riwayat as $id_kuesioner => $list): ?>
            <?php $profil = $profil_kuesioner[$id_kuesioner]; ?>
            <div class="card card-riwayat shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-calendar-day"></i> 
                        <strong><?= date('d M Y, H:i', strtotime($profil['tanggal_pengisian'])); ?></strong>
                        <span class="badge badge-light ml-2"><?= count($list) ?> rekomendasi</span>
                    </div>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $id_kuesioner ?>)">
                        <i class="fa-solid fa-trash"></i> Hapus Riwayat
                    </button>
                </div>
                
                <div class="card-body">
                    <!-- Tampilkan profil user saat mengisi kuesioner -->
                    <div class="profil-info">
                        <strong><i class="fas fa-user"></i> Profil Saat Isi Kuesioner:</strong><br>
                        <div class="row">
                            <div class="col-md-2"><strong>Usia:</strong> <?= $profil['usia'] ?> tahun</div>
                            <div class="col-md-3"><strong>Kebugaran:</strong> <?= ucfirst($profil['tingkat_kebugaran']) ?></div>
                            <div class="col-md-2"><strong>Kondisi:</strong> <?= ucfirst($profil['kondisi_kesehatan']) ?></div>
                            <div class="col-md-2"><strong>Waktu:</strong> <?= $profil['waktu_luang'] ?> jam/minggu</div>
                            <div class="col-md-3"><strong>Tujuan:</strong> <?= $profil['tujuan_kesehatan'] ?></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 8%">Peringkat</th>
                                    <th style="width: 25%">Olahraga</th>
                                    <th style="width: 12%">Skor Akhir</th>
                                    <th style="width: 15%">Kategori</th>
                                    <th style="width: 20%">Rekomendasi Waktu</th>
                                    <th style="width: 20%">Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list as $item): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge badge-primary"><?= $item['peringkat']; ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($item['nama_olahraga']); ?></strong>
                                            <br>
                                            <span class="tingkat-kesulitan kesulitan-<?= $item['tingkat_kesulitan'] ?>">
                                                <?= ucfirst($item['tingkat_kesulitan']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <strong style="color: #2196f3;">
                                                <?= number_format($item['skor'], 2); ?>
                                            </strong>
                                            <div class="skor-detail">
                                                Base: <?= number_format($item['output_fuzzy'], 2) ?><br>
                                                <?php if (isset($item['bonus_preferensi']) && $item['bonus_preferensi'] > 0): ?>
                                                +Pref: <?= number_format($item['bonus_preferensi'], 2) ?><br>
                                                <?php endif; ?>
                                                <?php if (isset($item['bonus_tujuan']) && $item['bonus_tujuan'] > 0): ?>
                                                +Tuj: <?= number_format($item['bonus_tujuan'], 2) ?>
                                                <?php endif; ?>
                                            </div> 
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?= ucfirst($item['kategori']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <i class="fas fa-clock text-primary"></i>
                                            <small><?= htmlspecialchars($item['rekomendasi_waktu']); ?></small>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($item['deskripsi']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="text-center mt-4">
            <a href="kuesioner.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus"></i> Buat Rekomendasi Baru
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Yakin ingin hapus riwayat ini?',
        text: "Data hasil rekomendasi dan kuesioner akan dihapus.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'riwayat.php?hapus=' + id;
        }
    });
}
</script>
</body>
</html>