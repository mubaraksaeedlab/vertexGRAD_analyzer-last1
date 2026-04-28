<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report->title ?: ('Analysis Report #' . $report->id) }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 28px;
            background: #ffffff;
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
            text-align: left;
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
    <div class="brand">VertexGrad Analyzer</div>
    <div class="title">{{ $report->title ?: ('Analysis Report #' . $report->id) }}</div>
    <div class="subtitle">
        Project: {{ $project['name'] ?? 'Unknown Project' }} |
        Report ID: #{{ $report->id }} |
        Generated: {{ optional($report->generated_at ?? $report->created_at)->format('Y-m-d h:i A') }}
    </div>
</div>

<div class="section">
    <div class="section-title">Project Overview</div>
    <table class="grid">
        <tr>
            <td>
                <div class="label">Project ID</div>
                <div class="value">{{ $project['id'] ?? '-' }}</div>
            </td>
            <td>
                <div class="label">Primary Language</div>
                <div class="value">{{ $project['primary_language'] ?? '-' }}</div>
            </td>
            <td>
                <div class="label">Scan Status</div>
                <div class="value">{{ $project['scan_status'] ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Analysis Run ID</div>
                <div class="value">{{ $analysisRun['id'] ?? '-' }}</div>
            </td>
            <td>
                <div class="label">Files Processed</div>
                <div class="value">{{ $analysisRun['files_processed'] ?? 0 }}</div>
            </td>
            <td>
                <div class="label">Issues Found</div>
                <div class="value">{{ $score['issues_count'] ?? count($issues) }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Score Summary</div>
    <table class="score-cards">
        <tr>
            <td class="score-card">
                <div class="score-card-title">Overall Score</div>
                <div class="score-card-value">{{ $score['overall_score'] ?? 0 }}</div>
            </td>
            <td class="score-card">
                <div class="score-card-title">Grade</div>
                <div class="score-card-value">{{ $score['grade'] ?? '-' }}</div>
            </td>
            <td class="score-card">
                <div class="score-card-title">Security Score</div>
                <div class="score-card-value">{{ $score['security_score'] ?? 0 }}</div>
            </td>
            <td class="score-card">
                <div class="score-card-title">Quality Score</div>
                <div class="score-card-value">{{ $score['quality_score'] ?? 0 }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Severity Breakdown</div>
    <table class="severity-table">
        <thead>
            <tr>
                <th>Critical</th>
                <th>High</th>
                <th>Medium</th>
                <th>Low</th>
                <th>Info</th>
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
    <div class="section-title">Detected Issues</div>
    <table class="issues-table">
        <thead>
            <tr>
                <th style="width: 18%;">Rule</th>
                <th style="width: 12%;">Severity</th>
                <th style="width: 12%;">Language</th>
                <th style="width: 10%;">Line</th>
                <th>Details</th>
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
                        <div class="issue-title">{{ $issue['title'] ?? 'Untitled Issue' }}</div>
                        <div>{{ $issue['description'] ?? '-' }}</div>
                        <div style="margin-top: 6px;"><strong>Recommendation:</strong> {{ $issue['recommendation'] ?? '-' }}</div>

                        @if(!empty($issue['metadata']['relative_path']))
                            <div style="margin-top: 6px;"><strong>Path:</strong> {{ $issue['metadata']['relative_path'] }}</div>
                        @endif

                        @if(!empty($issue['snippet']))
                            <div class="snippet">{{ $issue['snippet'] }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No issues found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="footer">
    Generated by VertexGrad Analyzer — Professional code analysis and reporting platform.
</div>
</body>
</html>