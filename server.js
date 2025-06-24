// Import modul yang diperlukan
const express = require("express");
const mysql = require("mysql2/promise");
const cors = require("cors");

// Inisialisasi aplikasi dan port
const app = express();
const port = 3000;

// Konfigurasi koneksi database MySQL
const dbConfig = {
  host: "localhost",
  user: "root",
  password: "",
  database: "konstruksi",
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
};

// Membuat connection pool
const pool = mysql.createPool(dbConfig);

// Middleware untuk mengaktifkan CORS dan JSON parser
app.use(cors());
app.use(express.json());

/* ============================================================================
   ENDPOINT UTAMA (Root): Menampilkan daftar endpoint API
============================================================================ */
app.get("/", (req, res) => {
  res.send(`
    API Konstruksi Aktif. 
    Endpoint tersedia:
    - GET /api/upah
    - GET /api/hari_kerja
    - GET /api/nilai_kontrak
    - GET /api/geojson
  `);
});

/* ============================================================================
   ENDPOINT: Ambil data upah harian pekerja konstruksi
   Parameter opsional: ?tahun=2023
============================================================================ */
app.get("/api/upah", async (req, res) => {
  try {
    const { tahun } = req.query;
    let query = "SELECT * FROM upah";
    const params = [];

    if (tahun) {
      query += " WHERE tahun = ?";
      params.push(tahun);
    }

    const [results] = await pool.query(query, params);
    res.json(results);
  } catch (err) {
    console.error("Query error (upah):", err);
    res.status(500).json({ error: "Gagal mengambil data upah harian" });
  }
});

/* ============================================================================
   ENDPOINT: Ambil data rata-rata hari kerja sektor konstruksi
   Parameter opsional: ?tahun=2023
============================================================================ */
app.get("/api/hari_kerja", async (req, res) => {
  try {
    const { tahun } = req.query;
    let query = "SELECT * FROM hari_kerja";
    const params = [];

    if (tahun) {
      query += " WHERE tahun = ?";
      params.push(tahun);
    }

    const [results] = await pool.query(query, params);
    res.json(results);
  } catch (err) {
    console.error("Query error (hari_kerja):", err);
    res.status(500).json({ error: "Gagal mengambil data hari kerja" });
  }
});

/* ============================================================================
   ENDPOINT: Ambil data indeks nilai kontrak proyek konstruksi
   Parameter opsional: ?tahun=2023
============================================================================ */
app.get("/api/nilai_kontrak", async (req, res) => {
  try {
    const { tahun } = req.query;
    let query = "SELECT * FROM nilai_kontrak";
    const params = [];

    if (tahun) {
      query += " WHERE tahun = ?";
      params.push(tahun);
    }

    const [results] = await pool.query(query, params);
    res.json(results);
  } catch (err) {
    console.error("Query error (nilai_kontrak):", err);
    res.status(500).json({ error: "Gagal mengambil data nilai kontrak" });
  }
});

/* ============================================================================
   ENDPOINT: Ambil data GeoJSON untuk masing-masing provinsi
   Parameter opsional: ?province=NamaProvinsi
============================================================================ */
app.get("/api/geojson", async (req, res) => {
  try {
    const { province } = req.query;
    let query = "SELECT province, geojson FROM provinsiind_geojson";
    const params = [];

    if (province) {
      query += " WHERE province = ?";
      params.push(province);
    }

    const [results] = await pool.query(query, params);

    // Parsing string JSON menjadi objek JS
    const formatted = results.map((row) => {
      let parsed = null;
      try {
        parsed = JSON.parse(row.geojson);

        // Validasi struktur GeoJSON
        if (
          !parsed ||
          !parsed.type ||
          (parsed.type !== "Feature" && parsed.type !== "FeatureCollection")
        ) {
          throw new Error("Struktur GeoJSON tidak valid");
        }
      } catch (err) {
        console.error(`Gagal parse GeoJSON (${row.province}):`, err.message);
      }

      return {
        province: row.province,
        geojson: parsed,
      };
    });

    res.json(formatted);
  } catch (err) {
    console.error("Query error (geojson):", err);
    res.status(500).json({ error: "Gagal mengambil data geojson" });
  }
});

/* ============================================================================
   ENDPOINT: Fallback untuk route yang tidak ditemukan
============================================================================ */
app.use((req, res) => {
  res.status(404).json({ error: "Endpoint tidak ditemukan." });
});

/* ============================================================================
   JALANKAN SERVER
============================================================================ */
app.listen(port, () => {
  console.log(`Server berjalan di http://localhost:${port}`);
});

/* ============================================================================
   SHUTDOWN HANDLER: Menutup koneksi database saat server dihentikan
============================================================================ */
process.on("SIGINT", async () => {
  console.log("Menutup koneksi database...");
  await pool.end();
  process.exit();
});
