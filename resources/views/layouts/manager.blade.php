<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Inventaire</title>
    @vite(['resources/css/app.css', 'resources/css/manager.css', 'resources/js/app.js'])
</head>
<body class="manager-page">
    @php($user = auth()->user())

    <header class="app-header">
        <button type="button" class="icon-button" data-menu-toggle
            aria-controls="side-menu" aria-expanded="false" aria-label="Open menu">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <a class="brand header-brand" href="{{ route($user->isManager() ? 'dashboard' : 'home') }}" aria-label="Inventaire home">
            <span class="brand-mark" aria-hidden="true">i</span>
            <span class="header-brand-name">inventaire</span>
        </a>

        <div class="account-chip">
            <span class="avatar" aria-hidden="true">{{ $user->initials() }}</span>
            <span class="account-text">
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->company?->name }}</span>
            </span>
        </div>
    </header>

    <div class="menu-overlay" data-menu-close></div>
    <nav id="side-menu" class="side-menu" aria-label="Main menu" aria-hidden="true" inert>
        <div class="side-menu-head">
            <span class="side-menu-title">Menu</span>
            <button type="button" class="icon-button" data-menu-close aria-label="Close menu">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        @php($menuItems = $user->isManager()
            ? [['dashboard', 'Dashboard'], ['categories.index', 'Categories'], ['account.edit', 'Edit account'], ['employees.create', 'Add employee']]
            : [['home', 'Home']])
        @foreach ($menuItems as [$routeName, $label])
            @if (request()->routeIs($routeName))
                <span class="side-link is-active" aria-current="page">{{ $label }}</span>
            @else
                <a href="{{ route($routeName) }}" class="side-link">{{ $label }}</a>
            @endif
        @endforeach

        <form method="POST" action="{{ route('logout') }}" class="side-menu-footer">
            @csrf
            <button type="submit" class="side-link side-link-button">Log out</button>
        </form>
    </nav>

    <main class="page">
        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
