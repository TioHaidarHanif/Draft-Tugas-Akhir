# Token Rahasia untuk Ticket Anonymous

Dokumentasi fitur "Token Rahasia untuk Ticket Anonymous" pada Helpdesk Ticketing System.

## Deskripsi Fitur

Fitur ini memungkinkan sistem untuk menghasilkan token rahasia secara otomatis untuk setiap ticket anonymous yang dibuat. Token ini dapat digunakan oleh pengguna untuk mengidentifikasi dan memverifikasi kepemilikan ticket anonymous mereka.

## Implementasi

### Database

- Kolom `token` telah ditambahkan pada tabel `tickets`
- Token memiliki format `XXXX-XXXX-XXXX` (contoh: `ABCD-1234-EFGH`)
- Token hanya dibuat untuk ticket yang bersifat anonymous

### Endpoints

1. **POST /tickets**
   - Auto-generate token saat pembuatan ticket anonymous
   - Token dihasilkan dengan format yang user-friendly dan mudah diingat

2. **POST /tickets/{id}/reveal-token**
   - Endpoint untuk melihat token dengan verifikasi password
   - Parameter: `password` (password akun pengguna)
   - Response: `token` jika verifikasi berhasil

3. **GET /tickets/{id}**
   - Menampilkan token dalam response jika:
     - Pengguna adalah admin, atau
     - Pengguna telah memverifikasi diri dengan password melalui endpoint reveal-token

### Keamanan

- Token hanya ditampilkan setelah verifikasi password
- Admin dapat melihat token tanpa verifikasi
- Token divalidasi formatnya untuk memastikan keamanan
- Token bersifat unik untuk setiap ticket

## Cara Penggunaan

### Untuk Pengguna:

1. **Membuat ticket anonymous:**
   - Set parameter `anonymous: true` saat membuat ticket
   - Token akan di-generate secara otomatis

2. **Melihat token:**
   - Kirim request ke `/tickets/{id}/reveal-token` dengan password akun
   - Setelah berhasil, token dapat dilihat pada detail ticket

### Untuk Admin:

- Admin dapat melihat token semua ticket anonymous langsung dari endpoint detail ticket tanpa perlu verifikasi tambahan.

## Testing

Unit tests telah dibuat untuk memastikan fitur berfungsi dengan baik:
- Test token generation untuk ticket anonymous
- Test token tidak dihasilkan untuk ticket non-anonymous
- Test admin dapat melihat token tanpa verifikasi
- Test pengguna dapat melihat token dengan password yang benar
- Test pengguna tidak dapat melihat token dengan password yang salah
- Test token service menghasilkan token yang valid
