<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Akses ditolak');
}

// pastikan admin
$uid = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id_user = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!isset($r['role']) || $r['role'] !== 'admin') {
    http_response_code(403);
    exit('Hanya admin');
}

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'view_feedback' && $id > 0) {
    $s = $conn->prepare("SELECT f.*, u.nama AS user_nama FROM feedback f LEFT JOIN users u ON f.id_user = u.id_user WHERE f.id_feedback = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $row = $s->get_result()->fetch_assoc();
    $s->close();
    if (!$row) { echo '<div class="alert alert-warning">Feedback tidak ditemukan.</div>'; exit; }

    echo '<h5>Feedback dari: '.htmlspecialchars($row['user_nama'] ?? 'Guest').'</h5>';
    echo '<p><strong>Rating:</strong> '.htmlspecialchars($row['rating'] ?? '-').'</p>';
    echo '<p><strong>Tanggal:</strong> '.htmlspecialchars($row['tanggal']).'</p>';
    echo '<div style="white-space:pre-wrap;">'.htmlspecialchars($row['pesan']).'</div>';
    exit;
}

if ($action === 'view_kuesioner' && $id > 0) {
    $s = $conn->prepare("SELECT k.*, u.nama AS user_nama FROM kuesioner k LEFT JOIN users u ON k.id_user = u.id_user WHERE k.id_kuesioner = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $k = $s->get_result()->fetch_assoc();
    $s->close();
    if (!$k) { echo '<div class="alert alert-warning">Kuesioner tidak ditemukan.</div>'; exit; }

    echo '<h5>Kuesioner - '.htmlspecialchars($k['user_nama'] ?? 'Guest').'</h5>';
    echo '<p><strong>Usia:</strong> '.htmlspecialchars($k['usia'] ?? '-').'</p>';
    echo '<p><strong>Tingkat Kebugaran:</strong> '.htmlspecialchars($k['tingkat_kebugaran'] ?? '-').'</p>';
    echo '<p><strong>Kondisi Kesehatan:</strong> '.htmlspecialchars($k['kondisi_kesehatan'] ?? '-').'</p>';
    echo '<p><strong>Waktu Luang:</strong> '.htmlspecialchars($k['waktu_luang'] ?? '-').' jam</p>';
    echo '<p><strong>Preferensi:</strong> '.htmlspecialchars($k['preferensi_olahraga'] ?? '-').'</p>';
    echo '<p><strong>Tujuan:</strong> '.htmlspecialchars($k['tujuan_kesehatan'] ?? '-').'</p>';

    // ambil hasil rekomendasi terkait jika diperlukan
    $s2 = $conn->prepare("SELECT hr.*, o.nama_olahraga FROM hasil_rekomendasi hr LEFT JOIN olahraga o ON hr.id_olahraga = o.id_olahraga WHERE hr.id_kuesioner = ? ORDER BY hr.peringkat ASC");
    $s2->bind_param("i", $id);
    $s2->execute();
    $res = $s2->get_result();
    if ($res && $res->num_rows) {
        echo '<h6 class="mt-3">Hasil Rekomendasi</h6><ul>';
        while ($h = $res->fetch_assoc()) {
            echo '<li>'.htmlspecialchars($h['nama_olahraga'] ?? '—').' — skor: '.htmlspecialchars($h['skor'] ?? $h['output_fuzzy']).'</li>';
        }
        echo '</ul>';
    }
    $s2->close();
    exit;
}

http_response_code(400);
echo 'Aksi tidak valid';
?>