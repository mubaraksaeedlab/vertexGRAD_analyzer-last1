@extends('layouts.app')

@php
    $pageTitle = $project->name . ' | VertexGrad Analyzer';
    $pageHeading = 'Project Details';
    $pageSubheading = 'Technical overview, scan state, files, and latest analysis summary for this project.';
    $summary = is_array($project->summary) ? $project->summary : [];
@endphp

@section('content')
    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-6">
            <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="section-title">{{ $project->name }}</h3>
                        <p class="section-subtitle">Project ID #{{ $project->id }}</p>
                    </div>

                    <div>
                        @php
                            $status = strtolower($project->scan_status ?? 'unknown');
                            $statusClasses = match ($status) {
                                'completed' => 'bg-emerald-50 text-emerald-700',
                                'running' => 'bg-blue-50 text-blue-700',
                                'pending' => 'bg-amber-50 text-amber-700',
                                'failed' => 'bg-rose-50 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            };

                            $dotClasses = match ($status) {
                                'completed' => 'bg-emerald-500',
                                'running' => 'bg-blue-500',
                                'pending' => 'bg-amber-500',
                                'failed' => 'bg-rose-500',
                                default => 'bg-slate-400',
                            };
                        @endphp

                        <span class="status-badge {{ $statusClasses }}">
                            <span class="status-dot {{ $dotClasses }}"></span>
                            {{ ucfirst($project->scan_status ?? 'unknown') }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Overall Score</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['overall_score'] ?? '—' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Grade</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['grade'] ?? '—' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Issues Found</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['issues_found'] ?? '—' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Primary Language</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary['primary_language'] ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
                <div class="mb-5">
                    <h3 class="section-title">Project Files</h3>
                    <p class="section-subtitle">Files discovered and attached to this project</p>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/80">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-5 py-4 text-left font-semibold">File Name</th>
                                    <th class="px-5 py-4 text-left font-semibold">Extension</th>
                                    <th class="px-5 py-4 text-left font-semibold">Relative Path</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @forelse($project->files as $file)
                                    <tr class="table-row-hover border-t border-slate-100 transition">
                                        <td class="px-5 py-4 font-medium text-slate-800">{{ $file->file_name }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $file->extension }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $file->relative_path }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-5 py-12 text-center text-slate-400">
                                            No files attached to this project.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
                <div class="mb-5">
                    <h3 class="section-title">Analysis Summary</h3>
                    <p class="section-subtitle">Quick technical snapshot</p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Latest Run ID</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $summary['latest_run_id'] ?? '—' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Report ID</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $summary['report_id'] ?? '—' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Detected Languages</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse(($summary['detected_languages'] ?? []) as $language)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $language }}
                                </span>
                            @empty
                                <span class="text-sm text-slate-400">No languages detected.</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Created At</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">
                            {{ optional($project->created_at)->format('Y-m-d h:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection