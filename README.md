# SPK Rekomendasi Olahraga bagi Pemula (Metode Fuzzy Logic)

[![icon](https://img.shields.io/badge/PHP-AEB2D5?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![badge](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![badge](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)

## Deskripsi Proyek
Sistem Pendukung Keputusan (SPK) berbasis web ini dirancang untuk memberikan rekomendasi jenis olahraga yang tepat bagi pemula guna meminimalkan risiko cedera saat memulai aktivitas fisik. Sistem ini mengolah data profil kesehatan pengguna secara objektif untuk menghasilkan saran olahraga yang personal dan aman.

## Fitur Utama
- **Analisis Personalisasi**: Menghitung rekomendasi berdasarkan 4 kriteria utama: Usia, Tingkat Kebugaran, Kondisi Kesehatan, dan Waktu Luang.
- **Logika Cerdas**: Mengimplementasikan **Metode Fuzzy Logic Takagi-Sugeno** untuk memproses variabel yang bersifat subjektif menjadi skor keputusan yang akurat.
- **Safety Screening**: Sistem secara otomatis menyaring jenis olahraga intensitas tinggi bagi pengguna dengan riwayat kesehatan berisiko tinggi demi keamanan medis.
- **Interface Responsif**: Dibangun dengan Bootstrap 5 untuk memastikan pengalaman pengguna yang optimal di perangkat mobile maupun desktop.

## Metodologi
Proyek ini menggunakan pendekatan logika fuzzy dengan tahapan sebagai berikut:
1. **Fuzzifikasi**: Mengubah input kuesioner menjadi nilai linguistik.
2. **Inference Engine**: Menerapkan aturan (rules) berdasarkan basis pengetahuan olahraga.
3. **Defuzzifikasi**: Menghasilkan output berupa jenis olahraga (seperti Jalan Santai, Berenang, atau Bersepeda) beserta durasi yang disarankan.

## Tech Stack
- **Bahasa Pemrograman**: PHP (Native)
- **Database**: MySQL
- **Frontend Framework**: Bootstrap 5
- **Icons**: Font Awesome
