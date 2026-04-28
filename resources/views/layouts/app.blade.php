<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? 'VertexGrad Analyzer' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f8fafc;
        }

        .bg-grid {
            background-image:
                linear-gradient(to right, rgba(15, 23, 42, 0.035) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(15, 23, 42, 0.035) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .soft-shadow {
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08),
                0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .sidebar-shadow {
            box-shadow:
                0 20px 40px rgba(15, 23, 42, 0.10),
                0 4px 12px rgba(15, 23, 42, 0.05);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            border-radius: 1rem;
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #475569;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: rgba(15, 23, 42, 0.05);
            color: #0f172a;
            transform: translateX(2px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #0f766e 0%, #0891b2 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(8, 145, 178, 0.20);
        }

        .nav-icon {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
        }

        .status-dot {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
            display: inline-block;
        }

        .table-row-hover:hover {
            background: rgba(15, 23, 42, 0.025);
        }

        .section-title {
            font-size: 1.35rem;
            line-height: 1.2;
            font-weight: 800;
            color: #0f172a;
        }

        .section-subtitle {
            margin-top: 0.35rem;
            font-size: 0.94rem;
            color: #64748b;
        }
    </style>
</head>
<body class="min-h-screen text-slate-800">
    <div class="fixed inset-0 bg-grid pointer-events-none"></div>

    <div class="relative flex min-h-screen">
        <!-- Sidebar -->
        <aside class="hidden xl:flex xl:w-[290px] xl:flex-col xl:border-r xl:border-slate-200/70 xl:bg-white/80 xl:backdrop-blur-xl">
            <div class="flex h-full flex-col px-6 py-6">
                <!-- Brand -->
                <div class="mb-8 flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 via-cyan-500 to-slate-900 text-white shadow-xl shadow-cyan-200/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 7.5h-9m9 4.5h-9m9 4.5h-9M6 4.5h12A1.5 1.5 0 0119.5 6v12a1.5 1.5 0 01-1.5 1.5H6A1.5 1.5 0 014.5 18V6A1.5 1.5 0 016 4.5z" />
                        </svg>
                    </div>

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-teal-600">VertexGrad</p>
                        <h1 class="text-xl font-extrabold text-slate-900">Analyzer</h1>
                        <p class="mt-1 text-xs text-slate-500">Multi-language Code Analysis Platform</p>
                    </div>
                </div>

                <!-- Navigation -->
                <div>
                    <p class="mb-3 px-2 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">Navigation</p>

                    <nav class="space-y-2">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12l7.5-7.5L18.75 12M5.25 10.5v8.25A1.5 1.5 0 006.75 20.25h10.5a1.5 1.5 0 001.5-1.5V10.5" />
                            </svg>
                            Dashboard
                        </a>

                        <a href="{{ route('admin.projects.index') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M6 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75z" />
    </svg>
    Projects
</a>

                        <a href="javascript:void(0)" class="nav-link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v-6m3 6V6.75m3 10.5v-3.75M4.5 19.5h15" />
                            </svg>
                            Analysis Runs
                        </a>
<a
                        href="{{ route('admin.reports.index') }}"
class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6l3 3v13.5H7.5A2.25 2.25 0 015.25 18V6A2.25 2.25 0 017.5 3.75z" />
                            </svg>
                            Reports
                        </a>

                        <a href="javascript:void(0)" class="nav-link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008v.008H12v-.008zM10.29 3.86l-7.5 13a1.5 1.5 0 001.3 2.25h15.82a1.5 1.5 0 001.3-2.25l-7.5-13a1.5 1.5 0 00-2.6 0z" />
                            </svg>
                            Issues
                        </a>

                        <a href="javascript:void(0)" class="nav-link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 12h9.75M10.5 18h9.75M3.75 6h.008v.008H3.75V6zm0 6h.008v.008H3.75V12zm0 6h.008v.008H3.75V18z" />
                            </svg>
                            Logs
                        </a>

                        <a href="javascript:void(0)" class="nav-link">
                            <svg xmlns="http://www.w3.org/2000/svg" class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6.75a1.5 1.5 0 113 0v.443a6.75 6.75 0 012.25 1.3l.384-.22a1.5 1.5 0 111.5 2.598l-.384.222a6.77 6.77 0 010 2.6l.384.222a1.5 1.5 0 11-1.5 2.598l-.384-.22a6.75 6.75 0 01-2.25 1.3v.443a1.5 1.5 0 11-3 0v-.443a6.75 6.75 0 01-2.25-1.3l-.384.22a1.5 1.5 0 11-1.5-2.598l.384-.222a6.77 6.77 0 010-2.6l-.384-.222a1.5 1.5 0 111.5-2.598l.384.22a6.75 6.75 0 012.25-1.3V6.75z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                            Settings
                        </a>
                    </nav>
                </div>

                <!-- Bottom Panel -->
                <div class="mt-auto rounded-[24px] bg-gradient-to-br from-slate-950 via-slate-900 to-teal-900 p-5 text-white sidebar-shadow">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-300">Platform Status</p>
                    <h3 class="mt-3 text-lg font-bold">Analyzer Core Ready</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-300">
                        PHP, JavaScript, and Python analyzers are registered and active inside the detection pipeline.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">PHP</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">JavaScript</span>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">Python</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex min-h-screen flex-1 flex-col">
            <!-- Top Header -->
            <header class="border-b border-slate-200/70 bg-white/80 backdrop-blur-xl">
                <div class="flex items-center justify-between px-6 py-5 lg:px-8">
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">
                            {{ $pageHeading ?? 'Dashboard' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $pageSubheading ?? 'Professional control center for the VertexGrad Analyzer platform.' }}
                        </p>
                    </div>

                    <div class="hidden md:flex items-center gap-3">
                        <div class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-500 shadow-sm">
                            Multi-language Engine
                        </div>

                        <button class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-300 transition hover:-translate-y-0.5 hover:bg-slate-800">
                            Create Analysis
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 px-6 py-8 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>