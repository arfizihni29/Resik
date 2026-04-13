# RESIK - Sistem Pelaporan Sampah Desa Terintegrasi

RESIK (Sistem Pelaporan dan Manajemen Sampah Terintegrasi) adalah platform berbasis web yang dirancang untuk membantu warga dalam melaporkan tumpukan sampah serta membantu pengurus desa/kelurahan dalam memantau dan mengelola kebersihan lingkungan secara digital dan profesional.

## ✨ Fitur Utama
- 📸 **Pelaporan via Foto**: Warga dapat melaporkan sampah cukup dengan mengunggah foto.
- ⚙️ **Klasifikasi Otomatis**: Sistem dilengkapi dengan *Engine Klasifikasi* yang secara otomatis mengenali kategori sampah (Organik, Anorganik, B3) untuk mempercepat validasi.
- 📍 **Penentuan Lokasi GPS**: Integrasi peta interaktif untuk akurasi lokasi pelaporan yang memudahkan petugas lapangan.
- 📊 **Dashboard Analytics & Insight**: Visualisasi statistik persebaran sampah dan tren laporan untuk pengambilan keputusan strategis.
- 💬 **Integrasi WhatsApp**: Notifikasi langsung untuk koordinasi cepat antara admin dan warga.

## 🛠️ Teknologi yang Digunakan
- **PHP 8.x** (Native, Performa Tinggi)
- **MySQL** (Penyimpanan Data Terstruktur)
- **Bootstrap 5** (Premium Dashboard & Antarmuka Responsif)
- **Leaflet JS** (Sistem Informasi Geografis Interaktif)
- **Engine Klasifikasi Pintar** (Integrasi Teachable Machine untuk pengenalan gambar)

## 🚀 Cara Instalasi
1. Clone repository ini ke direktori web server Anda (`htdocs` atau `www`).
2. Buat database baru bernama `resik` (atau sesuai keinginan).
3. Impor berkas `database.sql` ke dalam database tersebut.
4. Salin berkas `.env.example` menjadi `.env` (jika ada) dan sesuaikan kredensial database serta API Key yang diperlukan.
5. Jalankan aplikasi melalui browser.

---
*Dikembangkan untuk menciptakan lingkungan yang lebih bersih dan sehat melalui partisipasi digital.*
