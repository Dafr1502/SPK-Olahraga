<?php
session_start();
include 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    }
    elseif (!validateEmailDomain($email)) {
        $error = "Email tidak valid. Pastikan menggunakan email aktif (Gmail, Yahoo, Outlook, dll).";
    }
    elseif (isEmailExists($conn, $email)) {
        $error = "Email sudah terdaftar. Gunakan email lain atau login.";
    }
    elseif ($password !== $confirm) {
        $error = "Password dan konfirmasi password tidak sama.";
    }
    elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    }
    else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['register_success'] = "Registrasi berhasil! Silakan login.";
            header("Location: index.php");
            exit();
        } else {
            $error = "Registrasi gagal. Coba lagi.";
        }
    }
}

function validateEmailDomain($email) {
    $domain = substr(strrchr($email, "@"), 1);
    
    // Cek apakah domain memiliki MX record (Mail Exchange)
    if (checkdnsrr($domain, "MX")) {
        return true;
    }
    
    // Jika MX record tidak ada, cek A record
    if (checkdnsrr($domain, "A")) {
        return true;
    }
    
    return false;
}

function isEmailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Montserrat', sans-serif;
        }
        .card-register {
            background: rgba(255,255,255,0.96);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 2.5rem 2rem;
            max-width: 420px;
            margin: 40px auto;
        }
        h2.title-register {
            color: #2196f3;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
            font-family: 'Montserrat', sans-serif;
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
        .input-group-text {
            background: #f8f9fa;
            cursor: pointer;
        }
        .form-text {
            font-size: 0.85rem;
            color: #666;
        }
        .email-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .is-valid {
            border-color: #28a745;
        }
        .invalid-feedback, .valid-feedback {
            display: block;
            font-size: 0.85rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="container mt-3">
        <a href="index.php" class="btn btn-outline-secondary mb-3">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card-register">
        <h2 class="title-register">Registrasi Akun</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" id="registerForm">
            <div class="form-group">
                <label for="nama">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" id="nama" class="form-control" required minlength="3">
                <small class="form-text">Minimal 3 karakter</small>
            </div>

            <div class="form-group">
                <label for="email">Email Aktif <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" class="form-control" required>
                <small class="email-hint">
                    <i class="fas fa-info-circle"></i> 
                    Gunakan email aktif seperti Gmail, Yahoo, Outlook, atau email institusi
                </small>
                <div id="emailFeedback"></div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required minlength="6">
                    <div class="input-group-append">
                        <span class="input-group-text" id="togglePassword">
                            <i class="fa fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>
                <small class="form-text">Minimal 6 karakter</small>
                <div id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                    <div class="input-group-append">
                        <span class="input-group-text" id="toggleConfirmPassword">
                            <i class="fa fa-eye" id="eyeIconConfirm"></i>
                        </span>
                    </div>
                </div>
                <div id="confirmFeedback"></div>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-4" id="submitBtn">
                <i class="fas fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>

        <p class="mt-3 text-center">
            Sudah punya akun? <a href="index.php" style="color:#2196f3; font-weight:600;">Login</a>
        </p>
    </div>

    <script>
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value.trim();
            const feedback = document.getElementById('emailFeedback');
            const validDomains = [
                'gmail.com', 'yahoo.com', 'yahoo.co.id', 'outlook.com', 'hotmail.com',
                'icloud.com', 'live.com', 'msn.com', 'aol.com', 'protonmail.com',
                'yandex.com', 'zoho.com', 'mail.com'
            ];
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!email) {
                this.classList.remove('is-valid', 'is-invalid');
                feedback.innerHTML = '';
                return;
            }
            
            if (!emailRegex.test(email)) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                feedback.innerHTML = '<div class="invalid-feedback"><i class="fas fa-times-circle"></i> Format email tidak valid</div>';
                return;
            }
            
            const domain = email.split('@')[1];
            const isValidDomain = validDomains.some(d => domain === d || domain.endsWith('.' + d));
            
            if (isValidDomain || domain.includes('.ac.id') || domain.includes('.edu')) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                feedback.innerHTML = '<div class="valid-feedback"><i class="fas fa-check-circle"></i> Email valid</div>';
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                feedback.innerHTML = '<div class="invalid-feedback"><i class="fas fa-exclamation-triangle"></i> Gunakan email dari provider terpercaya (Gmail, Yahoo, dll)</div>';
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strength.innerHTML = '';
                return;
            }
            
            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[^a-zA-Z\d]/.test(password)) score++;
            
            let strengthText = '';
            let strengthColor = '';
            
            if (score <= 2) {
                strengthText = 'Lemah';
                strengthColor = 'danger';
            } else if (score <= 3) {
                strengthText = 'Sedang';
                strengthColor = 'warning';
            } else {
                strengthText = 'Kuat';
                strengthColor = 'success';
            }
            
            strength.innerHTML = `<small class="text-${strengthColor}"><i class="fas fa-shield-alt"></i> Kekuatan password: ${strengthText}</small>`;
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const feedback = document.getElementById('confirmFeedback');
            
            if (!confirm) {
                this.classList.remove('is-valid', 'is-invalid');
                feedback.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                feedback.innerHTML = '<div class="valid-feedback"><i class="fas fa-check-circle"></i> Password cocok</div>';
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                feedback.innerHTML = '<div class="invalid-feedback"><i class="fas fa-times-circle"></i> Password tidak cocok</div>';
            }
        });

        document.getElementById('togglePassword').addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
            const input = document.getElementById('confirm_password');
            const icon = document.getElementById('eyeIconConfirm');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            
            if (email.classList.contains('is-invalid')) {
                e.preventDefault();
                alert('Gunakan email yang valid!');
                email.focus();
                return false;
            }
            
            if (password.value !== confirm.value) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak sama!');
                confirm.focus();
                return false;
            }
            
            if (password.value.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                password.focus();
                return false;
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>