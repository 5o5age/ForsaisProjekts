<!doctype html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panelis') · StableCare</title>
    <meta name="theme-color" content="#183e32">
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
    <script defer src="{{ asset('assets/app.js') }}"></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('home') }}">
            <span class="brand-mark">S</span><span><strong>StableCare</strong><small>Staļļa pārvaldība</small></span>
        </a>
        @auth
        <div class="sidebar-caption">DARBA VIETA</div>
        <nav class="side-nav" aria-label="Galvenā navigācija">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="nav-icon">⌂</span>Pārskats</a>
            <a href="{{ route('horses.index') }}" class="nav-item {{ request()->routeIs('horses.*') ? 'active' : '' }}"><span class="nav-icon">♘</span>Zirgi</a>
            <a href="{{ route('calendar.index') }}" class="nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}"><span class="nav-icon">▦</span>Kalendārs</a>
            @if(auth()->user()->canManageTasks())
                <a href="{{ route('tasks.index') }}" class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}"><span class="nav-icon">✓</span>Ikdienas uzdevumi</a>
                <a href="{{ route('feedings.index') }}" class="nav-item {{ request()->routeIs('feedings.*') ? 'active' : '' }}"><span class="nav-icon">◌</span>Barošanas plāni</a>
                <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}"><span class="nav-icon">▤</span>Noliktava</a>
            @endif
            <a href="{{ route('trainings.index') }}" class="nav-item {{ request()->routeIs('trainings.*') ? 'active' : '' }}"><span class="nav-icon">↗</span>Treniņi</a>
            <a href="{{ route('health.index') }}" class="nav-item {{ request()->routeIs('health.*') ? 'active' : '' }}"><span class="nav-icon">＋</span>Veselība</a>
            @if(auth()->user()->isAdmin())
                <div class="sidebar-caption sidebar-caption-spaced">SISTĒMA</div>
                <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"><span class="nav-icon">♙</span>Lietotāji</a>
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"><span class="nav-icon">▥</span>Atskaites</a>
            @endif
        </nav>
        <div class="sidebar-bottom">
            <a href="{{ route('profile.edit') }}" class="profile-mini {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="profile-mini-text"><strong>{{ auth()->user()->name }}</strong><small>{{ \App\Models\User::ROLE_LABELS[auth()->user()->role] ?? auth()->user()->role }}</small></span>
                <span class="profile-arrow">↗</span>
            </a>
            <form action="{{ route('logout') }}" method="post">@csrf<button class="nav-item nav-logout" type="submit"><span class="nav-icon">↪</span>Iziet no sistēmas</button></form>
        </div>
        @else
            <nav class="side-nav"><a class="nav-item" href="{{ route('login') }}">Pieslēgties</a><a class="nav-item" href="{{ route('register') }}">Reģistrēties</a></nav>
        @endauth
        <div class="sidebar-footer">StableCare · {{ date('Y') }}</div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button class="mobile-menu" type="button" aria-label="Atvērt izvēlni" aria-controls="sidebar" aria-expanded="false" data-menu-toggle>☰</button>
            <div class="breadcrumb"><span>StableCare</span><span class="crumb-sep">/</span><strong>@yield('title', 'Pārskats')</strong></div>
            <div class="topbar-right"><span class="status-dot"></span><span class="topbar-status">Sistēma gatava</span><span class="topbar-date">{{ now()->translatedFormat('j. F Y') }}</span></div>
        </header>
        <main class="page-content">
            @if(session('success'))<div class="flash flash-success" role="status"><span>✓</span><div>{{ session('success') }}</div><button type="button" data-dismiss aria-label="Aizvērt">×</button></div>@endif
            @if($errors->any())<div class="flash flash-error" role="alert"><span>!</span><div><strong>Lūdzu, pārbaudi ievadīto informāciju.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div><button type="button" data-dismiss aria-label="Aizvērt">×</button></div>@endif
            @yield('content')
        </main>
        <footer class="app-footer"><span>© {{ date('Y') }} StableCare</span><span>Rūpes par zirgiem. Sakārtotas ikdienas.</span></footer>
    </div>
</div>
<div class="mobile-scrim" data-menu-close></div>
</body>
</html>
