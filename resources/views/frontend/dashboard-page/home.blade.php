@extends('frontend.layouts.frontend-layout')

@section('title', __('frontend.analyzer.home.meta.title'))

@section('content')

{{-- HERO --}}
<section class="hero section-lg">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-centered-wrap fade-up">

                <h1 class="hero-title text-gradient">
                    {{ __('frontend.analyzer.home.hero.title_line_1') }}
                    <br>
                    <span class="text-glow">
                        {{ __('frontend.analyzer.home.hero.title_line_2') }}
                    </span>
                </h1>

                <p class="hero-text">
                    {{ __('frontend.analyzer.home.hero.description') }}
                </p>

                <div class="hero-actions">
                    <a href="#start" class="btn btn-primary btn-lg">
                        🚀 {{ __('frontend.analyzer.home.hero.start_analysis') }}
                    </a>

                    <a href="#features" class="btn btn-outline btn-lg">
                        {{ __('frontend.analyzer.home.hero.explore_platform') }}
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

{{-- FEATURES --}}
<section id="features" class="section">
    <div class="container">

        <div class="section-head">
            <div>
                <h2 class="section-title">
                    {{ __('frontend.analyzer.home.features.title') }}
                </h2>

                <p class="section-subtitle text-soft">
                    {{ __('frontend.analyzer.home.features.subtitle') }}
                </p>
            </div>
        </div>

        <div class="grid grid-auto">

            @php
                $features = [
                    'smart_analysis',
                    'intelligent_scoring',
                    'supervisor_review',
                    'project_pipeline',
                    'smart_reports',
                    'investor_ready',
                ];
            @endphp

            @foreach ($features as $feature)
                <div class="card fade-up">
                    <div class="card-title">
                        {{ __('frontend.analyzer.home.features.items.' . $feature . '.title') }}
                    </div>
                    <p class="text-sm">
                        {{ __('frontend.analyzer.home.features.items.' . $feature . '.description') }}
                    </p>
                </div>
            @endforeach

        </div>
    </div>
</section>

{{-- PROCESS --}}
<section id="analysis" class="section-sm">
    <div class="container">

        <div class="section-head">
            <h2 class="section-title">
                {{ __('frontend.analyzer.home.process.title') }}
            </h2>
        </div>

        <div class="grid grid-3">

            @php
                $steps = ['upload', 'analyze', 'review'];
            @endphp

            @foreach ($steps as $index => $step)
                <div class="card fade-up">
                    <div class="card-title">
                        {{ ($index + 1) . '. ' . __('frontend.analyzer.home.process.steps.' . $step . '.title') }}
                    </div>
                    <p class="text-sm">
                        {{ __('frontend.analyzer.home.process.steps.' . $step . '.description') }}
                    </p>
                </div>
            @endforeach

        </div>
    </div>
</section>

{{-- CTA --}}
<section id="start" class="section">
    <div class="container">

        <div class="panel text-center fade-up">

            <h2 class="text-gradient mb-4">
                {{ __('frontend.analyzer.home.cta.title') }}
            </h2>

            <p class="text-soft mb-6">
                {{ __('frontend.analyzer.home.cta.description') }}
            </p>

            <a href="#" class="btn btn-primary btn-lg">
                🚀 {{ __('frontend.analyzer.home.cta.button') }}
            </a>

        </div>

    </div>
</section>

@endsection