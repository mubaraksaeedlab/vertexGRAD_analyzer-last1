<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>
        {{ $report->title ?: str_replace(':id', $report->id, __('frontend.project_pdf.title_fallback')) }}
    </title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 28px;
            background: #ffffff;
            direction: {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 24px;
        }

        .brand {
            font-size: 12px;
            font-weight: 700;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .title {
            font-size: 24px;
            font-weight: 800;
            margin-top: 6px;
            margin-bottom: 4px;
        }

        .subtitle {
            color: #475569;
            font-size: 12px;
        }

        .section {
            margin-top: 24px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #0f172a;
            border-left: 4px solid #0f766e;
            padding-left: 10px;
        }

        html[dir="rtl"] .section-title,
        body[dir="rtl"] .section-title {
            border-left: none;
            border-right: 4px solid #0f766e;
            padding-left: 0;
            padding-right: 10px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
        }

        .grid td {
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            vertical-align: top;
        }

        .label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .value {
            margin-top: 4px;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        .score-cards {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin-left: -10px;
            margin-right: -10px;
        }

        .score-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 14px;
            border-radius: 10px;
        }

        .score-card-title {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
        }

        .score-card-value {
            font-size: 22px;
            font-weight: 800;
            margin-top: 6px;
        }

        .severity-table,
        .issues-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .severity-table th,
        .severity-table td,
        .issues-table th,
        .issues-table td {
            border: 1px solid #e2e8f0;
            padding: 10px;
            text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }};
            vertical-align: top;
        }

        .severity-table th,
        .issues-table th {
            background: #f8fafc;
            font-size: 11px;
            text-transform: uppercase;
        }

        .issue-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .snippet {
            background: #0f172a;
            color: #e2e8f0;
            padding: 10px;
            border-radius: 8px;
            font-size: 11px;
            white-space: pre-wrap;
            word-break: break-word;
            margin-top: 8px;
        }

        .footer {
            margin-top: 32px;
            padding-top: 14px;
            border-top: 1px solid #cbd5e1;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>
<body>
@php
    $project = $data['project'] ?? [];
    $analysisRun = $data['analysis_run'] ?? [];
    $score = $data['score'] ?? [];
    $issues = $data['issues'] ?? [];
@endphp

<div class="header">
    <div class="brand">{{ __('frontend.project_pdf.brand') }}</div>
    <div class="title">
        {{ $report->title ?: str_replace(':id', $report->id, __('frontend.project_pdf.title_fallback')) }}
    </div>
    <div class="subtitle">
        {{ __('frontend.project_pdf.project') }}: {{ $project['name'] ?? __('frontend.project_pdf.unknown_project') }} |
        {{ __('frontend.project_pdf.report_id') }}: #{{ $report->id }} |
        {{ __('frontend.project_pdf.generated') }}: {{ optional($report->generated_at ?? $report->created_at)->format('Y-m-d h:i A') }}
    </div>
</div>

<div class="section">
    <div class="section-title">{{ __('frontend.project_pdf.project_overview') }}</div>
    <table class="grid">
        <tr>
            <td>
                <div class="label">{{ __('frontend.project_pdf.project_id') }}</div>
                <div class="value">{{ $project['id'] ?? '-' }}</div>
            </td>
            <td>
                <div class="label">{{ __('frontend.project_pdf.primary_language') }}</div>
                <div class="value">{{ $project['primary_language'] ?? '-' }}</div>
            </td>
            <td>
                <div class="label">{{ __('frontend.project_pdf.scan_status') }}</div>
                <div class="value">{{ $project['scan_status'] ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ __('frontend.project_pdf.analysis_run_id') }}</div>
                <div class="value">{{ $analysisRun['id'] ?? '-' }}</div>
            </td>
            <td>
                <div class="label">{{ __('frontend.project_pdf.files_processed') }}</div>
                <div class="value">{{ $analysisRun['files_processed'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">{{ __('frontend.project_pdf.issues_found') }}</div>
                <div class="value">{{ $score['issues_count'] ?? count($issues) }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('frontend.project_pdf.score_summary') }}</div>
    <table class="score-cards">
        <tr>
            <td class="score-card">
                <div class="score-card-title">{{ __('frontend.project_pdf.overall_score') }}</div>
                <div class="score-card-value">{{ $score['overall_score'] ?? 0 }}</div>
            </td>
            <td class="score-card">
                <div class="score-card-title">{{ __('frontend.project_pdf.grade') }}</div>
                <div class="score-card-value">{{ $score['grade'] ?? '-' }}</div>
            </td>
            <td class="score-card">
                <div class="score-card-title">{{ __('frontend.project_pdf.security_score') }}</div>
                <div class="score-card-value">{{ $score['security_score'] ?? 0 }}</div>
            </td>
            <td class="score-card">
                <div class="score-card-title">{{ __('frontend.project_pdf.quality_score') }}</div>
                <div class="score-card-value">{{ $score['quality_score'] ?? 0 }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('frontend.project_pdf.severity_breakdown') }}</div>
    <table class="severity-table">
        <thead>
            <tr>
                <th>{{ __('frontend.project_pdf.critical') }}</th>
                <th>{{ __('frontend.project_pdf.high') }}</th>
                <th>{{ __('frontend.project_pdf.medium') }}</th>
                <th>{{ __('frontend.project_pdf.low') }}</th>
                <th>{{ __('frontend.project_pdf.info') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $score['critical_count'] ?? 0 }}</td>
                <td>{{ $score['high_count'] ?? 0 }}</td>
                <td>{{ $score['medium_count'] ?? 0 }}</td>
                <td>{{ $score['low_count'] ?? 0 }}</td>
                <td>{{ $score['info_count'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ __('frontend.project_pdf.detected_issues') }}</div>
    <table class="issues-table">
        <thead>
            <tr>
                <th style="width: 18%;">{{ __('frontend.project_pdf.rule') }}</th>
                <th style="width: 12%;">{{ __('frontend.project_pdf.severity') }}</th>
                <th style="width: 12%;">{{ __('frontend.project_pdf.language') }}</th>
                <th style="width: 10%;">{{ __('frontend.project_pdf.line') }}</th>
                <th>{{ __('frontend.project_pdf.details') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($issues as $issue)
                <tr>
                    <td>{{ $issue['rule_code'] ?? '-' }}</td>
                    <td>{{ strtoupper($issue['severity'] ?? '-') }}</td>
                    <td>{{ $issue['language'] ?? '-' }}</td>
                    <td>{{ $issue['line_start'] ?? '-' }}</td>
                    <td>
                        <div class="issue-title">{{ $issue['title'] ?? __('frontend.project_pdf.untitled_issue') }}</div>
                        <div>{{ $issue['description'] ?? '-' }}</div>
                        <div style="margin-top: 6px;">
                            <strong>{{ __('frontend.project_pdf.recommendation') }}:</strong>
                            {{ $issue['recommendation'] ?? '-' }}
                        </div>

                        @if(!empty($issue['metadata']['relative_path']))
                            <div style="margin-top: 6px;">
                                <strong>{{ __('frontend.project_pdf.path') }}:</strong>
                                {{ $issue['metadata']['relative_path'] }}
                            </div>
                        @endif

                        @if(!empty($issue['snippet']))
                            <div class="snippet">{{ $issue['snippet'] }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('frontend.project_pdf.no_issues') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="footer">
    {{ __('frontend.project_pdf.footer') }}
</div>
</body>
</html>