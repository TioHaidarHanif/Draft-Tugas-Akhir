# Sistem Keluhan Mahasiswa (Student Complaint Management System)

## Deskripsi Proyek
Sistem Keluhan Mahasiswa adalah aplikasi berbasis web yang dirancang untuk memfasilitasi mahasiswa Telkom University dalam menyampaikan keluhan, pertanyaan, atau permintaan bantuan kepada pihak administrasi kampus. Sistem ini menggunakan Laravel sebagai backend framework dengan API REST sebagai antarmuka komunikasi antara frontend dan backend, dengan autentikasi menggunakan Laravel Sanctum.

## Fitur Utama

### 1. Sistem Autentikasi dan Manajemen Pengguna
- Registrasi mahasiswa dengan data lengkap (nama, email, password, NIM, program studi, semester, nomor telepon)
- Login untuk mahasiswa, admin, dan staff disposisi
- Manajemen profil pengguna (lihat dan edit profil)
- Manajemen pengguna oleh admin (melihat daftar pengguna, mengedit, menghapus, mengubah role)

### 2. Manajemen Tiket Keluhan
- Pembuatan tiket keluhan baru oleh mahasiswa dengan kategori dan sub-kategori
- Melihat daftar tiket (berbeda berdasarkan peran: mahasiswa hanya melihat tiket mereka, admin melihat semua, disposisi melihat tiket yang ditugaskan)
- Melihat detail tiket
- Memperbarui status tiket (open, in_progress, resolved, closed, reopened)
- Mendisposisikan tiket ke staff yang sesuai (oleh admin)
- Menambahkan komentar/feedback pada tiket
- Mengedit dan menghapus tiket (soft delete)
- Mengembalikan tiket yang sudah dihapus (restore)
- Statistik tiket untuk monitoring dan analisis

### 3. Sistem Notifikasi
- Notifikasi otomatis untuk berbagai kejadian (tiket baru, perubahan status, komentar baru, dll)
- Melihat daftar notifikasi dengan status dibaca/belum
- Menandai notifikasi sebagai telah dibaca
- Menandai semua notifikasi sebagai telah dibaca
- Berbagai tipe notifikasi (new_ticket, assignment, status_change, feedback)

### 4. Kategori dan Sub-kategori
- Sistem kategori dan sub-kategori untuk pengelompokan tiket
- Manajemen kategori oleh admin (membuat, mengedit, melihat)
- Manajemen sub-kategori oleh admin

### 5. Statistik dan Pelaporan
- Statistik pengguna untuk admin (total, per role, aktif, baru)
- Statistik tiket untuk pemantauan dan analisis (total, per status, per kategori, waktu resolusi)

## Arsitektur Sistem
- Backend: Laravel 12 (PHP Framework)
- API: RESTful API
- Database: MySQL
- Autentikasi: Laravel Sanctum
- Frontend: Tidak tercakup dalam dokumentasi API ini, dapat dikembangkan dengan framework JavaScript (React, Vue, Angular, dll)

## Tahapan Pengembangan

### Tahap 1: Setup Dasar dan Autentikasi
1. Setup proyek Laravel dan konfigurasi database
2. Implementasi sistem autentikasi (register, login, logout) dengan Sanctum
3. Implementasi manajemen profil pengguna
4. Implementasi manajemen pengguna oleh admin

### Tahap 2: Kategori dan Manajemen Tiket
1. Implementasi model dan migrasi untuk kategori dan sub-kategori
2. Implementasi model dan migrasi untuk tiket
3. Implementasi pembuatan tiket dengan kategori
4. Implementasi melihat daftar dan detail tiket
5. Implementasi memperbarui status tiket
6. Implementasi disposisi tiket

### Tahap 3: Komentar dan Notifikasi
1. Implementasi sistem feedback pada tiket
2. Implementasi model dan migrasi untuk notifikasi
3. Implementasi pembuatan notifikasi otomatis berdasarkan tipe
4. Implementasi API untuk melihat dan mengelola notifikasi

### Tahap 4: Statistik dan Pengembangan Lanjut
1. Implementasi statistik pengguna dan tiket
2. Implementasi soft delete dan restore tiket
3. Implementasi upload dan manajemen lampiran tiket
4. Pengujian dan optimasi sistem

## Struktur Database
Sistem ini memerlukan beberapa tabel utama:
1. `users` - menyimpan data pengguna (mahasiswa, admin, disposisi)
2. `categories` - menyimpan kategori tiket keluhan
3. `sub_categories` - menyimpan sub-kategori yang terkait dengan kategori
4. `tickets` - menyimpan data tiket keluhan
5. `ticket_histories` - menyimpan riwayat perubahan pada tiket
6. `ticket_attachments` - menyimpan lampiran pada tiket
7. `ticket_feedbacks` - menyimpan feedback/komentar pada tiket
8. `notifications` - menyimpan notifikasi untuk pengguna

## Spesifikasi API
API yang dikembangkan mengikuti format respons yang konsisten:
- Sukses: `{"status": "success", "message": "...", "data": {...}}` atau `{"status": "success", "data": {...}}`
- Error: `{"status": "error", "message": "...", "code": xxx}` atau `{"status": "error", "message": "...", "errors": {...}, "code": xxx}`

Endpoint API diorganisasikan sesuai dengan resource:
- `/auth/*` - Endpoint autentikasi (register, login, logout, profile)
- `/users/*` - Endpoint manajemen pengguna
- `/tickets/*` - Endpoint manajemen tiket
- `/categories/*` - Endpoint manajemen kategori
- `/notifications/*` - Endpoint manajemen notifikasi

Detail lengkap endpoint API dapat dilihat di dokumentasi API dalam `api.md`.

## Keamanan
- Validasi input untuk semua data yang diterima dari pengguna
- Otorisasi berbasis peran (admin, disposisi, user) untuk membatasi akses
- Enkripsi password menggunakan algoritma hashing Laravel
- Autentikasi token menggunakan Laravel Sanctum
- Proteksi terhadap serangan umum (CSRF, XSS, SQL Injection)

## Persyaratan Non-Fungsional
- Performa: respons API cepat (< 500ms)
- Skalabilitas: mampu menangani banyak pengguna secara bersamaan
- Ketersediaan: sistem harus dapat diakses 24/7
- Keamanan: perlindungan data sensitif dan pencegahan serangan umum
- Pemeliharaan: kode yang mudah dipelihara dengan dokumentasi yang baik
- Konsistensi: format respons API yang konsisten di seluruh endpoint

## Rincian Pengembangan Per Fitur

### Fitur 1: Setup Proyek dan Konfigurasi Awal
- Instalasi Laravel
- Konfigurasi database MySQL
- Instalasi dan konfigurasi Laravel Sanctum untuk autentikasi
- Setup struktur dasar API

### Fitur 2: Sistem Autentikasi
- Implementasi registrasi mahasiswa dengan validasi email domain Telkom University
- Implementasi login untuk mahasiswa, admin, dan disposisi
- Implementasi logout dan invalidasi token
- Middleware untuk otorisasi berbasis peran (admin, disposisi, user)

### Fitur 3: Manajemen Profil
- Implementasi API untuk melihat profil pengguna saat ini

### Fitur 4: Manajemen Pengguna (Admin)
- Implementasi API untuk melihat daftar pengguna dengan paginasi dan filter berdasarkan role
- Implementasi API untuk melihat detail pengguna berdasarkan ID
- Implementasi API untuk mengubah data pengguna
- Implementasi API untuk menghapus pengguna
- Implementasi API untuk mengubah role pengguna (admin, disposisi, user)
- Implementasi API untuk statistik pengguna (total, per role, aktif, baru)

### Fitur 5: Manajemen Kategori
- Implementasi model dan migrasi untuk kategori dan sub-kategori
- Implementasi API untuk melihat daftar kategori dan sub-kategori
- Implementasi API untuk membuat kategori baru (admin only)
- Implementasi API untuk membuat sub-kategori baru (admin only)
- Validasi relasi antara kategori dan sub-kategori

