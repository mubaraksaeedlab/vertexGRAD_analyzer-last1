<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'VertexGrad Analyzer' }}</title>

    @vite(['resources/css/app.css', 'resources/css/frontend.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="frontend-body">
    <div class="frontend-shell">
        <header class="frontend-header">
            <div class="container frontend-header-inner">
                <a href="{{ route('frontend.home') }}" class="brand">
                    <span class="brand-mark">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 6.5C5 5.67 5.67 5 6.5 5h11A1.5 1.5 0 0 1 19 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 17.5v-11Z" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span>
                        <small>VertexGrad</small>
                        <strong>Analyzer</strong>
                    </span>
                </a>

                <nav class="frontend-nav">
                    <a href="{{ route('frontend.home') }}">Home</a>
                    <a href="{{ route('frontend.submit.index') }}">Submit Project</a>
                </nav>
            </div>
        </header>

        <main class="frontend-main">
            @php
                $isHomePage = request()->routeIs('frontend.home');
            @endphp

            @if($isHomePage)
                @if(session('success'))
                    <div class="container">
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="container">
                        <div class="alert alert-danger">
                            <div class="alert-title">Please fix the following:</div>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            @else
                <div class="container">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <div class="alert-title">Please fix the following:</div>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            @endif
        </main>

        <footer class="frontend-footer">
            <div class="container">
                <p>VertexGrad Analyzer — Offline-ready project submission and analysis platform.</p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>