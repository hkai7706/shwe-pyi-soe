@php($isStaff = $isStaff ?? false)
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b5b4a">
    @if ($isStaff)
        <meta name="robots" content="noindex,nofollow">
    @endif
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <title>@yield('title') · Shwe Pyi Soe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
    <div x-cloak x-show="drawer" class="drawer-overlay" @click="drawer = false"></div>

    <aside id="navigation" class="sidebar" :class="{ 'is-open': drawer }">
        <a href="{{ $isStaff ? route('admin.overview') : route('home') }}" class="brand">
            <img class="school-logo" src="{{ asset('school-logo.jpg') }}" alt="{{ $settings['name_en'] ?? 'Shwe Pyi Soe Private High School' }}">
            <span>
                <strong>{{ Str::before($settings['name_en'] ?? 'Shwe Pyi Soe Private High School', ' Private High School') }}</strong>
                <span>{{ __('ui.school') }}</span>
                <small>{{ $settings['name_my'] ?? 'ရွှေပြည်စိုးကိုယ်ပိုင်အထက်တန်းကျောင်း' }}</small>
            </span>
        </a>
        <button type="button" class="drawer-close icon-button" @click="drawer = false" aria-label="{{ __('ui.close') }}">
            <x-icon name="close"/>
        </button>

        <div class="sidebar-label">{{ $isStaff ? __('ui.staff_portal') : __('ui.community') }}</div>
        <nav>
            @if ($isStaff)
                @foreach (['overview', 'students', 'attendance', 'marks', 'fees', 'teachers', 'announcements', 'settings'] as $item)
                    @php($url = in_array($item, ['students', 'teachers', 'announcements']) ? route('admin.records', $item) : route('admin.'.$item))
                    <a href="{{ $url }}" class="nav-item {{ request()->url() === $url || request()->is(config('school.admin_path').'/'.$item.'/*') ? 'active' : '' }}">
                        <x-icon :name="$item"/>
                        <span>{{ __('ui.'.($item === 'overview' ? 'admin_overview' : $item)) }}</span>
                    </a>
                @endforeach
            @else
                @foreach (['home', 'announcements', 'grades', 'teachers', 'achievers', 'overview'] as $item)
                    <a href="{{ route($item) }}" class="nav-item {{ request()->routeIs($item) || ($item === 'grades' && request()->routeIs('grade', 'student')) ? 'active' : '' }}" @if (request()->routeIs($item)) aria-current="page" @endif>
                        <x-icon :name="$item"/>
                        <span>{{ __('ui.'.$item) }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-note">
                <x-icon name="book"/>
                <p>{{ __('ui.year_value') }}<small>{{ __('ui.school_year') }}</small></p>
            </div>
            <div class="language-toggle" aria-label="{{ __('ui.language') }}">
                @foreach (['en' => 'English', 'my' => 'မြန်မာ'] as $code => $label)
                    <form method="POST" action="{{ route('language', $code) }}">
                        @csrf
                        <button class="{{ app()->getLocale() === $code ? 'selected' : '' }}" lang="{{ $code }}">{{ $label }}</button>
                    </form>
                @endforeach
            </div>
            @if ($isStaff)
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout"><x-icon name="logout"/>{{ __('ui.logout') }}</button>
                </form>
            @endif
            <div class="sidebar-copyright">© {{ date('Y') }} Shwe Pyi Soe</div>
        </div>
    </aside>

    <div class="main-shell">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu icon-button" @click="drawer = true" aria-controls="navigation" :aria-expanded="drawer" aria-label="{{ __('ui.menu') }}"><x-icon name="menu"/></button>
                <span>{{ $isStaff ? __('ui.staff_portal') : __('ui.portal') }}</span>
            </div>
            <div class="topbar-right"><span class="status-dot"></span><span>{{ __('ui.school_year') }} · {{ __('ui.year_value') }}</span></div>
        </header>
        <main id="main-content" class="content">
            @if (session('success'))
                <div role="status" class="flash">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="error-box"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
        <footer class="footer"><span>{{ __('ui.public_footer') }}</span><span>Yangon, Myanmar</span></footer>
    </div>
</body>
</html>
