@extends('layouts.app')

@php
    $pageTitle = 'Projects | VertexGrad Analyzer';
    $pageHeading = 'Projects';
    $pageSubheading = 'Browse uploaded projects, inspect their analysis status, and open detailed technical views.';
@endphp

@section('content')
    <section class="grid grid-cols-1 gap-6">
        <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="section-title">Project Workspace</h3>
                    <p class="section-subtitle">
                        Central list of submitted projects inside the analyzer platform.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-500">
                        Total: {{ $projects->total() }}
                    </div>
                    <button class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-300 transition hover:-translate-y-0.5 hover:bg-slate-800">
                        New Project
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-5 py-4 text-left font-semibold">Project</th>
                                <th class="px-5 py-4 text-left font-semibold">Status</th>
                                <th class="px-5 py-4 text-left font-semibold">Score</th>
                                <th class="px-5 py-4 text-left font-semibold">Scanned At</th>
                                <th class="px-5 py-4 text-left font-semibold">Created At</th>
                                <th class="px-5 py-4 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($projects as $project)
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

                                    $summary = is_array($project->summary) ? $project->summary : [];
                                    $score = $summary['overall_score'] ?? '-';
                                @endphp

                                <tr class="table-row-hover border-t border-slate-100 transition">
                                    <td class="px-5 py-4">
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ $project->name }}</p>
                                            <p class="mt-1 text-xs text-slate-400">Project ID: #{{ $project->id }}</p>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="status-badge {{ $statusClasses }}">
                                            <span class="status-dot {{ $dotClasses }}"></span>
                                            {{ ucfirst($project->scan_status ?? 'unknown') }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-slate-700 font-semibold">
                                        {{ $score }}
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        {{ optional($project->scanned_at)->format('Y-m-d h:i A') ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        {{ optional($project->created_at)->format('Y-m-d h:i A') }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <a href="{{ route('admin.projects.show', $project) }}"
                                           class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                        No projects found yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($projects->hasPages())
                <div class="mt-6">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection