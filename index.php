<?php
session_start();
include 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Email dan password wajib diisi.";
    } else {
        $stmt = $conn->prepare("SELECT id_user, password, nama, role FROM users WHERE email = ?");
        if (!$stmt) {
            die("Prepare gagal: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $hash = $row['password'];

            if (password_verify($password, $hash)) {
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $u = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                    $u->bind_param("si", $newHash, $row['id_user']);
                    $u->execute();
                    $u->close();
                }

                // secure session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id_user'];
                $_SESSION['user_name'] = $row['nama'];
                $_SESSION['role'] = $row['role'] ?? 'user';

                // redirect berdasarkan role
                if ($_SESSION['role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: dashboard.php");
                }
                $stmt->close();
                exit();
            } else {
                $error = "Email atau password salah.";
            }
        } else {
            $error = "Email atau password salah.";
        }
        $stmt->close();
    }
}
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/style.css">
<link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
</head>
<body class="sport-bg pt-4">
<div class="container main-content">
  <div class="row justify-content-center align-items-center" style="min-height: 90vh;">
    <div class="col-md-6 col-lg-5">
      <div class="card-login">
        <h3 class="mb-3 title-login">SPK Olahraga</h3>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
          </div>
          <div class="form-group">
            <label>Password</label>
          <div class="input-group">
            <input type="password" name="password" id="passwordInput" class="form-control" required>
          <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
        <i class="fa fa-eye" id="eyeIcon"></i>
      </button>
    </div>
  </div>
</div>
          <button class="btn btn-primary btn-block">Login</button>
        </form>
        <p class="mt-3 text-center">Belum punya akun? <a href="register.php">Daftar</a></p>
      </div>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
</body>
</html>