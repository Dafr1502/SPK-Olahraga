<?php

session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// pastikan admin
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

// CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

// POST actions: add / edit / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $error = "Token tidak valid.";
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $nama = trim($_POST['nama'] ?? '');
            $desc = trim($_POST['deskripsi'] ?? '');
            $kategori = trim($_POST['kategori'] ?? '');
            $tingkat = trim($_POST['tingkat_kesulitan'] ?? '');
            $rekom = trim($_POST['rekomendasi_waktu'] ?? '');
            $intens = is_numeric($_POST['intensitas_fuzzy'] ?? null) ? (float)$_POST['intensitas_fuzzy'] : null;
            $lokasi = trim($_POST['lokasi_olahraga'] ?? '');

            if ($nama === '') {
                $error = "Nama olahraga wajib diisi.";
            } else {
                $ins = $conn->prepare("INSERT INTO olahraga (nama_olahraga, deskripsi, kategori, tingkat_kesulitan, rekomendasi_waktu, intensitas_fuzzy, lokasi_olahraga) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param("ssssdis", $nama, $desc, $kategori, $tingkat, $rekom, $intens, $lokasi);
                if ($ins->execute()) {
                    $ins->close();
                    header("Location: manage_olahraga.php");
                    exit();
                } else {
                    $error = "Gagal menambah: " . $conn->error;
                }
                $ins->close();
            }

        } elseif ($action === 'edit') {
            $id = (int)($_POST['id_olahraga'] ?? 0);
            $nama = trim($_POST['nama'] ?? '');
            $desc = trim($_POST['deskripsi'] ?? '');
            $kategori = trim($_POST['kategori'] ?? '');
            $tingkat = trim($_POST['tingkat_kesulitan'] ?? '');
            $rekom = trim($_POST['rekomendasi_waktu'] ?? '');
            $intens = is_numeric($_POST['intensitas_fuzzy'] ?? null) ? (float)$_POST['intensitas_fuzzy'] : null;
            $lokasi = trim($_POST['lokasi_olahraga'] ?? '');

            if ($id <= 0 || $nama === '') {
                $error = "Data tidak lengkap.";
            } else {
                $up = $conn->prepare("UPDATE olahraga SET nama_olahraga=?, deskripsi=?, kategori=?, tingkat_kesulitan=?, rekomendasi_waktu=?, intensitas_fuzzy=?, lokasi_olahraga=? WHERE id_olahraga=?");
                $up->bind_param("ssssdisi", $nama, $desc, $kategori, $tingkat, $rekom, $intens, $lokasi, $id);
                if ($up->execute()) {
                    $up->close();
                    header("Location: manage_olahraga.php");
                    exit();
                } else {
                    $error = "Gagal mengubah: " . $conn->error;
                }
                $up->close();
            }

        } elseif ($action === 'delete') {
            $id = (int)($_POST['id_olahraga'] ?? 0);
            if ($id <= 0) {
                $error = "Id tidak valid.";
            } else {
                $del = $conn->prepare("DELETE FROM olahraga WHERE id_olahraga = ?");
                $del->bind_param("i", $id);
                if ($del->execute()) {
                    $del->close();
                    header("Location: manage_olahraga.php");
                    exit();
                } else {
                    $error = "Gagal menghapus: " . $conn->error;
                }
                $del->close();
            }
        }
    }
}

// ambil daftar olahraga
$rows = [];
$q = $conn->query("SELECT id_olahraga, nama_olahraga, deskripsi, kategori, tingkat_kesulitan, rekomendasi_waktu, intensitas_fuzzy, lokasi_olahraga FROM olahraga ORDER BY nama_olahraga ASC");
if ($q) while ($r = $q->fetch_assoc()) $rows[] = $r;

