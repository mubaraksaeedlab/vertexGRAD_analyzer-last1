@extends('layouts.app')

@php
    $pageTitle = 'Report #' . $report->id . ' | VertexGrad Analyzer';
    $pageHeading = 'Analysis Report';
    $pageSubheading = 'Comprehensive technical analysis summary, severity overview, and detected issues for this report.';

    $project = $data['project'] ?? [];
    $analysisRun = $data['analysis_run'] ?? [];
    $score = $data['score'] ?? [];
    $issues = $data['issues'] ?? [];

    $overallScore = $score['overall_score'] ?? 0;
    $grade = $score['grade'] ?? '-';
    $issuesCount = $score['issues_count'] ?? count($issues);
    $filesProcessed = $analysisRun['files_processed'] ?? 0;

    $criticalCount = $score['critical_count'] ?? 0;
    $highCount = $score['high_count'] ?? 0;
    $mediumCount = $score['medium_count'] ?? 0;
    $lowCount = $score['low_count'] ?? 0;
    $infoCount = $score['info_count'] ?? 0;
@endphp

@section('content')
    <section class="grid grid-cols-1 gap-6">
        <!-- Top action bar -->
        <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-teal-700">
                        Report #{{ $report->id }}
                    </div>

                    <h3 class="section-title">
                        {{ $report->title ?: ('Analysis Report #' . $report->id) }}
                    </h3>

                    <p class="section-subtitle">
                        {{ $project['name'] ?? 'Unknown Project' }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('reports.api', $report) }}"
                       class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        API JSON
                    </a>

                    <a href="{{ route('reports.raw', $report) }}"
                       class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        Raw JSON
                    </a>

                    <a href="{{ route('reports.download', $report) }}"
                       class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-slate-300 transition hover:-translate-y-0.5 hover:bg-slate-800">
                        Download JSON
                    </a>

                    <a href="{{ route('reports.download-pdf', $report) }}"
                       class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-teal-200 transition hover:-translate-y-0.5 hover:bg-teal-700">
                        Download PDF
                    </a>
                </div>
            </div>
        </div>

        <!-- KPI cards -->
        <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Overall Score</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $overallScore }}</h3>
                <p class="mt-2 text-xs text-slate-400">Calculated overall technical result</p>
            </div>

            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Grade</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $grade }}</h3>
                <p class="mt-2 text-xs text-slate-400">Final grading classification</p>
            </div>

            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Issues Found</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $issuesCount }}</h3>
                <p class="mt-2 text-xs text-slate-400">Detected security and code findings</p>
            </div>

            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Files Processed</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $filesProcessed }}</h3>
                <p class="mt-2 text-xs text-slate-400">Analyzed source files in this run</p>
            </div>
        </section>

        <!-- Main summary -->
        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
                <div class="mb-5">
                    <h3 class="section-title">Project Information</h3>
                    <p class="section-subtitle">Metadata for the analyzed project</p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Project ID</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $project['id'] ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">UUID</p>
                        <p class="mt-2 break-all text-sm font-medium text-slate-700">{{ $project['uuid'] ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Primary Language</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $project['primary_language'] ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Scan Status</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $project['scan_status'] ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
                <div class="mb-5">
                    <h3 class="section-title">Analysis Run</h3>
                    <p class="section-subtitle">Execution information for this report</p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Run ID</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $analysisRun['id'] ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Status</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $analysisRun['status'] ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Stage</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $analysisRun['stage'] ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Started At</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ $analysisRun['started_at'] ?? '-' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Finished At</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ $analysisRun['finished_at'] ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
                <div class="mb-5">
                    <h3 class="section-title">Severity Summary</h3>
                    <p class="section-subtitle">Issue distribution by severity level</p>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50/70 p-4">
                        <span class="font-semibold text-rose-700">Critical</span>
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-bold text-rose-700">{{ $criticalCount }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-red-200 bg-red-50/70 p-4">
                        <span class="font-semibold text-red-700">High</span>
                        <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-bold text-red-700">{{ $highCount }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
                        <span class="font-semibold text-amber-700">Medium</span>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-700">{{ $mediumCount }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-sky-200 bg-sky-50/70 p-4">
                        <span class="font-semibold text-sky-700">Low</span>
                        <span class="rounded-full bg-sky-100 px-3 py-1 text-sm font-bold text-sky-700">{{ $lowCount }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-violet-200 bg-violet-50/70 p-4">
                        <span class="font-semibold text-violet-700">Info</span>
                        <span class="rounded-full bg-violet-100 px-3 py-1 text-sm font-bold text-violet-700">{{ $infoCount }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Score breakdown -->
        <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Security Score</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $score['security_score'] ?? 0 }}</h3>
            </div>

            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Quality Score</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $score['quality_score'] ?? 0 }}</h3>
            </div>

            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Performance Score</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $score['performance_score'] ?? 0 }}</h3>
            </div>

            <div class="glass-card soft-shadow rounded-[24px] border border-white/60 p-5">
                <p class="text-sm font-medium text-slate-500">Maintainability</p>
                <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $score['maintainability_score'] ?? 0 }}</h3>
            </div>
        </section>

        <!-- Issues -->
        <section class="glass-card soft-shadow rounded-[28px] border border-white/70 p-6">
            <div class="mb-6">
                <h3 class="section-title">Detected Issues</h3>
                <p class="section-subtitle">Detailed technical findings discovered during analysis</p>
            </div>

            <div class="space-y-4">
                @forelse($issues as $issue)
                    @php
                        $severity = strtolower($issue['severity'] ?? 'info');

                        $severityBadge = match ($severity) {
                            'critical' => 'bg-rose-100 text-rose-700 border border-rose-200',
                            'high' => 'bg-red-100 text-red-700 border border-red-200',
                            'medium' => 'bg-amber-100 text-amber-700 border border-amber-200',
                            'low' => 'bg-sky-100 text-sky-700 border border-sky-200',
                            default => 'bg-violet-100 text-violet-700 border border-violet-200',
                        };
                    @endphp

                    <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900">
                                    {{ $issue['title'] ?? 'Untitled Issue' }}
                                </h4>

                                <p class="mt-2 text-sm text-slate-500">
                                    Rule: <span class="font-semibold text-slate-700">{{ $issue['rule_code'] ?? '-' }}</span>
                                    <span class="mx-2 text-slate-300">|</span>
                                    Language: <span class="font-semibold text-slate-700">{{ $issue['language'] ?? '-' }}</span>
                                    <span class="mx-2 text-slate-300">|</span>
                                    Line: <span class="font-semibold text-slate-700">{{ $issue['line_start'] ?? '-' }}</span>
                                </p>
                            </div>

                            <div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $severityBadge }}">
                                    {{ $severity }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Description</p>
                                <p class="mt-2 text-sm leading-7 text-slate-700">
                                    {{ $issue['description'] ?? '-' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Recommendation</p>
                                <p class="mt-2 text-sm leading-7 text-slate-700">
                                    {{ $issue['recommendation'] ?? '-' }}
                                </p>
                            </div>
                        </div>

                        @if(!empty($issue['metadata']['relative_path']))
                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">File Path</p>
                                <p class="mt-2 break-all text-sm font-medium text-slate-700">
                                    {{ $issue['metadata']['relative_path'] }}
                                </p>
                            </div>
                        @endif

                        @if(!empty($issue['snippet']))
                            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950">
                                <div class="border-b border-slate-800 px-4 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                    Code Snippet
                                </div>
                                <pre class="overflow-x-auto px-4 py-4 text-sm leading-7 text-slate-200"><code>{{ $issue['snippet'] }}</code></pre>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[24px] border border-slate-200 bg-slate-50/70 p-10 text-center text-slate-400">
                        No issues found.
                    </div>
                @endforelse
            </div>
        </section>
    </section>
@endsection