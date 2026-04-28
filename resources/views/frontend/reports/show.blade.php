@extends('frontend.layouts.frontend-layout')

@php
    $pageTitle = ($report->title ?? __('frontend.executive_report.title_fallback')) . ' | VertexGrad Analyzer';

    $isArabic = app()->getLocale() === 'ar';
    $dir = $isArabic ? 'rtl' : 'ltr';
    $textAlign = $isArabic ? 'right' : 'left';

    $rawReport = $report->report
        ?? $report->data
        ?? $report->payload
        ?? $report->analysis_report
        ?? $report->report_json
        ?? [];

    if (is_string($rawReport)) {
        $decoded = json_decode($rawReport, true);
        $rawReport = is_array($decoded) ? $decoded : [];
    }

    $reportData = is_array($rawReport) ? $rawReport : [];

    $analysisRun = $report->analysisRun ?? $report->analysis_run ?? $report->run ?? null;

    if ($analysisRun) {
        $analysisRun->loadMissing(['issues.projectFile', 'score', 'project', 'aiInsight']);
    }

    $project = $report->project ?? $analysisRun?->project;
    $aiInsight = $analysisRun?->aiInsight ?? null;

    $runSummary = is_array($analysisRun?->summary) ? $analysisRun->summary : [];
    $projectSummary = is_array($project?->summary) ? $project->summary : [];
    $summary = data_get($reportData, 'summary', []);

    $findings = data_get(
        $reportData,
        'findings',
        data_get($reportData, 'issues', data_get($reportData, 'detected_issues', []))
    );

    if (empty($findings) && $analysisRun?->issues) {
        $findings = $analysisRun->issues->map(function ($issue) {
            return [
                'title' => $issue->title,
                'description' => $issue->description,
                'recommendation' => $issue->recommendation,
                'severity' => $issue->severity,
                'category' => $issue->category,
                'language' => $issue->language,
                'rule' => $issue->rule_code,
                'line' => $issue->line_start,
                'file' => optional($issue->projectFile)->relative_path
                    ?? optional($issue->projectFile)->file_name
                    ?? __('frontend.executive_report.unknown_file'),
            ];
        })->toArray();
    }

    $severitySummary = data_get($reportData, 'severity_summary', data_get($reportData, 'severity', []));
    $scoreBreakdown = data_get($reportData, 'score_breakdown', data_get($reportData, 'scores', []));

    $projectName = $project?->name
        ?? data_get($summary, 'project_name')
        ?? $report->title
        ?? __('frontend.executive_report.project_report');

    $overallScore = data_get(
        $summary,
        'overall_score',
        data_get(
            $reportData,
            'overall_score',
            data_get(
                $runSummary,
                'overall_score',
                data_get($projectSummary, 'overall_score', $analysisRun?->score?->overall_score ?? '—')
            )
        )
    );

    $grade = data_get(
        $summary,
        'grade',
        data_get(
            $reportData,
            'grade',
            data_get(
                $runSummary,
                'grade',
                data_get($projectSummary, 'grade', $analysisRun?->score?->grade ?? '—')
            )
        )
    );

    $issuesFound = (int) data_get(
        $summary,
        'issues_found',
        data_get(
            $reportData,
            'issues_found',
            data_get(
                $runSummary,
                'total_issues',
                data_get($projectSummary, 'issues_found', count($findings))
            )
        )
    );

    $primaryLanguage = data_get(
        $summary,
        'primary_language',
        data_get(
            $reportData,
            'primary_language',
            data_get(
                $runSummary,
                'primary_language',
                data_get($projectSummary, 'primary_language', $project?->primary_language ?? __('frontend.executive_report.not_specified'))
            )
        )
    );

    if (blank($primaryLanguage) || $primaryLanguage === 'Not specified' || $primaryLanguage === 'غير محددة') {
        $primaryLanguage = data_get($runSummary, 'languages.0', __('frontend.executive_report.not_specified'));
    }

    $analysisStatus = strtolower((string) (
        data_get(
            $summary,
            'status',
            $analysisRun?->status
                ?? $report->status
                ?? 'completed'
        )
    ));

    $statusLabel = match ($analysisStatus) {
        'completed' => __('frontend.executive_report.statuses.completed'),
        'failed' => __('frontend.executive_report.statuses.failed'),
        'running' => __('frontend.executive_report.statuses.running'),
        'processing' => __('frontend.executive_report.statuses.processing'),
        'queued' => __('frontend.executive_report.statuses.queued'),
        default => ucfirst($analysisStatus),
    };

    $filesProcessed = (int) data_get(
        $summary,
        'files_processed',
        data_get($projectSummary, 'analyzed_files_count', $analysisRun?->processed_files ?? 0)
    );

    $filesDiscovered = (int) data_get(
        $summary,
        'files_discovered',
        data_get($projectSummary, 'analysis_total_files', $analysisRun?->total_files ?? 0)
    );

    $analyzedAt = optional($analysisRun?->finished_at)->format('Y-m-d h:i A')
        ?? optional($report->created_at)->format('Y-m-d h:i A')
        ?? '—';

    $securityScore = (int) data_get(
        $scoreBreakdown,
        'security',
        $analysisRun?->score?->security_score
            ?? data_get($reportData, 'security_score', 0)
    );

    $qualityScore = (int) data_get(
        $scoreBreakdown,
        'quality',
        $analysisRun?->score?->quality_score
            ?? data_get($reportData, 'quality_score', 0)
    );

    $performanceScore = (int) data_get(
        $scoreBreakdown,
        'performance',
        $analysisRun?->score?->performance_score
            ?? data_get($reportData, 'performance_score', 0)
    );

    $maintainabilityScore = (int) data_get(
        $scoreBreakdown,
        'maintainability',
        data_get(
            $reportData,
            'maintainability_score',
            $analysisRun?->score?->maintainability_score
                ?? $analysisRun?->score?->structure_score
                ?? 0
        )
    );

    if (empty($severitySummary)) {
        $severitySummary = collect($findings)
            ->countBy(function ($item) {
                return strtolower((string) (data_get($item, 'severity') ?? data_get($item, 'level') ?? 'medium'));
            })
            ->toArray();
    }

    $criticalCount = (int) data_get($severitySummary, 'critical', 0);
    $highCount = (int) data_get($severitySummary, 'high', 0);
    $mediumCount = (int) data_get($severitySummary, 'medium', 0);
    $lowCount = (int) data_get($severitySummary, 'low', 0);
    $infoCount = (int) data_get($severitySummary, 'info', 0);

    $normalizedFindings = collect($findings)->map(function ($item, $index) {
        if (is_object($item)) {
            $item = (array) $item;
        }

        $severity = strtolower((string) (
            data_get($item, 'severity')
            ?? data_get($item, 'level')
            ?? 'medium'
        ));

        $title = data_get($item, 'title')
            ?? data_get($item, 'message')
            ?? data_get($item, 'name')
            ?? data_get($item, 'rule')
            ?? __('frontend.executive_report.untitled_finding');

        $file = data_get($item, 'file')
            ?? data_get($item, 'file_path')
            ?? data_get($item, 'path')
            ?? __('frontend.executive_report.unknown_file');

        $line = data_get($item, 'line')
            ?? data_get($item, 'line_number')
            ?? '—';

        $description = data_get($item, 'description')
            ?? data_get($item, 'details')
            ?? data_get($item, 'message')
            ?? __('frontend.executive_report.no_description');

        $recommendation = data_get($item, 'recommendation')
            ?? data_get($item, 'fix')
            ?? data_get($item, 'solution')
            ?? __('frontend.executive_report.generic_fix');

        $rule = data_get($item, 'rule')
            ?? data_get($item, 'rule_name')
            ?? 'General';

        return [
            'id' => $index + 1,
            'severity' => in_array($severity, ['critical', 'high', 'medium', 'low', 'info']) ? $severity : 'medium',
            'title' => $title,
            'file' => $file,
            'line' => $line,
            'description' => $description,
            'recommendation' => $recommendation,
            'rule' => $rule,
        ];
    })->values();

    if ($issuesFound <= 0 && $normalizedFindings->count() > 0) {
        $issuesFound = $normalizedFindings->count();
    }

    $topFindings = $normalizedFindings
        ->sortBy(function ($item) {
            return match ($item['severity']) {
                'critical' => 1,
                'high' => 2,
                'medium' => 3,
                'low' => 4,
                'info' => 5,
                default => 6,
            };
        })
        ->take(3)
        ->values();

    $severityTotal = max(1, $criticalCount + $highCount + $mediumCount + $lowCount + $infoCount);

    $readinessScore = (int) ($aiInsight->readiness_score ?? 0);
    $riskLevel = strtolower((string) ($aiInsight->risk_level ?? 'medium'));
    $confidenceLevel = strtolower((string) ($aiInsight->confidence_level ?? 'medium'));
    $aiSummary = $aiInsight->summary ?? __('frontend.executive_report.no_ai_summary');
    $aiDecision = $aiInsight->decision_support ?? __('frontend.executive_report.default_decision');
    $aiRecommendations = collect($aiInsight->recommendations ?? [])->filter()->values();

    if ($readinessScore <= 0) {
        $readinessScore = match (true) {
            $criticalCount > 0 => 20,
            $highCount > 0 => 45,
            is_numeric($overallScore) => (int) round((float) $overallScore),
            default => 60,
        };
    }

    $riskToneClass = match ($riskLevel) {
        'critical' => 'tone-critical',
        'high', 'danger' => 'tone-danger',
        'medium', 'warning' => 'tone-warning',
        'low', 'success' => 'tone-success',
        default => 'tone-info',
    };

    $confidenceToneClass = match ($confidenceLevel) {
        'high' => 'tone-success',
        'medium' => 'tone-warning',
        'low' => 'tone-danger',
        default => 'tone-info',
    };

    $readinessLabel = match (true) {
        $readinessScore >= 80 => __('frontend.executive_report.ready'),
        $readinessScore >= 60 => __('frontend.executive_report.moderate_readiness'),
        default => __('frontend.executive_report.needs_fixes'),
    };

    $readinessGradient = match (true) {
        $readinessScore >= 80 => 'conic-gradient(from 220deg, #16c784 0deg, #16c784 calc(var(--value)*1deg), rgba(255,255,255,.08) 0deg)',
        $readinessScore >= 60 => 'conic-gradient(from 220deg, #f6b73c 0deg, #f6b73c calc(var(--value)*1deg), rgba(255,255,255,.08) 0deg)',
        default => 'conic-gradient(from 220deg, #ef5350 0deg, #ef5350 calc(var(--value)*1deg), rgba(255,255,255,.08) 0deg)',
    };

    $topAction = $aiRecommendations->first();
    $topActionTitle = is_array($topAction) ? ($topAction['title'] ?? __('frontend.executive_report.main_recommendation')) : __('frontend.executive_report.main_recommendation');
    $topActionText = is_array($topAction)
        ? ($topAction['action'] ?? $topAction['text'] ?? $topAction['description'] ?? __('frontend.executive_report.default_action'))
        : __('frontend.executive_report.default_action');

    $backUrl = url()->previous();
    $currentUrl = url()->current();
    if ($backUrl === $currentUrl) {
        $backUrl = url('/');
    }

    $explainIssueStudent = function ($severity) {
        $severity = strtolower((string) $severity);

        return match ($severity) {
            'critical' => __('frontend.executive_report.student_explanations.critical'),
            'high' => __('frontend.executive_report.student_explanations.high'),
            'medium' => __('frontend.executive_report.student_explanations.medium'),
            'low' => __('frontend.executive_report.student_explanations.low'),
            default => __('frontend.executive_report.student_explanations.default'),
        };
    };
@endphp

@section('title', $pageTitle)

@section('content')
<style>
    .report-page {
        position: relative;
        z-index: 2;
        padding: 28px 0 64px;
        direction: {{ $dir }};
        text-align: {{ $textAlign }};
    }

    .report-stack {
        display: grid;
        gap: 22px;
    }

    .hero-section {
        position: relative;
        overflow: hidden;
        border-radius: 32px;
        padding: 34px;
        background:
            radial-gradient(circle at 15% 20%, rgba(56, 189, 248, 0.16), transparent 24%),
            radial-gradient(circle at 85% 18%, rgba(99, 102, 241, 0.18), transparent 28%),
            linear-gradient(135deg, rgba(8, 15, 28, 0.95) 0%, rgba(16, 24, 40, 0.94) 48%, rgba(20, 34, 58, 0.96) 100%);
        border: 1px solid var(--border);
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    html[data-theme="light"] .hero-section {
        background:
            radial-gradient(circle at 15% 20%, rgba(56, 189, 248, 0.10), transparent 24%),
            radial-gradient(circle at 85% 18%, rgba(99, 102, 241, 0.10), transparent 28%),
            linear-gradient(135deg, rgba(255,255,255,0.85) 0%, rgba(248,250,252,0.92) 100%);
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.10);
    }

    .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 34px 34px;
        opacity: .35;
        pointer-events: none;
    }

    .hero-inner {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 18px;
    }

    .hero-badges,
    .hero-actions,
    .hero-meta {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .hero-chip,
    .tone-pill,
    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .03em;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .hero-chip {
        background: rgba(56, 189, 248, 0.12);
        border-color: rgba(56, 189, 248, 0.22);
        color: #e0f7ff;
    }

    html[data-theme="light"] .hero-chip {
        color: var(--text);
    }

    .status-success, .tone-success {
        background: rgba(22, 199, 132, 0.10);
        color: var(--success);
        border-color: rgba(22, 199, 132, 0.18);
    }

    .status-info, .tone-info {
        background: rgba(59, 130, 246, 0.10);
        color: var(--primary);
        border-color: rgba(59, 130, 246, 0.18);
    }

    .status-warning, .tone-warning {
        background: rgba(246, 183, 60, 0.10);
        color: var(--warning);
        border-color: rgba(246, 183, 60, 0.18);
    }

    .status-danger, .tone-danger, .tone-critical {
        background: rgba(239, 83, 80, 0.10);
        color: var(--danger);
        border-color: rgba(239, 83, 80, 0.18);
    }

    .hero-title {
        margin: 0;
        font-size: clamp(2.2rem, 4vw, 4.2rem);
        line-height: 1.05;
        font-weight: 900;
        color: #fff;
        letter-spacing: -0.03em;
    }

    html[data-theme="light"] .hero-title {
        color: var(--text);
    }

    .hero-subtitle {
        margin: 0;
        max-width: 900px;
        color: rgba(255,255,255,0.82);
        line-height: 1.9;
        font-size: 1rem;
    }

    html[data-theme="light"] .hero-subtitle {
        color: var(--text-soft);
    }

    .hero-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .hero-kpi {
        padding: 18px 16px;
        border-radius: 22px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.10);
    }

    html[data-theme="light"] .hero-kpi {
        background: rgba(255,255,255,0.65);
        border-color: var(--border);
    }

    .hero-kpi span {
        display: block;
        margin-bottom: 8px;
        font-size: 11px;
        letter-spacing: .07em;
        color: rgba(255,255,255,0.68);
        font-weight: 800;
    }

    html[data-theme="light"] .hero-kpi span {
        color: var(--text-muted);
    }

    .hero-kpi strong {
        display: block;
        color: #fff;
        font-size: 1.75rem;
        font-weight: 900;
    }

    html[data-theme="light"] .hero-kpi strong {
        color: var(--text);
    }

    .report-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 50px;
        padding: 0 18px;
        border-radius: 16px;
        border: 1px solid var(--border);
        text-decoration: none;
        font-weight: 900;
        transition: .22s ease;
        cursor: pointer;
    }

    .report-btn-primary {
        color: #fff;
        border: none;
        background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
        box-shadow: 0 16px 36px rgba(37, 99, 235, 0.26);
    }

    .report-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 22px 46px rgba(37, 99, 235, 0.30);
    }

    .report-btn-soft {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }

    html[data-theme="light"] .report-btn-soft {
        background: rgba(255,255,255,0.72);
        color: var(--text);
    }

    .report-btn-soft:hover {
        transform: translateY(-1px);
        border-color: var(--border-strong);
    }

    .exec-grid,
    .two-col-grid,
    .three-col-grid {
        display: grid;
        gap: 18px;
    }

    .exec-grid {
        grid-template-columns: 340px minmax(0, 1fr);
    }

    .two-col-grid {
        grid-template-columns: 1fr 1fr;
    }

    .three-col-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .report-card {
        background: linear-gradient(180deg, var(--surface-strong), var(--glass-bg));
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 24px;
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .card-head h2 {
        margin: 0 0 8px;
        color: var(--text);
        font-size: 1.35rem;
        font-weight: 900;
        line-height: 1.08;
    }

    .card-head p {
        margin: 0;
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 0.92rem;
    }

    .readiness-card {
        display: grid;
        place-items: center;
        min-height: 100%;
    }

    .readiness-ring {
        --value: {{ max(0, min(100, $readinessScore)) * 3.6 }};
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: {!! json_encode($readinessGradient) !!};
        position: relative;
        display: grid;
        place-items: center;
        box-shadow: inset 0 0 36px rgba(255,255,255,.05), 0 18px 44px rgba(0,0,0,.12);
    }

    .readiness-ring::before {
        content: "";
        position: absolute;
        inset: 22px;
        border-radius: 50%;
        background: linear-gradient(180deg, rgba(9,15,28,.96), rgba(17,24,39,.96));
        border: 1px solid rgba(255,255,255,.08);
    }

    html[data-theme="light"] .readiness-ring::before {
        background: linear-gradient(180deg, rgba(255,255,255,.97), rgba(248,250,252,.99));
        border-color: var(--border);
    }

    .readiness-content {
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .readiness-content span {
        display: block;
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 800;
        color: var(--text-muted);
        letter-spacing: .08em;
    }

    .readiness-content strong {
        display: block;
        font-size: 3rem;
        line-height: 1;
        color: var(--text);
        font-weight: 900;
    }

    .readiness-content small {
        display: block;
        margin-top: 8px;
        color: var(--text-soft);
        font-weight: 700;
        font-size: .92rem;
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .metric-box {
        padding: 18px;
        border-radius: 22px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.05);
    }

    html[data-theme="light"] .metric-box {
        background: rgba(255,255,255,0.70);
    }

    .metric-box span {
        display: block;
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 800;
        color: var(--text-muted);
        letter-spacing: .08em;
    }

    .metric-box strong {
        display: block;
        margin-bottom: 8px;
        font-size: 1.8rem;
        line-height: 1;
        color: var(--text);
        font-weight: 900;
    }

    .metric-box p {
        margin: 0;
        color: var(--text-soft);
        line-height: 1.8;
        font-size: .88rem;
        font-weight: 700;
    }

    .mini-progress {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(148,163,184,.18);
        margin-top: 12px;
    }

    .mini-progress > span {
        display: block;
        height: 100%;
        width: 0;
        border-radius: 999px;
        background: linear-gradient(90deg, #2563eb, #06b6d4);
        transition: width .9s cubic-bezier(.22,1,.36,1);
    }

    .donut-row {
        display: grid;
        grid-template-columns: 180px minmax(0, 1fr);
        gap: 18px;
        align-items: center;
    }

    .donut {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background:
            conic-gradient(
                #ef4444 0deg {{ round(($criticalCount / $severityTotal) * 360, 2) }}deg,
                #f97316 {{ round(($criticalCount / $severityTotal) * 360, 2) }}deg {{ round((($criticalCount + $highCount) / $severityTotal) * 360, 2) }}deg,
                #f59e0b {{ round((($criticalCount + $highCount) / $severityTotal) * 360, 2) }}deg {{ round((($criticalCount + $highCount + $mediumCount) / $severityTotal) * 360, 2) }}deg,
                #10b981 {{ round((($criticalCount + $highCount + $mediumCount) / $severityTotal) * 360, 2) }}deg {{ round((($criticalCount + $highCount + $mediumCount + $lowCount) / $severityTotal) * 360, 2) }}deg,
                #3b82f6 {{ round((($criticalCount + $highCount + $mediumCount + $lowCount) / $severityTotal) * 360, 2) }}deg 360deg
            );
        position: relative;
        margin-inline: auto;
    }

    .donut::before {
        content: "";
        position: absolute;
        inset: 22px;
        border-radius: 50%;
        background: var(--surface-strong);
        border: 1px solid var(--border);
    }

    .donut-center {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        text-align: center;
        z-index: 1;
    }

    .donut-center strong {
        display: block;
        font-size: 2rem;
        font-weight: 900;
        color: var(--text);
    }

    .donut-center span {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        color: var(--text-muted);
        letter-spacing: .08em;
        font-weight: 800;
    }

    .legend-list {
        display: grid;
        gap: 10px;
    }

    .legend-item {
        display: grid;
        grid-template-columns: 14px minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        color: var(--text);
        font-weight: 800;
    }

    .legend-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
    }

    .summary-panel,
    .action-panel,
    .finding-card {
        padding: 20px;
        border-radius: 22px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.05);
    }

    html[data-theme="light"] .summary-panel,
    html[data-theme="light"] .action-panel,
    html[data-theme="light"] .finding-card {
        background: rgba(255,255,255,0.70);
    }

    .summary-panel p,
    .action-panel p,
    .finding-desc,
    .finding-fix,
    .finding-student {
        margin: 0;
        color: var(--text-soft);
        line-height: 1.9;
        font-size: 0.94rem;
    }

    .action-panel strong,
    .finding-title {
        display: block;
        margin-bottom: 10px;
        color: var(--text);
        font-size: 1rem;
        font-weight: 900;
    }

    .finding-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .mini-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--border);
        color: var(--text-soft);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
    }

    .finding-section-label {
        display: block;
        margin: 12px 0 6px;
        color: var(--text-muted);
        font-size: .82rem;
        font-weight: 900;
    }

    .finding-student {
        margin-top: 8px;
        color: #fbbf24;
        font-weight: 800;
    }

    .fade-up {
        opacity: 0;
        transform: translateY(16px);
        animation: reportFadeUp .7s cubic-bezier(.22,1,.36,1) forwards;
    }

    .fade-up.delay-1 { animation-delay: .06s; }
    .fade-up.delay-2 { animation-delay: .12s; }
    .fade-up.delay-3 { animation-delay: .18s; }

    @keyframes reportFadeUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 1280px) {
        .exec-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1180px) {
        .hero-kpis,
        .metrics-grid,
        .three-col-grid {
            grid-template-columns: 1fr 1fr;
        }

        .two-col-grid {
            grid-template-columns: 1fr;
        }

        .donut-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 820px) {
        .hero-kpis,
        .metrics-grid,
        .three-col-grid {
            grid-template-columns: 1fr;
        }

        .hero-section {
            padding: 24px;
        }

        .hero-title {
            font-size: 2rem;
        }

        .readiness-ring {
            width: 210px;
            height: 210px;
        }
    }
