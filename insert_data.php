<?php
// Konfigurasi database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'konstruksi'; // Ganti dengan nama database Anda

// Koneksi ke database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Baca file JSON
$json_file = '38 Provinsi Indonesia - Provinsi.json';
$json_data = file_get_contents($json_file);
if (!$json_data) {
    die("Gagal membaca file JSON.");
}

// Decode JSON
$geojson = json_decode($json_data, true);
if (!$geojson || !isset($geojson['features'])) {
    die("Format JSON tidak valid atau tidak ada fitur.");
}

// Daftar provinsi sesuai urutan
$provinces = [
     "Aceh",
    "Sumatera Utara",
    "Sumatera Barat",
    "Riau",
    "Jambi",
    "Sumatera Selatan",
    "Bengkulu",
    "Lampung",
    "Kepulauan Bangka Belitung",
    "Kepulauan Riau",
    "DKI Jakarta",
    "Jawa Barat",
    "Jawa Tengah",
    "DI Yogyakarta",
    "Jawa Timur",
    "Banten",
    "Bali",
    "Nusa Tenggara Barat",
    "Nusa Tenggara Timur",
    "Kalimantan Barat",
    "Kalimantan Tengah",
    "Kalimantan Selatan",
    "Kalimantan Timur",
    "Kalimantan Utara",
    "Sulawesi Utara",
    "Sulawesi Tengah",
    "Sulawesi Selatan",
    "Sulawesi Tenggara",
    "Gorontalo",
    "Sulawesi Barat",
    "Maluku",
    "Maluku Utara",
    "Papua Barat",
    "Papua Barat Daya",
    "Papua",
    "Papua Selatan",
    "Papua Tengah",
    "Papua Pegunungan"
];

// Hapus data lama (opsional)
$conn->query("DELETE FROM provinsi");

// Masukkan data ke database
foreach ($geojson['features'] as $index => $feature) {
    $province = $provinces[$index] ?? null;
    if (!$province || !isset($feature['geometry'])) continue;

    // Ambil hanya bagian geometry saja
    $geometry = $feature['geometry'];
    $geojson_text = json_encode($geometry);

    $stmt = $conn->prepare("INSERT INTO provinsi (province, geojson) VALUES (?, ?)");
    $stmt->bind_param("ss", $province, $geojson_text);
    $stmt->execute();
    $stmt->close();
}

echo "Data berhasil dimasukkan ke database hanya dengan bagian geometry.";

$conn->close();
?>