### Fitur 6: Manajemen Tiket - Dasar
- Implementasi model dan migrasi untuk tiket dengan UUID dan soft deletes
- Implementasi API untuk membuat tiket baru dengan kategori dan sub-kategori
- Implementasi API untuk melihat daftar tiket dengan paginasi dan filtering (berdasarkan status, kategori, tanggal, dll)
- Implementasi API untuk melihat detail tiket termasuk riwayat, lampiran, dan feedback
- Implementasi upload dan penyimpanan lampiran tiket

### Fitur 7: Manajemen Tiket - Lanjutan
- Implementasi API untuk mengubah status tiket (open, in_progress, resolved, closed, reopened)
- Implementasi API untuk mendisposisikan tiket ke staff (admin only)
- Implementasi API untuk mengedit dan memperbarui tiket
- Implementasi API untuk soft delete dan restore tiket
- Implementasi API untuk statistik tiket (total, per status, per kategori, waktu resolusi)

### Fitur 8: Sistem Feedback Tiket
- Implementasi model dan migrasi untuk feedback pada tiket
- Implementasi API untuk menambahkan feedback/komentar pada tiket
- Implementasi API untuk melihat riwayat feedback pada tiket
- Notifikasi otomatis saat feedback baru ditambahkan

### Fitur 9: Sistem Notifikasi
- Implementasi model dan migrasi untuk notifikasi dengan UUID
- Implementasi pembuatan notifikasi otomatis berdasarkan tipe (new_ticket, assignment, status_change, feedback)
- Implementasi API untuk melihat notifikasi dengan filtering berdasarkan status dibaca
- Implementasi API untuk menandai notifikasi sebagai telah dibaca
- Implementasi API untuk menandai semua notifikasi sebagai telah dibaca

### Fitur 10: Pengujian dan Dokumentasi
- Implementasi unit testing untuk model dan relasi
- Implementasi feature testing untuk API endpoints
- Dokumentasi API lengkap dengan format request dan response
- Optimasi performa dan validasi

## Model Data
Berikut adalah model data utama dalam sistem:

### User
- id: string (ULID)
- name: string
- email: string (unique, dengan validasi domain Telkom University)
- password: string (hashed)
- role: enum ('admin', 'disposisi', 'user')
- nim: string (untuk mahasiswa)
- prodi: string (untuk mahasiswa)
- semester: string (untuk mahasiswa)
- phone: string
- timestamps (created_at, updated_at)

### Category
- id: integer
- name: string
- timestamps (created_at, updated_at)

### SubCategory
- id: integer
- category_id: integer (foreign key)
- name: string
- timestamps (created_at, updated_at)

### Ticket
- id: string (UUID)
- ticket_id: string (auto-generated)
- title: string
- description: text
- category_id: integer (foreign key)
- subcategory_id: integer (foreign key)
- creator_id: string (foreign key ke User)
- assigned_to: string (foreign key ke User, nullable)
- status: enum ('open', 'in_progress', 'resolved', 'closed', 'reopened')
- priority: enum ('low', 'medium', 'high')
- resolved_at: timestamp (nullable)
- timestamps (created_at, updated_at, deleted_at)

### TicketAttachment
- id: integer
- ticket_id: string (foreign key)
- history_id: integer (foreign key, nullable)
- file_name: string
- file_path: string
- file_type: string
- file_size: integer
- timestamps (created_at, updated_at)

### TicketHistory
- id: integer
- ticket_id: string (foreign key)
- user_id: string (foreign key)
- status: string
- comments: text
- timestamps (created_at, updated_at)

### TicketFeedback
- id: integer
- ticket_id: string (foreign key)
- user_id: string (foreign key)
- rating: integer
- comment: text
- timestamps (created_at, updated_at)

### Notification
- id: string (UUID)
- user_id: string (foreign key)
- title: string
- content: text
- read: boolean
- type: string
- data: json
- timestamps (created_at, updated_at)

## Catatan Pengembangan
- Gunakan fitur Laravel seperti middleware, resource controllers, form request validation
- Ikuti prinsip RESTful API
- Gunakan Eloquent ORM untuk interaksi dengan database
- Implementasikan pagination untuk daftar yang panjang
- Gunakan soft delete untuk data penting