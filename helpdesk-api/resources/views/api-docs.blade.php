<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 32px; }
        h1, h2, h3 { color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        th, td { border: 1px solid #e1e1e1; padding: 8px 12px; text-align: left; }
        th { background: #f0f0f0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; background: #e3e3e3; margin-right: 4px; }
        .badge-auth { background: #ffe0b2; color: #b26a00; }
        .badge-role { background: #b3e5fc; color: #01579b; }
        .section { margin-bottom: 40px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
        pre { background: #f4f4f4; padding: 12px; border-radius: 6px; overflow-x: auto; }
        @media (max-width: 700px) {
            .container { padding: 10px; }
            table, th, td { font-size: 13px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>API Documentation</h1>
    <div class="section">
        <h2>Daftar Endpoint</h2>
        <table>
            <thead>
            <tr>
                <th>Method</th>
                <th>Path</th>
                <th>Keterangan</th>
                <th>Middleware</th>
            </tr>
            </thead>
            <tbody>
            @foreach($routes as $route)
                <tr>
                    <td><code>{{ $route['method'] }}</code></td>
                    <td><code>{{ '/' . $route['uri'] }}</code></td>
                    <td>{{ $route['action'] }}</td>
                    <td>
                        @foreach($route['middleware'] as $mw)
                            <span class="badge {{ Str::contains($mw, 'auth') ? 'badge-auth' : (Str::contains($mw, 'role') ? 'badge-role' : '') }}">{{ $mw }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="section">
        <h2>Cara Menggunakan API</h2>
        <h3>Autentikasi</h3>
        <p>Gunakan <code>Bearer Token</code> yang didapat dari endpoint <code>/api/auth/login</code> pada header <code>Authorization</code> untuk mengakses endpoint yang membutuhkan autentikasi.</p>
        <h3>Contoh Login (cURL)</h3>
        <pre>{{ $usage['login']['curl'] }}</pre>
        <h3>Contoh Login (Postman)</h3>
        <pre>{
    "method": "{{ $usage['login']['postman']['method'] }}",
    "url": "{{ $usage['login']['postman']['url'] }}",
    "body": {
        "email": "{{ $usage['login']['postman']['body']['email'] }}",
        "password": "{{ $usage['login']['postman']['body']['password'] }}"
    }
}</pre>
        <h3>Format Response Sukses</h3>
        <pre>{
    "status": "success",
    "message": "Login berhasil",
    "data": {
        "token": "..."
    }
}</pre>
        <h3>Format Response Error</h3>
        <pre>{
    "status": "error",
    "message": "Email atau password salah"
}</pre>
    </div>
    <div class="section">
        <h2>Batasan & Validasi Penting</h2>
        <ul>
            @foreach($limitations as $lim)
                <li>{{ $lim }}</li>
            @endforeach
        </ul>
    </div>
    <div class="section">
        <h2>Catatan</h2>
        <ul>
            <li>Endpoint dengan <span class="badge badge-auth">auth</span> membutuhkan autentikasi.</li>
            <li>Endpoint dengan <span class="badge badge-role">role</span> hanya bisa diakses oleh role tertentu.</li>
            <li>Gunakan <code>Accept: application/json</code> pada setiap request.</li>
        </ul>
    </div>
</div>
</body>
</html>
