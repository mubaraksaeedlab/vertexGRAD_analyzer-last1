@extends('frontend.layouts.frontend-layout')

@php
    $pageTitle = $project->name . ' | VertexGrad Analyzer';

    $summary = is_array($project->summary) ? $project->summary : [];

    $scanStatus = strtolower($project->scan_status ?? 'unknown');
    $uploadStatus = strtolower($summary['upload_status'] ?? 'queued');

    $statusLabel = match ($scanStatus) {
        'completed' => __('frontend.project_page.status.completed'),
        'running' => __('frontend.project_page.status.running'),
        'queued' => __('frontend.project_page.status.queued'),
        'pending' => __('frontend.project_page.status.pending'),
        'failed' => __('frontend.project_page.status.failed'),
        default => __('frontend.project_page.status.unknown'),
    };

    $statusClass = match ($scanStatus) {
        'completed' => 'status-success',
        'running' => 'status-info',
        'queued' => 'status-warning',
        'pending' => 'status-warning',
        'failed' => 'status-danger',
        default => 'status-muted',
    };

    $uploadStatusLabel = match ($uploadStatus) {
        'queued' => __('frontend.project_page.upload_status.queued'),
        'extracting' => __('frontend.project_page.upload_status.extracting'),
        'discovering_files' => __('frontend.project_page.upload_status.discovering_files'),
        'prepared' => __('frontend.project_page.upload_status.prepared'),
        'failed' => __('frontend.project_page.upload_status.failed'),
        default => __('frontend.project_page.upload_status.preparing'),
    };

    $preparationMessage = match ($uploadStatus) {
        'queued' => __('frontend.project_page.preparation_message.queued'),
        'extracting' => __('frontend.project_page.preparation_message.extracting'),
        'discovering_files' => __('frontend.project_page.preparation_message.discovering_files'),
        'prepared' => __('frontend.project_page.preparation_message.prepared'),
        'failed' => $summary['upload_error'] ?? __('frontend.project_page.preparation_message.failed'),
        default => __('frontend.project_page.preparation_message.preparing'),
    };

    $overallScore = $summary['overall_score'] ?? '—';
    $grade = $summary['grade'] ?? '—';
    $issuesFound = $summary['issues_found'] ?? '—';
    $primaryLanguage = $summary['primary_language'] ?? ($project->primary_language ?: __('frontend.project_page.overview.not_specified_yet'));
    $reportId = $summary['report_id'] ?? null;
    $latestRunId = $summary['latest_run_id'] ?? null;

    $isRunning = in_array($scanStatus, ['running', 'queued']);
    $statusUrl = $latestRunId ? route('frontend.analysis-runs.status', $latestRunId) : '';

    $discoveredFiles = $project->files
        ->map(function ($file) {
            return [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'extension' => $file->extension,
                'language' => $file->language,
                'category' => $file->category,
                'relative_path' => $file->relative_path,
            ];
        })
        ->values();
@endphp

@section('title', $pageTitle)

