@extends('layouts.app')

@php
    $pageTitle = 'Reports | VertexGrad Analyzer';
    $pageHeading = 'Reports Center';
    $pageSubheading = 'Browse generated analysis reports, filter results, and access JSON or PDF exports.';
@endphp

@section('content')
    <section class="grid grid-cols-1 gap-6">
        <!-- Filters -->
        <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
            <div class="mb-6">
                <h3 class="section-title">Search & Filters</h3>
                <p class="section-subtitle">Find reports quickly using advanced search and filtering controls.</p>
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Keyword</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search title, generator, version..."
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-500 focus:ring-4 focus:ring-teal-100"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Project ID</label>
                    <input
                        type="number"
                        name="project_id"
                        value="{{ request('project_id') }}"
                        placeholder="e.g. 6"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-500 focus:ring-4 focus:ring-teal-100"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Analysis Run ID</label>
                    <input
                        type="number"
                        name="analysis_run_id"
                        value="{{ request('analysis_run_id') }}"
                        placeholder="e.g. 5"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-500 focus:ring-4 focus:ring-teal-100"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-600">Report Type</label>
                    <select
                        name="report_type"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-500 focus:ring-4 focus:ring-teal-100"
                    >
                        <option value="">All Types</option>
                        <option value="json" @selected(request('report_type') === 'json')>JSON</option>
                        <option value="pdf" @selected(request('report_type') === 'pdf')>PDF</option>
                        <option value="full" @selected(request('report_type') === 'full')>Full</option>
                        <option value="technical" @selected(request('report_type') === 'technical')>Technical</option>
                        <option value="summary" @selected(request('report_type') === 'summary')>Summary</option>
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-300 transition hover:-translate-y-0.5 hover:bg-slate-800"
                    >
                        Apply Filters
                    </button>

                    <a
                        href="{{ route('admin.reports.index') }}"
                        class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Reports table -->
        <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="section-title">Generated Reports</h3>
                    <p class="section-subtitle">All analysis reports produced by the analyzer engine.</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-500">
                    Total: {{ $reports->total() }}
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-5 py-4 text-left font-semibold">Report</th>
                                <th class="px-5 py-4 text-left font-semibold">Project</th>
                                <th class="px-5 py-4 text-left font-semibold">Run</th>
                                <th class="px-5 py-4 text-left font-semibold">Type</th>
                                <th class="px-5 py-4 text-left font-semibold">Generator</th>
                                <th class="px-5 py-4 text-left font-semibold">Generated At</th>
                                <th class="px-5 py-4 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white">
                            @forelse($reports as $report)
                                @php
                                    $type = strtolower($report->report_type ?? 'unknown');

                                    $typeClasses = match ($type) {
                                        'json' => 'bg-cyan-50 text-cyan-700 border border-cyan-200',
                                        'pdf' => 'bg-rose-50 text-rose-700 border border-rose-200',
                                        'technical' => 'bg-violet-50 text-violet-700 border border-violet-200',
                                        'summary' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                        'full' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                        default => 'bg-slate-100 text-slate-700 border border-slate-200',
                                    };
                                @endphp

                                <tr class="table-row-hover border-t border-slate-100 transition">
                                    <td class="px-5 py-4">
                                        <div>
                                            <p class="font-semibold text-slate-800">
                                                {{ $report->title ?: ('Report #' . $report->id) }}
                                            </p>
                                            <p class="mt-1 text-xs text-slate-400">ID: #{{ $report->id }}</p>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-slate-700">
                                        #{{ $report->project_id }}
                                    </td>

                                    <td class="px-5 py-4 text-slate-700">
                                        #{{ $report->analysis_run_id }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $typeClasses }}">
                                            {{ $report->report_type ?? 'unknown' }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-slate-700">
                                        {{ $report->generator ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 text-slate-600">
                                        {{ optional($report->generated_at ?? $report->created_at)->format('Y-m-d h:i A') }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('admin.reports.show', $report) }}"
                                               class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                                View
                                            </a>

                                            <a href="{{ route('admin.reports.raw', $report) }}"
                                               class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                                Raw
                                            </a>

                                            <a href="{{ route('admin.reports.download', $report) }}"
                                               class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800">
                                                JSON
                                            </a>

                                            <a href="{{ route('admin.reports.download-pdf', $report) }}"
                                               class="rounded-xl bg-teal-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-teal-700">
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                        No reports found for the current filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($reports->hasPages())
                <div class="mt-6">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection