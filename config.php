<?php
/**
 * File Konfigurasi untuk Proyek Visualisasi Konstruksi
 * Berisi konstanta dan fungsi untuk koneksi ke database.
 */

// Aktifkan pelaporan error untuk lingkungan development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Konfigurasi Database ---
// Sesuaikan jika perlu, tetapi ini adalah default untuk Laragon/XAMPP.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Password default biasanya kosong
define('DB_NAME', 'konstruksi'); // **Diubah ke database konstruksi**

/**
 * Membuat koneksi ke database menggunakan informasi di atas.
 * @return mysqli Objek koneksi database yang berhasil.
 * @throws Exception jika koneksi gagal atau gagal mengatur charset.
 */
function connectDB()
{
    // Membuat objek koneksi baru
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Memeriksa apakah terjadi error saat koneksi
    if ($conn->connect_error) {
        throw new Exception("Koneksi database gagal: " . $conn->connect_error);
    }

    // Mengatur set karakter ke utf8mb4 untuk mendukung karakter yang lebih luas
    if (!$conn->set_charset("utf8mb4")) {
        // Ini jarang gagal, tetapi penting untuk diperiksa
        throw new Exception("Gagal mengatur charset: " . $conn->error);
    }

    return $conn;
}
?>