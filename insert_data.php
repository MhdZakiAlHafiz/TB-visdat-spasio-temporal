<?php
// Konfigurasi database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'konstruksi';

// Koneksi ke database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}

// Baca file GeoJSON
$json_file = '38 Provinsi Indonesia - Provinsi.json';
if (!file_exists($json_file)) {
    die("❌ File JSON tidak ditemukan.");
}

$json_data = file_get_contents($json_file);
if (!$json_data) {
    die("❌ Gagal membaca isi file JSON.");
}

// Decode JSON
$geojson = json_decode($json_data, true);
if (!$geojson || !isset($geojson['features'])) {
    die("❌ Format JSON tidak valid atau tidak memiliki elemen 'features'.");
}

// Hapus data lama
if (!$conn->query("DELETE FROM provinsiind_geojson")) {
    die("❌ Gagal menghapus data lama: " . $conn->error);
}

// Proses setiap fitur dan masukkan ke database
$count = 0;
foreach ($geojson['features'] as $feature) {
    if (!isset($feature['geometry'])) {
        continue;
    }

    // Ambil nama provinsi dan ubah ke format Capital Each Word
    $provinceRaw = $feature['properties']['PROVINSI'] ??
        $feature['properties']['provinsi'] ?? null;

    if (!$provinceRaw) {
        continue;
    }

    $provinceName = ucwords(strtolower(trim($provinceRaw)));

    // Ambil geometri dalam format GeoJSON
    $geometry = $feature['geometry'];
    $geojson_text = json_encode($geometry, JSON_UNESCAPED_UNICODE);

    // Simpan ke tabel
    $stmt = $conn->prepare("INSERT INTO provinsiind_geojson (province, geojson) VALUES (?, ?)");
    if (!$stmt) {
        echo "❌ Gagal menyiapkan statement: " . $conn->error . "<br>";
        continue;
    }

    $stmt->bind_param("ss", $provinceName, $geojson_text);
    if (!$stmt->execute()) {
        echo "❌ Gagal menyimpan data untuk provinsi $provinceName: " . $stmt->error . "<br>";
    } else {
        $count++;
    }

    $stmt->close();
}

echo "✅ Berhasil menyimpan $count data provinsi ke database.";
$conn->close();
?>