<?php
// Mengatur header agar outputnya adalah JSON dan mengizinkan akses dari domain lain (penting untuk development)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Menampilkan semua error PHP untuk mempermudah debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. KONFIGURASI DATABASE ---
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Biasanya kosong untuk instalasi XAMPP default
$db_name = 'konstruksi';

// --- 2. KONEKSI KE DATABASE DENGAN PENANGANAN ERROR ---
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception('Koneksi Gagal: ' . $conn->connect_error);
    }
} catch (Exception $e) {
    // Jika koneksi gagal, kirim status error HTTP 500 dan pesan yang jelas
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit(); // Hentikan eksekusi
}

// --- 3. VALIDASI INPUT DARI PENGGUNA ---
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : 2023;
$jenis_data = isset($_GET['jenis']) ? $_GET['jenis'] : 'upah';

// Daftar putih (whitelist) untuk mencegah SQL Injection pada nama tabel/kolom
$valid_jenis = [
    'upah' => ['tabel' => 'upah', 'kolom' => 'upah'],
    'hari_kerja' => ['tabel' => 'hari_kerja', 'kolom' => 'hari_kerja'],
    'nilai_kontrak' => ['tabel' => 'nilai_kontrak', 'kolom' => 'nilai_kontrak']
];

if (!array_key_exists($jenis_data, $valid_jenis)) {
    // Jika jenis data tidak valid, kirim error 400 Bad Request
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Jenis data yang diminta tidak valid.']);
    exit();
}

$tabel = $valid_jenis[$jenis_data]['tabel'];
$kolom_nilai = $valid_jenis[$jenis_data]['kolom'];

// --- 4. MEMBUAT DAN MENJALANKAN QUERY DENGAN PREPARED STATEMENT ---
// Ini adalah cara paling aman untuk menjalankan query
$sql = "SELECT provinsi, $kolom_nilai AS nilai FROM $tabel WHERE tahun = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query SQL: ' . $conn->error]);
    exit();
}

// Mengikat parameter tahun ke query
$stmt->bind_param("i", $tahun);
$stmt->execute();
$result = $stmt->get_result();

// --- 5. MENGOLAH HASIL DAN MENGIRIMKAN RESPONSE ---
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Normalisasi nama provinsi ke huruf kapital agar mudah dicocokkan di frontend
        $nama_provinsi_normal = strtoupper($row['provinsi']);
        $data[$nama_provinsi_normal] = (float) $row['nilai'];
    }
}

// Menutup statement dan koneksi
$stmt->close();
$conn->close();

// Kirim response sukses dalam format JSON yang terstruktur
echo json_encode([
    'status' => 'success',
    'timestamp' => time(),
    'filters' => ['tahun' => $tahun, 'jenis' => $jenis_data],
    'data' => $data
]);
?>