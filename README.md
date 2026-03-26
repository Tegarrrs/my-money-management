# Money Management App

Aplikasi sederhana untuk mencatat pemasukan dan pengeluaran, tapi dibangun dengan pendekatan yang serius di sisi arsitektur.

Project ini saya gunakan sebagai eksplorasi bagaimana menerapkan clean architecture di Laravel tanpa mengorbankan pragmatisme.

---

## Kenapa project ini dibuat

Kebanyakan aplikasi finance pribadi itu berhenti di level “bisa dipakai”.
Masalahnya, begitu fitur mulai nambah (import data, parsing struk, analytics), struktur kodenya langsung berantakan.

Di project ini saya mencoba dari awal:

* memisahkan business logic dengan jelas
* menjaga controller tetap tipis
* menghindari logic penting tersembunyi di model atau helper

Jadi bukan sekadar CRUD.

---

## Scope saat ini

Saat ini masih di tahap awal, fokus ke fondasi:

* pencatatan transaksi (income & expense)
* kategori transaksi
* laporan sederhana

Belum ada fitur kompleks, karena prioritasnya memastikan struktur sudah benar sebelum scaling.

---

## Rencana pengembangan

Beberapa fitur yang akan ditambahkan:

* import transaksi dari file (CSV / Excel)
* parsing struk belanja (OCR)
* dashboard analytics
* budgeting

Fitur-fitur ini sengaja direncanakan dari awal supaya arsitekturnya tidak mentok di tengah jalan.

---

## Pendekatan arsitektur

Struktur project tidak sepenuhnya mengikuti default Laravel.

Saya memisahkan beberapa layer:

* **Http Layer**
  Controller dan FormRequest, hanya handle request/response

* **Application Layer**
  Use case (misalnya CreateTransaction), berisi alur bisnis

* **Domain Layer**
  Entity, value object, dan aturan bisnis inti

* **Infrastructure Layer**
  Implementasi teknis seperti database (Eloquent), parser, dll

Intinya:

* controller tidak boleh berisi business logic
* validasi wajib di FormRequest
* query tidak ditulis sembarangan di controller
* logic utama tidak ditaruh di model

Kalau aturan ini dilanggar, biasanya akan jadi masalah saat fitur mulai kompleks.

---

## Contoh flow

Create transaksi kira-kira seperti ini:

Request → FormRequest → Controller → Use Case → Model/Repository → Database

Controller hanya jadi penghubung, bukan tempat logika.

---

## Setup

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

## Catatan

Project ini masih berkembang.
Beberapa bagian mungkin akan berubah seiring kebutuhan fitur yang lebih kompleks.

Fokus utama bukan cepat selesai, tapi memastikan fondasi tetap konsisten saat sistem tumbuh.
