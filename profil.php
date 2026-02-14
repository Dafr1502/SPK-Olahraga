<?php
session_start();
include 'config/db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_user = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_nama = trim($_POST['nama']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_nama === '') {
        $error = "Nama tidak boleh kosong.";
    } elseif ($new_password !== '' && $new_password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak sama.";
    } else {
        // Update nama
        $stmt = $conn->prepare("UPDATE users SET nama=? WHERE id_user=?");
        $stmt->bind_param("si", $new_nama, $id_user);
        $stmt->execute();

        // Update password jika diisi
        if ($new_password !== '') {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id_user=?");
            $stmt2->bind_param("si", $hashed, $id_user);
            $stmt2->execute();
        }
        $success = "Profil berhasil diperbarui.";
    }
}

// Ambil data user
$stmt = $conn->prepare("SELECT nama, email FROM users WHERE id_user=?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$stmt->bind_result($nama, $email);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Akun</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:500,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f5f6fa;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
        }

        header, .navbar {
            padding: 18px 30px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            background: #fff;
        }

        footer {
            text-align: center;
            padding: 18px 0;
            margin-top: 40px;
            background: #fff;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
            color: #00c853;
            font-weight: 600;
        }

        .main-content {
            padding-top: 50px;
            padding-bottom: 60px;
        }

        .card-profile {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
            padding: 2.5rem;
            max-width: 450px;
            margin: auto;
        }

        h3.title-profile {
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
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2196f3 0%, #00c853 100%);
        }

        .input-group-append .btn {
            border-radius: 0 8px 8px 0;
        }

        .btn-outline-secondary {
            font-family: 'Montserrat', sans-serif !important;
            font-weight: 600 !important;
            letter-spacing: 0.3px;
            border-radius: 8px;
            border-width: 2px;
            transition: all 0.2s ease-in-out;
        }

        .btn-outline-secondary:hover {
            color: #fff !important;
            border-color: transparent !important;
        }
            .input-group {
        display: flex;
        align-items: stretch;
    }
    
    .input-group .form-control {
        border-radius: 8px 0 0 8px !important;
        flex: 1;
    }
    
    .input-group-append {
        display: flex;
        align-items: stretch;
    }
    
    .input-group-append .btn {
        border-radius: 0 8px 8px 0 !important;
        padding: 0.375rem 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        height: auto;
        border-left: 1px solid #ced4da;
    }
    
    .input-group-append .btn i {
        margin: 0;
        font-size: 1rem;
    }
    
    @media (max-width: 767px) {
        .main-content {
            padding-top: 30px;
            padding-bottom: 40px;
        }
        
        .card-profile {
            padding: 1.5rem 1rem;
            margin: 0 10px;
            border-radius: 15px;
        }
        
        h3.title-profile {
            font-size: 1.5rem;
            margin-bottom: 1.2rem;
        }
        
        label {
            font-size: 14px;
            margin-bottom: 0.4rem;
        }
        
        .form-control {
            font-size: 14px !important;
            padding: 0.65rem 0.75rem !important;
            height: auto !important;
            min-height: 44px !important;
        }
        
        .input-group .form-control {
            min-height: 44px !important;
        }
        
        .input-group-append .btn {
            padding: 0 0.85rem !important;
            min-height: 44px !important;
            height: 44px;
            border-radius: 0 8px 8px 0 !important;
        }
        
        .input-group-append .btn i {
            font-size: 16px;
        }
        
        .btn-block {
            font-size: 14px !important;
            padding: 0.75rem 1rem !important;
            min-height: 44px !important;
        }
        
        .alert {
            font-size: 14px;
            padding: 0.75rem;
        }
        
        small.text-muted {
            font-size: 12px;
        }
        
        hr {
            margin: 1.2rem 0;
        }
    }
    
    @media (max-width: 575px) {
        .card-profile {
            padding: 1.2rem 0.75rem;
            margin: 0 5px;
        }
        
        h3.title-profile {
            font-size: 1.3rem;
        }
        
        label {
            font-size: 13px;
        }
        
        .form-control {
            font-size: 14px !important;
            padding: 0.6rem 0.7rem !important;
        }
        
        .input-group .form-control,
        .input-group-append .btn {
            min-height: 44px !important;
            height: 44px;
        }
        
        .input-group-append .btn {
            padding: 0 0.75rem !important;
        }
        
        .btn-block {
            font-size: 13px !important;
            padding: 0.7rem 0.9rem !important;
        }
        
        small.text-muted {
            font-size: 11px;
        }
    }
    
    @media (max-width: 375px) {
        .card-profile {
            padding: 1rem 0.6rem;
        }
        
        h3.title-profile {
            font-size: 1.15rem;
        }
        
        label {
            font-size: 12px;
        }
        
        .form-control {
            font-size: 13px !important;
            padding: 0.55rem 0.65rem !important;
        }
        
        .input-group .form-control,
        .input-group-append .btn {
            min-height: 42px !important;
            height: 42px;
        }
        
        .input-group-append .btn {
            padding: 0 0.65rem !important;
        }
        
        .input-group-append .btn i {
            font-size: 14px;
        }
        
        .btn-block {
            font-size: 12px !important;
            padding: 0.65rem 0.8rem !important;
        }
    }
    
    @media (max-width: 767px) {
        .container.mt-3 .btn-outline-secondary {
            font-size: 13px !important;
            padding: 0.5rem 0.9rem !important;
            min-height: auto !important;
        }
    }
    
    @media (max-width: 575px) {
        .container.mt-3 .btn-outline-secondary {
            font-size: 12px !important;
            padding: 0.45rem 0.8rem !important;
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

<div class="container main-content">
    <div class="card-profile">
        <h3 class="title-profile"><i class="fa-solid fa-user-circle mr-2"></i>Profil Akun</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo $success; ?>',
                    confirmButtonColor: '#00c853'
                });
            </script>
        <?php endif; ?>

        <form id="profileForm" method="POST" autocomplete="off">
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($nama); ?>" required>
            </div>
            <hr>
            <div class="form-group">
                <label>Password Baru <small class="text-muted">(Kosongkan jika tidak ingin mengganti)</small></label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordInput" class="form-control" autocomplete="new-password">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fa fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" autocomplete="new-password">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="fa fa-eye" id="eyeConfirmIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" id="saveBtn" class="btn btn-primary btn-block mt-3">Simpan Perubahan</button>
        </form>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    input.type === "password" ? input.type = "text" : input.type = "password";
    icon.classList.toggle('fa-eye-slash');
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
    const input = document.getElementById('confirmPasswordInput');
    const icon = document.getElementById('eyeConfirmIcon');
    input.type === "password" ? input.type = "text" : input.type = "password";
    icon.classList.toggle('fa-eye-slash');
});

document.getElementById('saveBtn').addEventListener('click', function () {
    Swal.fire({
        title: 'Simpan Perubahan?',
        text: "Pastikan data yang diubah sudah benar.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        background: '#f5f6fa',
        color: '#333'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('profileForm').submit();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
