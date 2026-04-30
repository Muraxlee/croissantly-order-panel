<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div @class(['app-shell', 'guest-shell' => auth()->guest()])>
        @auth
            <button class="menu-toggle" type="button" data-menu-toggle aria-controls="app-sidebar" aria-expanded="false" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="menu-backdrop" data-menu-close></div>
            <aside class="sidebar" id="app-sidebar">
                <a class="brand" href="{{ route('dashboard') }}">
                    <span>
                        <strong class="brand-wordmark">Croissantly</strong>
                        <small class="brand-kicker">Order Panel</small>
                    </span>
                </a>

                <nav class="nav-list">
                    @if(auth()->user()->isAdmin())
                        <a @class(['active' => request()->routeIs('admin.dashboard')]) href="{{ route('dashboard') }}">Dashboard</a>
                        <a @class(['active' => request()->routeIs('admin.orders.*')]) href="{{ route('admin.orders.index') }}">Orders</a>
                        <a @class(['active' => request()->routeIs('admin.accounts.*')]) href="{{ route('admin.accounts.index') }}">Accounts</a>
                        <a @class(['active' => request()->routeIs('admin.slots.*')]) href="{{ route('admin.slots.index') }}">Staff Calendar</a>
                        <a @class(['active' => request()->routeIs('admin.employees.*', 'admin.timesheets.*')]) href="{{ route('admin.timesheets.index') }}">Timesheets</a>
                        <a @class(['active' => request()->routeIs('admin.menu.*')]) href="{{ route('admin.menu.index') }}">Menu</a>
                    @elseif(auth()->user()->isClient())
                        <a @class(['active' => request()->routeIs('client.place')]) href="{{ route('client.place') }}">Place order</a>
                        <a @class(['active' => request()->routeIs('client.orders.*')]) href="{{ route('client.orders.index') }}">Your orders</a>
                    @else
                        <a @class(['active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">Dashboard</a>
                    @endif
                </nav>

                <div class="sidebar-user">
                    <span>{{ auth()->user()->name }}</span>
                    <small>{{ ucfirst(auth()->user()->role) }}</small>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="link-button">Sign out</button>
                    </form>
                </div>
            </aside>
        @endauth

        <main class="main-panel">
            @if(session('status'))
                <div class="notice success">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="notice error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
