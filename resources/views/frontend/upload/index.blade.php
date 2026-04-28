@extends('frontend.layouts.frontend-layout')

@php
    $pageTitle = __('frontend.submit_page.meta.title');
@endphp

@section('title', __('frontend.submit_page.meta.title'))

@section('content')
@php
    $scanData = [];

    if (filled(request('data'))) {
        $decoded = base64_decode(request('data'), true);

        if ($decoded !== false) {
            $scanData = json_decode($decoded, true) ?: [];
        }
    }

    $isIntegratedRequest = filled(request('data')) || filled(request('platform_project_id'));

    $prefillPlatformProjectId = $scanData['platform_project_id'] ?? request('platform_project_id');
    $prefillProjectName = $scanData['project_name'] ?? request('project_name', old('project_name'));
    $prefillStudentName = $scanData['student_name'] ?? request('student_name', old('student_name'));
    $prefillStudentEmail = $scanData['student_email'] ?? request('student_email', old('student_email'));
    $prefillLanguage = $scanData['language'] ?? request('language', old('language'));
@endphp

<style>
    .submit-page {
        position: relative;
        z-index: 2;
        padding: 28px 0 56px;
    }

    html[dir="rtl"] .submit-page {
        direction: rtl;
    }

    html[dir="ltr"] .submit-page {
        direction: ltr;
    }

    .submit-stack {
        display: grid;
        gap: 24px;
    }

    .submit-hero {
        position: relative;
        overflow: hidden;
        border-radius: 32px;
        padding: 34px;
        background:
            radial-gradient(circle at 18% 20%, rgba(20, 216, 255, 0.14), transparent 24%),
            radial-gradient(circle at 82% 18%, rgba(47, 107, 255, 0.14), transparent 26%),
            linear-gradient(135deg, rgba(7, 17, 31, 0.86) 0%, rgba(11, 23, 40, 0.88) 45%, rgba(16, 33, 58, 0.92) 100%);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    html[data-theme="light"] .submit-hero {
        background:
            radial-gradient(circle at 18% 20%, rgba(20, 216, 255, 0.10), transparent 24%),
            radial-gradient(circle at 82% 18%, rgba(47, 107, 255, 0.10), transparent 26%),
            linear-gradient(135deg, rgba(255,255,255,0.26) 0%, rgba(255,255,255,0.22) 100%);
    }

    .submit-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 34px 34px;
        pointer-events: none;
        opacity: .45;
    }

    .submit-hero-wrap {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 18px;
        text-align: center;
        max-width: 980px;
        margin: 0 auto;
    }

    .submit-hero-badge {
        width: fit-content;
        margin: 0 auto;
    }

    .submit-hero-title {
        margin: 0;
        font-size: clamp(2.4rem, 5vw, 4.8rem);
        line-height: 1.08;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    html[lang="ar"] .submit-hero-title,
    body.rtl .submit-hero-title {
        line-height: 1.2;
        letter-spacing: 0;
    }

    .submit-hero-text {
        max-width: 760px;
        margin: 0 auto;
        color: var(--text-soft);
        font-size: 1.05rem;
        line-height: 1.9;
    }

    .submit-hero-actions {
        display: flex;
        justify-content: center;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    html[dir="rtl"] .submit-hero-actions {
        flex-direction: row-reverse;
    }

    html[dir="ltr"] .submit-hero-actions {
        flex-direction: row;
    }

    .submit-grid {
        display: grid;
        grid-template-columns: 0.95fr 1.2fr;
        gap: 22px;
        align-items: start;
    }

    html[dir="rtl"] .submit-grid {
        direction: rtl;
    }

    html[dir="ltr"] .submit-grid {
        direction: ltr;
    }

    .submit-card {
        background: linear-gradient(180deg, var(--surface-strong), var(--glass-bg));
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 24px;
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    html[dir="rtl"] .submit-card {
        text-align: right;
    }

    html[dir="ltr"] .submit-card {
        text-align: left;
    }

    .submit-card-head {
        margin-bottom: 18px;
    }

    .submit-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 32px;
        padding: 0 12px;
        border-radius: 999px;
        background: rgba(20, 216, 255, 0.10);
        border: 1px solid rgba(20, 216, 255, 0.16);
        color: var(--secondary);
        font-size: 0.75rem;
        font-weight: 800;
        margin-bottom: 12px;
    }

    html[dir="rtl"] .submit-eyebrow {
        flex-direction: row-reverse;
    }

    html[dir="ltr"] .submit-eyebrow {
        flex-direction: row;
    }

    .submit-card-head h2 {
        margin: 0 0 8px;
        color: var(--text);
        font-size: 1.75rem;
        line-height: 1.08;
        font-weight: 900;
    }

    .submit-card-head p {
        margin: 0;
        color: var(--text-muted);
        line-height: 1.85;
        font-size: 0.96rem;
    }

    .language-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .language-card {
        position: relative;
        cursor: pointer;
    }

    .language-card input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .language-card-ui {
        min-height: 108px;
        padding: 16px;
        border-radius: 22px;
        background: linear-gradient(180deg, var(--surface-2), var(--glass-bg));
        border: 1px solid var(--border);
        display: grid;
        gap: 8px;
        transition: .22s ease;
        text-align: start;
    }

    .language-card-ui .lang-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(47, 107, 255, 0.14), rgba(20, 216, 255, 0.12));
        border: 1px solid rgba(20, 216, 255, 0.16);
        font-size: 20px;
    }

    .language-card-ui strong {
        color: var(--text);
        font-size: 0.98rem;
        font-weight: 900;
    }

    .language-card-ui span {
        color: var(--text-muted);
        font-size: 0.80rem;
        line-height: 1.6;
        font-weight: 700;
    }

    .language-card input:checked + .language-card-ui {
        transform: translateY(-1px);
        border-color: rgba(47, 107, 255, 0.36);
        box-shadow: 0 0 0 1px rgba(47, 107, 255, 0.16), 0 16px 32px rgba(47, 107, 255, 0.12);
        background: linear-gradient(180deg, rgba(47,107,255,0.10), rgba(20,216,255,0.06));
    }

    .scope-box {
        display: grid;
        gap: 14px;
        padding: 18px;
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(47, 107, 255, 0.08), rgba(20, 216, 255, 0.04));
        border: 1px solid rgba(20, 216, 255, 0.16);
        margin-top: 18px;
    }

    .scope-box strong {
        color: var(--text);
        font-size: 1rem;
        font-weight: 900;
    }

    .scope-box p {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.88rem;
        line-height: 1.8;
        font-weight: 700;
    }

    .folder-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .folder-chip {
        min-height: 50px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 14px;
        border-radius: 18px;
        background: rgba(255,255,255,0.08);
        border: 1px solid var(--border);
    }

    html[dir="rtl"] .folder-chip {
        flex-direction: row-reverse;
        text-align: right;
    }

    html[dir="ltr"] .folder-chip {
        flex-direction: row;
        text-align: left;
    }

    html[data-theme="light"] .folder-chip {
        background: rgba(255,255,255,0.16);
    }

    .folder-chip .check {
        width: 26px;
        height: 26px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 12px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .folder-chip code {
        color: var(--text);
        font-size: 0.84rem;
        font-weight: 800;
        background: transparent;
        padding: 0;
    }

    .privacy-box {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 18px;
        border-radius: 20px;
        background: linear-gradient(180deg, rgba(25, 195, 125, 0.08), rgba(25, 195, 125, 0.03));
        border: 1px solid rgba(25, 195, 125, 0.16);
    }

    html[dir="rtl"] .privacy-box {
        flex-direction: row-reverse;
        text-align: right;
    }

    html[dir="ltr"] .privacy-box {
        flex-direction: row;
        text-align: left;
    }

    .privacy-box .icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.18);
        color: var(--success);
        font-size: 20px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .privacy-box strong {
        display: block;
        color: var(--text);
        font-size: 0.95rem;
        font-weight: 900;
    }

    .privacy-box p {
        margin: 6px 0 0;
        color: var(--text-muted);
        font-size: 0.84rem;
        line-height: 1.8;
        font-weight: 700;
    }

    .error-box {
        display: none;
        padding: 16px 18px;
        border-radius: 18px;
        background: rgba(239, 83, 80, 0.08);
        border: 1px solid rgba(239, 83, 80, 0.20);
        color: var(--danger);
        font-size: 0.84rem;
        font-weight: 800;
        line-height: 1.8;
        text-align: start;
    }

    .error-box.show {
        display: block;
    }

    .form-shell {
        display: grid;
        gap: 18px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .form-group {
        display: grid;
        gap: 8px;
    }

    .form-group-full {
        grid-column: 1 / -1;
    }

    .form-group label {
        font-size: 0.78rem;
        font-weight: 900;
        color: var(--text-soft);
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    html[dir="rtl"] .form-group label {
        text-align: right;
    }

    html[dir="ltr"] .form-group label {
        text-align: left;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        min-height: 54px;
        border-radius: 18px;
        border: 1px solid var(--border);
        background: var(--glass-bg);
        padding: 14px 16px;
        font-size: 0.95rem;
        color: var(--text);
        outline: none;
        transition: .2s ease;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.10);
    }

    html[dir="rtl"] .form-group input,
    html[dir="rtl"] .form-group select,
    html[dir="rtl"] .form-group textarea {
        direction: rtl;
        text-align: right;
    }

    html[dir="ltr"] .form-group input,
    html[dir="ltr"] .form-group select,
    html[dir="ltr"] .form-group textarea {
        direction: ltr;
        text-align: left;
    }

    html[data-theme="light"] .form-group input,
    html[data-theme="light"] .form-group select,
    html[data-theme="light"] .form-group textarea {
        background: rgba(255,255,255,0.22);
    }

    .form-group textarea {
        min-height: 126px;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: rgba(47, 107, 255, 0.34);
        box-shadow: 0 0 0 4px rgba(47, 107, 255, 0.08);
    }

    .field-help {
        color: var(--text-muted);
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.7;
    }

    html[dir="rtl"] .field-help {
        text-align: right;
    }

    html[dir="ltr"] .field-help {
        text-align: left;
    }

    .upload-box {
        border: 1.5px dashed rgba(20, 216, 255, 0.26);
        border-radius: 24px;
        background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
        padding: 20px;
        transition: .25s ease;
    }

    html[data-theme="light"] .upload-box {
        background: linear-gradient(180deg, rgba(255,255,255,0.22), rgba(255,255,255,0.14));
    }

    .upload-box.is-active {
        border-color: rgba(47, 107, 255, 0.40);
        box-shadow: 0 0 0 1px rgba(47, 107, 255, 0.14), 0 16px 36px rgba(47, 107, 255, 0.12);
        transform: translateY(-1px);
    }

    .upload-box-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    html[dir="rtl"] .upload-box-top {
        flex-direction: row-reverse;
    }

    html[dir="ltr"] .upload-box-top {
        flex-direction: row;
    }

    .upload-box-title {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    html[dir="rtl"] .upload-box-title {
        flex-direction: row-reverse;
        text-align: right;
    }

    html[dir="ltr"] .upload-box-title {
        flex-direction: row;
        text-align: left;
    }

    .upload-icon {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(47, 107, 255, 0.16), rgba(20, 216, 255, 0.14));
        border: 1px solid rgba(20, 216, 255, 0.18);
        color: var(--primary);
        font-size: 24px;
        font-weight: 900;
    }

    .upload-meta strong {
        display: block;
        color: var(--text);
        font-size: 1rem;
        font-weight: 900;
    }

    .upload-meta span {
        display: block;
        margin-top: 4px;
        color: var(--text-muted);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .file-input-hidden {
        display: none;
    }

    .upload-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    html[dir="rtl"] .upload-actions {
        flex-direction: row-reverse;
    }

    html[dir="ltr"] .upload-actions {
        flex-direction: row;
    }

    .soft-btn {
        min-height: 46px;
        padding: 0 16px;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.10);
        color: var(--text);
        font-weight: 900;
        cursor: pointer;
        transition: .2s ease;
    }

    html[data-theme="light"] .soft-btn {
        background: rgba(255,255,255,0.18);
    }

    .soft-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0 0 1px rgba(20, 216, 255, 0.14), 0 0 26px rgba(20, 216, 255, 0.08);
    }

    .selected-file {
        margin-top: 12px;
        min-height: 24px;
        color: var(--text);
        font-size: 0.84rem;
        font-weight: 800;
        word-break: break-word;
    }

    html[dir="rtl"] .selected-file {
        text-align: right;
    }

    html[dir="ltr"] .selected-file {
        text-align: left;
    }

    .submit-note {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-radius: 20px;
        background: linear-gradient(180deg, rgba(47, 107, 255, 0.08), rgba(20, 216, 255, 0.04));
        border: 1px solid rgba(20, 216, 255, 0.14);
        color: var(--text-soft);
        font-size: 0.84rem;
        font-weight: 800;
    }

    html[dir="rtl"] .submit-note {
        flex-direction: row-reverse;
        text-align: right;
    }

    html[dir="ltr"] .submit-note {
        flex-direction: row;
        text-align: left;
    }

    .submit-note .dot {
        width: 28px;
        height: 28px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 13px;
        font-weight: 900;
        flex-shrink: 0;
    }

    .submit-main-btn {
        width: 100%;
        min-height: 58px;
        border: none;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        font-size: 0.98rem;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 18px 38px rgba(47, 107, 255, 0.20);
        transition: .2s ease;
    }

    .submit-main-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 22px 42px rgba(47, 107, 255, 0.26);
    }

    .submit-main-btn:disabled {
        opacity: .72;
        cursor: not-allowed;
        box-shadow: none;
    }

    .helper-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .helper-card {
        padding: 18px;
        border-radius: 22px;
        background: linear-gradient(180deg, var(--surface-strong), var(--glass-bg));
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    html[dir="rtl"] .helper-card {
        text-align: right;
    }

    html[dir="ltr"] .helper-card {
        text-align: left;
    }

    .helper-card strong {
        display: block;
        color: var(--text);
        font-size: 0.95rem;
        font-weight: 900;
    }

    .helper-card p {
        margin: 8px 0 0;
        color: var(--text-muted);
        line-height: 1.8;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .upload-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(2, 6, 23, 0.76);
        backdrop-filter: blur(8px);
    }

    .upload-overlay.show {
        display: flex;
    }

    .upload-modal {
        width: 100%;
        max-width: 560px;
        background: linear-gradient(180deg, var(--surface-strong), var(--glass-bg));
        border-radius: 30px;
        padding: 30px;
        box-shadow: 0 40px 120px rgba(2, 6, 23, 0.40);
        border: 1px solid var(--border);
        text-align: center;
        backdrop-filter: blur(18px);
    }

    .upload-modal .spinner-wrap {
        position: relative;
        width: 124px;
        height: 124px;
        margin: 0 auto 20px;
    }

    .upload-spinner {
        width: 124px;
        height: 124px;
        border-radius: 999px;
        border: 9px solid rgba(191, 219, 254, 0.24);
        border-top-color: var(--primary);
        animation: spin 1s linear infinite;
        box-shadow: 0 0 0 1px rgba(20, 216, 255, 0.12), 0 0 34px rgba(20, 216, 255, 0.10);
    }

    .upload-count {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        font-weight: 900;
        color: var(--text);
    }

    .upload-modal h3 {
        margin: 0 0 10px;
        font-size: 1.7rem;
        font-weight: 900;
        color: var(--text);
    }

    .upload-modal p {
        margin: 0 0 18px;
        color: var(--text-muted);
        line-height: 1.9;
        font-weight: 700;
    }

    .upload-status-line {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(47, 107, 255, 0.08);
        color: var(--primary);
        border: 1px solid rgba(20, 216, 255, 0.16);
        font-size: 0.82rem;
        font-weight: 900;
    }

    html[dir="rtl"] .upload-status-line {
        flex-direction: row-reverse;
    }

    html[dir="ltr"] .upload-status-line {
        flex-direction: row;
    }

    .upload-progress-wrap {
        margin-top: 22px;
    }

    html[dir="rtl"] .upload-progress-wrap {
        text-align: right;
    }

    html[dir="ltr"] .upload-progress-wrap {
        text-align: left;
    }

    .upload-progress-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
        color: var(--text);
        font-weight: 900;
        font-size: 0.82rem;
    }

    html[dir="rtl"] .upload-progress-top {
        flex-direction: row-reverse;
    }

    html[dir="ltr"] .upload-progress-top {
        flex-direction: row;
    }

    .upload-progress-bar {
        width: 100%;
        height: 14px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(148, 163, 184, 0.22);
    }

    .upload-progress-bar-fill {
        width: 0%;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        transition: width .2s ease;
        box-shadow: 0 0 24px rgba(20, 216, 255, 0.24);
    }

    .upload-extra {
        margin-top: 14px;
        font-size: 0.76rem;
        color: var(--text-muted);
        font-weight: 800;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 1180px) {
        .submit-grid,
        .helper-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 860px) {
        .language-grid,
        .form-grid,
        .folder-grid {
            grid-template-columns: 1fr;
        }

        .submit-hero,
        .submit-card {
            padding: 22px;
            border-radius: 24px;
        }

        .submit-hero-title {
            font-size: clamp(2rem, 10vw, 3.6rem);
        }

        .submit-hero-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .upload-box-top,
        .upload-box-title,
        .upload-actions,
        .privacy-box,
        .submit-note,
        .upload-progress-top {
            align-items: stretch;
        }
    }
</style>

<div class="submit-page">
    <div class="container submit-stack">
        <section class="submit-hero">
            <div class="submit-hero-wrap fade-up">
                <span class="badge badge-secondary submit-hero-badge">
                    {{ __('frontend.submit_page.hero.badge') }}
                </span>

                <h1 class="submit-hero-title text-gradient">
                    {{ __('frontend.submit_page.hero.title') }}
                </h1>

                <p class="submit-hero-text">
                    {{ __('frontend.submit_page.hero.description') }}
                </p>

                <div class="submit-hero-actions">
                    <button type="button" class="btn btn-primary btn-lg" id="heroStartBtn">
                        🚀 {{ __('frontend.submit_page.hero.start_submission') }}
                    </button>

                    <button type="button" class="btn btn-outline btn-lg" id="heroGuideBtn">
                        {{ __('frontend.submit_page.hero.view_requirements') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="submit-grid">
            <div class="submit-card" id="guideSection">
                <div class="submit-card-head">
                    <span class="submit-eyebrow">{{ __('frontend.submit_page.guide.eyebrow') }}</span>
                    <h2>{{ __('frontend.submit_page.guide.title') }}</h2>
                    <p>{{ __('frontend.submit_page.guide.description') }}</p>
                </div>

                <div class="language-grid" id="projectTypeGrid">
                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="php" checked>
                        <div class="language-card-ui">
                            <span class="lang-icon">🐘</span>
                            <strong>{{ __('frontend.submit_page.languages.php.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.php.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="javascript">
                        <div class="language-card-ui">
                            <span class="lang-icon">🟨</span>
                            <strong>{{ __('frontend.submit_page.languages.javascript.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.javascript.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="typescript">
                        <div class="language-card-ui">
                            <span class="lang-icon">🔷</span>
                            <strong>{{ __('frontend.submit_page.languages.typescript.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.typescript.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="python">
                        <div class="language-card-ui">
                            <span class="lang-icon">🐍</span>
                            <strong>{{ __('frontend.submit_page.languages.python.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.python.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="java">
                        <div class="language-card-ui">
                            <span class="lang-icon">☕</span>
                            <strong>{{ __('frontend.submit_page.languages.java.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.java.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="c">
                        <div class="language-card-ui">
                            <span class="lang-icon">🧠</span>
                            <strong>{{ __('frontend.submit_page.languages.c.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.c.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="cpp">
                        <div class="language-card-ui">
                            <span class="lang-icon">⚙️</span>
                            <strong>{{ __('frontend.submit_page.languages.cpp.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.cpp.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="csharp">
                        <div class="language-card-ui">
                            <span class="lang-icon">🧩</span>
                            <strong>{{ __('frontend.submit_page.languages.csharp.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.csharp.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="go">
                        <div class="language-card-ui">
                            <span class="lang-icon">🐹</span>
                            <strong>{{ __('frontend.submit_page.languages.go.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.go.description') }}</span>
                        </div>
                    </label>

                    <label class="language-card">
                        <input type="radio" name="project_type_ui" value="dart">
                        <div class="language-card-ui">
                            <span class="lang-icon">📱</span>
                            <strong>{{ __('frontend.submit_page.languages.dart.title') }}</strong>
                            <span>{{ __('frontend.submit_page.languages.dart.description') }}</span>
                        </div>
                    </label>
                </div>

                <div class="scope-box">
                    <div>
                        <strong>{{ __('frontend.submit_page.guide.required_folders_title') }}</strong>
                        <p id="recommendedDescription">
                            {{ __('frontend.submit_page.guide.required_folders_description') }}
                        </p>
                    </div>

                    <div class="folder-grid" id="recommendedFoldersGrid"></div>
                </div>
            </div>

            <div class="submit-card" id="submitSection">
                <div class="submit-card-head">
                    <span class="submit-eyebrow">{{ __('frontend.submit_page.upload.eyebrow') }}</span>
                    <h2>{{ __('frontend.submit_page.upload.title') }}</h2>
                    <p>{{ __('frontend.submit_page.upload.description') }}</p>
                </div>

                <div class="error-box @if($errors->any()) show @endif" id="uploadErrorBox">
                    @if($errors->any())
                        {{ $errors->first() }}
                    @endif
                </div>

                <form
                    action="{{ route('frontend.submit.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    id="projectUploadForm"
                    class="form-shell"
                >
                    @csrf

                    @if($isIntegratedRequest)
                        <input type="hidden" name="platform_project_id" value="{{ $prefillPlatformProjectId }}">
                        <input type="hidden" name="callback_url" value="http://127.0.0.1:8001/api/scanner/callback">
                    @endif

                    @if($isIntegratedRequest)
                        <div class="privacy-box">
                            <span class="icon">🔗</span>
                            <div>
                                <strong>{{ __('frontend.submit_page.integration.imported_title') }}</strong>
                                <p>{{ __('frontend.submit_page.integration.imported_description') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="privacy-box">
                            <span class="icon">🔒</span>
                            <div>
                                <strong>{{ __('frontend.submit_page.integration.privacy_title') }}</strong>
                                <p>{{ __('frontend.submit_page.integration.privacy_description') }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="project_name">{{ __('frontend.submit_page.form.project_name') }}</label>
                            <input
                                id="project_name"
                                type="text"
                                name="project_name"
                                value="{{ $prefillProjectName }}"
                                required
                                @readonly($isIntegratedRequest)
                            >
                        </div>

                        <div class="form-group">
                            <label for="student_name">{{ __('frontend.submit_page.form.student_name') }}</label>
                            <input
                                id="student_name"
                                type="text"
                                name="student_name"
                                value="{{ $prefillStudentName }}"
                                @readonly($isIntegratedRequest)
                            >
                        </div>

                        <div class="form-group">
                            <label for="student_email">{{ __('frontend.submit_page.form.student_email') }}</label>
                            <input
                                id="student_email"
                                type="email"
                                name="student_email"
                                value="{{ $prefillStudentEmail }}"
                                @readonly($isIntegratedRequest)
                            >
                        </div>

                        <div class="form-group">
                            <label for="language">{{ __('frontend.submit_page.form.primary_language') }}</label>
                            @if($isIntegratedRequest)
                                <select id="language" disabled>
                                    <option value="">{{ __('frontend.submit_page.form.auto_not_specified') }}</option>
                                    <option value="php" @selected($prefillLanguage === 'php')>PHP</option>
                                    <option value="javascript" @selected($prefillLanguage === 'javascript')>JavaScript</option>
                                    <option value="typescript" @selected($prefillLanguage === 'typescript')>TypeScript</option>
                                    <option value="python" @selected($prefillLanguage === 'python')>Python</option>
                                    <option value="java" @selected($prefillLanguage === 'java')>Java</option>
                                    <option value="c" @selected($prefillLanguage === 'c')>C</option>
                                    <option value="cpp" @selected($prefillLanguage === 'cpp')>C++</option>
                                    <option value="csharp" @selected($prefillLanguage === 'csharp')>C#</option>
                                    <option value="go" @selected($prefillLanguage === 'go')>Go</option>
                                    <option value="dart" @selected($prefillLanguage === 'dart')>Dart</option>
                                </select>
                                <input type="hidden" name="language" value="{{ $prefillLanguage }}">
                            @else
                                <select id="language" name="language">
                                    <option value="">{{ __('frontend.submit_page.form.auto_not_specified') }}</option>
                                    <option value="php" @selected(old('language') === 'php')>PHP</option>
                                    <option value="javascript" @selected(old('language') === 'javascript')>JavaScript</option>
                                    <option value="typescript" @selected(old('language') === 'typescript')>TypeScript</option>
                                    <option value="python" @selected(old('language') === 'python')>Python</option>
                                    <option value="java" @selected(old('language') === 'java')>Java</option>
                                    <option value="c" @selected(old('language') === 'c')>C</option>
                                    <option value="cpp" @selected(old('language') === 'cpp')>C++</option>
                                    <option value="csharp" @selected(old('language') === 'csharp')>C#</option>
                                    <option value="go" @selected(old('language') === 'go')>Go</option>
                                    <option value="dart" @selected(old('language') === 'dart')>Dart</option>
                                </select>
                            @endif
                        </div>

                        <div class="form-group form-group-full">
                            <label for="description">{{ __('frontend.submit_page.form.project_description') }}</label>
                            <textarea id="description" name="description" rows="5">{{ old('description') }}</textarea>
                            <small class="field-help">
                                {{ __('frontend.submit_page.form.project_description_help') }}
                            </small>
                        </div>

                        <div class="form-group form-group-full">
                            <label for="project_zip">{{ __('frontend.submit_page.upload.package_label') }}</label>

                            <div class="upload-box" id="uploadBox">
                                <div class="upload-box-top">
                                    <div class="upload-box-title">
                                        <span class="upload-icon">📦</span>
                                        <div class="upload-meta">
                                            <strong>{{ __('frontend.submit_page.upload.source_package_title') }}</strong>
                                            <span>{{ __('frontend.submit_page.upload.source_package_hint') }}</span>
                                        </div>
                                    </div>

                                    <div class="upload-actions">
                                        <button type="button" class="soft-btn" id="chooseFileBtn">
                                            {{ __('frontend.submit_page.upload.choose_file') }}
                                        </button>
                                    </div>
                                </div>

                                <input
                                    id="project_zip"
                                    class="file-input-hidden"
                                    type="file"
                                    name="project_zip"
                                    accept=".zip,.rar,application/zip,application/x-rar-compressed,application/vnd.rar"
                                    required
                                >

                                <div class="selected-file" id="selectedFileName">
                                    {{ __('frontend.submit_page.upload.no_file_selected') }}
                                </div>
                            </div>

                            <small class="field-help">
                                {{ __('frontend.submit_page.upload.recommended_format') }}
                            </small>
                        </div>

                        <div class="form-group form-group-full">
                            <div class="submit-note">
                                <span class="dot">i</span>
                                <span>
                                    {{ __('frontend.submit_page.upload.submit_note') }}
                                </span>
                            </div>
                        </div>

                        <div class="form-group form-group-full">
                            <button type="submit" class="submit-main-btn" id="submitBtn">
                                <span>{{ __('frontend.submit_page.upload.submit_button') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="helper-grid">
            <div class="helper-card">
                <strong>{{ __('frontend.submit_page.helpers.step_1_title') }}</strong>
                <p>{{ __('frontend.submit_page.helpers.step_1_text') }}</p>
            </div>

            <div class="helper-card">
                <strong>{{ __('frontend.submit_page.helpers.step_2_title') }}</strong>
                <p>{{ __('frontend.submit_page.helpers.step_2_text') }}</p>
            </div>

            <div class="helper-card">
                <strong>{{ __('frontend.submit_page.helpers.step_3_title') }}</strong>
                <p>{{ __('frontend.submit_page.helpers.step_3_text') }}</p>
            </div>
        </section>
    </div>
</div>

<div class="upload-overlay" id="uploadOverlay">
    <div class="upload-modal">
        <div class="spinner-wrap">
            <div class="upload-spinner"></div>
            <div class="upload-count" id="uploadCount">0%</div>
        </div>

        <h3>{{ __('frontend.submit_page.overlay.title') }}</h3>
        <p id="uploadMessage">
            {{ __('frontend.submit_page.overlay.message') }}
        </p>

        <div class="upload-status-line" id="uploadStatusLine">
            {{ __('frontend.submit_page.overlay.initializing') }}
        </div>

        <div class="upload-progress-wrap">
            <div class="upload-progress-top">
                <span>{{ __('frontend.submit_page.overlay.progress_label') }}</span>
                <span id="uploadPercentText">0%</span>
            </div>

            <div class="upload-progress-bar">
                <div class="upload-progress-bar-fill" id="uploadProgressBarFill"></div>
            </div>
        </div>

        <div class="upload-extra" id="uploadExtraText">
            {{ __('frontend.submit_page.overlay.extra') }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const heroStartBtn = document.getElementById('heroStartBtn');
        const heroGuideBtn = document.getElementById('heroGuideBtn');
        const submitSection = document.getElementById('submitSection');
        const guideSection = document.getElementById('guideSection');

        const form = document.getElementById('projectUploadForm');
        const fileInput = document.getElementById('project_zip');
        const chooseFileBtn = document.getElementById('chooseFileBtn');
        const selectedFileName = document.getElementById('selectedFileName');
        const uploadBox = document.getElementById('uploadBox');
        const submitBtn = document.getElementById('submitBtn');
        const errorBox = document.getElementById('uploadErrorBox');

        const overlay = document.getElementById('uploadOverlay');
        const uploadCount = document.getElementById('uploadCount');
        const uploadProgressBarFill = document.getElementById('uploadProgressBarFill');
        const uploadPercentText = document.getElementById('uploadPercentText');
        const uploadStatusLine = document.getElementById('uploadStatusLine');
        const uploadMessage = document.getElementById('uploadMessage');
        const uploadExtraText = document.getElementById('uploadExtraText');

        const recommendedFoldersGrid = document.getElementById('recommendedFoldersGrid');
        const recommendedDescription = document.getElementById('recommendedDescription');
        const projectTypeInputs = document.querySelectorAll('input[name="project_type_ui"]');
        const languageSelect = document.getElementById('language');

        const t = {
            noFileSelected: @json(__('frontend.submit_page.upload.no_file_selected')),
            selectedPackage: @json(__('frontend.submit_page.upload.selected_package')),
            uploadingButton: @json(__('frontend.submit_page.upload.uploading_button')),
            submitButton: @json(__('frontend.submit_page.upload.submit_button')),
            overlayInitializing: @json(__('frontend.submit_page.overlay.initializing')),
            overlayMessage: @json(__('frontend.submit_page.overlay.message')),
            overlayExtra: @json(__('frontend.submit_page.overlay.extra')),
            overlayUploadingSource: @json(__('frontend.submit_page.overlay.uploading_source')),
            overlayTransferProgress: @json(__('frontend.submit_page.overlay.transfer_progress')),
            overlayFinalizing: @json(__('frontend.submit_page.overlay.finalizing')),
            overlayAlmostDone: @json(__('frontend.submit_page.overlay.almost_done')),
            overlayCompleteRedirecting: @json(__('frontend.submit_page.overlay.complete_redirecting')),
            overlaySuccessMessage: @json(__('frontend.submit_page.overlay.success_message')),
            overlaySuccessExtra: @json(__('frontend.submit_page.overlay.success_extra')),
            errorChoosePackageFirst: @json(__('frontend.submit_page.errors.choose_package_first')),
            errorInvalidFileType: @json(__('frontend.submit_page.errors.invalid_file_type')),
            errorNonJsonResponse: @json(__('frontend.submit_page.errors.non_json_response')),
            errorUnexpectedServerResponse: @json(__('frontend.submit_page.errors.unexpected_server_response')),
            errorUploadFailed: @json(__('frontend.submit_page.errors.upload_failed')),
            errorNetwork: @json(__('frontend.submit_page.errors.network_error')),
        };

        const projectTypeConfig = {
            php: {
                language: 'php',
                description: @json(__('frontend.submit_page.languages.php.required_description')),
                folders: ['app/', 'routes/', 'resources/', 'config/', 'database/']
            },
            javascript: {
                language: 'javascript',
                description: @json(__('frontend.submit_page.languages.javascript.required_description')),
                folders: ['src/', 'public/', 'package.json', 'package-lock.json']
            },
            typescript: {
                language: 'typescript',
                description: @json(__('frontend.submit_page.languages.typescript.required_description')),
                folders: ['src/', 'tsconfig.json', 'package.json', 'public/']
            },
            python: {
                language: 'python',
                description: @json(__('frontend.submit_page.languages.python.required_description')),
                folders: ['src/', 'app/', '*.py', 'requirements.txt']
            },
            java: {
                language: 'java',
                description: @json(__('frontend.submit_page.languages.java.required_description')),
                folders: ['src/main/java/', 'src/test/java/', 'pom.xml', 'build.gradle']
            },
            c: {
                language: 'c',
                description: @json(__('frontend.submit_page.languages.c.required_description')),
                folders: ['src/', 'include/', '*.c', '*.h', 'Makefile']
            },
            cpp: {
                language: 'cpp',
                description: @json(__('frontend.submit_page.languages.cpp.required_description')),
                folders: ['src/', 'include/', '*.cpp', '*.hpp', 'CMakeLists.txt']
            },
            csharp: {
                language: 'csharp',
                description: @json(__('frontend.submit_page.languages.csharp.required_description')),
                folders: ['src/', '*.cs', '*.csproj', '*.sln', 'Controllers/']
            },
            go: {
                language: 'go',
                description: @json(__('frontend.submit_page.languages.go.required_description')),
                folders: ['cmd/', 'internal/', 'pkg/', 'go.mod', 'go.sum']
            },
            dart: {
                language: 'dart',
                description: @json(__('frontend.submit_page.languages.dart.required_description')),
                folders: ['lib/', 'pubspec.yaml', 'android/app/src/', 'ios/Runner/']
            }
        };

        function scrollToSection(element) {
            if (!element) return;
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        heroStartBtn?.addEventListener('click', () => scrollToSection(submitSection));
        heroGuideBtn?.addEventListener('click', () => scrollToSection(guideSection));

        function renderRecommendedFolders(type) {
            const config = projectTypeConfig[type] || projectTypeConfig.php;

            recommendedDescription.textContent = config.description;

            recommendedFoldersGrid.innerHTML = config.folders.map(folder => `
                <div class="folder-chip">
                    <span class="check">✓</span>
                    <code>${folder}</code>
                </div>
            `).join('');

            if (config.language && languageSelect && !languageSelect.dataset.userChanged && !languageSelect.disabled) {
                languageSelect.value = config.language;
            }
        }

        languageSelect?.addEventListener('change', function () {
            languageSelect.dataset.userChanged = '1';
        });

        projectTypeInputs.forEach(input => {
            input.addEventListener('change', function () {
                renderRecommendedFolders(this.value);
            });
        });

        const selectedProjectType = document.querySelector('input[name="project_type_ui"]:checked')?.value || 'php';
        renderRecommendedFolders(selectedProjectType);

        function formatFileSize(bytes) {
            if (!bytes && bytes !== 0) return '';
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;

            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }

            return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
        }

        function replacePlaceholders(template, replacements = {}) {
            let result = template;
            Object.keys(replacements).forEach((key) => {
                result = result.replace(`:${key}`, replacements[key]);
            });
            return result;
        }

        function setError(message) {
            if (!errorBox) return;
            errorBox.textContent = message;
            errorBox.classList.add('show');
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.classList.remove('show');
        }

        function updateSelectedFile() {
            const file = fileInput.files?.[0];

            if (!file) {
                selectedFileName.textContent = t.noFileSelected;
                uploadBox.classList.remove('is-active');
                return;
            }

            selectedFileName.textContent = replacePlaceholders(t.selectedPackage, {
                name: file.name,
                size: formatFileSize(file.size)
            });
            uploadBox.classList.add('is-active');
        }

        function showOverlay() {
            overlay.classList.add('show');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span>${t.uploadingButton}</span>`;
            uploadCount.textContent = '0%';
            uploadPercentText.textContent = '0%';
            uploadProgressBarFill.style.width = '0%';
            uploadStatusLine.textContent = t.overlayInitializing;
            uploadMessage.textContent = t.overlayMessage;
            uploadExtraText.textContent = t.overlayExtra;
        }

        function hideOverlay() {
            overlay.classList.remove('show');
            submitBtn.disabled = false;
            submitBtn.innerHTML = `<span>${t.submitButton}</span>`;
        }

        function updateProgress(percent) {
            const safePercent = Math.max(0, Math.min(100, Math.round(percent)));
            uploadCount.textContent = `${safePercent}%`;
            uploadPercentText.textContent = `${safePercent}%`;
            uploadProgressBarFill.style.width = `${safePercent}%`;

            if (safePercent < 25) {
                uploadStatusLine.textContent = t.overlayUploadingSource;
            } else if (safePercent < 55) {
                uploadStatusLine.textContent = t.overlayTransferProgress;
            } else if (safePercent < 80) {
                uploadStatusLine.textContent = t.overlayFinalizing;
            } else if (safePercent < 100) {
                uploadStatusLine.textContent = t.overlayAlmostDone;
            } else {
                uploadStatusLine.textContent = t.overlayCompleteRedirecting;
            }
        }

        chooseFileBtn?.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput?.addEventListener('change', function () {
            clearError();
            updateSelectedFile();
        });

        uploadBox?.addEventListener('dragover', function (event) {
            event.preventDefault();
            uploadBox.classList.add('is-active');
        });

        uploadBox?.addEventListener('dragleave', function () {
            if (!fileInput.files?.length) {
                uploadBox.classList.remove('is-active');
            }
        });

        uploadBox?.addEventListener('drop', function (event) {
            event.preventDefault();
            clearError();

            const files = event.dataTransfer?.files;
            if (!files || !files.length) return;

            fileInput.files = files;
            updateSelectedFile();
        });

        form?.addEventListener('submit', function (event) {
            event.preventDefault();
            clearError();

            const file = fileInput.files?.[0];

            if (!file) {
                setError(t.errorChoosePackageFirst);
                uploadBox.classList.remove('is-active');
                return;
            }

            const fileName = (file.name || '').toLowerCase();
            const isValidExtension = fileName.endsWith('.zip') || fileName.endsWith('.rar');

            if (!isValidExtension) {
                setError(t.errorInvalidFileType);
                uploadBox.classList.remove('is-active');
                return;
            }

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            showOverlay();

            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    updateProgress(percent);
                }
            });

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;

                let response = null;
                const contentType = xhr.getResponseHeader('Content-Type') || '';

                try {
                    if (contentType.includes('application/json')) {
                        response = JSON.parse(xhr.responseText);
                    } else {
                        console.error('Non-JSON response:', xhr.responseText);
                        hideOverlay();
                        setError(t.errorNonJsonResponse);
                        return;
                    }
                } catch (error) {
                    console.error('JSON parse error:', xhr.responseText);
                    hideOverlay();
                    setError(t.errorUnexpectedServerResponse);
                    return;
                }

                if (xhr.status >= 200 && xhr.status < 300 && response?.ok) {
                    updateProgress(100);
                    uploadMessage.textContent = t.overlaySuccessMessage;
                    uploadExtraText.textContent = t.overlaySuccessExtra;
                    setTimeout(() => {
                        window.location.href = response.redirect_url;
                    }, 900);
                    return;
                }

                hideOverlay();

                if (response?.errors) {
                    const firstErrorKey = Object.keys(response.errors)[0];
                    const firstError = response.errors[firstErrorKey]?.[0] || t.errorUploadFailed;
                    setError(firstError);
                    return;
                }

                setError(response?.message || t.errorUploadFailed);
            };

            xhr.onerror = function () {
                hideOverlay();
                setError(t.errorNetwork);
            };

            xhr.send(formData);
        });
    });
</script>
@endsection