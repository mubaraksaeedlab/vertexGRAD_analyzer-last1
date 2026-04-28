@extends('frontend.layouts.frontend-layout')

@php
    $pageTitle = ($report->title ?? __('frontend.project_details.page_title_fallback')) . ' | VertexGrad Analyzer';

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
                'line_end' => $issue->line_end,
                'file' => optional($issue->projectFile)->relative_path
                    ?? optional($issue->projectFile)->file_name
                    ?? __('frontend.project_details.labels.unknown_file'),
                'snippet' => $issue->snippet,
                'confidence' => $issue->confidence,
            ];
        })->toArray();
    }

    $projectName = $project?->name
        ?? data_get($summary, 'project_name')
        ?? $report->title
        ?? __('frontend.project_details.labels.project_report');

    $primaryLanguage = data_get(
        $summary,
        'primary_language',
        data_get(
            $reportData,
            'primary_language',
            data_get(
                $runSummary,
                'primary_language',
                data_get($projectSummary, 'primary_language', $project?->primary_language ?? __('frontend.project_details.labels.not_specified'))
            )
        )
    );

    if (blank($primaryLanguage) || $primaryLanguage === 'Not specified' || $primaryLanguage === 'غير محددة') {
        $primaryLanguage = data_get($runSummary, 'languages.0', __('frontend.project_details.labels.not_specified'));
    }

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
            ?? __('frontend.project_details.states.untitled_finding');

        $file = data_get($item, 'file')
            ?? data_get($item, 'file_path')
            ?? data_get($item, 'path')
            ?? __('frontend.project_details.labels.unknown_file');

        $line = data_get($item, 'line')
            ?? data_get($item, 'line_number')
            ?? '—';

        $lineEnd = data_get($item, 'line_end') ?? null;

        $description = data_get($item, 'description')
            ?? data_get($item, 'details')
            ?? data_get($item, 'message')
            ?? __('frontend.project_details.states.no_description');

        $recommendation = data_get($item, 'recommendation')
            ?? data_get($item, 'fix')
            ?? data_get($item, 'solution')
            ?? __('frontend.project_details.states.generic_fix');

        $rule = data_get($item, 'rule')
            ?? data_get($item, 'rule_name')
            ?? 'General';

        $snippet = data_get($item, 'snippet');
        $confidence = data_get($item, 'confidence');

        return [
            'id' => $index + 1,
            'severity' => in_array($severity, ['critical', 'high', 'medium', 'low', 'info']) ? $severity : 'medium',
            'title' => $title,
            'file' => $file,
            'line' => $line,
            'line_end' => $lineEnd,
            'description' => $description,
            'recommendation' => $recommendation,
            'rule' => $rule,
            'snippet' => $snippet,
            'confidence' => $confidence,
        ];
    })->values();

    $issuesFound = $normalizedFindings->count();
    $affectedFiles = $normalizedFindings->pluck('file')->filter()->unique()->count();

    $criticalCount = $normalizedFindings->where('severity', 'critical')->count();
    $highCount = $normalizedFindings->where('severity', 'high')->count();
    $mediumCount = $normalizedFindings->where('severity', 'medium')->count();
    $lowCount = $normalizedFindings->where('severity', 'low')->count();
    $infoCount = $normalizedFindings->where('severity', 'info')->count();

    $riskLevel = strtolower((string) ($aiInsight->risk_level ?? ($criticalCount > 0 ? 'critical' : ($highCount > 0 ? 'high' : ($mediumCount > 0 ? 'medium' : 'low')))));

    $riskToneClass = match ($riskLevel) {
        'critical' => 'tone-critical',
        'high', 'danger' => 'tone-danger',
        'medium', 'warning' => 'tone-warning',
        'low', 'success' => 'tone-success',
        default => 'tone-info',
    };

    $backUrl = url()->previous();
    $currentUrl = url()->current();
    if ($backUrl === $currentUrl) {
        $backUrl = url('/reports/' . $report->id);
    }

    $explainIssueStudent = function ($severity) {
        $severity = strtolower((string) $severity);

        return match ($severity) {
            'critical' => __('frontend.project_details.student_explanations.critical'),
            'high' => __('frontend.project_details.student_explanations.high'),
            'medium' => __('frontend.project_details.student_explanations.medium'),
            'low' => __('frontend.project_details.student_explanations.low'),
            default => __('frontend.project_details.student_explanations.default'),
        };
    };
