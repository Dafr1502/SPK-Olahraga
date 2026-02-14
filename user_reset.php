<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// cek admin
$uid = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role, nama FROM users WHERE id_user = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();
if (!$userRow || ($userRow['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Akses ditolak.";
    exit();
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

$message = '';
$error = '';
$newpw = '';

// handle POST reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $error = "Token tidak valid.";
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'reset') {
            $target = (int)($_POST['user_id'] ?? 0);
            if ($target <= 0) { $error = "User tidak valid."; }
            else {
                // generate password sementara
                $pw = bin2hex(random_bytes(4)); // 8 hex chars
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $u = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                $u->bind_param("si", $hash, $target);
                if ($u->execute()) {
                    $message = "Password berhasil direset. Simpan password sementara dan segera minta user mengganti.";
                    $newpw = $pw;
                } else {
                    $error = "Gagal reset: " . $conn->error;
                }
                $u->close();
            }
        } elseif ($action === 'set') {
            $target = (int)($_POST['user_id'] ?? 0);
            $pw = trim($_POST['password'] ?? '');
            if ($target <= 0 || $pw === '') { $error = "Data tidak lengkap."; }
            else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $u = $conn->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                $u->bind_param("si", $hash, $target);
                if ($u->execute()) {
                    $message = "Password diset sesuai input.";
                } else {
                    $error = "Gagal update: " . $conn->error;
                }
                $u->close();
            }
        }
    }
    // fall through to show page with result
}

// ambil daftar user
$users = [];
$q = $conn->query("SELECT id_user, nama, email, role FROM users ORDER BY nama ASC");
if ($q) while ($r = $q->fetch_assoc()) $users[] = $r;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password User</title>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Reset Password User</h4>
    <div>
      <a href="admin.php" class="btn btn-sm btn-secondary">Kembali</a>
      <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </div>

  <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?><?php if ($newpw) echo ' <strong>Password sementara:</strong> ' . htmlspecialchars($newpw); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <h6>Reset cepat (generate password sementara)</h6>
      <form method="POST" class="form-inline">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="reset">
        <div class="form-group mr-2">
          <select name="user_id" class="form-control">
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id_user'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-warning">Reset & Dapatkan Password Sementara</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h6>Set password manual untuk user</h6>
      <form method="POST" class="form-inline">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="set">
        <div class="form-group mr-2">
          <select name="user_id" class="form-control">
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id_user'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group mr-2">
          <input name="password" class="form-control" placeholder="Password baru">
        </div>
        <button class="btn btn-primary">Set Password</button>
      </form>
    </div>
  </div>

</div>
</body>
</html>