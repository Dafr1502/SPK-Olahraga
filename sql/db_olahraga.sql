-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Nov 2025 pada 17.16
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_olahraga`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `feedback`
--

CREATE TABLE `feedback` (
  `id_feedback` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `rating` tinyint(1) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_rekomendasi`
--

CREATE TABLE `hasil_rekomendasi` (
  `id_hasil` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_kuesioner` int(11) NOT NULL,
  `id_olahraga` int(11) NOT NULL,
  `output_fuzzy` decimal(5,2) NOT NULL DEFAULT 0.00,
  `skor` decimal(5,2) NOT NULL,
  `peringkat` tinyint(2) NOT NULL,
  `bonus_preferensi` decimal(5,3) DEFAULT 0.000,
  `bonus_tujuan` decimal(5,3) DEFAULT 0.000,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuesioner`
--

CREATE TABLE `kuesioner` (
  `id_kuesioner` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `usia` tinyint(3) NOT NULL,
  `tingkat_kebugaran` enum('rendah','sedang','tinggi') NOT NULL,
  `waktu_luang` tinyint(2) DEFAULT NULL COMMENT 'Jumlah jam olahraga yang bisa dilakukan per minggu',
  `nilai_kebugaran` tinyint(2) DEFAULT NULL CHECK (`nilai_kebugaran` between 0 and 10),
  `skor_kesehatan` tinyint(2) DEFAULT NULL CHECK (`skor_kesehatan` between 0 and 10),
  `preferensi_olahraga` varchar(20) DEFAULT NULL,
  `tujuan_kesehatan` varchar(30) DEFAULT NULL,
  `kondisi_kesehatan` enum('baik','cukup','kurang') DEFAULT 'baik',
  `tanggal_pengisian` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `olahraga`
--

CREATE TABLE `olahraga` (
  `id_olahraga` int(11) NOT NULL,
  `nama_olahraga` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(20) DEFAULT NULL,
  `tingkat_kesulitan` enum('ringan','sedang','berat') DEFAULT NULL,
  `rekomendasi_waktu` varchar(50) DEFAULT NULL,
  `intensitas_fuzzy` decimal(3,1) NOT NULL DEFAULT 0.0,
  `lokasi_olahraga` enum('Indoor','Outdoor','Fleksibel') DEFAULT 'Fleksibel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `olahraga`
--

INSERT INTO `olahraga` (`id_olahraga`, `nama_olahraga`, `deskripsi`, `kategori`, `tingkat_kesulitan`, `rekomendasi_waktu`, `intensitas_fuzzy`, `lokasi_olahraga`) VALUES
(1, 'Jogging', 'Lari santai untuk meningkatkan stamina', 'kardio', 'ringan', '30 menit, 3-4x/minggu', 0.7, 'Outdoor'),
(2, 'Bersepeda', 'Olahraga kardio yang dapat dilakukan outdoor', 'kardio', 'sedang', '45 menit, 3x/minggu', 0.6, 'Outdoor'),
(3, 'Yoga', 'Latihan fleksibilitas dan relaksasi', 'fleksibilitas', 'ringan', '20-30 menit, 4x/minggu', 0.3, 'Indoor'),
(4, 'Angkat Beban', 'Latihan kekuatan untuk meningkatkan massa otot', 'kekuatan', 'berat', '30-40 menit, 3x/minggu', 0.9, 'Indoor'),
(5, 'HIIT', 'Latihan interval intensitas tinggi', 'kardio', 'berat', '20 menit, 2-3x/minggu', 0.9, 'Indoor'),
(6, 'Latihan Keseimbangan (Balance Training)', 'Latihan yang meningkatkan kestabilan tubuh seperti berdiri satu kaki, heel-toe walk, latihan dengan bola agar mencegah jatuh.', 'fleksibilitas', 'ringan', '15 menit, 2-3x/minggu', 0.3, 'Indoor'),
(7, 'Renang Santai', 'Renang dengan kecepatan ringan hingga sedang; bagus untuk kesehatan kardiovaskular dan sendi, cocok bagi pengguna dengan masalah sendi.', 'kardio', 'sedang', '30 menit, 2x/minggu', 0.5, 'Fleksibel'),
(8, 'Stretching & Peregangan (Stretching)', 'Gerakan peregangan otot pasca latihan atau pemanasan; membantu fleksibilitas, mengurangi rasa tegang dan risiko cedera.', 'fleksibilitas', 'ringan', '10-15 menit setiap sesi, 3x/minggu', 0.2, 'Indoor'),
(9, 'Senam Kekuatan Berbobot Badan (Bodyweight Strength', 'Latihan menggunakan berat badan sendiri seperti push-ups, squats, lunges; meningkatkan kekuatan otot dan ketahanan.', 'kekuatan', 'sedang', '20-30 menit, 3x/minggu', 0.6, 'Indoor'),
(10, 'Latihan Mobilitas Sendi (Joint Mobility)', 'Gerakan fleksibel ringan untuk memperbaiki jangkauan gerak sendi seperti rotasi pergelangan, lutut, bahu.', 'fleksibilitas', 'ringan', '10-15 menit, 3x/minggu', 0.2, 'Indoor'),
(11, 'Brisk Walking (Jalan Cepat)', 'Jalan cepat luar ruangan / treadmill; meningkatkan detak jantung, mudah dilakukan dan cocok untuk pemula.', 'kardio', 'ringan', '30 menit, 5x/minggu', 0.4, 'Outdoor'),
(12, 'Pilates Dasar', 'Pilates tingkat pemula fokus pada core, postur, dan fleksibilitas dengan gerakan terkendali.', 'fleksibilitas', 'sedang', '25 menit, 3x/minggu', 0.4, 'Indoor'),
(13, 'Renang Interval', 'Berenang repetitif cepat dan istirahat; bagus untuk kardio & sendi', 'kardio', 'sedang', '30-40 menit, 2-3x/minggu', 0.7, 'Fleksibel'),
(14, 'Squats & Lunges', 'Latihan kaki & bokong melalui squat dan lunge; meningkatkan kekuatan tubuh bawah', 'kekuatan', 'sedang', '2-3 set, 10-15 repetisi, 2-3x/minggu', 0.8, 'Indoor'),
(15, 'Plank & Core Stability', 'Latihan plank & variasinya untuk memperkuat core dan punggung bawah', 'kekuatan', 'ringan', '3 set, 30-60 detik, 3-4x/minggu', 0.5, 'Indoor'),
(16, 'Tai Chi', 'Gerakan perlahan untuk fleksibilitas, keseimbangan & relaksasi', 'fleksibilitas', 'ringan', '15-20 menit, 3-4x/minggu', 0.3, 'Fleksibel'),
(17, 'Zumba / Dance Fitness', 'Latihan menari energetik; kardio & fun sekaligus', 'kardio', 'sedang', '30-45 menit, 3-4x/minggu', 0.7, 'Indoor'),
(18, 'Step Aerobics', 'Aerobik naik turun step platform; kardio & koordinasi', 'kardio', 'sedang', '20-30 menit, 3x/minggu', 0.7, 'Indoor'),
(19, 'Farmer\'s Carry', 'Membawa beban di tangan sambil berjalan; melatih grip & core', 'kekuatan', 'sedang', '2-3 set, 1-2x/minggu', 0.7, 'Indoor'),
(20, 'Bear Crawl', 'Merangkak ke depan & belakang; melatih core, bahu, koordinasi', 'kekuatan', 'berat', '3-5 menit, 2-3x/minggu', 0.9, 'Indoor'),
(21, 'Walking Meditation', 'Jalan santai sambil mindful, menenangkan pikiran.', 'kardio', 'ringan', '15-20 menit, setiap hari', 0.2, 'Outdoor'),
(22, 'Resistance Band Workout', 'Latihan ringan menggunakan resistance band.', 'kekuatan', 'ringan', '20 menit, 2-3x/minggu', 0.4, 'Indoor'),
(23, 'Chair Yoga', 'Yoga yang dilakukan di kursi, aman untuk lansia.', 'fleksibilitas', 'ringan', '15 menit, 3x/minggu', 0.2, 'Indoor'),
(24, 'Aqua Aerobics', 'Latihan di air untuk mengurangi beban sendi.', 'kardio', 'ringan', '30 menit, 2-3x/minggu', 0.4, 'Fleksibel'),
(25, 'Breathing Exercise', 'Latihan pernapasan dalam untuk relaksasi dan paru-paru.', 'fleksibilitas', 'ringan', '10 menit, setiap hari', 0.1, 'Indoor'),
(26, 'Morning Stretch Routine', 'Peregangan ringan di pagi hari untuk kelenturan.', 'fleksibilitas', 'ringan', '10 menit setiap pagi', 0.2, 'Indoor'),
(27, 'Nordic Walking', 'Jalan santai dengan bantuan tongkat, baik untuk lansia.', 'kardio', 'ringan', '20-30 menit, 3x/minggu', 0.3, 'Outdoor'),
(28, 'Balance Board Training', 'Latihan keseimbangan menggunakan balance board.', 'fleksibilitas', 'sedang', '10-15 menit, 3x/minggu', 0.4, 'Indoor'),
(29, 'Foam Rolling', 'Self-massage untuk meningkatkan sirkulasi otot.', 'fleksibilitas', 'ringan', '10 menit setelah olahraga', 0.2, 'Indoor'),
(30, 'Light Dance', 'Menari santai dengan musik lambat, low impact.', 'kardio', 'ringan', '20 menit, 3x/minggu', 0.3, 'Indoor'),
(31, 'Treadmill Walking', 'Jalan di treadmill dengan kecepatan lambat', 'kardio', 'ringan', '20-30 menit, 3-4x/minggu', 0.4, 'Indoor'),
(32, 'Stationary Bike', 'Sepeda statis intensitas rendah', 'kardio', 'ringan', '20-30 menit, 3x/minggu', 0.4, 'Indoor'),
(33, 'Wall Push-ups', 'Push-up di dinding untuk pemula', 'kekuatan', 'ringan', '2-3 set 10 repetisi, 2x/minggu', 0.3, 'Indoor');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Danu', 'danfebrian1201@gmail.com', '$2y$10$4JH6wPI/XScxgEq/vvRa0.gbnpiP5U39lA3mNcU9ga7tt8cYLVaPS', 'user', '2025-09-09 05:02:45'),
(3, 'Anto', 'febrianto370@gmail.com', '$2y$10$gswdkFpNBaKxmgsEUUn1fOpR72MLYXSdRXbwkSJKlcnL5a6FBAzzW', 'user', '2025-09-12 15:33:30');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id_feedback`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `hasil_rekomendasi`
--
ALTER TABLE `hasil_rekomendasi`
  ADD PRIMARY KEY (`id_hasil`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kuesioner` (`id_kuesioner`),
  ADD KEY `id_olahraga` (`id_olahraga`);

--
-- Indeks untuk tabel `kuesioner`
--
ALTER TABLE `kuesioner`
  ADD PRIMARY KEY (`id_kuesioner`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `olahraga`
--
ALTER TABLE `olahraga`
  ADD PRIMARY KEY (`id_olahraga`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id_feedback` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `hasil_rekomendasi`
--
ALTER TABLE `hasil_rekomendasi`
  MODIFY `id_hasil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=874;

--
-- AUTO_INCREMENT untuk tabel `kuesioner`
--
ALTER TABLE `kuesioner`
  MODIFY `id_kuesioner` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT untuk tabel `olahraga`
--
ALTER TABLE `olahraga`
  MODIFY `id_olahraga` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `hasil_rekomendasi`
--
ALTER TABLE `hasil_rekomendasi`
  ADD CONSTRAINT `hasil_rekomendasi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `hasil_rekomendasi_ibfk_2` FOREIGN KEY (`id_kuesioner`) REFERENCES `kuesioner` (`id_kuesioner`),
  ADD CONSTRAINT `hasil_rekomendasi_ibfk_3` FOREIGN KEY (`id_olahraga`) REFERENCES `olahraga` (`id_olahraga`);

--
-- Ketidakleluasaan untuk tabel `kuesioner`
--
ALTER TABLE `kuesioner`
  ADD CONSTRAINT `kuesioner_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