@endphp

@section('title', $pageTitle)

@section('content')
<style>
    .details-page {
        position: relative;
        z-index: 2;
        padding: 28px 0 64px;
        direction: {{ $dir }};
        text-align: {{ $textAlign }};
    }

    .details-stack {
        display: grid;
        gap: 22px;
    }

    .details-hero {
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

    html[data-theme="light"] .details-hero {
        background:
            radial-gradient(circle at 15% 20%, rgba(56, 189, 248, 0.10), transparent 24%),
            radial-gradient(circle at 85% 18%, rgba(99, 102, 241, 0.10), transparent 28%),
            linear-gradient(135deg, rgba(255,255,255,0.85) 0%, rgba(248,250,252,0.92) 100%);
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.10);
    }

    .details-hero::before {
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

    .details-hero-inner {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 18px;
    }

    .hero-top,
    .hero-actions,
    .summary-cards,
    .toolbar {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .hero-chip,
    .pill,
    .severity-pill {
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

    .tone-success {
        background: rgba(22, 199, 132, 0.10);
        color: var(--success);
        border-color: rgba(22, 199, 132, 0.18);
    }

    .tone-info {
        background: rgba(59, 130, 246, 0.10);
        color: var(--primary);
        border-color: rgba(59, 130, 246, 0.18);
    }

    .tone-warning {
        background: rgba(246, 183, 60, 0.10);
        color: var(--warning);
        border-color: rgba(246, 183, 60, 0.18);
    }

    .tone-danger,
    .tone-critical {
        background: rgba(239, 83, 80, 0.10);
        color: var(--danger);
        border-color: rgba(239, 83, 80, 0.18);
    }

    .details-title {
        margin: 0;
        font-size: clamp(2rem, 4vw, 4rem);
        line-height: 1.06;
        font-weight: 900;
        color: #fff;
        letter-spacing: -0.03em;
    }

    html[data-theme="light"] .details-title {
        color: var(--text);
    }

    .details-subtitle {
        margin: 0;
        max-width: 920px;
        color: rgba(255,255,255,0.82);
        line-height: 1.9;
        font-size: 1rem;
    }

    html[data-theme="light"] .details-subtitle {
        color: var(--text-soft);
    }

    .details-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 48px;
        padding: 0 18px;
        border-radius: 16px;
        border: 1px solid var(--border);
        text-decoration: none;
        font-weight: 900;
        transition: .22s ease;
    }

    .details-btn-primary {
        color: #fff;
        border: none;
        background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
        box-shadow: 0 16px 36px rgba(37, 99, 235, 0.26);
    }

    .details-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 22px 46px rgba(37, 99, 235, 0.30);
    }

    .details-btn-soft {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }

    html[data-theme="light"] .details-btn-soft {
        background: rgba(255,255,255,0.72);
        color: var(--text);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .summary-card {
        padding: 18px 16px;
        border-radius: 22px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.10);
    }

    html[data-theme="light"] .summary-card {
        background: rgba(255,255,255,0.70);
        border-color: var(--border);
    }

    .summary-card span {
        display: block;
        margin-bottom: 8px;
        font-size: 11px;
        letter-spacing: .07em;
        color: rgba(255,255,255,0.68);
        font-weight: 800;
    }

    html[data-theme="light"] .summary-card span {
        color: var(--text-muted);
    }

    .summary-card strong {
        display: block;
        color: #fff;
        font-size: 1.75rem;
        font-weight: 900;
    }

    html[data-theme="light"] .summary-card strong {
        color: var(--text);
    }

    .surface-card {
        background: linear-gradient(180deg, var(--surface-strong), var(--glass-bg));
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 24px;
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .toolbar {
        justify-content: space-between;
        gap: 16px;
    }

    .toolbar-left,
    .toolbar-right {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .search-input {
        min-width: 320px;
        min-height: 48px;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.06);
        color: var(--text);
        padding: 0 16px;
        outline: none;
        font-weight: 700;
    }

    html[data-theme="light"] .search-input {
        background: rgba(255,255,255,0.72);
    }

    .search-input:focus {
        border-color: rgba(37, 99, 235, 0.38);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    }

    .filter-btn {
        min-height: 42px;
        padding: 0 14px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.06);
        color: var(--text);
        font-weight: 900;
        cursor: pointer;
        transition: .2s ease;
    }

    html[data-theme="light"] .filter-btn {
        background: rgba(255,255,255,0.72);
    }

    .filter-btn.is-active {
        background: linear-gradient(135deg, rgba(37,99,235,0.14), rgba(6,182,212,0.16));
        border-color: rgba(37,99,235,0.26);
    }

    .details-list {
        display: grid;
        gap: 18px;
    }

    .issue-card {
        position: relative;
        overflow: hidden;
        padding: 22px;
        border-radius: 24px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.05);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    html[data-theme="light"] .issue-card {
        background: rgba(255,255,255,0.72);
    }

    .issue-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-sm);
        border-color: var(--border-strong);
    }

    .issue-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 14px;
    }

    .issue-title {
        margin: 0;
        color: var(--text);
        font-size: 1.12rem;
        font-weight: 900;
        line-height: 1.6;
    }

    .issue-meta {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .meta-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--border);
        color: var(--text-soft);
        font-size: 11px;
        font-weight: 800;
    }

    .section-label {
        display: block;
        margin: 14px 0 6px;
        color: var(--text-muted);
        font-size: .82rem;
        font-weight: 900;
    }

    .issue-text,
    .issue-student,
    .issue-fix {
        margin: 0;
        color: var(--text-soft);
        line-height: 1.9;
        font-size: .95rem;
    }

    .issue-student {
        color: #fbbf24;
        font-weight: 800;
    }

    .code-box {
        position: relative;
        margin-top: 10px;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid var(--border);
        background: #0b1220;
    }

    html[data-theme="light"] .code-box {
        background: #f8fafc;
    }

    .code-box-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-bottom: 1px solid var(--border);
        background: rgba(255,255,255,0.04);
    }

    .copy-btn {
        min-height: 34px;
        padding: 0 12px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.08);
        color: var(--text);
        font-weight: 800;
        cursor: pointer;
    }

    pre.code-pre {
        margin: 0;
        padding: 16px;
        overflow-x: auto;
        color: #e5eefc;
        font-family: Consolas, Monaco, monospace;
        font-size: .89rem;
        line-height: 1.8;
        white-space: pre-wrap;
        word-break: break-word;
    }

    html[data-theme="light"] pre.code-pre {
        color: #0f172a;
    }

    .empty-state {
        padding: 34px;
        border-radius: 24px;
        border: 1px dashed var(--border);
        text-align: center;
        color: var(--text-soft);
        font-weight: 700;
    }

    .ai-chat-card {
        display: grid;
        gap: 16px;
    }

    .ai-chat-title {
        margin: 0;
        color: var(--text);
        font-size: 1.2rem;
        font-weight: 900;
    }

    .ai-chat-subtitle {
        margin: 0;
        color: var(--text-soft);
        line-height: 1.9;
    }

    .ai-chat-form {
        display: grid;
        gap: 12px;
    }

    .ai-chat-textarea {
        min-height: 120px;
        width: 100%;
        border-radius: 18px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.06);
        color: var(--text);
        padding: 16px;
        outline: none;
        resize: vertical;
        font-weight: 700;
        line-height: 1.8;
    }

    html[data-theme="light"] .ai-chat-textarea {
        background: rgba(255,255,255,0.78);
    }

    .ai-chat-textarea:focus {
        border-color: rgba(37, 99, 235, 0.38);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    }

    .ai-chat-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .ai-chat-answer-box {
        border-radius: 22px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.05);
        padding: 18px;
        min-height: 120px;
    }

    html[data-theme="light"] .ai-chat-answer-box {
        background: rgba(255,255,255,0.72);
    }

    .ai-chat-answer-label {
        display: block;
        margin-bottom: 10px;
        color: var(--text-muted);
        font-size: .82rem;
        font-weight: 900;
    }

    .ai-chat-answer {
        margin: 0;
        white-space: pre-wrap;
        line-height: 1.95;
        color: var(--text);
        font-weight: 700;
    }

    .ai-chat-answer.is-empty {
        color: var(--text-soft);
        font-weight: 600;
    }

    .ai-chat-status {
        font-size: .9rem;
        font-weight: 800;
        color: var(--text-soft);
    }

    .ai-chat-status.is-error {
        color: var(--danger);
    }

    .fade-up {
        opacity: 0;
        transform: translateY(16px);
        animation: reportFadeUp .7s cubic-bezier(.22,1,.36,1) forwards;
    }

    .fade-up.delay-1 { animation-delay: .06s; }
    .fade-up.delay-2 { animation-delay: .12s; }
    .fade-up.delay-3 { animation-delay: .18s; }
    .fade-up.delay-4 { animation-delay: .24s; }

    @keyframes reportFadeUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 1180px) {
        .summary-grid {
            grid-template-columns: 1fr 1fr;
        }

        .toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .toolbar-left,
        .toolbar-right {
            width: 100%;
        }

        .search-input {
            min-width: 0;
            width: 100%;
        }
    }

    @media (max-width: 820px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }

        .details-hero {
            padding: 24px;
        }

        .details-title {
            font-size: 2rem;
        }

        .issue-top {
            flex-direction: column;
        }
    }
