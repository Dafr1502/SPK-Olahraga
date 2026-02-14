<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = (int) $_SESSION['user_id'];

// Ambil role user saat ini (pastikan keamanan)
$stmt = $conn->prepare("SELECT role, nama FROM users WHERE id_user = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$ures = $stmt->get_result();
if (!$ures || $ures->num_rows !== 1) {
    header("Location: index.php");
    exit();
}
$userRow = $ures->fetch_assoc();
$current_role = $userRow['role'] ?? 'user';

// Hanya admin yang boleh mengakses
if ($current_role !== 'admin') {
    http_response_code(403);
    include 'includes/header.php';
    echo '<div class="container mt-4"><div class="alert alert-danger">Akses ditolak. Halaman ini hanya untuk admin.</div></div>';
    include 'includes/footer.php';
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

$message = '';
$error = '';

// Tangani POST (sederhana, tetap seperti sebelumnya)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $error = "Token tidak valid.";
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'change_role') {
            $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
            $new_role = $_POST['role'] ?? 'user';
            if ($target_id <= 0) {
                $error = "User tidak valid.";
            } elseif ($target_id === $current_user_id) {
                $error = "Tidak dapat mengubah role akun sendiri.";
            } else {
                $allowed = ['user', 'admin'];
                if (!in_array($new_role, $allowed, true)) $new_role = 'user';
                $up = $conn->prepare("UPDATE users SET role = ? WHERE id_user = ?");
                $up->bind_param("si", $new_role, $target_id);
                if ($up->execute()) {
                    $message = "Role user berhasil diperbarui.";
                } else {
                    $error = "Gagal memperbarui role: " . $conn->error;
                }
                $up->close();
            }
        } elseif ($action === 'delete_user') {
            $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
            if ($target_id <= 0) {
                $error = "User tidak valid.";
            } elseif ($target_id === $current_user_id) {
                $error = "Tidak dapat menghapus akun sendiri.";
            } else {
                // Hapus transaksi terhubung terlebih dahulu, gunakan transaction
                $conn->begin_transaction();
                try {
                    $d1 = $conn->prepare("DELETE FROM feedback WHERE id_user = ?");
                    $d1->bind_param("i", $target_id); $d1->execute(); $d1->close();

                    $d2 = $conn->prepare("DELETE FROM hasil_rekomendasi WHERE id_user = ?");
                    $d2->bind_param("i", $target_id); $d2->execute(); $d2->close();

                    $d3 = $conn->prepare("DELETE FROM kuesioner WHERE id_user = ?");
                    $d3->bind_param("i", $target_id); $d3->execute(); $d3->close();

                    $d4 = $conn->prepare("DELETE FROM users WHERE id_user = ?");
                    $d4->bind_param("i", $target_id); $d4->execute(); $d4->close();

                    $conn->commit();
                    $message = "User dan data terkait berhasil dihapus.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Gagal menghapus user: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_feedback') {
            $fid = isset($_POST['feedback_id']) ? (int) $_POST['feedback_id'] : 0;
            if ($fid > 0) {
                $d = $conn->prepare("DELETE FROM feedback WHERE id_feedback = ?");
                $d->bind_param("i", $fid);
                if ($d->execute()) {
                    $message = "Feedback dihapus.";
                } else {
                    $error = "Gagal menghapus feedback.";
                }
                $d->close();
            } else {
                $error = "Feedback tidak valid.";
            }
        } elseif ($action === 'delete_kuesioner') {
            $kid = isset($_POST['kuesioner_id']) ? (int) $_POST['kuesioner_id'] : 0;
            if ($kid > 0) {
                $conn->begin_transaction();
                try {
                    // Hapus hasil rekomendasi terkait kuesioner dulu (jika ada)
                    $dhr = $conn->prepare("DELETE FROM hasil_rekomendasi WHERE id_kuesioner = ?");
                    $dhr->bind_param("i", $kid); $dhr->execute(); $dhr->close();

                    $dk = $conn->prepare("DELETE FROM kuesioner WHERE id_kuesioner = ?");
                    $dk->bind_param("i", $kid); $dk->execute(); $dk->close();

                    $conn->commit();
                    $message = "Kuesioner dan hasil terkait berhasil dihapus.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Gagal menghapus kuesioner: " . $e->getMessage();
                }
            } else {
                $error = "Kuesioner tidak valid.";
            }
        } elseif ($action === 'mark_feedback_handled') {
            $fid = isset($_POST['feedback_id']) ? (int) $_POST['feedback_id'] : 0;
            // jika tabel feedback memiliki kolom 'status' atau 'handled' gunakan UPDATE, jika tidak, buat asumsi kolom 'status'
            $hasStatus = $conn->query("SHOW COLUMNS FROM feedback LIKE 'status'")->num_rows > 0;
            if ($fid > 0 && $hasStatus) {
                $u = $conn->prepare("UPDATE feedback SET status = 'handled' WHERE id_feedback = ?");
                $u->bind_param("i", $fid);
                if ($u->execute()) $message = "Feedback ditandai sudah ditangani.";
                else $error = "Gagal menandai feedback.";
                $u->close();
            } else {
                $error = $hasStatus ? "Feedback tidak valid." : "Kolom status tidak tersedia pada tabel feedback.";
            }
        } else {
            $error = "Aksi tidak dikenal.";
        }
    }
}

// Ambil data untuk tampilan
$users = [];
$q = $conn->query("SELECT id_user, nama, email, role, created_at FROM users ORDER BY role DESC, created_at ASC");
if ($q) while ($r = $q->fetch_assoc()) $users[] = $r;

$feedbacks = [];
$fr = $conn->query("SELECT f.*, u.nama AS user_nama FROM feedback f LEFT JOIN users u ON f.id_user = u.id_user ORDER BY f.tanggal DESC");
if ($fr) while ($r = $fr->fetch_assoc()) $feedbacks[] = $r;

$kuesioners = [];
$kr = $conn->query("SELECT k.*, u.nama AS user_nama FROM kuesioner k LEFT JOIN users u ON k.id_user = u.id_user ORDER BY k.tanggal_pengisian DESC");
if ($kr) while ($r = $kr->fetch_assoc()) $kuesioners[] = $r;

$tab = $_GET['tab'] ?? 'users';

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Admin Panel — SPK Olahraga</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Montserrat', sans-serif; background:#f4f6f8; }
    .admin-card { max-width:1200px; margin:28px auto; background:#fff; border-radius:8px; padding:18px; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
    .table-sm td, .table-sm th { vertical-align: middle; }
    .truncate-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .btn-small { padding: .25rem .5rem; font-size:.85rem; }
    @media (max-width:767px) {
      .admin-card { padding:12px; margin:12px; }
      .nav-tabs .nav-link { font-size: .9rem; }
    }
    /* Modal body scroll fix */
    .modal-body { max-height:60vh; overflow:auto; }
  </style>
</head>
<body>
  <div class="container">
    <div class="admin-card">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <div>
          <h4 class="mb-1">Panel Admin</h4>
          <div class="text-muted small">Masuk sebagai: <?= htmlspecialchars($userRow['nama']) ?> — <?= htmlspecialchars($current_role) ?></div>
        </div>
        <div class="mt-2 mt-md-0">
          <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
      </div>

      <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><a class="nav-link <?= $tab==='users' ? 'active' : '' ?>" href="?tab=users">Pengguna</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='kuesioner' ? 'active' : '' ?>" href="?tab=kuesioner">Kuesioner & Riwayat</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab==='feedback' ? 'active' : '' ?>" href="?tab=feedback">Feedback</a></li>
        <li class="nav-item ml-auto">
            <a class="nav-link" href="manage_olahraga.php">Manage Olahraga</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="user_reset.php">Reset Password User</a>
          </li>
     </ul>

      <div class="tab-content mt-3">
        <!-- USERS -->
        <div class="tab-pane <?= $tab==='users' ? 'active' : '' ?>" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
              <thead class="thead-light">
                <tr>
                  <th style="width:4%;">#</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th style="width:10%;">Role</th>
                  <th style="width:16%;">Terdaftar</th>
                  <th style="width:20%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($users) === 0): ?>
                  <tr><td colspan="6" class="text-center text-muted">Belum ada pengguna.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $i => $u): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($u['nama']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                    <td>
                      <?php if ((int)$u['id_user'] !== $current_user_id): ?>
                        <form method="POST" class="d-inline-block mb-1">
                          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                          <input type="hidden" name="action" value="change_role">
                          <input type="hidden" name="user_id" value="<?= (int)$u['id_user'] ?>">
                          <div class="input-group input-group-sm">
                            <select name="role" class="form-control form-control-sm">
                              <option value="user" <?= $u['role']==='user' ? 'selected' : '' ?>>user</option>
                              <option value="admin" <?= $u['role']==='admin' ? 'selected' : '' ?>>admin</option>
                            </select>
                            <div class="input-group-append">
                              <button class="btn btn-outline-primary btn-small" type="submit">Ubah</button>
                            </div>
                          </div>
                        </form>

                        <form method="POST" class="d-inline-block" onsubmit="return confirm('Yakin hapus user ini beserta data terkait?');">
                          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                          <input type="hidden" name="action" value="delete_user">
                          <input type="hidden" name="user_id" value="<?= (int)$u['id_user'] ?>">
                          <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted">(akun Anda)</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- FEEDBACK -->
        <div class="tab-pane <?= $tab==='feedback' ? 'active' : '' ?>" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead class="thead-light">
                <tr>
                  <th style="width:4%;">#</th>
                  <th>User</th>
                  <th>Pesan</th>
                  <th style="width:8%;">Rating</th>
                  <th style="width:16%;">Tanggal</th>
                  <th style="width:20%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($feedbacks) === 0): ?>
                  <tr><td colspan="6" class="text-center text-muted">Belum ada feedback.</td></tr>
                <?php endif; ?>
                <?php foreach ($feedbacks as $i => $f): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($f['user_nama'] ?? 'Guest') ?></td>
                    <td><div class="truncate-2"><?= nl2br(htmlspecialchars($f['pesan'] ?? '')) ?></div></td>
                    <td><?= htmlspecialchars($f['rating'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($f['tanggal'] ?? '') ?></td>
                    <td>
                      <form method="POST" class="d-inline-block mb-1">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete_feedback">
                        <input type="hidden" name="feedback_id" value="<?= (int)$f['id_feedback'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                      </form>

                      <button class="btn btn-sm btn-info" type="button" onclick="viewFeedback(<?= (int)$f['id_feedback'] ?>)">Lihat</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- KUESIONER -->
        <div class="tab-pane <?= $tab==='kuesioner' ? 'active' : '' ?>" role="tabpanel">
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead class="thead-light">
                <tr>
                  <th style="width:4%;">#</th>
                  <th>User</th>
                  <th>Usia</th>
                  <th>Kebugaran</th>
                  <th>Kesehatan</th>
                  <th>Waktu Luang</th>
                  <th style="width:14%;">Tanggal</th>
                  <th style="width:16%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($kuesioners) === 0): ?>
                  <tr><td colspan="8" class="text-center text-muted">Belum ada kuesioner.</td></tr>
                <?php endif; ?>
                <?php foreach ($kuesioners as $i => $k): ?>
                  <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($k['user_nama'] ?? 'Guest') ?></td>
                    <td><?= htmlspecialchars($k['usia'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($k['tingkat_kebugaran'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($k['kondisi_kesehatan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($k['waktu_luang'] ?? '-') ?> jam</td>
                    <td><?= htmlspecialchars($k['tanggal_pengisian'] ?? $k['tanggal'] ?? '') ?></td>
                    <td>
                      <form method="POST" class="d-inline-block mb-1" onsubmit="return confirm('Hapus kuesioner ini dan hasil rekomendasi terkait?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="delete_kuesioner">
                        <input type="hidden" name="kuesioner_id" value="<?= (int)$k['id_kuesioner'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                      </form>
                      <button class="btn btn-sm btn-info" onclick="viewKuesioner(<?= (int)$k['id_kuesioner'] ?>)">Lihat</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Bootstrap modal -->
  <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detailTitle">Detail</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body" id="detailBody">
          <div class="text-center">Memuat…</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <script>
    function viewFeedback(id) {
      $('#detailTitle').text('Detail Feedback #' + id);
      $('#detailBody').html('<div class="text-center">Memuat…</div>');
      $('#detailModal').modal('show');
      fetch('admin_ajax.php?action=view_feedback&id=' + encodeURIComponent(id))
        .then(r => r.text())
        .then(html => { $('#detailBody').html(html); })
        .catch(() => { $('#detailBody').html('<div class="text-danger">Gagal memuat.</div>'); });
    }

    function viewKuesioner(id) {
      $('#detailTitle').text('Detail Kuesioner #' + id);
      $('#detailBody').html('<div class="text-center">Memuat…</div>');
      $('#detailModal').modal('show');
      fetch('admin_ajax.php?action=view_kuesioner&id=' + encodeURIComponent(id))
        .then(r => r.text())
        .then(html => { $('#detailBody').html(html); })
        .catch(() => { $('#detailBody').html('<div class="text-danger">Gagal memuat.</div>'); });
    }
  </script>
</body>
</html>