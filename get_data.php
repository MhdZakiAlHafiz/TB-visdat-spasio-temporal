<?php
// Memuat file konfigurasi database
require_once 'config.php';

// Mengatur header HTTP untuk memastikan response adalah JSON dan tidak di-cache oleh browser.
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Tanggal di masa lalu

// Menggunakan blok try-catch-finally untuk penanganan error yang rapi
try {
    // 1. Membuat koneksi ke database
    $conn = connectDB();

    // 2. Mengambil dan memvalidasi parameter dari URL (filter)
    $tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : 2023; // Default ke 2023
    $jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'upah';   // Default ke 'upah'

    // Daftar putih (whitelist) untuk keamanan. Mencegah input sembarangan.
    $valid_jenis = [
        'upah' => ['tabel' => 'upah', 'kolom' => 'upah'],
        'hari_kerja' => ['tabel' => 'hari_kerja', 'kolom' => 'hari_kerja'],
        'nilai_kontrak' => ['tabel' => 'nilai_kontrak', 'kolom' => 'nilai_kontrak']
    ];

    if (!array_key_exists($jenis, $valid_jenis)) {
        throw new Exception("Jenis data yang diminta tidak valid.");
    }

    $tabel = $valid_jenis[$jenis]['tabel'];
    $kolom_nilai = $valid_jenis[$jenis]['kolom'];

    // 3. Mempersiapkan dan menjalankan query SQL
    $sql = "SELECT provinsi, $kolom_nilai AS nilai FROM $tabel WHERE tahun = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("i", $tahun);
    $stmt->execute();
    $result = $stmt->get_result();

    // 4. Mengolah hasil query menjadi format yang diinginkan
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Menggunakan nama provinsi (dalam huruf kapital) sebagai kunci agar mudah dicocokkan
        $data[strtoupper($row['provinsi'])] = (float) $row['nilai'];
    }

    // 5. Menyiapkan response JSON yang akan dikirim ke frontend
    $response = [
        "status" => "success",
        "timestamp" => date("Y-m-d H:i:s"),
        "filters" => [
            "tahun" => $tahun,
            "jenis_data" => $jenis
        ],
        "data" => $data
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Jika terjadi error di mana pun dalam blok 'try', kode ini akan dijalankan
    http_response_code(500); // Set status HTTP ke 500 (Internal Server Error)
    $errorResponse = [
        "status" => "error",
        "timestamp" => date("Y-m-d H:i:s"),
        "message" => $e->getMessage()
    ];

    echo json_encode($errorResponse, JSON_PRETTY_PRINT);

} finally {
    // Blok ini akan selalu dijalankan, baik ada error maupun tidak
    // Pastikan koneksi database selalu ditutup
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>