</style>

<div class="details-page">
    <div class="container details-stack">

        <section class="details-hero fade-up">
            <div class="details-hero-inner">
                <div class="hero-top">
                    <span class="hero-chip">{{ __('frontend.project_details.details_report') }}</span>
                    <span class="pill {{ $riskToneClass }}">{{ __('frontend.project_details.summary.risk_level') }}: {{ strtoupper($riskLevel) }}</span>
                </div>

                <h1 class="details-title">{{ $projectName }}</h1>

                <p class="details-subtitle">{{ __('frontend.project_details.details_subtitle') }}</p>

                <div class="summary-grid">
                    <div class="summary-card">
                        <span>{{ __('frontend.project_details.summary.issues_found') }}</span>
                        <strong>{{ $issuesFound }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>{{ __('frontend.project_details.summary.files_affected') }}</span>
                        <strong>{{ $affectedFiles }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>{{ __('frontend.project_details.summary.primary_language') }}</span>
                        <strong>{{ $primaryLanguage }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>{{ __('frontend.project_details.summary.risk_level') }}</span>
                        <strong>{{ strtoupper($riskLevel) }}</strong>
                    </div>
                </div>

                <div class="hero-actions">
                    <a href="{{ $backUrl }}" class="details-btn details-btn-soft">
                        {{ __('frontend.project_details.back') }}
                    </a>

                    <a href="{{ url('/reports/' . $report->id) }}" class="details-btn details-btn-primary">
                        {{ __('frontend.project_details.back_to_summary') }}
                    </a>

                    <a href="{{ route('frontend.reports.download', $report) }}"
                       class="details-btn details-btn-primary">
                        {{ __('frontend.project_details.download_pdf') }}
                    </a>

                    @if($project->integration_mode === 'vertexgrad' && $project->platform_project_id)
                        <div class="mt-5 text-center">
                            <a href="http://127.0.0.1:8001/dashboard/academic"
                               style="
                                    display:inline-flex;
                                    align-items:center;
                                    gap:10px;
                                    background: linear-gradient(135deg, #1b00ff, #4338ca);
                                    color:#fff;
                                    padding:14px 26px;
                                    border-radius:14px;
                                    font-weight:700;
                                    text-decoration:none;
                                    box-shadow:0 12px 24px rgba(27,0,255,0.20);
                                    transition:all .3s ease;
                               "
                               onmouseover="this.style.transform='translateY(-2px)'"
                               onmouseout="this.style.transform='translateY(0)'">
                                <i class="fas fa-arrow-left"></i>
                                {{ __('frontend.project_details.back_to_main_platform') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="surface-card fade-up delay-1 ai-chat-card">
            <div>
                <h2 class="ai-chat-title">{{ __('frontend.project_details.ai_chat.title') }}</h2>
                <p class="ai-chat-subtitle">{{ __('frontend.project_details.ai_chat.subtitle') }}</p>
            </div>

            <form id="aiChatForm" class="ai-chat-form">
                <textarea
                    id="aiQuestion"
                    class="ai-chat-textarea"
                    placeholder="{{ __('frontend.project_details.ai_chat.placeholder') }}"
                ></textarea>

                <div class="ai-chat-actions">
                    <button type="submit" id="askAiBtn" class="details-btn details-btn-primary">
                        {{ __('frontend.project_details.ai_chat.button') }}
                    </button>

                    <span id="aiChatStatus" class="ai-chat-status"></span>
                </div>
            </form>

            <div class="ai-chat-answer-box">
                <span class="ai-chat-answer-label">{{ __('frontend.project_details.ai_chat.answer') }}</span>
                <p id="aiAnswer" class="ai-chat-answer is-empty">{{ __('frontend.project_details.ai_chat.empty') }}</p>
            </div>
        </section>

        <section class="surface-card fade-up delay-2">
            <div class="toolbar">
                <div class="toolbar-left">
                    <input
                        type="text"
                        id="issueSearch"
                        class="search-input"
                        placeholder="{{ __('frontend.project_details.search_placeholder') }}"
                    >
                </div>

                <div class="toolbar-right">
                    <button type="button" class="filter-btn is-active" data-filter="all">{{ __('frontend.project_details.filters.all') }}</button>
                    <button type="button" class="filter-btn" data-filter="critical">{{ __('frontend.project_details.filters.critical') }}</button>
                    <button type="button" class="filter-btn" data-filter="high">{{ __('frontend.project_details.filters.high') }}</button>
                    <button type="button" class="filter-btn" data-filter="medium">{{ __('frontend.project_details.filters.medium') }}</button>
                    <button type="button" class="filter-btn" data-filter="low">{{ __('frontend.project_details.filters.low') }}</button>
                    <button type="button" class="filter-btn" data-filter="info">{{ __('frontend.project_details.filters.info') }}</button>
                </div>
            </div>
        </section>

        <section class="details-list fade-up delay-3" id="detailsList">
            @forelse($normalizedFindings as $finding)
                @php
                    $searchText = strtolower(
                        $finding['title'].' '.
                        $finding['file'].' '.
                        $finding['rule'].' '.
                        $finding['description'].' '.
                        $finding['recommendation']
                    );
                @endphp

                <article
                    class="issue-card"
                    data-severity="{{ $finding['severity'] }}"
                    data-search="{{ $searchText }}"
                >
                    <div class="issue-top">
                        <div>
                            <h2 class="issue-title">{{ $finding['title'] }}</h2>

                            <div class="issue-meta">
                                <span class="meta-pill">{{ __('frontend.project_details.labels.severity') }}: {{ ucfirst($finding['severity']) }}</span>
                                <span class="meta-pill">{{ __('frontend.project_details.labels.rule') }}: {{ $finding['rule'] }}</span>
                                <span class="meta-pill">{{ __('frontend.project_details.labels.line') }}: {{ $finding['line'] }}@if($finding['line_end'] && $finding['line_end'] !== $finding['line']) - {{ $finding['line_end'] }} @endif</span>
                            </div>
                        </div>

                        <span class="severity-pill {{
                            $finding['severity'] === 'critical' ? 'tone-critical' :
                            ($finding['severity'] === 'high' ? 'tone-danger' :
                            ($finding['severity'] === 'medium' ? 'tone-warning' :
                            ($finding['severity'] === 'low' ? 'tone-success' : 'tone-info')))
                        }}">
                            {{ ucfirst($finding['severity']) }}
                        </span>
                    </div>

                    <span class="section-label">{{ __('frontend.project_details.labels.file') }}</span>
                    <p class="issue-text">{{ $finding['file'] }}</p>

                    <span class="section-label">{{ __('frontend.project_details.labels.technical_description') }}</span>
                    <p class="issue-text">{{ $finding['description'] }}</p>

                    <span class="section-label">{{ __('frontend.project_details.labels.student_explanation') }}</span>
                    <p class="issue-student">{{ $explainIssueStudent($finding['severity']) }}</p>

                    <span class="section-label">{{ __('frontend.project_details.labels.recommended_fix') }}</span>
                    <p class="issue-fix">{{ $finding['recommendation'] }}</p>

                    <span class="section-label">{{ __('frontend.project_details.labels.code_snippet') }}</span>

                    @if(!empty($finding['snippet']))
                        <div class="code-box">
                            <div class="code-box-top">
                                <span>{{ __('frontend.project_details.labels.code_snippet') }}</span>
                                <button type="button" class="copy-btn" data-copy-target="code-snippet-{{ $finding['id'] }}">
                                    {{ __('frontend.project_details.labels.copy_code') }}
                                </button>
                            </div>
                            <pre class="code-pre" id="code-snippet-{{ $finding['id'] }}">{{ $finding['snippet'] }}</pre>
                        </div>
                    @else
                        <div class="empty-state">
                            {{ __('frontend.project_details.states.no_snippet') }}
                        </div>
                    @endif
                </article>
            @empty
                <div class="empty-state">
                    <strong>{{ __('frontend.project_details.states.no_results') }}</strong>
                    <div style="margin-top:8px;">{{ __('frontend.project_details.states.no_results_desc') }}</div>
                </div>
            @endforelse

            <div class="empty-state" id="noResultsState" style="display:none;">
                <strong>{{ __('frontend.project_details.states.no_results') }}</strong>
                <div style="margin-top:8px;">{{ __('frontend.project_details.states.no_results_desc') }}</div>
            </div>
        </section>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('issueSearch');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const issueCards = document.querySelectorAll('.issue-card');
        const noResultsState = document.getElementById('noResultsState');

        const aiChatForm = document.getElementById('aiChatForm');
        const aiQuestion = document.getElementById('aiQuestion');
        const askAiBtn = document.getElementById('askAiBtn');
        const aiAnswer = document.getElementById('aiAnswer');
        const aiChatStatus = document.getElementById('aiChatStatus');

        const tr = {
            copied: @json(__('frontend.project_details.labels.copied')),
            enterQuestion: @json(__('frontend.project_details.ai_chat.enter_question')),
            loading: @json(__('frontend.project_details.ai_chat.loading')),
            error: @json(__('frontend.project_details.ai_chat.error')),
            empty: @json(__('frontend.project_details.ai_chat.empty')),
        };

        function applyFilters() {
            const query = (searchInput?.value || '').trim().toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.is-active')?.dataset.filter || 'all';

            let visibleCount = 0;

            issueCards.forEach((card) => {
                const severity = card.dataset.severity || '';
                const search = card.dataset.search || '';

                const matchesFilter = activeFilter === 'all' || severity === activeFilter;
                const matchesSearch = !query || search.includes(query);

                const visible = matchesFilter && matchesSearch;
                card.style.display = visible ? '' : 'none';

                if (visible) visibleCount++;
            });

            if (noResultsState) {
                noResultsState.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        filterButtons.forEach((button) => {
            button.addEventListener('click', function () {
                filterButtons.forEach((btn) => btn.classList.remove('is-active'));
                this.classList.add('is-active');
                applyFilters();
            });
        });

        searchInput?.addEventListener('input', applyFilters);

        document.querySelectorAll('.copy-btn').forEach((button) => {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-copy-target');
                const target = document.getElementById(targetId);

                if (!target) return;

                navigator.clipboard.writeText(target.textContent || '').then(() => {
                    const original = this.textContent;
                    this.textContent = tr.copied;
                    setTimeout(() => {
                        this.textContent = original;
                    }, 1200);
                }).catch(() => {});
            });
        });

        aiChatForm?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const question = (aiQuestion?.value || '').trim();

            if (!question) {
                aiChatStatus.textContent = tr.enterQuestion;
                aiChatStatus.classList.add('is-error');
                return;
            }

            askAiBtn.disabled = true;
            aiChatStatus.textContent = tr.loading;
            aiChatStatus.classList.remove('is-error');

            try {
                const response = await fetch('{{ route('api.reports.ask-ai', $report) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        question: question
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || tr.error);
                }

                aiAnswer.textContent = data.answer || tr.empty;
                aiAnswer.classList.remove('is-empty');
                aiChatStatus.textContent = '';
            } catch (error) {
                aiAnswer.textContent = error.message || tr.error;
                aiAnswer.classList.remove('is-empty');
                aiChatStatus.textContent = tr.error;
                aiChatStatus.classList.add('is-error');
            } finally {
                askAiBtn.disabled = false;
            }
        });
    });
</script>
@endsection