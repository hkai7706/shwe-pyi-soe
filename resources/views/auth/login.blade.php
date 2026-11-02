<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <title>{{ __('ui.staff_portal') }} · Shwe Pyi Soe</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-card card">
        <div class="login-brand">
            <img class="school-logo login-logo" src="{{ asset('school-logo.jpg') }}" alt="Shwe Pyi Soe Private High School">
            <h1>Shwe Pyi Soe</h1>
            <p>{{ __('ui.staff_portal') }}</p>
        </div>
        <h2>{{ __('ui.login') }}</h2>
        <p class="muted">{{ __('ui.staff_intro') }}</p>
        @if ($errors->any())
            <div class="error-box" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label>{{ __('ui.username') }}<input name="username" value="{{ old('username') }}" required autofocus autocomplete="username"></label>
            <label>{{ __('ui.password') }}<input name="password" type="password" required autocomplete="current-password"></label>
            <label class="checkbox-label"><input type="checkbox" name="remember">{{ __('ui.remember') }}</label>
            <button class="button">{{ __('ui.login') }}<x-icon name="arrow"/></button>
        </form>
        <div class="language-toggle">
            @foreach (['en' => 'English', 'my' => 'မြန်မာ'] as $code => $label)
                <form method="POST" action="{{ route('language', $code) }}">
                    @csrf
                    <button class="{{ app()->getLocale() === $code ? 'selected' : '' }}">{{ $label }}</button>
                </form>
            @endforeach
        </div>
    </main>
</body>
</html>