</style>

<div class="report-page">
    <div class="container report-stack">

        <section class="hero-section fade-up">
            <div class="hero-inner">
                <div class="hero-badges">
                    <span class="hero-chip">{{ __('frontend.executive_report.executive_report') }}</span>

                    <span class="status-pill {{
                        $analysisStatus === 'completed' ? 'status-success' :
                        ($analysisStatus === 'failed' ? 'status-danger' :
                        (($analysisStatus === 'running' || $analysisStatus === 'processing') ? 'status-info' : 'status-warning'))
                    }}">
                        {{ $statusLabel }}
                    </span>

                    <span class="tone-pill {{ $riskToneClass }}">
                        {{ __('frontend.executive_report.risk') }}: {{ strtoupper($riskLevel) }}
                    </span>

                    <span class="tone-pill {{ $confidenceToneClass }}">
                        {{ __('frontend.executive_report.confidence') }}: {{ strtoupper($confidenceLevel) }}
                    </span>
                </div>

                <h1 class="hero-title">{{ $projectName }}</h1>

                <p class="hero-subtitle">
                    {{ __('frontend.executive_report.executive_subtitle') }}
                </p>

                <div class="hero-kpis">
                    <div class="hero-kpi">
                        <span>{{ __('frontend.executive_report.overall_score') }}</span>
                        <strong>{{ $overallScore }}</strong>
                    </div>

                    <div class="hero-kpi">
                        <span>{{ __('frontend.executive_report.grade') }}</span>
                        <strong>{{ $grade }}</strong>
                    </div>

                    <div class="hero-kpi">
                        <span>{{ __('frontend.executive_report.issues_found') }}</span>
                        <strong>{{ $issuesFound }}</strong>
                    </div>

                    <div class="hero-kpi">
                        <span>{{ __('frontend.executive_report.primary_language') }}</span>
                        <strong>{{ $primaryLanguage }}</strong>
                    </div>
                </div>

                <div class="hero-meta">
                    <span class="hero-chip">{{ __('frontend.executive_report.analyzed_at') }}: {{ $analyzedAt }}</span>
                    <span class="hero-chip">{{ __('frontend.executive_report.files_processed') }}: {{ $filesProcessed }}</span>
                    <span class="hero-chip">{{ __('frontend.executive_report.files_discovered') }}: {{ $filesDiscovered }}</span>
                </div>

                <div class="hero-actions">
                    <a href="{{ $backUrl }}" class="report-btn report-btn-soft">
                        {{ __('frontend.executive_report.back') }}
                    </a>

                    <a href="{{ url('/reports/' . $report->id . '/details') }}" class="report-btn report-btn-primary">
                        {{ __('frontend.executive_report.view_details') }}
                    </a>

                    @if(method_exists($report, 'getAttribute') && $report->getAttribute('pdf_path'))
                        <a href="{{ asset($report->pdf_path) }}" class="report-btn report-btn-soft">
                            {{ __('frontend.executive_report.download_pdf') }}
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="exec-grid fade-up delay-1">
            <div class="report-card readiness-card">
                <div class="readiness-ring">
                    <div class="readiness-content">
                        <span>{{ __('frontend.executive_report.readiness') }}</span>
                        <strong>{{ $readinessScore }}%</strong>
                        <small>{{ $readinessLabel }}</small>
                    </div>
                </div>
            </div>

            <div class="report-card">
                <div class="card-head">
                    <div>
                        <h2>{{ __('frontend.executive_report.executive_metrics') }}</h2>
                        <p>{{ __('frontend.executive_report.executive_metrics_desc') }}</p>
                    </div>
                </div>

                <div class="metrics-grid">
                    <div class="metric-box">
                        <span>{{ __('frontend.executive_report.risk') }}</span>
                        <strong>{{ strtoupper($riskLevel) }}</strong>
                        <p>{{ $aiDecision }}</p>
                    </div>

                    <div class="metric-box">
                        <span>{{ __('frontend.executive_report.confidence') }}</span>
                        <strong>{{ strtoupper($confidenceLevel) }}</strong>
                        <p>{{ __('frontend.executive_report.confidence_desc') }}</p>
                    </div>

                    <div class="metric-box">
                        <span>{{ __('frontend.executive_report.decision') }}</span>
                        <strong>{{ $statusLabel }}</strong>
                        <p>{{ $aiSummary }}</p>
                    </div>
                </div>

                <div class="metrics-grid" style="margin-top:14px;">
                    <div class="metric-box">
                        <span>{{ __('frontend.executive_report.security_findings') }}</span>
                        <strong>{{ $highCount + $criticalCount }}</strong>
                        <div class="mini-progress">
                            <span data-width="{{ max(0, min(100, round((($highCount + $criticalCount) / max(1, $issuesFound)) * 100))) }}"></span>
                        </div>
                    </div>

                    <div class="metric-box">
                        <span>{{ __('frontend.executive_report.files_processed') }}</span>
                        <strong>{{ $filesProcessed }}</strong>
                        <div class="mini-progress">
                            <span data-width="{{ max(0, min(100, $filesProcessed > 0 && $filesDiscovered > 0 ? round(($filesProcessed / max(1, $filesDiscovered)) * 100) : 0)) }}"></span>
                        </div>
                    </div>

                    <div class="metric-box">
                        <span>{{ __('frontend.executive_report.high_severity') }}</span>
                        <strong>{{ $highCount }}</strong>
                        <div class="mini-progress">
                            <span data-width="{{ max(0, min(100, round(($highCount / $severityTotal) * 100))) }}"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="two-col-grid fade-up delay-2">
            <div class="report-card">
                <div class="card-head">
                    <div>
                        <h2>{{ __('frontend.executive_report.severity_distribution') }}</h2>
                        <p>{{ __('frontend.executive_report.severity_distribution_desc') }}</p>
                    </div>
                </div>

                <div class="donut-row">
                    <div class="donut">
                        <div class="donut-center">
                            <strong>{{ $issuesFound }}</strong>
                            <span>{{ __('frontend.executive_report.total_findings') }}</span>
                        </div>
                    </div>

                    <div class="legend-list">
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#ef4444;"></span>
                            <span>{{ __('frontend.executive_report.severity_labels.critical') }}</span>
                            <strong>{{ $criticalCount }}</strong>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#f97316;"></span>
                            <span>{{ __('frontend.executive_report.severity_labels.high') }}</span>
                            <strong>{{ $highCount }}</strong>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#f59e0b;"></span>
                            <span>{{ __('frontend.executive_report.severity_labels.medium') }}</span>
                            <strong>{{ $mediumCount }}</strong>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#10b981;"></span>
                            <span>{{ __('frontend.executive_report.severity_labels.low') }}</span>
                            <strong>{{ $lowCount }}</strong>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:#3b82f6;"></span>
                            <span>{{ __('frontend.executive_report.severity_labels.info') }}</span>
                            <strong>{{ $infoCount }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-card">
                <div class="card-head">
                    <div>
                        <h2>{{ __('frontend.executive_report.ai_summary') }}</h2>
                        <p>{{ __('frontend.executive_report.ai_summary_desc') }}</p>
                    </div>
                </div>

                <div class="summary-panel">
                    <p>{{ $aiSummary }}</p>
                </div>

                <div class="action-panel" style="margin-top:14px;">
                    <strong>{{ $topActionTitle }}</strong>
                    <p>{{ $topActionText }}</p>
                </div>
            </div>
        </section>

        <section class="report-card fade-up delay-3">
            <div class="card-head">
                <div>
                    <h2>{{ __('frontend.executive_report.top_findings') }}</h2>
                    <p>{{ __('frontend.executive_report.top_findings_desc') }}</p>
                </div>
            </div>

            <div class="three-col-grid">
                @forelse($topFindings as $finding)
                    <div class="finding-card">
                        <strong class="finding-title">{{ $finding['title'] }}</strong>

                        <div class="finding-meta">
                            <span class="mini-pill">{{ ucfirst($finding['severity']) }}</span>
                            <span class="mini-pill">{{ $finding['rule'] }}</span>
                            <span class="mini-pill">{{ __('frontend.executive_report.line') }} {{ $finding['line'] }}</span>
                        </div>

                        <span class="finding-section-label">{{ __('frontend.executive_report.technical_description') }}</span>
                        <p class="finding-desc">{{ $finding['description'] }}</p>

                        <span class="finding-section-label">{{ __('frontend.executive_report.student_explanation') }}</span>
                        <p class="finding-student">{{ $explainIssueStudent($finding['severity']) }}</p>

                        <span class="finding-section-label">{{ __('frontend.executive_report.recommended_fix') }}</span>
                        <p class="finding-fix">{{ $finding['recommendation'] }}</p>
                    </div>
                @empty
                    <div class="finding-card">
                        <strong class="finding-title">{{ __('frontend.executive_report.no_major_findings') }}</strong>
                        <p class="finding-desc">{{ __('frontend.executive_report.no_major_findings_desc') }}</p>
                    </div>
                @endforelse
            </div>
        </section>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusText = @json($analysisStatus);

        if (['running', 'processing', 'queued'].includes(String(statusText).toLowerCase())) {
            setInterval(() => {
                fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.querySelector('.report-page');
                    const currentContent = document.querySelector('.report-page');

                    if (newContent && currentContent) {
                        currentContent.innerHTML = newContent.innerHTML;
                    }
                })
                .catch(() => {});
            }, 5000);
        }

        const bars = document.querySelectorAll('.mini-progress > span');

        requestAnimationFrame(() => {
            bars.forEach((bar) => {
                const width = Math.max(0, Math.min(100, parseInt(bar.dataset.width || '0', 10)));
                bar.style.width = `${width}%`;
            });
        });
    });
</script>
@endsection