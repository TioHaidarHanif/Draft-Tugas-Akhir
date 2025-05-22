# Sistem Keluhan Mahasiswa (Student Complaint Management System)

## Deskripsi Proyek
Sistem Keluhan Mahasiswa adalah aplikasi berbasis web yang dirancang untuk memfasilitasi mahasiswa Telkom University dalam menyampaikan keluhan, pertanyaan, atau permintaan bantuan kepada pihak administrasi kampus. Sistem ini menggunakan Laravel sebagai backend framework dengan API REST sebagai antarmuka komunikasi antara frontend dan backend.

## Fitur Utama

### 1. Sistem Autentikasi dan Manajemen Pengguna
- Registrasi mahasiswa dengan data lengkap (nama, email, password, NIM, program studi, semester, nomor telepon)
- Login untuk mahasiswa dan admin
- Manajemen profil pengguna (lihat dan edit profil)
- Manajemen pengguna oleh admin (melihat daftar pengguna, mengedit, menghapus)

### 2. Manajemen Tiket Keluhan
- Pembuatan tiket keluhan baru oleh mahasiswa
- Melihat daftar tiket (berbeda berdasarkan peran: mahasiswa hanya melihat tiket mereka, admin melihat semua)
- Melihat detail tiket
- Memperbarui status tiket (dibuka, diproses, selesai, ditutup)
- Mendisposisikan tiket ke staff yang sesuai
- Menambahkan komentar pada tiket
- Mengedit dan menghapus tiket (soft delete)
- Mengembalikan tiket yang sudah dihapus (restore)

### 3. Sistem Notifikasi
- Melihat notifikasi
- Menandai notifikasi sebagai telah dibaca
- Menandai semua notifikasi sebagai telah dibaca

### 4. Statistik dan Pelaporan
- Statistik pengguna untuk admin
- Statistik tiket untuk pemantauan dan analisis

## Arsitektur Sistem
- Backend: Laravel (PHP Framework)
- API: RESTful API
- Database: MySQL/PostgreSQL (disesuaikan kebutuhan)
- Frontend: Tidak tercakup dalam dokumentasi API ini, dapat dikembangkan dengan framework JavaScript (React, Vue, Angular, dll)
- Autentikasi: Sactum

## Tahapan Pengembangan

### Tahap 1: Setup Dasar dan Autentikasi
1. Setup proyek Laravel dan konfigurasi database
2. Implementasi sistem autentikasi (register, login, logout)
3. Implementasi manajemen profil pengguna
4. Implementasi manajemen pengguna oleh admin

### Tahap 2: Manajemen Tiket
1. Implementasi model dan migrasi untuk tiket
2. Implementasi pembuatan tiket
3. Implementasi melihat daftar dan detail tiket
4. Implementasi memperbarui status tiket
5. Implementasi disposisi tiket

### Tahap 3: Komentar dan Notifikasi
1. Implementasi sistem komentar pada tiket
2. Implementasi model dan migrasi untuk notifikasi
3. Implementasi pembuatan notifikasi otomatis
4. Implementasi API untuk melihat dan mengelola notifikasi

### Tahap 4: Statistik dan Pengembangan Lanjut
1. Implementasi statistik pengguna dan tiket
2. Implementasi soft delete dan restore tiket
3. Pengujian dan optimasi sistem

## Struktur Database
Sistem ini memerlukan beberapa tabel utama:
1. users - menyimpan data pengguna (mahasiswa dan admin)
2. tickets - menyimpan data tiket keluhan
3. ticket_comments - menyimpan komentar pada tiket
4. notifications - menyimpan notifikasi untuk pengguna
5. categories - (opsional) kategori tiket keluhan

## Spesifikasi API
API yang dikembangkan harus mengikuti format respons yang konsisten:
- Sukses: `{"status": "success", "data": {...}}`
- Error: `{"status": "error", "message": "...", "code": xxx}`

Detail lengkap endpoint API dapat dilihat di dokumentasi Postman yang telah disediakan.

## Keamanan
- Validasi input untuk semua data yang diterima dari pengguna
- Otorisasi berbasis peran untuk membatasi akses
- Enkripsi password menggunakan algoritma yang aman
- Penggunaan JWT dengan masa berlaku yang sesuai

## Persyaratan Non-Fungsional
- Performa: respons API cepat (< 500ms)
- Skalabilitas: mampu menangani banyak pengguna secara bersamaan
- Ketersediaan: sistem harus dapat diakses 24/7
- Keamanan: perlindungan data sensitif dan pencegahan serangan umum

## Rincian Pengembangan Per Fitur

### Fitur 1: Setup Proyek dan Konfigurasi Awal
- Instalasi Laravel
- Konfigurasi database
- Instalasi dan konfigurasi JWT untuk autentikasi
- Setup struktur dasar API

### Fitur 2: Sistem Autentikasi
- Implementasi registrasi mahasiswa
- Implementasi login admin dan mahasiswa
- Implementasi logout
- Middleware untuk otorisasi berbasis peran

### Fitur 3: Manajemen Profil
- Implementasi API untuk melihat profil pengguna
- Implementasi API untuk mengupdate profil pengguna
- Validasi data profil

### Fitur 4: Manajemen Pengguna (Admin)
- Implementasi API untuk melihat daftar pengguna
- Implementasi API untuk melihat detail pengguna
- Implementasi API untuk mengubah data pengguna
- Implementasi API untuk menghapus pengguna
- Implementasi API untuk mengubah role pengguna
- Implementasi API untuk statistik pengguna

### Fitur 5: Manajemen Tiket - Dasar
- Implementasi model dan migrasi untuk tiket
- Implementasi API untuk membuat tiket baru
- Implementasi API untuk melihat daftar tiket (dengan pagination)
- Implementasi API untuk melihat detail tiket

### Fitur 6: Manajemen Tiket - Lanjutan
- Implementasi API untuk mengubah status tiket
- Implementasi API untuk mendisposisikan tiket
- Implementasi API untuk mengedit tiket
- Implementasi API untuk soft delete dan restore tiket

### Fitur 7: Sistem Komentar Tiket
- Implementasi model dan migrasi untuk komentar tiket
- Implementasi API untuk menambahkan komentar
- Implementasi API untuk melihat komentar pada tiket

### Fitur 8: Sistem Notifikasi
- Implementasi model dan migrasi untuk notifikasi
- Implementasi pembuatan notifikasi otomatis (saat update tiket, komentar baru, dll)
- Implementasi API untuk melihat notifikasi
- Implementasi API untuk menandai notifikasi telah dibaca

### Fitur 9: Statistik dan Pelaporan
- Implementasi API untuk statistik tiket
- Implementasi API untuk pelaporan berdasarkan kategori, status, waktu

### Fitur 10: Pengujian dan Dokumentasi
- Pengujian unit dan integrasi
- Dokumentasi API lengkap
- Optimasi performa

## Catatan Pengembangan
- Gunakan fitur Laravel seperti middleware, resource controllers, form request validation
- Ikuti prinsip RESTful API
- Gunakan Eloquent ORM untuk interaksi dengan database
- Implementasikan pagination untuk daftar yang panjang
- Gunakan soft delete untuk data penting