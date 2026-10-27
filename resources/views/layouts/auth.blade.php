<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account') · Inventaire</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <a class="brand" href="{{ route('login') }}" aria-label="Inventaire home">
            <span class="brand-mark" aria-hidden="true">i</span>
            <span>inventaire</span>
        </a>
        @yield('content')
        <p class="auth-footnote">Inventory clarity for your whole team.</p>
    </main>
</body>
</html>