// jika edit mode via GET
$edit = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $s = $conn->prepare("SELECT id_olahraga, nama_olahraga, deskripsi, kategori, tingkat_kesulitan, rekomendasi_waktu, intensitas_fuzzy, lokasi_olahraga FROM olahraga WHERE id_olahraga = ?");
    $s->bind_param("i", $eid);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc() ?: null;
    $s->close();
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manajemen Olahraga</title>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Manajemen Olahraga</h4>
    <div>
      <a href="admin.php" class="btn btn-sm btn-secondary">Kembali</a>
      <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </div>

  <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <h5 class="card-title"><?= $edit ? 'Edit Olahraga' : 'Tambah Olahraga' ?></h5>
      <form method="POST" class="form-row">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="id_olahraga" value="<?= (int)$edit['id_olahraga'] ?>"><?php endif; ?>

        <div class="form-group col-md-6">
          <label>Nama</label>
          <input name="nama" class="form-control" required value="<?= htmlspecialchars($edit['nama_olahraga'] ?? '') ?>">
        </div>
        <div class="form-group col-md-6">
          <label>Kategori</label>
          <input name="kategori" class="form-control" value="<?= htmlspecialchars($edit['kategori'] ?? '') ?>">
        </div>
        <div class="form-group col-md-12">
          <label>Deskripsi</label>
          <textarea name="deskripsi" class="form-control"><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>
        </div>
        <div class="form-group col-md-4">
          <label>Tingkat Kesulitan</label>
          <input name="tingkat_kesulitan" class="form-control" value="<?= htmlspecialchars($edit['tingkat_kesulitan'] ?? '') ?>">
        </div>
        <div class="form-group col-md-4">
          <label>Rekomendasi Waktu</label>
          <input name="rekomendasi_waktu" class="form-control" value="<?= htmlspecialchars($edit['rekomendasi_waktu'] ?? '') ?>">
        </div>
        <div class="form-group col-md-2">
          <label>Intensitas (0-1)</label>
          <input name="intensitas_fuzzy" type="number" step="0.01" min="0" max="1" class="form-control" value="<?= htmlspecialchars($edit['intensitas_fuzzy'] ?? '') ?>">
        </div>
        <div class="form-group col-md-2">
          <label>Lokasi</label>
          <select name="lokasi_olahraga" class="form-control">
            <option value="Indoor" <?= (isset($edit['lokasi_olahraga']) && $edit['lokasi_olahraga'] === 'Indoor') ? 'selected' : '' ?>>Indoor</option>
            <option value="Outdoor" <?= (isset($edit['lokasi_olahraga']) && $edit['lokasi_olahraga'] === 'Outdoor') ? 'selected' : '' ?>>Outdoor</option>
            <option value="Fleksibel" <?= (isset($edit['lokasi_olahraga']) && $edit['lokasi_olahraga'] === 'Fleksibel') ? 'selected' : '' ?>>Fleksibel</option>
          </select>
        </div>

        <div class="form-group col-12">
          <button class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : 'Tambah Olahraga' ?></button>
          <?php if ($edit): ?><a href="manage_olahraga.php" class="btn btn-secondary">Batal</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h5 class="card-title">Daftar Olahraga</h5>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="thead-light">
            <tr><th>#</th><th>Nama</th><th>Kategori</th><th>Tingkat</th><th>Intensitas</th><th>Lokasi</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i => $row): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($row['nama_olahraga'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['kategori'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['tingkat_kesulitan'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['intensitas_fuzzy'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['lokasi_olahraga'] ?? '') ?></td>
                <td>
                  <a href="manage_olahraga.php?edit=<?= (int)$row['id_olahraga'] ?>" class="btn btn-sm btn-info">Edit</a>
                  <form method="POST" style="display:inline-block" onsubmit="return confirm('Hapus olahraga?');">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id_olahraga" value="<?= (int)$row['id_olahraga'] ?>">
                    <button class="btn btn-sm btn-danger">Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($rows) === 0): ?>
              <tr><td colspan="7" class="text-center text-muted">Belum ada data.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
</body>
</html>