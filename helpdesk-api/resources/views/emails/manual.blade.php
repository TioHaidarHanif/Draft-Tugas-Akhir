<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
</head>
<body>
    <h2>{{ $subject }}</h2>
    <div>
        {!! nl2br(e($body)) !!}
    </div>
</body>
</html>
