# 💰 Money Management App

Aplikasi manajemen keuangan pribadi berbasis Laravel yang dirancang dengan pendekatan **clean architecture** dan **separation of concerns** yang jelas.
Project ini tidak hanya berfokus pada fitur, tetapi juga pada **desain sistem yang scalable dan maintainable**.

---

## 🚀 Goals

* Membantu pengguna mencatat pemasukan & pengeluaran
* Memberikan insight finansial melalui laporan
* Menjadi portfolio yang menunjukkan:

  * Clean Architecture di Laravel
  * Best practice separation of concerns
  * Extensible system (OCR, automation, dll)

---

## 🧱 Tech Stack

* **Backend**: Laravel 13
* **Database**: MySQL / PostgreSQL
* **Architecture Style**: Clean Architecture (Controller → Use Case / Service → Domain)
* **Authentication**: Laravel Sanctum (planned)
* **Queue (planned)**: Redis
* **OCR (planned)**: Tesseract / API-based OCR

---

## 📂 Project Structure

Struktur project tidak mengikuti Laravel konvensional sepenuhnya.
Business logic dipisahkan agar tidak tercampur dengan framework.

```
app/
├── Http/
│   ├── Controllers/
│   └── Requests/
│
├── Domain/
│   ├── Transaction/
│   │   ├── Entities/
│   │   ├── ValueObjects/
│   │   └── Enums/
│   │
│   └── Category/
│
├── Application/
│   ├── UseCases/
│   │   └── Transaction/
│   │       ├── CreateTransaction.php
│   │       └── ImportTransaction.php
│   │
│   └── DTOs/
│
├── Infrastructure/
│   ├── Persistence/
│   │   └── Eloquent/
│   ├── Services/
│   │   └── OCR/
│   └── Parsers/
│
└── Models/ (Eloquent only, no business logic)
```

---

## ⚠️ Design Principles

Project ini mengikuti aturan ketat:

* Controller **tidak boleh berisi business logic**
* Validasi **harus menggunakan FormRequest**
* Query kompleks **tidak boleh di controller**
* Business rules **harus di Use Case / Service**
* Model hanya untuk representasi data (bukan tempat logika bisnis utama)

Jika ada pelanggaran, itu dianggap **design flaw**, bukan sekadar "style issue".

---

## ✨ Features (Current)

* [x] Basic transaction management
* [x] Category management
* [x] Daily report (basic)

---

## 🧪 Features (Planned)

* [ ] Import transaksi dari file (CSV / Excel)
* [ ] OCR struk belanja → auto parsing transaksi
* [ ] Dashboard analytics
* [ ] Budgeting system
* [ ] Scheduled financial summary

---

## 🔄 Example Flow

**Create Transaction Flow:**

```
Request → FormRequest → Controller → UseCase → Domain → Repository → Database
```

---

## ⚙️ Installation

```bash
git clone https://gitlab.com/your-username/money-management-app.git
cd money-management-app

composer install
cp .env.example .env
php artisan key:generate

php artisan migrate
php artisan serve
```

---

## 🧠 Why This Project Matters

Banyak project Laravel hanya fokus "biar jalan", tapi sulit di-scale dan di-maintain.

Project ini mencoba menyelesaikan masalah tersebut dengan:

* Struktur yang eksplisit
* Dependency flow yang jelas
* Minim coupling ke framework
* Mudah ditest dan dikembangkan

---

## 📸 Future Direction (Portfolio)

Project ini akan berkembang menjadi:

* Showcase implementasi OCR di Laravel
* Studi kasus clean architecture di project nyata
* Sistem yang mendekati production-grade

---

## 🤝 Contributing

Saat ini project masih dalam tahap pengembangan pribadi.
Namun struktur dan standar code dibuat agar mudah dikembangkan secara tim.

---

## 📄 License

MIT License
