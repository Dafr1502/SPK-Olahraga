<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id_user = $_SESSION['user_id'];
$edit_id = $_GET['edit'] ?? null;
$hapus_id = $_GET['hapus'] ?? null;

// HAPUS FEEDBACK
if ($hapus_id) {
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id_feedback = ? AND id_user = ?");
    $stmt->bind_param("ii", $hapus_id, $id_user);
    $stmt->execute();
    echo "<script>
        window.onload = function() {
            Swal.fire({
                icon: 'success',
                title: 'Feedback Dihapus',
                text: 'Feedback berhasil dihapus.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location='feedback.php';
            });
        }
    </script>";
}

// TAMBAH / EDIT FEEDBACK
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pesan = $_POST['pesan'];
    $rating = $_POST['rating'];

    if (isset($_POST['id_feedback']) && $_POST['id_feedback'] != '') {
        // UPDATE
        $id_feedback = $_POST['id_feedback'];
        $stmt = $conn->prepare("UPDATE feedback SET pesan=?, rating=? WHERE id_feedback=? AND id_user=?");
        $stmt->bind_param("siii", $pesan, $rating, $id_feedback, $id_user);
        $stmt->execute();
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Feedback Diedit',
                    text: 'Perubahan berhasil disimpan.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location='feedback.php';
                });
            }
        </script>";
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO feedback (id_user, pesan, rating) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $id_user, $pesan, $rating);
        $stmt->execute();
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Feedback Dikirim',
                    text: 'Terima kasih atas masukan Anda!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location='feedback.php';
                });
            }
        </script>";
    }
}

