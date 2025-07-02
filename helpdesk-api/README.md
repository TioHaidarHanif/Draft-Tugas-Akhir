<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Fitur: Token Rahasia untuk Ticket Anonymous

Fitur ini memungkinkan setiap ticket anonymous memiliki token rahasia yang hanya dapat diakses oleh user pembuat (dengan verifikasi password) atau admin.

### Cara Kerja
- Saat membuat ticket anonymous (`anonymous=true`), sistem otomatis membuat token unik dan mudah diingat.
- Token hanya dikembalikan pada response create ticket untuk user pembuat.
- Untuk melihat token setelah pembuatan, gunakan endpoint:
  - `POST /tickets/{id}/reveal-token` dengan body `{ "password": "..." }` (wajib login sebagai pembuat ticket)
  - Jika password benar, response akan mengembalikan token.
- Admin selalu dapat melihat token pada detail ticket.
- User lain tidak dapat mengakses token ticket anonymous milik orang lain.

### Keamanan
- Token unik dan tidak mudah ditebak.
- Validasi password menggunakan hash Laravel.
- Rate limiting dapat diaktifkan pada endpoint reveal-token.
- Token hanya dikembalikan pada response jika user berhak.

### Testing
- Terdapat feature test untuk seluruh skenario utama (lihat `tests/Feature/Tickets/TicketTokenTest.php`).

### Migrasi
- Jalankan `php artisan migrate` untuk menambah kolom token pada tabel tickets.

### Kode Terkait
- Model: `app/Models/Ticket.php`
- Service: `app/Services/TicketTokenService.php`
- Controller: `app/Http/Controllers/TicketController.php`
- FormRequest: `app/Http/Requests/RevealTicketTokenRequest.php`
- Route: `routes/api.php`

## Ticket Prioritas

Fitur prioritas ticket:
- Kolom prioritas pada tabel tickets (low, medium, high, urgent; default: medium)
- Endpoint pembuatan & update ticket support prioritas
- Validasi prioritas pada input
- Response API menampilkan prioritas
- Unit test & feature test prioritas ticket

### Cara migrasi
```
php artisan migrate
```

### Contoh request pembuatan ticket dengan prioritas
```
POST /api/tickets
{
  "judul": "Contoh Ticket",
  "deskripsi": "Isi deskripsi",
  ...,
  "prioritas": "high"
}
```

### Contoh response
```
{
  "status": "success",
  "data": {
    ...,
    "prioritas": "high"
  }
}
```

### Nilai prioritas yang valid
- low
- medium
- high
- urgent

Jika tidak diisi, prioritas otomatis menjadi medium.

# Helpdesk API

## 📄 Landing Page Dokumentasi API Otomatis

Aplikasi ini memiliki landing page otomatis di `/` yang menampilkan dokumentasi seluruh endpoint API secara dinamis. Fitur ini:

- Membaca seluruh endpoint API dari file routes dan controller.
- Menampilkan daftar endpoint (method, path, keterangan, middleware).
- Menjelaskan cara menggunakan API (login, autentikasi, format request/response, contoh cURL/Postman).
- Menampilkan batasan penting (validasi file, role akses, dsb).
- Tampilan rapi, responsif, dan mudah dipahami.
- Dokumentasi selalu up-to-date mengikuti perubahan route.

**Akses dokumentasi:**
- Buka root URL aplikasi (`/`) di browser.

**Contoh tampilan:**
- Tabel endpoint, penjelasan autentikasi, contoh request/response, dan batasan penting.

## Cara Menambah/Mengubah Endpoint
- Tambahkan/ubah route di file `routes/api.php` atau controller terkait.
- Landing page dokumentasi akan otomatis menyesuaikan.
