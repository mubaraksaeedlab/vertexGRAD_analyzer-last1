<!DOCTYPE html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    data-theme="dark"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $metaDescription ?? __('frontend.layout.footer_description') }}">

    <title>@yield('title', 'VertexGrad Analyzer')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        (function () {
            const savedTheme = localStorage.getItem('vertexgrad_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    @vite([
        'resources/css/app.css',
        'resources/css/frontend/frontend-theme.css',
        'resources/css/frontend/frontend-base.css',
        'resources/js/app.js',
        'resources/js/frontend/frontend-core.js',
    ])

    <style>
        html[dir="rtl"] body {
            direction: rtl;
            font-family: 'Cairo', 'Inter', sans-serif;
        }

        html[dir="ltr"] body {
            direction: ltr;
            font-family: 'Inter', 'Cairo', sans-serif;
        }

        .navbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        /* English layout */
        html[dir="ltr"] .navbar-inner {
            flex-direction: row;
        }

        /* Arabic layout */
        html[dir="rtl"] .navbar-inner {
            flex-direction: row-reverse;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        html[dir="rtl"] .nav-links {
            flex-direction: row-reverse;
        }

        html[dir="ltr"] .nav-links {
            flex-direction: row;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        html[dir="rtl"] .navbar-actions {
            flex-direction: row-reverse;
        }

        html[dir="ltr"] .navbar-actions {
            flex-direction: row;
        }

        html[dir="rtl"] .brand,
        html[dir="rtl"] .nav-links,
        html[dir="rtl"] .mobile-menu-list,
        html[dir="rtl"] .footer-box {
            text-align: right;
        }

        html[dir="ltr"] .brand,
        html[dir="ltr"] .nav-links,
        html[dir="ltr"] .mobile-menu-list,
        html[dir="ltr"] .footer-box {
            text-align: left;
        }

        @media (max-width: 992px) {
            .nav-links,
            .desktop-cta {
                display: none;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="page-shell {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="site-bg" aria-hidden="true">
        <canvas id="networkCanvas" class="network-canvas"></canvas>
        <div class="site-bg-orb orb-1"></div>
        <div class="site-bg-orb orb-2"></div>
        <div class="site-bg-vignette"></div>
        <div class="site-bg-beam"></div>
    </div>

    <header class="navbar">
        <div class="container navbar-inner">

            <a href="{{ route('frontend.home') }}" class="brand">
                <span class="brand-mark">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 17L12 4L19 17H5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="M9 13H15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>

                <span class="stack-xs">
                    <span class="fw-800">VertexGrad</span>
                    <span class="text-xs text-muted">
                        {{ __('frontend.layout.analyzer_platform') }}
                    </span>
                </span>
            </a>

            <nav class="nav-links">
                <a
                    href="{{ route('frontend.home') }}"
                    class="nav-link {{ request()->routeIs('frontend.home') ? 'is-active' : '' }}"
                >
                    {{ __('frontend.layout.home') }}
                </a>

                <a
                    href="{{ route('frontend.submit.index') }}"
                    class="nav-link {{ request()->routeIs('frontend.submit.*') ? 'is-active' : '' }}"
                >
                    {{ __('frontend.layout.upload') }}
                </a>

                @if(isset($project))
                    <a
                        href="{{ route('frontend.projects.show', $project) }}"
                        class="nav-link {{ request()->routeIs('frontend.projects.*') ? 'is-active' : '' }}"
                    >
                        {{ __('frontend.layout.project') }}
                    </a>
                @endif
            </nav>

            <div class="navbar-actions">
                <form action="{{ route('locale.switch') }}" method="POST" class="inline-flex">
                    @csrf
                    <input
                        type="hidden"
                        name="locale"
                        value="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}"
                    >
                    <button
                        type="submit"
                        class="btn btn-soft btn-sm"
                        aria-label="Toggle language"
                    >
                        {{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}
                    </button>
                </form>

                <button
                    type="button"
                    class="btn btn-soft btn-sm"
                    data-theme-toggle
                    aria-label="Toggle theme"
                >
                    <span class="theme-label" data-theme-text>☀️</span>
                </button>

                <a
                    href="{{ route('frontend.submit.index') }}"
                    class="btn btn-primary btn-sm desktop-cta"
                >
                    {{ __('frontend.layout.get_started') }}
                </a>

                <button
                    type="button"
                    class="mobile-toggle"
                    data-mobile-toggle
                    aria-label="Toggle menu"
                    aria-expanded="false"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M4 12H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        <path d="M4 17H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

        </div>

        <div class="container relative">
            <div class="mobile-menu" data-mobile-menu hidden>
                <div class="mobile-menu-list">
                    <a
                        href="{{ route('frontend.home') }}"
                        class="nav-link {{ request()->routeIs('frontend.home') ? 'is-active' : '' }}"
                    >
                        {{ __('frontend.layout.home') }}
                    </a>

                    <a
                        href="{{ route('frontend.submit.index') }}"
                        class="nav-link {{ request()->routeIs('frontend.submit.*') ? 'is-active' : '' }}"
                    >
                        {{ __('frontend.layout.upload') }}
                    </a>

                    @if(isset($project))
                        <div class="divider my-2"></div>

                        <a
                            href="{{ route('frontend.projects.show', $project) }}"
                            class="nav-link {{ request()->routeIs('frontend.projects.*') ? 'is-active' : '' }}"
                        >
                            {{ __('frontend.layout.project') }}
                        </a>
                    @endif

                    <div class="divider my-2"></div>

                    <a
                        href="{{ route('frontend.submit.index') }}"
                        class="btn btn-primary w-full"
                    >
                        {{ __('frontend.layout.get_started') }}
                    </a>

                    <form action="{{ route('locale.switch') }}" method="POST" class="mt-3">
                        @csrf
                        <input
                            type="hidden"
                            name="locale"
                            value="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}"
                        >
                        <button type="submit" class="btn btn-soft btn-sm w-full">
                            {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-box glass">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="stack-xs">
                        <div class="fw-700">VertexGrad Analyzer</div>
                        <div class="text-sm text-muted">
                            {{ __('frontend.layout.footer_description') }}
                        </div>
                    </div>

                    <div class="text-sm text-muted">
                        © {{ now()->year }} VertexGrad
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeText = document.querySelector('[data-theme-text]');

            function updateThemeIcon() {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                if (themeText) {
                    themeText.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
                }
            }

            updateThemeIcon();
            document.addEventListener('vertexgrad:theme-changed', updateThemeIcon);
        });
    </script>

    @stack('scripts')
</body>
</html>