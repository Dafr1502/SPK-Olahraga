<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_SESSION['user_id'];
    $usia = $_POST['usia'];
    $tingkat_kebugaran = $_POST['tingkat_kebugaran'];
    $waktu_luang = $_POST['waktu_luang'];
    $kondisi_kesehatan = $_POST['kondisi_kesehatan'];
    $preferensi_olahraga = $_POST['preferensi_olahraga'];
    $tujuan_kesehatan = $_POST['tujuan_kesehatan'];

    $sql = "INSERT INTO kuesioner 
            (id_user, usia, tingkat_kebugaran, waktu_luang, kondisi_kesehatan, preferensi_olahraga, tujuan_kesehatan) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("Terjadi kesalahan: " . $conn->error);
    }

    $stmt->bind_param("iisssss", 
        $id_user,
        $usia,
        $tingkat_kebugaran,
        $waktu_luang,
        $kondisi_kesehatan,
        $preferensi_olahraga,
        $tujuan_kesehatan
    );

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
        header("Location: rekomendasi.php?id_kuesioner=" . $last_id);
        exit();
    } else {
        error_log("Execute failed: " . $stmt->error);
        die("Gagal menyimpan data: " . $stmt->error); 
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Anda</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Montserrat', sans-serif;
        }
        .card-kuesioner {
            background: rgba(255,255,255,0.96);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 2.5rem 2rem;
            max-width: 520px;
            margin: 40px auto;
        }
        h2.title-kuesioner {
            color: #2196f3;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        label {
            font-weight: 600;
            color: #2196f3;
        }
        .btn-primary {
            background: linear-gradient(90deg, #00c853 0%, #2196f3 100%);
            border: none;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #2196f3 0%, #00c853 100%);
        }
        .btn-outline-secondary {
            font-weight: 500;
            border-radius: 8px;
        }
        .form-text {
            color: #666;
        }
        .required {
            color: #dc3545;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Warning untuk kondisi kesehatan "Kurang"
    document.querySelector('select[name="kondisi_kesehatan"]').addEventListener('change', function() {
        if (this.value === 'kurang') {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian Khusus!',
                html: `
                    <div style="text-align:left;">
                        <p>Anda memilih kondisi kesehatan <strong>"Kurang"</strong>.</p>
                        <p><strong>⚠️ Sangat disarankan untuk:</strong></p>
                        <ul>
                            <li>Konsultasi dengan dokter sebelum memulai program olahraga</li>
                            <li>Melakukan medical check-up terlebih dahulu</li>
                            <li>Memulai dengan aktivitas intensitas sangat rendah</li>
                            <li>Monitor kondisi tubuh secara berkala</li>
                        </ul>
                        <p style="color:#f44336; font-weight:bold; margin-top:15px;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Sistem ini BUKAN pengganti konsultasi medis profesional.
                        </p>
                    </div>
                `,
                confirmButtonText: 'Saya Mengerti',
                confirmButtonColor: '#f44336',
                allowOutsideClick: false,
                width: '600px'
            });
        }
    });

    // Warning tambahan untuk kombinasi berisiko
    document.querySelector('form').addEventListener('submit', function(e) {
        const kesehatan = document.querySelector('select[name="kondisi_kesehatan"]').value;
        const kebugaran = document.querySelector('select[name="tingkat_kebugaran"]').value;

        if (kesehatan === 'kurang' && kebugaran === 'rendah') {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Kombinasi Berisiko Tinggi',
                html: `
                    <p style="text-align:left;">
                        Kombinasi <strong>kondisi kesehatan kurang</strong> dan 
                        <strong>kebugaran rendah</strong> memerlukan perhatian medis khusus.
                    </p>
                    <p style="text-align:left; color:#f44336;">
                        <strong>Sistem akan memberikan rekomendasi aktivitas intensitas SANGAT RENDAH.</strong>
                    </p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit();
                }
            });
        }
    });
});

    // Validasi real-time untuk usia
    document.querySelector('input[name="usia"]').addEventListener('input', function() {
        let usia = parseInt(this.value);
        let warningDiv = document.getElementById('usia-warning');
    
    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'usia-warning';
        warningDiv.className = 'mt-2';
        this.parentNode.appendChild(warningDiv);
    }
    
    // Validasi range
    if (isNaN(usia) || usia < 13 || usia > 90) {
        warningDiv.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-circle"></i> Usia harus antara 13-90 tahun</small>';
        this.classList.add('is-invalid');
        this.classList.remove('is-valid');
    } else {
        warningDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle"></i> Usia valid</small>';
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
});

    // Validasi saat submit
    document.querySelector('form').addEventListener('submit', function(e) {
        let usia = parseInt(document.querySelector('input[name="usia"]').value);
    
    if (isNaN(usia) || usia < 13 || usia > 90) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Usia Tidak Valid',
            text: 'Masukkan usia antara 13-90 tahun.',
            confirmButtonColor: '#f44336'
        });
        return false;
    }
    
    // Peringatan khusus untuk usia ekstrim
    if (usia >= 70) {
        e.preventDefault();
        Swal.fire({
            icon: 'info',
            title: 'Usia 70 Tahun ke Atas',
            html: `
                <p style="text-align:left;">
                    Untuk usia 70+, sistem akan merekomendasikan aktivitas dengan intensitas rendah-sedang.
                </p>
                <p style="text-align:left;">
                    <strong>Sangat disarankan:</strong>
                </p>
                <ul style="text-align:left;">
                    <li>Konsultasi dengan dokter sebelum memulai</li>
                    <li>Fokus pada latihan keseimbangan & fleksibilitas</li>
                    <li>Didampingi instruktur atau keluarga saat berolahraga</li>
                </ul>
            `,
            showCancelButton: true,
            confirmButtonText: 'Lanjutkan',
            cancelButtonText: 'Kembali',
            confirmButtonColor: '#2196f3'
        }).then((result) => {
            if (result.isConfirmed) {
                e.target.submit();
            }
        });
    }
});
</script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-3">
        <a href="dashboard.php" class="btn btn-outline-secondary mb-3">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card-kuesioner">
        <h2 class="title-kuesioner">Input Data Anda</h2>
        <p class="text-center text-muted mb-4">Isi data berikut untuk mendapatkan rekomendasi olahraga yang sesuai</p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- KRITERIA 1: USIA -->
            <div class="form-group">
                <label for="usia">Usia <span class="required">*</span></label>
                <input type="number" name="usia" class="form-control" min="13" max="90" required>
                <small class="form-text">Masukkan usia Anda dalam tahun (13-90 tahun).</small>
            </div>

            <!-- KRITERIA 2: TINGKAT KEBUGARAN FISIK SAAT INI -->
            <div class="form-group">
                <label for="tingkat_kebugaran">Tingkat Kebugaran Fisik Saat Ini <span class="required">*</span></label>
                <select name="tingkat_kebugaran" class="form-control" required>
                    <option value="">-- Pilih --</option>
                    <option value="rendah">Rendah (Jarang/tidak pernah berolahraga)</option>
                    <option value="sedang">Sedang (1-3x seminggu olahraga ringan)</option>
                    <option value="tinggi">Tinggi (Rutin >3x seminggu, kondisi bugar)</option>
                </select>
                <small class="form-text">Pilih sesuai kebiasaan olahraga Anda selama 3 bulan terakhir.</small>
            </div>

            <!-- KRITERIA 3: KONDISI KESEHATAN -->
            <div class="form-group">
                <label for="kondisi_kesehatan">Kondisi Kesehatan Umum <span class="required">*</span></label>
                <select name="kondisi_kesehatan" class="form-control" required>
                    <option value="">-- Pilih --</option>
                    <option value="baik">Baik (Tidak ada keluhan kesehatan berarti)</option>
                    <option value="cukup">Cukup (Ada sedikit keluhan ringan, namun masih aktif)</option>
                    <option value="kurang">Kurang (Sering merasa lelah atau ada keluhan kesehatan)</option>
                </select>
                <small class="form-text">Penilaian kondisi kesehatan Anda secara umum saat ini.</small>
            </div>

            <!-- KRITERIA 4: KETERSEDIAAN WAKTU -->
            <div class="form-group">
                <label for="waktu_luang">Ketersediaan Waktu untuk Olahraga (Jam/Minggu) <span class="required">*</span></label>
                <input type="number" name="waktu_luang" class="form-control" min="1" max="20" step="0.5" required>
                <small class="form-text">Estimasi waktu yang bisa Anda sisihkan untuk olahraga per minggu (1-20 jam).</small>
            </div>

            <!-- PREFERENSI LOKASI -->
            <div class="form-group">
                <label for="preferensi_olahraga">Preferensi Lokasi Olahraga <span class="required">*</span></label>
                <select name="preferensi_olahraga" class="form-control" required>
                    <option value="">-- Pilih --</option>
                    <option value="Indoor">Indoor (Dalam ruangan, gym, rumah)</option>
                    <option value="Outdoor">Outdoor (Luar ruangan, taman, lapangan)</option>
                    <option value="Fleksibel">Fleksibel (Indoor maupun outdoor)</option>
                </select>
                <small class="form-text">Pilih lokasi olahraga yang Anda sukai atau mudah diakses.</small>
            </div>

            <!-- TUJUAN KESEHATAN -->
            <div class="form-group">
                <label for="tujuan_kesehatan">Tujuan Kesehatan Pribadi <span class="required">*</span></label>
                <select name="tujuan_kesehatan" class="form-control" required>
                    <option value="">-- Pilih Tujuan --</option>
                    <option value="Menurunkan Berat Badan">Menurunkan Berat Badan</option>
                    <option value="Meningkatkan Stamina">Meningkatkan Stamina & Daya Tahan</option>
                    <option value="Menjaga Kebugaran">Menjaga Kebugaran & Kesehatan Umum</option>
                    <option value="Meningkatkan Massa Otot">Meningkatkan Massa Otot & Kekuatan</option>
                </select>
                <small class="form-text">Pilih tujuan utama yang ingin Anda capai melalui olahraga.</small>
            </div>

            <div class="alert alert-info mt-4">
                <small>
                    <i class="fas fa-info-circle"></i> 
                    <strong>Catatan:</strong> Sistem ini memberikan rekomendasi awal sebagai panduan. 
                    Untuk kondisi kesehatan khusus, konsultasikan dengan tenaga ahli.
                </small>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg mt-4">
                <i class="fas fa-search"></i> Dapatkan Rekomendasi Olahraga
            </button>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
    
</body>
</html>