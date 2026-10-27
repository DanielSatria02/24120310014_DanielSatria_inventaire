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
    <main class="auth-layout">
        <aside class="auth-visual">
            <img class="auth-visual-image" src="{{ asset('images/loginimage.jpg') }}"
                alt="Warehouse shelves stocked with inventory">
            <div class="auth-visual-shade"></div>
            <div class="auth-visual-copy">
                <span class="visual-eyebrow">A CLEARER WAY TO WORK</span>
                <p>Know what’s in stock.<br>Know what’s moving.</p>
                <span class="visual-caption">One calm place for your inventory operations.</span>
            </div>
        </aside>

        <section class="auth-panel">
            <div class="auth-panel-inner">
                <a class="brand" href="{{ route('login') }}" aria-label="Inventaire home">
                    <span class="brand-mark" aria-hidden="true">i</span>
                    <span>inventaire</span>
                </a>
                @yield('content')
                <p class="auth-footnote">Inventory clarity for your whole team.</p>
            </div>
        </section>
    </main>
</body>
</html>