@section('content')
<style>
    .project-page {
        position: relative;
        z-index: 2;
        padding: 28px 0 56px;
    }

    .project-stack {
        display: grid;
        gap: 22px;
    }

    .project-hero {
        position: relative;
        overflow: hidden;
        border-radius: 32px;
        padding: 34px;
        background:
            radial-gradient(circle at 18% 18%, rgba(20, 216, 255, 0.14), transparent 24%),
            radial-gradient(circle at 84% 16%, rgba(47, 107, 255, 0.16), transparent 28%),
            linear-gradient(135deg, rgba(7, 17, 31, 0.86) 0%, rgba(11, 23, 40, 0.88) 45%, rgba(16, 33, 58, 0.92) 100%);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    html[data-theme="light"] .project-hero {
        background:
            radial-gradient(circle at 18% 18%, rgba(20, 216, 255, 0.10), transparent 24%),
            radial-gradient(circle at 84% 16%, rgba(47, 107, 255, 0.12), transparent 28%),
            linear-gradient(135deg, rgba(255,255,255,0.24) 0%, rgba(255,255,255,0.18) 100%);
    }

    .project-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 34px 34px;
        opacity: .42;
        pointer-events: none;
    }

    .project-hero-inner {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 16px;
    }

    .project-hero-badge {
        width: fit-content;
    }

    .project-hero-title {
        margin: 0;
        font-size: clamp(2.2rem, 4.6vw, 4rem);
        line-height: 1.08;
        font-weight: 900;
        color: #fff;
        letter-spacing: -0.03em;
    }

    html[data-theme="light"] .project-hero-title {
        color: var(--text);
    }

    html[lang="ar"] .project-hero-title,
    body.rtl .project-hero-title {
        line-height: 1.2;
        letter-spacing: 0;
    }

    .project-hero-text {
        margin: 0;
        max-width: 760px;
        color: rgba(255,255,255,0.82);
        line-height: 1.85;
        font-size: 1rem;
    }

    html[data-theme="light"] .project-hero-text {
        color: var(--text-soft);
    }

    .project-hero-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    .glass-panel {
        background: linear-gradient(180deg, var(--surface-strong), var(--glass-bg));
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 24px;
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .section-head h2,
    .section-head h3 {
        margin: 0 0 8px;
        color: var(--text);
        font-size: 1.35rem;
        font-weight: 900;
        line-height: 1.08;
    }

    .section-head p {
        margin: 0;
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 0.92rem;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        border: 1px solid transparent;
    }

    .status-success {
        background: rgba(25, 195, 125, 0.10);
        color: var(--success);
        border-color: rgba(25, 195, 125, 0.18);
    }

    .status-info {
        background: rgba(47, 107, 255, 0.10);
        color: var(--primary);
        border-color: rgba(47, 107, 255, 0.18);
    }

    .status-warning {
        background: rgba(246, 183, 60, 0.10);
        color: var(--warning);
        border-color: rgba(246, 183, 60, 0.18);
    }

    .status-danger {
        background: rgba(239, 83, 80, 0.10);
        color: var(--danger);
        border-color: rgba(239, 83, 80, 0.18);
    }

    .status-muted {
        background: rgba(148, 163, 184, 0.10);
        color: var(--text-muted);
        border-color: var(--border);
    }

    .notice-box {
        padding: 18px 20px;
        border-radius: 20px;
        border: 1px solid rgba(47, 107, 255, 0.14);
        background: rgba(47, 107, 255, 0.08);
        color: var(--primary);
        font-weight: 700;
        line-height: 1.8;
    }

    .notice-box.success {
        background: rgba(25, 195, 125, 0.08);
        border-color: rgba(25, 195, 125, 0.16);
        color: var(--success);
    }

    .notice-box.warning {
        background: rgba(246, 183, 60, 0.08);
        border-color: rgba(246, 183, 60, 0.16);
        color: var(--warning);
    }

    .notice-box.danger {
        background: rgba(239, 83, 80, 0.08);
        border-color: rgba(239, 83, 80, 0.16);
        color: var(--danger);
    }

    .overview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .feature-list {
        display: grid;
        gap: 12px;
        margin-top: 18px;
    }

    .feature-item {
        padding: 16px 18px;
        border-radius: 18px;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--border);
    }

    html[data-theme="light"] .feature-item {
        background: rgba(255,255,255,0.18);
    }

    .feature-item h3 {
        margin: 0 0 6px;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--text-muted);
    }

    .feature-item p {
        margin: 0;
        color: var(--text);
        font-weight: 700;
        line-height: 1.8;
        word-break: break-word;
    }

    .action-row {
        margin-top: 20px;
    }

    .run-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 52px;
        padding: 0 20px;
        border: none;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: #fff;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 16px 36px rgba(47, 107, 255, 0.22);
        transition: .2s ease;
    }

    .run-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 20px 42px rgba(47, 107, 255, 0.28);
    }

    .run-btn:disabled {
        opacity: .72;
        cursor: not-allowed;
        box-shadow: none;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .stat-card {
        padding: 22px;
        border-radius: 22px;
        background: linear-gradient(180deg, var(--surface-strong), var(--glass-bg));
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .stat-label {
        display: block;
        margin-bottom: 10px;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--text-muted);
        font-weight: 800;
    }

    .stat-value {
        font-size: 1.9rem;
        font-weight: 900;
        color: var(--text);
    }

    .inline-link {
        color: var(--primary);
        font-weight: 900;
        text-decoration: none;
    }

    .inline-link:hover {
        text-decoration: underline;
    }

    .progress-shell {
        padding: 22px;
        border-radius: 22px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--border);
    }

    html[data-theme="light"] .progress-shell {
        background: rgba(255,255,255,0.14);
    }

    .progress-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
    }

    .progress-meta strong {
        font-size: 2rem;
        font-weight: 900;
        color: var(--text);
    }

    .progress-meta span {
        color: var(--text-soft);
        font-weight: 700;
    }

    .progress-bar-wrap {
        position: relative;
        width: 100%;
        height: 14px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(148, 163, 184, 0.22);
        margin-bottom: 14px;
    }

    .progress-bar-fill {
        position: absolute;
        inset: 0 auto 0 0;
        width: 0%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        transition: width .35s ease;
        box-shadow: 0 0 24px rgba(20, 216, 255, 0.20);
    }

    .progress-submeta {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        color: var(--text-muted);
        font-weight: 700;
    }

    .progress-dashboard {
        display: grid;
        grid-template-columns: 1.4fr .9fr;
        gap: 20px;
        margin-top: 18px;
    }

    .soft-panel {
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.05);
        border-radius: 20px;
        padding: 18px;
    }

    html[data-theme="light"] .soft-panel {
        background: rgba(255,255,255,0.16);
    }

    .progress-mini-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .mini-stat {
        border-radius: 16px;
        padding: 14px;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--border);
    }

    html[data-theme="light"] .mini-stat {
        background: rgba(255,255,255,0.18);
    }

    .mini-stat .label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.66rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .mini-stat .value {
        font-size: 1.35rem;
        font-weight: 900;
        color: var(--text);
    }

    .current-file-box {
        min-height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 10px;
    }

    .current-file-box .label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--text-muted);
        font-weight: 800;
    }

    .current-file-box .value {
        color: var(--text);
        font-weight: 700;
        line-height: 1.8;
        word-break: break-word;
    }

    .analysis-details-shell {
        margin-top: 18px;
        border: 1px solid var(--border);
        border-radius: 20px;
        overflow: hidden;
        background: rgba(255,255,255,0.04);
    }

    html[data-theme="light"] .analysis-details-shell {
        background: rgba(255,255,255,0.14);
    }

    .analysis-details-shell summary {
        list-style: none;
        cursor: pointer;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        background: rgba(255,255,255,0.04);
        border-bottom: 1px solid var(--border);
    }

    .analysis-details-shell summary::-webkit-details-marker {
        display: none;
    }

    .analysis-details-title {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .analysis-details-title strong {
        color: var(--text);
        font-size: 0.96rem;
        font-weight: 900;
    }

    .analysis-details-title span {
        color: var(--text-muted);
        font-size: 0.82rem;
        font-weight: 700;
    }

    .analysis-details-toggle {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--border);
        color: var(--text-soft);
        font-size: 13px;
        flex-shrink: 0;
        transition: transform .2s ease;
    }

    .analysis-details-shell[open] .analysis-details-toggle {
        transform: rotate(90deg);
    }

    .analysis-details-body {
        padding: 14px;
    }

    .progress-groups {
        display: grid;
        gap: 14px;
    }

    .progress-group {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: rgba(255,255,255,0.05);
        overflow: hidden;
        transition: box-shadow .2s ease, border-color .2s ease;
    }

    html[data-theme="light"] .progress-group {
        background: rgba(255,255,255,0.18);
    }

    .progress-group:hover {
        border-color: var(--border-strong);
        box-shadow: var(--shadow-sm);
    }

    .progress-group.is-complete {
        border-color: rgba(25, 195, 125, 0.20);
        background: linear-gradient(180deg, rgba(25,195,125,0.08) 0%, rgba(255,255,255,0.03) 100%);
    }

    .progress-group summary {
        list-style: none;
        cursor: pointer;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        font-weight: 800;
        color: var(--text);
        background: rgba(255,255,255,0.03);
    }

    .progress-group summary::-webkit-details-marker {
        display: none;
    }

    .progress-group-header {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .progress-group-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .progress-group-toggle {
        width: 26px;
        height: 26px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--border);
        color: var(--text-soft);
        font-size: 12px;
        flex-shrink: 0;
        transition: transform .2s ease;
    }

    .progress-group[open] .progress-group-toggle {
        transform: rotate(90deg);
    }

    .progress-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.06);
        color: var(--text-soft);
    }

    .progress-badge.badge-completed {
        background: rgba(25, 195, 125, 0.10);
        color: var(--success);
        border-color: rgba(25, 195, 125, 0.18);
    }

    .progress-badge.badge-processing {
        background: rgba(47, 107, 255, 0.10);
        color: var(--primary);
        border-color: rgba(47, 107, 255, 0.18);
    }

    .progress-badge.badge-pending {
        background: rgba(246, 183, 60, 0.10);
        color: var(--warning);
        border-color: rgba(246, 183, 60, 0.18);
    }

    .progress-badge.badge-failed {
        background: rgba(239, 83, 80, 0.10);
        color: var(--danger);
        border-color: rgba(239, 83, 80, 0.18);
    }

    .file-progress-list {
        display: grid;
        gap: 10px;
        padding: 14px;
    }

    .file-progress-item {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.05);
    }

    html[data-theme="light"] .file-progress-item {
        background: rgba(255,255,255,0.18);
    }

    .file-progress-item.item-completed {
        background: linear-gradient(180deg, rgba(25,195,125,0.10) 0%, rgba(255,255,255,0.04) 100%);
        border-color: rgba(25, 195, 125, 0.18);
    }

    .file-progress-item.item-processing {
        background: linear-gradient(180deg, rgba(47,107,255,0.10) 0%, rgba(255,255,255,0.04) 100%);
        border-color: rgba(47, 107, 255, 0.18);
    }

    .file-progress-item.item-failed {
        background: linear-gradient(180deg, rgba(239,83,80,0.10) 0%, rgba(255,255,255,0.04) 100%);
        border-color: rgba(239, 83, 80, 0.18);
    }

    .file-progress-item.item-pending {
        background: linear-gradient(180deg, rgba(246,183,60,0.10) 0%, rgba(255,255,255,0.04) 100%);
        border-color: rgba(246, 183, 60, 0.18);
    }

    .file-progress-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
        font-size: 16px;
        flex-shrink: 0;
        background: rgba(255,255,255,0.08);
    }

    .file-progress-icon.icon-completed {
        background: radial-gradient(circle at 30% 30%, #34d399, #059669);
        color: #fff;
        border-color: #10b981;
        box-shadow: 0 10px 18px rgba(16, 185, 129, 0.18);
    }

    .file-progress-icon.icon-processing {
        background: radial-gradient(circle at 30% 30%, #60a5fa, #2563eb);
        color: #fff;
        border-color: #3b82f6;
        box-shadow: 0 10px 18px rgba(37, 99, 235, 0.18);
    }

    .file-progress-icon.icon-pending {
        background: radial-gradient(circle at 30% 30%, #fbbf24, #d97706);
        color: #fff;
        border-color: #f59e0b;
        box-shadow: 0 10px 18px rgba(217, 119, 6, 0.18);
    }

    .file-progress-icon.icon-failed {
        background: radial-gradient(circle at 30% 30%, #f87171, #dc2626);
        color: #fff;
        border-color: #ef4444;
        box-shadow: 0 10px 18px rgba(220, 38, 38, 0.18);
    }

    .file-progress-text {
        min-width: 0;
    }

    .file-progress-text strong {
        display: block;
        color: var(--text);
        font-size: 14px;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-progress-text small {
        display: block;
        margin-top: 3px;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-progress-status {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-soft);
        padding: 8px 10px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.06);
    }

    .file-progress-status.status-completed {
        color: var(--success);
        background: rgba(25, 195, 125, 0.10);
        border-color: rgba(25, 195, 125, 0.18);
    }

    .file-progress-status.status-processing {
        color: var(--primary);
        background: rgba(47, 107, 255, 0.10);
        border-color: rgba(47, 107, 255, 0.18);
    }

    .file-progress-status.status-pending {
        color: var(--warning);
        background: rgba(246, 183, 60, 0.10);
        border-color: rgba(246, 183, 60, 0.18);
    }

    .file-progress-status.status-failed {
        color: var(--danger);
        background: rgba(239, 83, 80, 0.10);
        border-color: rgba(239, 83, 80, 0.18);
    }

    .empty-box {
        padding: 22px;
        border: 1px dashed var(--border-strong);
        border-radius: 18px;
        background: rgba(255,255,255,0.04);
        color: var(--text-muted);
        text-align: center;
        font-weight: 700;
    }

    .explorer-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .explorer-search {
        flex: 1 1 320px;
        position: relative;
    }

    .explorer-search input {
        width: 100%;
        min-height: 48px;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.06);
        padding: 0 16px;
        font-weight: 700;
        color: var(--text);
        outline: none;
    }

    html[data-theme="light"] .explorer-search input {
        background: rgba(255,255,255,0.18);
    }

    .explorer-search input:focus {
        border-color: rgba(47, 107, 255, 0.34);
        box-shadow: 0 0 0 4px rgba(47,107,255,.08);
    }

    .explorer-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .soft-btn {
        min-height: 44px;
        padding: 0 14px;
        border-radius: 14px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.06);
        color: var(--text);
        cursor: pointer;
        font-weight: 800;
        transition: .2s ease;
    }

    html[data-theme="light"] .soft-btn {
        background: rgba(255,255,255,0.18);
    }

    .soft-btn:hover {
        background: rgba(255,255,255,0.10);
        border-color: var(--border-strong);
    }

    .file-tree-shell {
        border: 1px solid var(--border);
        border-radius: 20px;
        overflow: hidden;
        background: rgba(255,255,255,0.04);
    }

    html[data-theme="light"] .file-tree-shell {
        background: rgba(255,255,255,0.14);
    }

    .file-tree-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--border);
        background: rgba(255,255,255,0.04);
        flex-wrap: wrap;
    }

    .file-tree-meta strong {
        color: var(--text);
        font-weight: 900;
    }

    .file-tree {
        padding: 14px 14px 18px;
        max-height: 720px;
        overflow: auto;
    }

    .tree-node {
        margin-left: 0;
    }

    .tree-node .node-children {
        margin-left: 22px;
        border-left: 1px dashed var(--border);
        padding-left: 12px;
    }

    .tree-folder,
    .tree-file {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 40px;
        padding: 8px 10px;
        border-radius: 12px;
        color: var(--text);
    }

    .tree-folder:hover,
    .tree-file:hover {
        background: rgba(255,255,255,0.06);
    }

    .tree-folder summary {
        list-style: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
    }

    .tree-folder summary::-webkit-details-marker {
        display: none;
    }

    .tree-main {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .tree-icon {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        background: rgba(47, 107, 255, 0.10);
        color: var(--primary);
        border: 1px solid rgba(47, 107, 255, 0.18);
        flex-shrink: 0;
    }

    .tree-name {
        min-width: 0;
        font-weight: 700;
        color: var(--text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tree-meta-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-left: auto;
    }

    .tree-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        background: rgba(255,255,255,0.06);
        color: var(--text-soft);
        border: 1px solid var(--border);
    }

    .tree-file.is-hidden {
        display: none;
    }

    .muted-note {
        color: var(--text-muted);
        font-weight: 700;
        font-size: 13px;
    }

    @media (max-width: 1100px) {
        .overview-grid,
        .progress-dashboard,
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .progress-mini-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .project-hero {
            padding: 24px;
        }

        .project-hero-title {
            font-size: 2rem;
        }

        .glass-panel,
        .stat-card {
            padding: 18px;
        }

        .progress-meta {
            flex-direction: column;
            align-items: flex-start;
        }

        .progress-mini-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="project-page">
    <div class="container project-stack">
        <section class="project-hero">
            <div class="project-hero-inner">
                <span class="badge badge-secondary project-hero-badge">
                    {{ __('frontend.project_page.hero_badge') }}
                </span>

                <h1 class="project-hero-title">{{ $project->name }}</h1>

                <p class="project-hero-text">
                    {{ __('frontend.project_page.hero_description') }}
                </p>

                <div class="project-hero-meta">
                    <span class="status-pill {{ $statusClass }}" id="projectStatusPill">
                        {{ $statusLabel }}
                    </span>
                    <span class="status-pill {{ $uploadStatus === 'failed' ? 'status-danger' : (in_array($uploadStatus, ['queued','extracting','discovering_files']) ? 'status-warning' : 'status-success') }}"
                          id="projectUploadStatusPill">
                        {{ $uploadStatusLabel }}
                    </span>
                </div>
            </div>
        </section>

        <div class="notice-box {{ $uploadStatus === 'failed' ? 'danger' : (($uploadStatus === 'prepared' || $project->files->count() > 0) ? 'success' : 'warning') }}"
             id="preparingNoticeBox">
            <span id="preparingNoticeText">{{ $preparationMessage }}</span>
        </div>

        <section class="overview-grid">
            <div class="glass-panel">
                <div class="section-head">
                    <div>
                        <h2>{{ __('frontend.project_page.overview.title') }}</h2>
                        <p>{{ __('frontend.project_page.overview.description') }}</p>
                    </div>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.overview.project_name') }}</h3>
                        <p>{{ $project->name }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.overview.status') }}</h3>
                        <p>
                            <span class="status-pill {{ $statusClass }}" id="projectStatusPillInline">
                                {{ $statusLabel }}
                            </span>
                        </p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.overview.preparation_stage') }}</h3>
                        <p id="projectUploadStatusText">{{ $uploadStatusLabel }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.overview.primary_language') }}</h3>
                        <p id="primaryLanguageText">{{ $primaryLanguage }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.overview.description_label') }}</h3>
                        <p id="projectDescriptionText">
                            {{ $project->description ?: __('frontend.project_page.overview.no_description') }}
                        </p>
                    </div>
                </div>

                <div class="action-row">
                    <form
                        action="{{ route('frontend.projects.run-analysis', $project) }}"
                        method="POST"
                        id="runAnalysisForm"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="run-btn"
                            id="runAnalysisBtn"
                            {{ in_array($uploadStatus, ['queued', 'extracting', 'discovering_files']) ? 'disabled' : '' }}
                        >
                            @if(in_array($uploadStatus, ['queued', 'extracting', 'discovering_files']))
                                {{ __('frontend.project_page.actions.preparing_files') }}
                            @elseif($isRunning)
                                {{ __('frontend.project_page.actions.analysis_running') }}
                            @else
                                {{ __('frontend.project_page.actions.run_analysis') }}
                            @endif
                        </button>
                    </form>
                </div>
            </div>

            <div class="glass-panel">
                <div class="section-head">
                    <div>
                        <h2>{{ __('frontend.project_page.submission.title') }}</h2>
                        <p>{{ __('frontend.project_page.submission.description') }}</p>
                    </div>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.submission.student_name') }}</h3>
                        <p>{{ $summary['student_name'] ?? '—' }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.submission.student_email') }}</h3>
                        <p>{{ $summary['student_email'] ?? '—' }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.submission.original_archive') }}</h3>
                        <p>{{ $summary['original_file_name'] ?? '—' }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.submission.stored_archive_path') }}</h3>
                        <p>{{ $summary['archive_path'] ?? '—' }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.submission.discovered_files') }}</h3>
                        <p id="discoveredFilesCountText">{{ $summary['discovered_files_count'] ?? $project->files->count() }}</p>
                    </div>

                    <div class="feature-item">
                        <h3>{{ __('frontend.project_page.submission.created_at') }}</h3>
                        <p>{{ optional($project->created_at)->format('Y-m-d h:i A') }}</p>
                    </div>
                </div>
            </div>
        </section>

        @if($isRunning || $latestRunId)
            <section
                class="glass-panel"
                id="analysisProgressCard"
                data-status-url="{{ $statusUrl }}"
                data-run-id="{{ $latestRunId }}"
            >
                <div class="section-head">
                    <div>
                        <h2>{{ __('frontend.project_page.progress.title') }}</h2>
                        <p>{{ __('frontend.project_page.progress.description') }}</p>
                    </div>
                    <span class="status-pill {{ $statusClass }}" id="analysisStatusPill">
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="progress-shell">
                    <div class="progress-meta">
                        <strong id="progressPercent">0%</strong>
                        <span id="progressStep">{{ __('frontend.project_page.progress.waiting') }}</span>
                    </div>

                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" id="progressBarFill" style="width: 0%;"></div>
                    </div>

                    <div class="progress-submeta">
                        <span id="progressFiles">0 / 0 {{ __('frontend.project_page.progress.files_processed') }}</span>
                        <span id="progressCurrentFile">—</span>
                    </div>

                    <div class="progress-dashboard">
                        <div class="soft-panel">
                            <div class="progress-mini-stats">
                                <div class="mini-stat">
                                    <span class="label">{{ __('frontend.project_page.progress.completed') }}</span>
                                    <span class="value" id="statCompleted">0</span>
                                </div>
                                <div class="mini-stat">
                                    <span class="label">{{ __('frontend.project_page.progress.processing') }}</span>
                                    <span class="value" id="statProcessing">0</span>
                                </div>
                                <div class="mini-stat">
                                    <span class="label">{{ __('frontend.project_page.progress.pending') }}</span>
                                    <span class="value" id="statPending">0</span>
                                </div>
                                <div class="mini-stat">
                                    <span class="label">{{ __('frontend.project_page.progress.failed') }}</span>
                                    <span class="value" id="statFailed">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="soft-panel current-file-box">
                            <span class="label">{{ __('frontend.project_page.progress.current_file') }}</span>
                            <div class="value" id="currentFileBox">—</div>
                            <span class="muted-note" id="refreshNote">{{ __('frontend.project_page.progress.live_updates') }}</span>
                        </div>
                    </div>
                </div>

                <details class="analysis-details-shell" id="analysisDetailsShell" open>
                    <summary>
                        <div class="analysis-details-title">
                            <span>📂</span>
                            <div>
                                <strong>{{ __('frontend.project_page.progress.details_title') }}</strong>
                                <span id="analysisDetailsHint">{{ __('frontend.project_page.progress.details_hint') }}</span>
                            </div>
                        </div>
                        <span class="analysis-details-toggle">▶</span>
                    </summary>

                    <div class="analysis-details-body">
                        <div class="progress-groups" id="fileProgressList">
                            <div class="empty-box">
                                {{ __('frontend.project_page.progress.waiting_data') }}
                            </div>
                        </div>
                    </div>
                </details>
            </section>
        @endif

        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">{{ __('frontend.project_page.stats.overall_score') }}</span>
                <strong class="stat-value" id="summaryOverallScore">{{ $overallScore }}</strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">{{ __('frontend.project_page.stats.grade') }}</span>
                <strong class="stat-value" id="summaryGrade">{{ $grade }}</strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">{{ __('frontend.project_page.stats.issues_found') }}</span>
                <strong class="stat-value" id="summaryIssuesFound">{{ $issuesFound }}</strong>
            </div>

            <div class="stat-card">
                <span class="stat-label">{{ __('frontend.project_page.stats.report') }}</span>
                <strong class="stat-value" id="summaryReportBox">
                    @if($reportId)
                        <a href="{{ route('frontend.reports.show', $reportId) }}" class="inline-link">
                            {{ __('frontend.project_page.stats.open_report') }}
                        </a>
                    @else
                        —
                    @endif
                </strong>
            </div>
        </section>

        <section class="glass-panel">
            <div class="section-head">
                <div>
                    <h2>{{ __('frontend.project_page.files.title') }}</h2>
                    <p>{{ __('frontend.project_page.files.description') }}</p>
                </div>
            </div>

            @if($project->files->count())
                <div class="explorer-toolbar">
                    <div class="explorer-search">
                        <input
                            type="text"
                            id="fileExplorerSearch"
                            placeholder="{{ __('frontend.project_page.files.search_placeholder') }}"
                        >
                    </div>

                    <div class="explorer-actions">
                        <button type="button" class="soft-btn" id="expandAllTreeBtn">
                            {{ __('frontend.project_page.files.expand_all') }}
                        </button>
                        <button type="button" class="soft-btn" id="collapseAllTreeBtn">
                            {{ __('frontend.project_page.files.collapse_all') }}
                        </button>
                    </div>
                </div>

                <div class="file-tree-shell">
                    <div class="file-tree-meta">
                        <strong id="fileIndexedCountText">
                            {{ $project->files->count() }} {{ __('frontend.project_page.files.files_indexed') }}
                        </strong>
                        <span class="muted-note" id="fileExplorerResultText">
                            {{ __('frontend.project_page.files.showing_all') }}
                        </span>
                    </div>

                    <div class="file-tree" id="fileTreeRoot"></div>
                </div>
            @else
                <div class="empty-box" id="emptyDiscoveredFilesBox">
                    {{ __('frontend.project_page.files.no_extracted_files') }}
                </div>

                <div class="file-tree-shell" style="display:none;" id="fileTreeShell">
                    <div class="file-tree-meta">
                        <strong id="fileIndexedCountText">0 {{ __('frontend.project_page.files.files_indexed') }}</strong>
                        <span class="muted-note" id="fileExplorerResultText">
                            {{ __('frontend.project_page.files.showing_all') }}
                        </span>
                    </div>

                    <div class="file-tree" id="fileTreeRoot"></div>
                </div>
            @endif
        </section>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const analysisCard = document.getElementById('analysisProgressCard');
        const runAnalysisForm = document.getElementById('runAnalysisForm');
        const runAnalysisBtn = document.getElementById('runAnalysisBtn');

        const treeRoot = document.getElementById('fileTreeRoot');
        const fileTreeShell = document.getElementById('fileTreeShell');
        const fileSearchInput = document.getElementById('fileExplorerSearch');
        const expandAllTreeBtn = document.getElementById('expandAllTreeBtn');
        const collapseAllTreeBtn = document.getElementById('collapseAllTreeBtn');
        const fileExplorerResultText = document.getElementById('fileExplorerResultText');

        const projectStatusPill = document.getElementById('projectStatusPill');
        const projectStatusPillInline = document.getElementById('projectStatusPillInline');
        const projectUploadStatusText = document.getElementById('projectUploadStatusText');
        const projectUploadStatusPill = document.getElementById('projectUploadStatusPill');
        const discoveredFilesCountText = document.getElementById('discoveredFilesCountText');
        const preparingNoticeText = document.getElementById('preparingNoticeText');
        const preparingNoticeBox = document.getElementById('preparingNoticeBox');
        const fileIndexedCountText = document.getElementById('fileIndexedCountText');
        const emptyDiscoveredFilesBox = document.getElementById('emptyDiscoveredFilesBox');
        const primaryLanguageText = document.getElementById('primaryLanguageText');

        let discoveredFiles = @json($discoveredFiles);
        const preparationStatusUrl = @json(route('frontend.projects.preparation-status', $project));
        const frontendReportBaseUrl = @json(url('/reports'));

        let currentProjectScanStatus = @json(strtolower($project->scan_status ?? 'pending'));
        let currentUploadStatus = @json(strtolower($summary['upload_status'] ?? 'queued'));
        let lastKnownSummary = @json($summary);

        const translations = {
            en: {
                noDescription: @json(__('frontend.project_page.overview.no_description')),
                noAnalysisRun: @json(__('frontend.project_page.progress.no_analysis_run')),
                invalidStatusResponse: @json(__('frontend.project_page.progress.invalid_status_response')),
                failedLoadStatus: @json(__('frontend.project_page.progress.failed_load_status')),
                networkErrorStatus: @json(__('frontend.project_page.progress.network_error_status')),
                waitingAnalysisData: @json(__('frontend.project_page.progress.waiting_data')),
                noGroupedActivity: @json(__('frontend.project_page.progress.no_files_progress')),
                liveGroupedActivity: @json(__('frontend.project_page.progress.live_grouped_activity')),
                analysisCompleteDetails: @json(__('frontend.project_page.progress.analysis_complete_details')),
                projectRoot: @json(__('frontend.project_page.progress.project_root')),
                filesIndexed: @json(__('frontend.project_page.files.files_indexed')),
                showingAllFiles: @json(__('frontend.project_page.files.showing_all')),
                showingMatchingFiles: @json(__('frontend.project_page.files.showing_matching_single')),
                showingMatchingFilesPlural: @json(__('frontend.project_page.files.showing_matching_plural')),
                queuedBg: @json(__('frontend.project_page.upload_status.queued')),
                extracting: @json(__('frontend.project_page.upload_status.extracting')),
                discovering: @json(__('frontend.project_page.upload_status.discovering_files')),
                prepared: @json(__('frontend.project_page.upload_status.prepared')),
                prepFailed: @json(__('frontend.project_page.upload_status.failed')),
                preparingProject: @json(__('frontend.project_page.upload_status.preparing')),
                projectQueuedMsg: @json(__('frontend.project_page.preparation_message.queued')),
                projectExtractingMsg: @json(__('frontend.project_page.preparation_message.extracting')),
                projectDiscoveringMsg: @json(__('frontend.project_page.preparation_message.discovering_files')),
                projectPreparedMsg: @json(__('frontend.project_page.preparation_message.prepared')),
                projectPreparingMsg: @json(__('frontend.project_page.preparation_message.preparing')),
                projectPrepFailedMsg: @json(__('frontend.project_page.preparation_message.failed')),
                completed: @json(__('frontend.project_page.status.completed')),
                running: @json(__('frontend.project_page.status.running')),
                failed: @json(__('frontend.project_page.status.failed')),
                pending: @json(__('frontend.project_page.status.pending')),
                unknown: @json(__('frontend.project_page.status.unknown')),
                preparingFiles: @json(__('frontend.project_page.actions.preparing_files')),
                analysisRunning: @json(__('frontend.project_page.actions.analysis_running')),
                runAnalysis: @json(__('frontend.project_page.actions.run_analysis')),
                runAnalysisAgain: @json(__('frontend.project_page.actions.run_analysis_again')),
                preparationFailedButton: @json(__('frontend.project_page.actions.preparation_failed')),
                waitingButton: @json(__('frontend.project_page.actions.waiting')),
                liveUpdates: @json(__('frontend.project_page.progress.live_updates')),
                analysisCompletedSuccess: @json(__('frontend.project_page.progress.analysis_completed_success')),
                analysisFinishedFailures: @json(__('frontend.project_page.progress.analysis_finished_failures')),
                currentFile: @json(__('frontend.project_page.progress.current_file')),
                creatingAnalysisRun: @json(__('frontend.project_page.actions.creating_analysis_run')),
                startAnalysisInvalidResponse: @json(__('frontend.project_page.progress.start_analysis_invalid_response')),
                couldNotStartAnalysis: @json(__('frontend.project_page.progress.could_not_start_analysis')),
                networkErrorStart: @json(__('frontend.project_page.progress.network_error_start')),
                preparingSession: @json(__('frontend.project_page.actions.preparing_session')),
                openReport: @json(__('frontend.project_page.stats.open_report')),
                noFilesDisplay: @json(__('frontend.project_page.files.no_files_display')),
                file: @json(__('frontend.project_page.progress.file')),
                files: @json(__('frontend.project_page.progress.files')),
                item: @json(__('frontend.project_page.progress.item')),
                items: @json(__('frontend.project_page.progress.items')),
                processing: @json(__('frontend.project_page.progress.processing')),
                startingAnalysis: @json(__('frontend.project_page.actions.starting_analysis')),
                noFilesProgress: @json(__('frontend.project_page.progress.no_files_progress')),
                filesProcessed: @json(__('frontend.project_page.progress.files_processed')),
                unknownFile: @json(__('frontend.project_page.files.unknown_file')),
                unknownLabel: @json(__('frontend.project_page.files.unknown')),
                otherLabel: @json(__('frontend.project_page.files.other'))
            },
            ar: {
                noDescription: @json(__('frontend.project_page.overview.no_description', [], 'ar')),
                noAnalysisRun: @json(__('frontend.project_page.progress.no_analysis_run', [], 'ar')),
                invalidStatusResponse: @json(__('frontend.project_page.progress.invalid_status_response', [], 'ar')),
                failedLoadStatus: @json(__('frontend.project_page.progress.failed_load_status', [], 'ar')),
                networkErrorStatus: @json(__('frontend.project_page.progress.network_error_status', [], 'ar')),
                waitingAnalysisData: @json(__('frontend.project_page.progress.waiting_data', [], 'ar')),
                noGroupedActivity: @json(__('frontend.project_page.progress.no_files_progress', [], 'ar')),
                liveGroupedActivity: @json(__('frontend.project_page.progress.live_grouped_activity', [], 'ar')),
                analysisCompleteDetails: @json(__('frontend.project_page.progress.analysis_complete_details', [], 'ar')),
                projectRoot: @json(__('frontend.project_page.progress.project_root', [], 'ar')),
                filesIndexed: @json(__('frontend.project_page.files.files_indexed', [], 'ar')),
                showingAllFiles: @json(__('frontend.project_page.files.showing_all', [], 'ar')),
                showingMatchingFiles: @json(__('frontend.project_page.files.showing_matching_single', [], 'ar')),
                showingMatchingFilesPlural: @json(__('frontend.project_page.files.showing_matching_plural', [], 'ar')),
                queuedBg: @json(__('frontend.project_page.upload_status.queued', [], 'ar')),
                extracting: @json(__('frontend.project_page.upload_status.extracting', [], 'ar')),
                discovering: @json(__('frontend.project_page.upload_status.discovering_files', [], 'ar')),
                prepared: @json(__('frontend.project_page.upload_status.prepared', [], 'ar')),
                prepFailed: @json(__('frontend.project_page.upload_status.failed', [], 'ar')),
                preparingProject: @json(__('frontend.project_page.upload_status.preparing', [], 'ar')),
                projectQueuedMsg: @json(__('frontend.project_page.preparation_message.queued', [], 'ar')),
                projectExtractingMsg: @json(__('frontend.project_page.preparation_message.extracting', [], 'ar')),
                projectDiscoveringMsg: @json(__('frontend.project_page.preparation_message.discovering_files', [], 'ar')),
                projectPreparedMsg: @json(__('frontend.project_page.preparation_message.prepared', [], 'ar')),
                projectPreparingMsg: @json(__('frontend.project_page.preparation_message.preparing', [], 'ar')),
                projectPrepFailedMsg: @json(__('frontend.project_page.preparation_message.failed', [], 'ar')),
                completed: @json(__('frontend.project_page.status.completed', [], 'ar')),
                running: @json(__('frontend.project_page.status.running', [], 'ar')),
                failed: @json(__('frontend.project_page.status.failed', [], 'ar')),
                pending: @json(__('frontend.project_page.status.pending', [], 'ar')),
                unknown: @json(__('frontend.project_page.status.unknown', [], 'ar')),
                preparingFiles: @json(__('frontend.project_page.actions.preparing_files', [], 'ar')),
                analysisRunning: @json(__('frontend.project_page.actions.analysis_running', [], 'ar')),
                runAnalysis: @json(__('frontend.project_page.actions.run_analysis', [], 'ar')),
                runAnalysisAgain: @json(__('frontend.project_page.actions.run_analysis_again', [], 'ar')),
                preparationFailedButton: @json(__('frontend.project_page.actions.preparation_failed', [], 'ar')),
                waitingButton: @json(__('frontend.project_page.actions.waiting', [], 'ar')),
                liveUpdates: @json(__('frontend.project_page.progress.live_updates', [], 'ar')),
                analysisCompletedSuccess: @json(__('frontend.project_page.progress.analysis_completed_success', [], 'ar')),
                analysisFinishedFailures: @json(__('frontend.project_page.progress.analysis_finished_failures', [], 'ar')),
                currentFile: @json(__('frontend.project_page.progress.current_file', [], 'ar')),
                creatingAnalysisRun: @json(__('frontend.project_page.actions.creating_analysis_run', [], 'ar')),
                startAnalysisInvalidResponse: @json(__('frontend.project_page.progress.start_analysis_invalid_response', [], 'ar')),
                couldNotStartAnalysis: @json(__('frontend.project_page.progress.could_not_start_analysis', [], 'ar')),
                networkErrorStart: @json(__('frontend.project_page.progress.network_error_start', [], 'ar')),
                preparingSession: @json(__('frontend.project_page.actions.preparing_session', [], 'ar')),
                openReport: @json(__('frontend.project_page.stats.open_report', [], 'ar')),
                noFilesDisplay: @json(__('frontend.project_page.files.no_files_display', [], 'ar')),
                file: @json(__('frontend.project_page.progress.file', [], 'ar')),
                files: @json(__('frontend.project_page.progress.files', [], 'ar')),
                item: @json(__('frontend.project_page.progress.item', [], 'ar')),
                items: @json(__('frontend.project_page.progress.items', [], 'ar')),
                processing: @json(__('frontend.project_page.progress.processing', [], 'ar')),
                startingAnalysis: @json(__('frontend.project_page.actions.starting_analysis', [], 'ar')),
                noFilesProgress: @json(__('frontend.project_page.progress.no_files_progress', [], 'ar')),
                filesProcessed: @json(__('frontend.project_page.progress.files_processed', [], 'ar')),
                unknownFile: @json(__('frontend.project_page.files.unknown_file', [], 'ar')),
                unknownLabel: @json(__('frontend.project_page.files.unknown', [], 'ar')),
                otherLabel: @json(__('frontend.project_page.files.other', [], 'ar'))
            }
        };

        function currentLang() {
            return document.documentElement.getAttribute('lang') === 'ar' ? 'ar' : 'en';
        }

        function t(key, replacements = {}) {
            const lang = currentLang();
            let value = (translations[lang] && translations[lang][key]) || (translations.en[key]) || key;

            Object.keys(replacements).forEach((name) => {
                value = value.replace(`:${name}`, replacements[name]);
            });

            return value;
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getFileIcon(extension) {
            const ext = (extension || '').toLowerCase();

            if (['php', 'js', 'ts', 'tsx', 'jsx', 'py', 'java', 'cs', 'go', 'rb', 'rs', 'cpp', 'c', 'swift', 'kt', 'dart', 'vue'].includes(ext)) {
                return '🧠';
            }
            if (['html', 'htm', 'css', 'scss', 'sass', 'less'].includes(ext)) {
                return '🎨';
            }
            if (['json', 'xml', 'yml', 'yaml', 'sql', 'md'].includes(ext)) {
                return '📄';
            }

            return '📄';
        }

        function buildTree(files) {
            const root = {};

            files.forEach(file => {
                const path = file.relative_path || file.file_name || 'unknown';
                const parts = path.split('/').filter(Boolean);

                let level = root;

                parts.forEach((part, index) => {
                    const isFile = index === parts.length - 1;

                    if (!level[part]) {
                        level[part] = {
                            name: part,
                            type: isFile ? 'file' : 'folder',
                            children: {},
                            file: isFile ? file : null
                        };
                    }

                    if (!isFile) {
                        level = level[part].children;
                    }
                });
            });

            return root;
        }

        function renderTreeNode(node, fullPath = '') {
            const currentPath = fullPath ? `${fullPath}/${node.name}` : node.name;

            if (node.type === 'folder') {
                const children = Object.values(node.children).sort((a, b) => {
                    if (a.type !== b.type) return a.type === 'folder' ? -1 : 1;
                    return a.name.localeCompare(b.name);
                });

                const childrenHtml = children.map(child => renderTreeNode(child, currentPath)).join('');
                const countText = `${children.length} ${children.length > 1 ? t('items') : t('item')}`;

                return `
                    <details class="tree-node tree-folder-node" open>
                        <summary class="tree-folder" data-path="${escapeHtml(currentPath)}">
                            <div class="tree-main">
                                <span class="tree-icon">📁</span>
                                <span class="tree-name">${escapeHtml(node.name)}</span>
                            </div>
                            <div class="tree-meta-badges">
                                <span class="tree-badge">${escapeHtml(countText)}</span>
                            </div>
                        </summary>
                        <div class="node-children">
                            ${childrenHtml}
                        </div>
                    </details>
                `;
            }

            const file = node.file || {};
            const searchable = [
                file.file_name,
                file.extension,
                file.language,
                file.category,
                file.relative_path
            ].filter(Boolean).join(' ').toLowerCase();

            return `
                <div class="tree-node tree-file" data-search="${escapeHtml(searchable)}">
                    <div class="tree-main" style="width:100%;">
                        <span class="tree-icon">${getFileIcon(file.extension)}</span>
                        <span class="tree-name">${escapeHtml(file.file_name || node.name)}</span>
                        <div class="tree-meta-badges">
                            <span class="tree-badge">${escapeHtml(file.extension || '—')}</span>
                            <span class="tree-badge">${escapeHtml(file.language || t('unknownLabel'))}</span>
                            <span class="tree-badge">${escapeHtml(file.category || t('otherLabel'))}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderFileTree(files) {
            if (!treeRoot) return;

            const tree = buildTree(files);
            const html = Object.values(tree)
                .sort((a, b) => {
                    if (a.type !== b.type) return a.type === 'folder' ? -1 : 1;
                    return a.name.localeCompare(b.name);
                })
                .map(node => renderTreeNode(node))
                .join('');

            treeRoot.innerHTML = html || `<div class="empty-box">${escapeHtml(t('noFilesDisplay'))}</div>`;

            if (fileIndexedCountText) {
                fileIndexedCountText.textContent = `${files.length} ${t('filesIndexed')}`;
            }
        }

        function filterFileTree() {
            if (!treeRoot || !fileSearchInput) return;

            const query = fileSearchInput.value.trim().toLowerCase();
            const fileNodes = Array.from(treeRoot.querySelectorAll('.tree-file'));

            let visibleCount = 0;

            fileNodes.forEach(node => {
                const haystack = node.dataset.search || '';
                const visible = !query || haystack.includes(query);
                node.classList.toggle('is-hidden', !visible);

                if (visible) visibleCount++;
            });

            const folderNodes = Array.from(treeRoot.querySelectorAll('.tree-folder-node'));

            folderNodes.forEach(folder => {
                const visibleChildren = folder.querySelectorAll('.tree-file:not(.is-hidden)').length;
                folder.style.display = visibleChildren > 0 || !query ? '' : 'none';

                if (query && visibleChildren > 0) {
                    folder.open = true;
                }
            });

            if (fileExplorerResultText) {
                if (!query) {
                    fileExplorerResultText.textContent = t('showingAllFiles');
                } else {
                    fileExplorerResultText.textContent = visibleCount === 1
                        ? t('showingMatchingFiles', { count: visibleCount })
                        : t('showingMatchingFilesPlural', { count: visibleCount });
                }
            }
        }

        function expandAllTree() {
            document.querySelectorAll('.tree-folder-node').forEach(node => {
                node.open = true;
            });
        }

        function collapseAllTree() {
            document.querySelectorAll('.tree-folder-node').forEach(node => {
                node.open = false;
            });
        }

        renderFileTree(discoveredFiles);

        if (fileSearchInput) {
            fileSearchInput.addEventListener('input', filterFileTree);
        }

        if (expandAllTreeBtn) {
            expandAllTreeBtn.addEventListener('click', expandAllTree);
        }

        if (collapseAllTreeBtn) {
            collapseAllTreeBtn.addEventListener('click', collapseAllTree);
        }

        function mapProjectScanStatusLabel(status) {
            status = (status || '').toLowerCase();

            if (status === 'completed') return t('completed');
            if (status === 'running') return t('running');
            if (status === 'failed') return t('failed');
            if (status === 'pending') return t('pending');
            if (status === 'queued') return t('pending');

            return t('unknown');
        }

        function mapUploadStatusLabel(status) {
            status = (status || '').toLowerCase();

            if (status === 'queued') return t('queuedBg');
            if (status === 'extracting') return t('extracting');
            if (status === 'discovering_files') return t('discovering');
            if (status === 'prepared') return t('prepared');
            if (status === 'failed') return t('prepFailed');

            return t('preparingProject');
        }

        function mapProjectStatusPillClass(scanStatus, uploadStatus) {
            scanStatus = (scanStatus || '').toLowerCase();
            uploadStatus = (uploadStatus || '').toLowerCase();

            if (scanStatus === 'completed') return 'status-success';
            if (scanStatus === 'running') return 'status-info';
            if (scanStatus === 'failed' || uploadStatus === 'failed') return 'status-danger';
            if (['queued', 'extracting', 'discovering_files'].includes(uploadStatus)) return 'status-warning';
            if (scanStatus === 'pending' || scanStatus === 'queued') return 'status-warning';

            return 'status-muted';
        }

        function preparationNoticeMessage(status, uploadError = null) {
            status = (status || '').toLowerCase();

            if (status === 'queued') return t('projectQueuedMsg');
            if (status === 'extracting') return t('projectExtractingMsg');
            if (status === 'discovering_files') return t('projectDiscoveringMsg');
            if (status === 'prepared') return t('projectPreparedMsg');
            if (status === 'failed') return uploadError || t('projectPrepFailedMsg');

            return t('projectPreparingMsg');
        }

        function isProjectPreparing(uploadStatus) {
            return ['queued', 'extracting', 'discovering_files'].includes((uploadStatus || '').toLowerCase());
        }

        function isProjectReady(uploadStatus, filesCount) {
            return (uploadStatus || '').toLowerCase() === 'prepared' || Number(filesCount || 0) > 0;
        }

        function isAnalysisRunning(scanStatus) {
            return (scanStatus || '').toLowerCase() === 'running';
        }

        function showFileExplorer() {
            if (emptyDiscoveredFilesBox) {
                emptyDiscoveredFilesBox.style.display = 'none';
            }

            if (fileTreeShell) {
                fileTreeShell.style.display = '';
            }
        }

        function setProjectStatusPills(label, className) {
            if (projectStatusPill) {
                projectStatusPill.textContent = label;
                projectStatusPill.className = `status-pill ${className}`;
            }

            if (projectStatusPillInline) {
                projectStatusPillInline.textContent = label;
                projectStatusPillInline.className = `status-pill ${className}`;
            }
        }

        function setUploadStatusPill(label, className) {
            if (projectUploadStatusPill) {
                projectUploadStatusPill.textContent = label;
                projectUploadStatusPill.className = `status-pill ${className}`;
            }
        }

        function updateProjectPreparationUi(scanStatus, uploadStatus, filesCount, uploadError = null, detectedPrimaryLanguage = null) {
            currentProjectScanStatus = (scanStatus || '').toLowerCase();
            currentUploadStatus = (uploadStatus || '').toLowerCase();

            setProjectStatusPills(
                mapProjectScanStatusLabel(currentProjectScanStatus),
                mapProjectStatusPillClass(currentProjectScanStatus, currentUploadStatus)
            );

            if (projectUploadStatusText) {
                projectUploadStatusText.textContent = mapUploadStatusLabel(currentUploadStatus);
            }

            setUploadStatusPill(
                mapUploadStatusLabel(currentUploadStatus),
                currentUploadStatus === 'failed'
                    ? 'status-danger'
                    : (['queued', 'extracting', 'discovering_files'].includes(currentUploadStatus) ? 'status-warning' : 'status-success')
            );

            if (discoveredFilesCountText) {
                discoveredFilesCountText.textContent = filesCount;
            }

            if (preparingNoticeText) {
                preparingNoticeText.textContent = preparationNoticeMessage(currentUploadStatus, uploadError);
            }

            if (preparingNoticeBox) {
                preparingNoticeBox.classList.remove('success', 'warning', 'danger');

                if (currentUploadStatus === 'failed') {
                    preparingNoticeBox.classList.add('danger');
                } else if (isProjectReady(currentUploadStatus, filesCount)) {
                    preparingNoticeBox.classList.add('success');
                } else {
                    preparingNoticeBox.classList.add('warning');
                }
            }

            if (primaryLanguageText && detectedPrimaryLanguage) {
                primaryLanguageText.textContent = detectedPrimaryLanguage;
            }

            if (runAnalysisBtn) {
                if (isAnalysisRunning(currentProjectScanStatus)) {
                    runAnalysisBtn.disabled = true;
                    runAnalysisBtn.textContent = t('analysisRunning');
                } else if (isProjectPreparing(currentUploadStatus)) {
                    runAnalysisBtn.disabled = true;
                    runAnalysisBtn.textContent = t('preparingFiles');
                } else if (isProjectReady(currentUploadStatus, filesCount)) {
                    runAnalysisBtn.disabled = false;
                    runAnalysisBtn.textContent = t('runAnalysis');
                } else if (currentUploadStatus === 'failed') {
                    runAnalysisBtn.disabled = true;
                    runAnalysisBtn.textContent = t('preparationFailedButton');
                } else {
                    runAnalysisBtn.disabled = true;
                    runAnalysisBtn.textContent = t('waitingButton');
                }
            }
        }

        async function pollPreparationStatus() {
            if (!preparationStatusUrl) return;

            try {
                const response = await fetch(preparationStatusUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const json = await response.json();

                if (!response.ok || !json.ok) return;

                const data = json.data || {};
                const summary = data.summary || {};
                const files = data.files || [];
                const scanStatus = (data.scan_status || 'pending').toLowerCase();
                const uploadStatus = (summary.upload_status || 'queued').toLowerCase();
                const filesCount = Number(summary.discovered_files_count ?? files.length ?? 0);
                const uploadError = summary.upload_error || null;
                const detectedPrimaryLanguage = summary.primary_language || null;

                updateProjectPreparationUi(
                    scanStatus,
                    uploadStatus,
                    filesCount,
                    uploadError,
                    detectedPrimaryLanguage
                );

                if (files.length > 0) {
                    discoveredFiles = files;
                    renderFileTree(discoveredFiles);
                    showFileExplorer();

                    if (discoveredFilesCountText) {
                        discoveredFilesCountText.textContent = files.length;
                    }

                    if (fileIndexedCountText) {
                        fileIndexedCountText.textContent = `${files.length} ${t('filesIndexed')}`;
                    }
                }

                if (isProjectReady(uploadStatus, filesCount) || uploadStatus === 'failed') {
                    clearInterval(window.projectPreparationTimer);
                    window.projectPreparationTimer = null;
                    return;
                }
            } catch (error) {
                // silent retry
            }
        }

        function updateSummaryCards(summary) {
            lastKnownSummary = summary || lastKnownSummary || {};

            const summaryOverallScore = document.getElementById('summaryOverallScore');
            const summaryGrade = document.getElementById('summaryGrade');
            const summaryIssuesFound = document.getElementById('summaryIssuesFound');
            const summaryReportBox = document.getElementById('summaryReportBox');

            if (summaryOverallScore && summary.overall_score !== null && summary.overall_score !== undefined) {
                summaryOverallScore.textContent = summary.overall_score;
            }

            if (summaryGrade && summary.grade) {
                summaryGrade.textContent = summary.grade;
            }

            if (summaryIssuesFound && summary.issues_found !== null && summary.issues_found !== undefined) {
                summaryIssuesFound.textContent = summary.issues_found;
            }

            if (summary.primary_language && primaryLanguageText) {
                primaryLanguageText.textContent = summary.primary_language;
            }

            if (summaryReportBox) {
                if (summary.report_id) {
                    summaryReportBox.innerHTML = `
                        <a href="${frontendReportBaseUrl}/${summary.report_id}" class="inline-link">
                            ${escapeHtml(t('openReport'))}
                        </a>
                    `;
                } else if (!summaryReportBox.textContent.trim()) {
                    summaryReportBox.textContent = '—';
                }
            }
        }

        async function refetchFinalSummary(maxAttempts = 6, delayMs = 900) {
            for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
                try {
                    const response = await fetch(currentStatusUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const json = await response.json();

                    if (!response.ok || !json.ok) {
                        await new Promise(resolve => setTimeout(resolve, delayMs));
                        continue;
                    }

                    const data = json.data || {};
                    const summary = data.summary || {};
                    updateSummaryCards(summary);

                    const hasVisibleResults =
                        summary.report_id ||
                        summary.overall_score !== null ||
                        summary.grade ||
                        summary.issues_found !== null;

                    if (hasVisibleResults) {
                        break;
                    }
                } catch (error) {
                    // retry
                }

                await new Promise(resolve => setTimeout(resolve, delayMs));
            }
        }

        updateProjectPreparationUi(
            currentProjectScanStatus,
            currentUploadStatus,
            discoveredFiles.length,
            null,
            @json($primaryLanguage !== __('frontend.project_page.overview.not_specified_yet') ? $primaryLanguage : null)
        );

        updateSummaryCards(lastKnownSummary);

        if (discoveredFiles.length > 0) {
            showFileExplorer();
        }

        if (isProjectPreparing(currentUploadStatus)) {
            window.projectPreparationTimer = setInterval(pollPreparationStatus, 2000);
            pollPreparationStatus();
        }

        if (!analysisCard) {
            return;
        }

        const percentEl = document.getElementById('progressPercent');
        const stepEl = document.getElementById('progressStep');
        const filesEl = document.getElementById('progressFiles');
        const currentFileEl = document.getElementById('progressCurrentFile');
        const currentFileBox = document.getElementById('currentFileBox');
        const barEl = document.getElementById('progressBarFill');
        const listEl = document.getElementById('fileProgressList');
        const analysisDetailsShell = document.getElementById('analysisDetailsShell');
        const analysisDetailsHint = document.getElementById('analysisDetailsHint');
        const refreshNote = document.getElementById('refreshNote');
        const statusPill = document.getElementById('analysisStatusPill');

        const statCompleted = document.getElementById('statCompleted');
        const statProcessing = document.getElementById('statProcessing');
        const statPending = document.getElementById('statPending');
        const statFailed = document.getElementById('statFailed');

        let timer = null;
        let currentStatusUrl = analysisCard.dataset.statusUrl || '';

        function analysisStatusPillClass(status) {
            if (status === 'completed') return 'status-success';
            if (status === 'running' || status === 'processing') return 'status-info';
            if (status === 'queued' || status === 'pending') return 'status-warning';
            if (status === 'failed') return 'status-danger';
            return 'status-muted';
        }

        function iconMarkupFor(status) {
            if (status === 'completed') {
                return `
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <circle cx="10" cy="10" r="10" fill="currentColor"></circle>
                        <path d="M6 10.2L8.6 12.7L14.2 7.3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                `;
            }

            if (status === 'processing') {
                return `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3a9 9 0 1 0 9 9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                `;
            }

            if (status === 'failed') {
                return `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 9L9 15M9 9l6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"></path>
                    </svg>
                `;
            }

            return `
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                    <circle cx="5" cy="5" r="5" fill="currentColor"></circle>
                </svg>
            `;
        }

        function groupFilesByFolder(files) {
            const groups = {};

            (files || []).forEach(file => {
                const path = file.relative_path || file.file_name || t('unknownFile');
                const parts = path.split('/');
                const folder = parts.length > 1 ? parts.slice(0, -1).join('/') : t('projectRoot');

                if (!groups[folder]) groups[folder] = [];
                groups[folder].push(file);
            });

            return groups;
        }

        function renderFiles(files, analysisStatus = null) {
            if (!files || !files.length) {
                if (analysisDetailsHint) {
                    analysisDetailsHint.textContent = t('noGroupedActivity');
                }

                listEl.innerHTML = `<div class="empty-box">${escapeHtml(t('noFilesProgress'))}</div>`;
                return;
            }

            const groups = groupFilesByFolder(files);
            const groupKeys = Object.keys(groups).sort((a, b) => a.localeCompare(b));
            const isFinished = analysisStatus === 'completed' || analysisStatus === 'failed';

            if (analysisDetailsHint) {
                analysisDetailsHint.textContent = isFinished
                    ? t('analysisCompleteDetails')
                    : t('liveGroupedActivity');
            }

            listEl.innerHTML = groupKeys.map((groupName, index) => {
                const items = groups[groupName];
                const counts = {
                    completed: items.filter(x => x.status === 'completed').length,
                    processing: items.filter(x => x.status === 'processing').length,
                    pending: items.filter(x => x.status === 'pending').length,
                    failed: items.filter(x => x.status === 'failed').length,
                };

                const shouldBeOpen = !isFinished && (index < 4 || counts.processing > 0 || counts.failed > 0);
                const allCompleted = counts.completed === items.length && items.length > 0;
                const groupClass = allCompleted ? 'progress-group is-complete' : 'progress-group';

                return `
                    <details class="${groupClass}" ${shouldBeOpen ? 'open' : ''}>
                        <summary>
                            <div class="progress-group-header">
                                <span class="progress-group-toggle">▶</span>
                                <span>📂</span>
                                <span class="progress-group-name">${escapeHtml(groupName)}</span>
                            </div>
                            <div class="tree-meta-badges">
                                <span class="progress-badge">${items.length} ${items.length > 1 ? escapeHtml(t('files')) : escapeHtml(t('file'))}</span>
                                <span class="progress-badge badge-completed">${escapeHtml(t('completed'))} ${counts.completed}</span>
                                <span class="progress-badge badge-processing">${escapeHtml(t('processing'))} ${counts.processing}</span>
                                <span class="progress-badge badge-pending">${escapeHtml(t('pending'))} ${counts.pending}</span>
                                <span class="progress-badge badge-failed">${escapeHtml(t('failed'))} ${counts.failed}</span>
                            </div>
                        </summary>
                        <div class="file-progress-list">
                            ${items.map(file => {
                                const itemStatus = file.status ?? 'pending';
                                return `
                                    <div class="file-progress-item item-${escapeHtml(itemStatus)}">
                                        <span class="file-progress-icon icon-${escapeHtml(itemStatus)}">
                                            ${iconMarkupFor(itemStatus)}
                                        </span>
                                        <div class="file-progress-text">
                                            <strong>${escapeHtml(file.file_name ?? t('unknownFile'))}</strong>
                                            <small>${escapeHtml(file.relative_path ?? '')}</small>
                                        </div>
                                        <span class="file-progress-status status-${escapeHtml(itemStatus)}">
                                            ${escapeHtml(mapProjectScanStatusLabel(itemStatus))}
                                        </span>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </details>
                `;
            }).join('');

            if (analysisDetailsShell) {
                if (isFinished) {
                    analysisDetailsShell.removeAttribute('open');
                } else {
                    analysisDetailsShell.setAttribute('open', 'open');
                }
            }

            if (isFinished) {
                listEl.querySelectorAll('.progress-group').forEach(group => {
                    group.removeAttribute('open');
                });
            }
        }

        function stopPolling() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        async function fetchStatus() {
            if (!currentStatusUrl) {
                stepEl.textContent = t('noAnalysisRun');
                listEl.innerHTML = `<div class="empty-box">${escapeHtml(t('noAnalysisRun'))}</div>`;
                stopPolling();
                return;
            }

            try {
                const response = await fetch(currentStatusUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const rawText = await response.text();
                let json = null;

                try {
                    json = JSON.parse(rawText);
                } catch (parseError) {
                    stepEl.textContent = t('invalidStatusResponse');
                    listEl.innerHTML = `<div class="empty-box">${escapeHtml(t('invalidStatusResponse'))}</div>`;
                    stopPolling();
                    return;
                }

                if (!response.ok || !json.ok) {
                    stepEl.textContent = json.message ?? t('failedLoadStatus');
                    listEl.innerHTML = `<div class="empty-box">${escapeHtml(json.message ?? t('failedLoadStatus'))}</div>`;
                    stopPolling();
                    return;
                }

                const data = json.data ?? {};
                const files = data.files ?? [];
                const summary = data.summary ?? {};

                percentEl.textContent = `${data.progress_percent ?? 0}%`;
                stepEl.textContent = data.current_step ?? '—';
                filesEl.textContent = `${data.processed_files ?? 0} / ${data.total_files ?? 0} ${t('filesProcessed')}`;
                currentFileEl.textContent = data.current_file ?? '—';
                currentFileBox.textContent = data.current_file ?? '—';
                barEl.style.width = `${data.progress_percent ?? 0}%`;

                renderFiles(files, data.status ?? null);
                updateSummaryCards(summary);

                const completedCount = files.filter(x => x.status === 'completed').length;
                const processingCount = files.filter(x => x.status === 'processing').length;
                const pendingCount = files.filter(x => x.status === 'pending').length;
                const failedCount = files.filter(x => x.status === 'failed').length;

                statCompleted.textContent = completedCount;
                statProcessing.textContent = processingCount;
                statPending.textContent = pendingCount;
                statFailed.textContent = failedCount;

                if (statusPill) {
                    statusPill.textContent = mapProjectScanStatusLabel(data.status ?? 'unknown');
                    statusPill.className = `status-pill ${analysisStatusPillClass(data.status)}`;
                }

                setProjectStatusPills(
                    mapProjectScanStatusLabel(data.status ?? currentProjectScanStatus),
                    analysisStatusPillClass(data.status)
                );

                if (summary.primary_language && primaryLanguageText) {
                    primaryLanguageText.textContent = summary.primary_language;
                }

                if (data.status === 'completed' || data.status === 'failed') {
                    refreshNote.textContent = data.status === 'completed'
                        ? t('analysisCompletedSuccess')
                        : t('analysisFinishedFailures');

                    if (analysisDetailsShell) {
                        analysisDetailsShell.removeAttribute('open');
                    }

                    if (runAnalysisBtn) {
                        runAnalysisBtn.disabled = false;
                        runAnalysisBtn.textContent = t('runAnalysisAgain');
                    }

                    await refetchFinalSummary();
                    stopPolling();
                } else {
                    refreshNote.textContent = t('liveUpdates');

                    if (analysisDetailsShell) {
                        analysisDetailsShell.setAttribute('open', 'open');
                    }
                }
            } catch (error) {
                stepEl.textContent = t('networkErrorStatus');
                listEl.innerHTML = `<div class="empty-box">${escapeHtml(t('networkErrorStatus'))}</div>`;
            }
        }

        function startPolling(statusUrl) {
            currentStatusUrl = statusUrl;
            analysisCard.dataset.statusUrl = statusUrl;
            stopPolling();
            fetchStatus();
            timer = setInterval(fetchStatus, 700);
        }

        if (runAnalysisForm) {
            runAnalysisForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (isProjectPreparing(currentUploadStatus)) {
                    return;
                }

                if (!isProjectReady(currentUploadStatus, discoveredFiles.length)) {
                    return;
                }

                if (!analysisCard) {
                    runAnalysisForm.submit();
                    return;
                }

                if (runAnalysisBtn) {
                    runAnalysisBtn.disabled = true;
                    runAnalysisBtn.textContent = t('startingAnalysis');
                }

                stepEl.textContent = t('creatingAnalysisRun');
                percentEl.textContent = '0%';
                filesEl.textContent = `0 / 0 ${t('filesProcessed')}`;
                currentFileEl.textContent = '—';
                currentFileBox.textContent = '—';
                barEl.style.width = '0%';
                listEl.innerHTML = `<div class="empty-box">${escapeHtml(t('preparingSession'))}</div>`;

                statCompleted.textContent = '0';
                statProcessing.textContent = '0';
                statPending.textContent = '0';
                statFailed.textContent = '0';

                try {
                    const response = await fetch(runAnalysisForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': runAnalysisForm.querySelector('input[name="_token"]').value,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const rawText = await response.text();
                    let json = null;

                    try {
                        json = JSON.parse(rawText);
                    } catch (e) {
                        stepEl.textContent = t('couldNotStartAnalysis');
                        listEl.innerHTML = `<div class="empty-box">${escapeHtml(t('startAnalysisInvalidResponse'))}</div>`;
                        updateProjectPreparationUi(currentProjectScanStatus, currentUploadStatus, discoveredFiles.length);
                        return;
                    }

                    if (!response.ok || !json.ok) {
                        stepEl.textContent = json.message ?? t('couldNotStartAnalysis');
                        listEl.innerHTML = `<div class="empty-box">${escapeHtml(json.message ?? t('couldNotStartAnalysis'))}</div>`;
                        updateProjectPreparationUi(currentProjectScanStatus, currentUploadStatus, discoveredFiles.length);
                        return;
                    }

                    currentProjectScanStatus = 'running';

                    if (statusPill) {
                        statusPill.textContent = t('running');
                        statusPill.className = 'status-pill status-info';
                    }

                    updateProjectPreparationUi(currentProjectScanStatus, currentUploadStatus, discoveredFiles.length);

                    refreshNote.textContent = t('liveUpdates');

                    if (analysisDetailsShell) {
                        analysisDetailsShell.setAttribute('open', 'open');
                    }

                    startPolling(json.status_url);
                } catch (error) {
                    stepEl.textContent = t('networkErrorStart');
                    listEl.innerHTML = `<div class="empty-box">${escapeHtml(t('networkErrorStart'))}</div>`;
                    updateProjectPreparationUi(currentProjectScanStatus, currentUploadStatus, discoveredFiles.length);
                }
            });
        }

        if (currentStatusUrl && currentProjectScanStatus === 'running') {
            startPolling(currentStatusUrl);
        }
    });
</script>
@endsection