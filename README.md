# Sistem Manajemen Tiket Helpdesk

Sistem manajemen tiket berbasis API dengan fitur notifikasi, obrolan, dan manajemen tiket untuk LAAK FIF.

## Teknologi

- **Backend**: Laravel
- **Database**: MySQL
- **Autentikasi**: Laravel Sanctum

## Dokumentasi API

### Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Rute Publik](#rute-publik)
3. [Autentikasi](#autentikasi)
4. [Manajemen Tiket](#manajemen-tiket)
5. [Notifikasi](#notifikasi)
6. [Obrolan (Chat)](#obrolan-chat)
7. [FAQ](#faq)
8. [Pengguna (Khusus Admin)](#pengguna-khusus-admin)
9. [Kategori (Khusus Admin)](#kategori-khusus-admin)
10. [Email (Khusus Admin)](#email-khusus-admin)
11. [Log Aktivitas (Khusus Admin)](#log-aktivitas-khusus-admin)

### Pengenalan

API ini menyediakan akses ke sistem manajemen tiket dengan berbagai fitur seperti autentikasi pengguna, pengelolaan tiket, notifikasi, obrolan, dan lainnya. Akses dibatasi berdasarkan peran pengguna (admin, student).

Semua endpoint diawali dengan `/api`.

### Rute Publik

Endpoint yang dapat diakses tanpa autentikasi.

#### Kategori

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/categories` | Mendapatkan daftar semua kategori |

#### FAQ

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/faqs/categories` | Mendapatkan daftar kategori FAQ |
| GET | `/faqs` | Mendapatkan daftar FAQ publik |
| GET | `/faqs/{id}` | Mendapatkan detail FAQ berdasarkan ID |

### Autentikasi

Menggunakan Laravel Sanctum untuk autentikasi berbasis token.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/auth/register` | Mendaftar pengguna baru |
| POST | `/auth/login` | Login dan mendapatkan token |
| POST | `/auth/logout` | Logout (memerlukan autentikasi) |
| GET | `/auth/profile` | Mendapatkan profil pengguna (memerlukan autentikasi) |
| POST | `/auth/profile` | Memperbarui profil pengguna (memerlukan autentikasi) |

### Manajemen Tiket

Semua endpoint berikut memerlukan autentikasi.

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| POST | `/tickets` | Membuat tiket baru | Semua pengguna |
| GET | `/tickets` | Mendapatkan daftar tiket (filter berdasarkan peran) | Semua pengguna |
| GET | `/tickets/statistics` | Mendapatkan statistik tiket | Semua pengguna |
| GET | `/tickets/export` | Mengekspor data tiket | Semua pengguna |
| GET | `/tickets/{id}` | Mendapatkan detail tiket | Pemilik tiket, Admin |
| POST | `/tickets/{id}/reveal-token` | Mengungkap token tiket anonim | Pemilik tiket, Admin |
| PATCH | `/tickets/{id}/status` | Memperbarui status tiket | Admin, Student (hanya untuk menutup) |
| PATCH | `/tickets/{id}/priority` | Memperbarui prioritas tiket | Admin |
| DELETE | `/tickets/{id}` | Menghapus tiket (soft delete) | Admin, Pemilik tiket |

### Notifikasi

Semua endpoint berikut memerlukan autentikasi.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/notifications` | Mendapatkan daftar notifikasi pengguna |
| PATCH | `/notifications/{id}/read` | Menandai notifikasi sebagai dibaca |
| PATCH | `/notifications/read-all` | Menandai semua notifikasi sebagai dibaca |
| POST | `/notifications` | Membuat notifikasi baru |

### Obrolan (Chat)

Semua endpoint berikut memerlukan autentikasi.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/tickets/{id}/chat` | Mendapatkan pesan chat untuk tiket |
| POST | `/tickets/{id}/chat` | Mengirim pesan baru |
| DELETE | `/tickets/{id}/chat/{message_id}` | Menghapus pesan |
| POST | `/tickets/{id}/chat/attachment` | Mengunggah lampiran ke chat |
| GET | `/tickets/{id}/chat/attachments` | Mendapatkan daftar lampiran chat |

### FAQ

Endpoint khusus admin (selain yang publik).

| Method | Endpoint | Deskripsi | Akses |
|--------|----------|-----------|-------|
| GET | `/admin/faqs` | Mendapatkan daftar semua FAQ (termasuk yang tidak publik) | Admin |
| GET | `/admin/faqs/{id}` | Mendapatkan detail FAQ (termasuk yang tidak publik) | Admin |
| POST | `/faqs` | Membuat FAQ baru | Admin |
| PATCH | `/faqs/{id}` | Memperbarui FAQ | Admin |
| DELETE | `/faqs/{id}` | Menghapus FAQ | Admin |
| POST | `/tickets/{id}/convert-to-faq` | Mengonversi tiket menjadi FAQ | Admin |

### Pengguna (Khusus Admin)

Semua endpoint berikut memerlukan autentikasi admin.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/users/statistics` | Mendapatkan statistik pengguna |
| GET | `/users` | Mendapatkan daftar pengguna |
| GET | `/users/{id}` | Mendapatkan detail pengguna |
| PATCH | `/users/{id}` | Memperbarui data pengguna |
| PATCH | `/users/{id}/role` | Memperbarui peran pengguna |
| DELETE | `/users/{id}` | Menghapus pengguna |

### Kategori (Khusus Admin)

Semua endpoint berikut memerlukan autentikasi admin.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/categories` | Membuat kategori baru |
| GET | `/categories/{id}` | Mendapatkan detail kategori |
| PUT | `/categories/{id}` | Memperbarui kategori |
| DELETE | `/categories/{id}` | Menghapus kategori |
| POST | `/categories/{category_id}/sub-categories` | Membuat sub-kategori |
| PUT | `/categories/{category_id}/sub-categories/{subcategory_id}` | Memperbarui sub-kategori |
| DELETE | `/categories/{category_id}/sub-categories/{subcategory_id}` | Menghapus sub-kategori |

### Email (Khusus Admin)

Semua endpoint berikut memerlukan autentikasi admin.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/emails/send` | Mengirim email |
| GET | `/emails/logs` | Mendapatkan log email |

### Log Aktivitas (Khusus Admin)

Semua endpoint berikut memerlukan autentikasi admin.

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/activity-logs` | Mendapatkan daftar log aktivitas |
| GET | `/activity-logs/statistics` | Mendapatkan statistik log aktivitas |
| GET | `/activity-logs/{id}` | Mendapatkan detail log aktivitas |

## Instalasi

1. Clone repositori:
   ```
   git clone https://github.com/TioHaidarHanif/Draft-Tugas-Akhir.git
   ```

2. Masuk ke direktori proyek:
   ```
   cd Draft-Tugas-Akhir
   ```

3. Install dependensi:
   ```
   composer install
   ```

4. Salin file .env.example:
   ```
   cp .env.example .env
   ```

5. Generate key aplikasi:
   ```
   php artisan key:generate
   ```

6. Konfigurasikan database di file .env

7. Jalankan migrasi dan seeder:
   ```
   php artisan migrate --seed
   ```

8. Jalankan server:
   ```
   php artisan serve
   ```