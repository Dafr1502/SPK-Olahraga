<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body.sport-bg {
            background: url('assets/bg_sport2.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        body.sport-bg::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(34, 49, 63, 0.7);
            z-index: 0;
        }
        .main-content {
            position: relative;
            z-index: 1;
            padding-bottom: 2rem;
        }
        h2.title-dashboard {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #fff;
            text-align: center;
            margin-bottom: 2rem;
            text-shadow: 0 2px 8px rgba(34,49,63,0.5), 0 1px 0 #2196f3;
            letter-spacing: 1px;
        }
        .dashboard-card {
            background: rgba(255,255,255,0.92);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            padding: 2rem 1.5rem;
            transition: transform 0.2s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            position: relative;
            z-index: 1;
        }
        .dashboard-card:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 24px rgba(0,0,0,0.18);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #2196f3;
        }
        h2, h5.card-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
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
        .btn-info {
            background: linear-gradient(90deg, #2196f3 0%, #00c853 100%);
            border: none;
            color: #fff;
            font-weight: bold;
        }
        .btn-warning {
            background: linear-gradient(90deg, #ff9800 0%, #00c853 100%);
            border: none;
            color: #fff;
            font-weight: bold;
        }
        .btn-danger {
            background: linear-gradient(90deg, #f44336 0%, #2196f3 100%);
            border: none;
            color: #fff;
            font-weight: bold;
        }
        .btn-info:hover {
            background: linear-gradient(90deg, #00c853 0%, #2196f3 100%);
            color: #fff;
        }
        .btn-warning:hover {
            background: linear-gradient(90deg, #00c853 0%, #ff9800 100%);
            color: #fff;
        }
        .btn-danger:hover {
            background: linear-gradient(90deg, #2196f3 0%, #f44336 100%);
            color: #fff;
        }
        a {
            color: #00c853;
            font-weight: 500;
        }
        a:hover {
            color: #2196f3;
            text-decoration: underline;
        }
        
        /* OVERRIDE RESPONSIVE - PRIORITAS TINGGI */
        
        /* Tablet */
        @media (max-width: 991px) {
            .dashboard-row .col-md-3 {
                flex: 0 0 50% !important;
                max-width: 50% !important;
            }
        }
        
        /* Mobile */
        @media (max-width: 767px) {
            .dashboard-row .col-md-3 {
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
            
            .card-icon {
                font-size: 2rem;
            }
            
            h2.title-dashboard {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .dashboard-card {
                padding: 1.5rem 1rem;
                margin-bottom: 1rem;
            }
            
            .main-content {
                padding: 10px 15px !important;
            }
        }
        
        /* Mobile Portrait */
        @media (max-width: 575px) {
            .dashboard-row .col-md-3 {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                padding-left: 15px !important;
                padding-right: 15px !important;
            }
            
            h2.title-dashboard {
                font-size: 1.3rem;
            }
            
            .dashboard-card {
                padding: 1.25rem 1rem;
            }
            
            .card-icon {
                font-size: 1.8rem;
            }
            
            h5.card-title {
                font-size: 1.1rem;
            }
            
            .card-text {
                font-size: 0.9rem;
            }
        }
        
        /* Very Small Devices */
        @media (max-width: 375px) {
            .dashboard-card {
                padding: 1rem 0.75rem;
            }
            
            h2.title-dashboard {
                font-size: 1.2rem;
            }
            
            .card-icon {
                font-size: 1.6rem;
            }
            
            h5.card-title {
                font-size: 1rem;
            }
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="sport-bg">
<?php include 'includes/header.php'; ?>

<div class="container main-content">
    <h2 class="title-dashboard">Selamat Datang di Dashboard</h2>
    <div class="row dashboard-row">
        <!-- Isi Kuesioner -->
        <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex">
            <div class="card dashboard-card text-center w-100">
                <i class="fa-solid fa-clipboard-list card-icon"></i>
                <h5 class="card-title">Isi Data</h5>
                <p class="card-text text-muted flex-grow-1">Lengkapi data Anda untuk dapat rekomendasi olahraga.</p>
                <a href="kuesioner.php" class="btn btn-primary btn-block mt-auto">Mulai</a>
            </div>
        </div>
        <!-- Riwayat Rekomendasi -->
        <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex">
            <div class="card dashboard-card text-center w-100">
                <i class="fa-solid fa-history card-icon"></i>
                <h5 class="card-title">Riwayat Rekomendasi</h5>
                <p class="card-text text-muted flex-grow-1">Lihat kembali hasil rekomendasi olahraga sebelumnya.</p>
                <a href="riwayat.php" class="btn btn-info btn-block mt-auto">Lihat</a>
            </div>
        </div>
        <!-- Feedback -->
        <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex">
            <div class="card dashboard-card text-center w-100">
                <i class="fa-solid fa-comments card-icon"></i>
                <h5 class="card-title">Feedback</h5>
                <p class="card-text text-muted flex-grow-1">Berikan saran atau rating agar aplikasi lebih baik.</p>
                <a href="feedback.php" class="btn btn-warning btn-block mt-auto">Kirim</a>
            </div>
        </div>
        <!-- Logout -->
        <div class="col-12 col-sm-6 col-md-3 mb-3 d-flex">
            <div class="card dashboard-card text-center w-100">
                <i class="fa-solid fa-right-from-bracket card-icon"></i>
                <h5 class="card-title">Logout</h5>
                <p class="card-text text-muted flex-grow-1">Keluar dari akun Anda dengan aman.</p>
                <a href="#" id="logoutBtn" class="btn btn-danger btn-block mt-auto">Logout</a>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Yakin ingin logout?',
        text: 'Anda akan keluar dari akun Anda.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', 
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>