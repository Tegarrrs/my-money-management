# Money Management App (Dompetra)

Aplikasi sederhana untuk mencatat pemasukan dan pengeluaran, dibangun dengan pendekatan Clean Architecture di Laravel tanpa mengorbankan pragmatisme.

Project ini digunakan sebagai eksplorasi bagaimana menerapkan arsitektur yang bersih, modular, dan mudah di-scale seiring bertambahnya fitur (seperti import data, parsing struk OCR, dan analytics).

---

## Fitur Utama

- **Pencatatan Transaksi**: Mengelola pemasukan (income), pengeluaran (expense), transfer antar dompet, dan pecah transaksi (split transaction).
- **Kategori Transaksi**: Pengelompokan pengeluaran dan pemasukan secara dinamis dengan ikon visual.
- **Import Transaksi**: Import data transaksi dari file (CSV / Excel).
- **OCR Scan Struk (Gemini AI)**: Unggah struk fisik Anda, dan sistem akan memindai barang belanjaan, tanggal, pajak, serta total biaya secara otomatis untuk langsung disimpan sebagai transaksi.
- **Laporan & Analytics**: Grafik dan rincian alur kas keuangan bulanan.

---

## Pendekatan Arsitektur

Struktur project memisahkan beberapa layer tanggung jawab:
- **Http Layer**: Controller dan FormRequest (hanya menghandle request & response).
- **Application Layer**: Use cases / Actions (misalnya `CreateTransaction`), berisi alur bisnis utama.
- **Domain Layer**: Entity, Value Object, dan aturan bisnis inti.
- **Infrastructure Layer**: Implementasi teknis database (Eloquent), OCR Parser (Gemini), file handling, dll.

---

## Panduan Instalasi (Local Setup)

Ikuti langkah-langkah di bawah ini untuk menjalankan project ini di komputer lokal Anda:

### 1. Prasyarat (Prerequisites)
Pastikan Anda telah menginstal:
- PHP >= 8.2 (dengan ekstensi `GD` disarankan untuk kompresi otomatis gambar struk sebelum dikirim ke API)
- Composer
- Node.js & NPM
- SQLite (default database aplikasi ini)

### 2. Kloning Project
```bash
git clone <repository-url>
cd my-money-management
```

### 3. Instal Dependensi
Instal package PHP (Composer) dan Javascript (NPM):
```bash
composer install
npm install
```

### 4. Konfigurasi Environment File
Salin file `.env.example` ke `.env`:
```bash
cp .env.example .env
```

### 5. Setup Database & Key
Generate Application Key:
```bash
php artisan key:generate
```

Secara default, aplikasi ini menggunakan SQLite. Buat file database SQLite kosong secara manual:
- **Windows (PowerShell)**:
  ```powershell
  New-Item -Path database -Name database.sqlite -ItemType File
  ```
- **Linux / macOS / Git Bash**:
  ```bash
  touch database/database.sqlite
  ```
*(Catatan: Jika Anda langsung menjalankan perintah migrasi tanpa membuat file database tersebut, Laravel biasanya akan menawarkan untuk membuatnya secara otomatis).*

Jalankan migrasi database beserta data awal (seeders):
```bash
php artisan migrate --seed
```
*Perintah ini akan membuat semua struktur tabel serta menambahkan akun uji coba default dan kategori transaksi utama.*

### 6. Compile Frontend Assets
Kompilasi asset Javascript & CSS menggunakan Vite:
```bash
# Untuk development:
npm run dev

# Atau compile untuk production:
npm run build
```

### 7. Jalankan Server
Jalankan development server Laravel:
```bash
php artisan serve
```
Aplikasi sekarang dapat diakses melalui browser di `http://127.0.0.1:8000`.

---

## Setup Fitur OCR Gemini AI

Fitur OCR Struk menggunakan Google Gemini API (`gemini-flash-latest`) untuk membaca foto struk belanja fisik Anda dan mengubahnya menjadi draf transaksi secara otomatis.

### 1. Dapatkan Gemini API Key
1. Buka [Google AI Studio](https://aistudio.google.com/).
2. Login menggunakan akun Google Anda.
3. Klik tombol **Get API Key** lalu klik **Create API Key**.
4. Salin API Key yang berhasil dibuat.

### 2. Konfigurasi di `.env`
Buka file `.env` di root project Anda, lalu tambahkan API Key tersebut di baris paling bawah:
```env
GEMINI_API_KEY=isi_dengan_api_key_gemini_anda
```

### 3. Cara Menggunakan
1. Login ke aplikasi.
2. Di halaman **Transaksi**, klik tombol **Import** lalu pilih tab/menu **Scan Struk**.
3. Unggah foto struk belanja fisik Anda (struk belanja ritel, restoran, parkir, dll).
4. Tunggu beberapa detik selama sistem memindai struk menggunakan Gemini AI.
5. Rincian barang, harga, tanggal struk, serta pajak akan diekstraksi ke tabel preview.
6. Pilih dompet dan kategori transaksi yang sesuai, lalu klik **Simpan Transaksi** untuk menyimpan seluruh data secara massal ke database.

---

## Akun Uji Coba Default

Setelah Anda menjalankan seeder database (`migrate --seed`), Anda dapat langsung login menggunakan akun default berikut:
- **Email**: `test@example.com`
- **Password**: `password`