// AMBIL FEEDBACK MILIK USER
$user_feedback = $conn->query("
    SELECT f.*, u.nama 
    FROM feedback f
    JOIN users u ON f.id_user = u.id_user
    WHERE f.id_user = $id_user
    ORDER BY f.tanggal DESC
");

// AMBIL FEEDBACK DARI USER LAIN
$other_feedbacks = $conn->query("
    SELECT f.*, u.nama 
    FROM feedback f
    JOIN users u ON f.id_user = u.id_user
    WHERE f.id_user != $id_user
    ORDER BY f.tanggal DESC
");

$edit_data = null;
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM feedback WHERE id_feedback=? AND id_user=?");
    $stmt->bind_param("ii", $edit_id, $id_user);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Montserrat', sans-serif;
    }
    .card-feedback {
        background: rgba(255,255,255,0.96);
        border-radius: 18px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        padding: 2.5rem 2rem;
        max-width: 600px;
        margin: 40px auto;
    }
    h2.title-feedback {
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
    .btn-outline-secondary {
        font-weight: 500;
        border-radius: 8px;
    }
    .star-rating {
        display: flex;
        gap: 5px;
        cursor: pointer;
        font-size: 1.5rem;
    }
    .star { color: #ccc; transition: color 0.2s; }
    .star.selected, .star.hovered { color: gold; }
    textarea { resize: none; }
    .list-group-item {
        background: rgba(255,255,255,0.92);
        border-radius: 10px;
        margin-bottom: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    h3 {
        color: #2196f3;
        font-weight: 700;
        margin-top: 2rem;
        font-family: 'Montserrat', sans-serif;
    }
    
    /* ===== RESPONSIVE DESIGN ===== */
    @media (max-width: 767px) {
        .card-feedback {
            padding: 1.5rem 1rem;
            margin: 15px 10px;
        }
        
        h2.title-feedback {
            font-size: 1.5rem;
        }
        
        h3 {
            font-size: 1.3rem;
            margin-top: 1.5rem;
        }
        
        .star-rating {
            font-size: 1.3rem;
        }
        
        /* List Item - Stack Vertical */
        .list-group-item {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        
        /* Button Container */
        .list-group-item > div:last-child {
            margin-top: 10px;
            width: 100%;
            display: flex !important;
            gap: 8px;
        }
        
        /* Button Edit & Hapus */
        .list-group-item .btn-warning,
        .list-group-item .btn-danger {
            font-size: 12px !important;
            padding: 0.4rem 0.75rem !important;
            flex: 1;
            margin: 0 !important;
            min-height: auto !important;
            width: auto !important;
        }
        
        .list-group-item .btn-warning i,
        .list-group-item .btn-danger i {
            font-size: 12px !important;
        }
        
        /* Form Buttons */
        .form-group .btn {
            font-size: 14px !important;
            padding: 0.65rem 1rem !important;
            min-height: auto !important;
        }
        
        label {
            font-size: 14px;
        }
        
        textarea {
            font-size: 14px !important;
            min-height: 100px !important;
        }
        
        .badge {
            font-size: 11px !important;
            padding: 3px 6px !important;
        }
    }
    
    @media (max-width: 575px) {
        .card-feedback {
            padding: 1rem 0.75rem;
            margin: 10px 5px;
        }
        
        h2.title-feedback {
            font-size: 1.3rem;
        }
        
        h3 {
            font-size: 1.1rem;
        }
        
        .star-rating {
            font-size: 1.2rem;
        }
        
        /* Button Edit & Hapus - Smaller */
        .list-group-item .btn-warning,
        .list-group-item .btn-danger {
            font-size: 11px !important;
            padding: 0.35rem 0.6rem !important;
        }
        
        .list-group-item strong {
            font-size: 14px;
        }
        
        .list-group-item p {
            font-size: 13px;
        }
        
        .list-group-item small {
            font-size: 11px;
        }
        
        .badge {
            font-size: 10px !important;
            padding: 2px 5px !important;
        }
        
        /* Form Buttons */
        .form-group .btn {
            font-size: 13px !important;
            padding: 0.6rem 0.85rem !important;
        }
    }
    
    @media (max-width: 375px) {
        .card-feedback {
            padding: 0.75rem 0.5rem;
        }
        
        h2.title-feedback {
            font-size: 1.1rem;
        }
        
        h3 {
            font-size: 1rem;
        }
        
        .star-rating {
            font-size: 1.1rem;
        }
        
        /* Button Edit & Hapus - Smallest */
        .list-group-item .btn-warning,
        .list-group-item .btn-danger {
            font-size: 10px !important;
            padding: 0.3rem 0.5rem !important;
        }
        
        .list-group-item strong {
            font-size: 13px;
        }
        
        .list-group-item p {
            font-size: 12px;
        }
        
        /* Form Buttons */
        .form-group .btn {
            font-size: 12px !important;
            padding: 0.55rem 0.75rem !important;
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

<div class="card-feedback">
    <h2 class="title-feedback">
        <i class="fa-solid fa-comment-dots mr-2" style="color:#00c853;"></i>
        <?= $edit_data ? "Edit Feedback" : "Kirim Feedback" ?>
    </h2>
    <form method="POST" autocomplete="off">
        <?php if ($edit_data): ?>
            <input type="hidden" name="id_feedback" value="<?= $edit_data['id_feedback']; ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="rating">Rating:</label>
            <div class="star-rating" id="star-rating" aria-label="Rating">
                <?php
                $current_rating = $edit_data['rating'] ?? 0;
                for ($i = 1; $i <= 5; $i++) {
                    $selected = ($i <= $current_rating) ? 'selected' : '';
                    echo "<span class='star $selected' data-value='$i' title='$i Bintang'>&#9733;</span>";
                }
                ?>
            </div>
            <input type="hidden" name="rating" id="rating-input" value="<?= $current_rating; ?>" required>
        </div>
        <div class="form-group">
            <label for="pesan">Komentar/Saran</label>
            <textarea name="pesan" class="form-control" rows="3" required placeholder="Tulis komentar atau saran Anda..."><?= $edit_data['pesan'] ?? ''; ?></textarea>
        </div>
        <div class="form-group d-flex justify-content-between align-items-center mt-4">
            <button type="submit" class="btn btn-primary flex-grow-1"><?= $edit_data ? "Simpan Perubahan" : "Kirim Feedback" ?></button>
            <?php if ($edit_data): ?>
            <a href="feedback.php" class="btn btn-outline-secondary ml-3 flex-grow-1">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="container mt-4">
    <h3><i class="fa-solid fa-user-check mr-2" style="color:#00c853;"></i>Ulasan Anda</h3>
    <?php if ($user_feedback->num_rows > 0): ?>
        <ul class="list-group mb-4">
            <?php while ($row = $user_feedback->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center border">
                    <div>
                        <strong><?= htmlspecialchars($row['nama']); ?> <span class="badge badge-success">Anda</span></strong>
                        <div>
                            <?php for ($i=1; $i<=5; $i++): ?>
                                <span style="color: <?= $i <= $row['rating'] ? 'gold' : '#ccc'; ?>">&#9733;</span>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-1"><?= htmlspecialchars($row['pesan']); ?></p>
                        <small class="text-muted"><?= $row['tanggal']; ?></small>
                    </div>
                    <div>
                        <a href="feedback.php?edit=<?= $row['id_feedback']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="javascript:void(0)" onclick="hapusFeedback(<?= $row['id_feedback']; ?>)" class="btn btn-danger btn-sm">Hapus</a>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted">Anda belum memberikan ulasan.</p>
    <?php endif; ?>

    <h3><i class="fa-solid fa-users mr-2" style="color:#2196f3;"></i>Ulasan & Rating Pengguna Lain</h3>
    <?php if ($other_feedbacks->num_rows > 0): ?>
        <ul class="list-group">
            <?php while ($row = $other_feedbacks->fetch_assoc()): ?>
                <li class="list-group-item border">
                    <strong><?= htmlspecialchars($row['nama']); ?></strong>
                    <div>
                        <?php for ($i=1; $i<=5; $i++): ?>
                            <span style="color: <?= $i <= $row['rating'] ? 'gold' : '#ccc'; ?>">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                    <p class="mb-1"><?= htmlspecialchars($row['pesan']); ?></p>
                    <small class="text-muted"><?= $row['tanggal']; ?></small>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted">Belum ada ulasan.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('mouseover', function () {
        let value = this.dataset.value;
        document.querySelectorAll('.star').forEach(s => {
            s.classList.toggle('hovered', s.dataset.value <= value);
        });
    });
    star.addEventListener('mouseout', function () {
        document.querySelectorAll('.star').forEach(s => s.classList.remove('hovered'));
    });
    star.addEventListener('click', function () {
        let value = this.dataset.value;
        document.getElementById('rating-input').value = value;
        document.querySelectorAll('.star').forEach(s => {
            s.classList.toggle('selected', s.dataset.value <= value);
        });
    });
});

function hapusFeedback(id) {
    Swal.fire({
        title: 'Yakin hapus?',
        text: "Data tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = "feedback.php?hapus=" + id;
        }
    });
}
</script>
</body>
</html>