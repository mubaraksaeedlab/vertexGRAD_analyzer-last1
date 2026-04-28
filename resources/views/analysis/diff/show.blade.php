@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 mb-2">Analysis Comparison</h1>
        <p class="text-muted mb-0">
            Project ID: {{ $diff->project_id }} |
            Old Run: {{ $diff->old_run_id }} |
            New Run: {{ $diff->new_run_id }}
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Old Total</div>
                    <div class="fs-3 fw-bold">{{ $diff->old_total_issues }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">New Total</div>
                    <div class="fs-3 fw-bold">{{ $diff->new_total_issues }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <div class="text-muted small">Resolved</div>
                    <div class="fs-3 fw-bold text-success">{{ $diff->resolved_count }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <div class="text-muted small">Existing</div>
                    <div class="fs-3 fw-bold text-primary">{{ $diff->existing_count }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    <div class="text-muted small">New Issues</div>
                    <div class="fs-3 fw-bold text-danger">{{ $diff->new_count }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Scope</div>
                    <div class="fw-bold">{{ $diff->comparison_scope }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold text-success">Resolved Rules</div>
                <div class="card-body">
                    @forelse($diff->resolved_rules ?? [] as $rule)
                        <div class="badge bg-success-subtle text-success-emphasis border mb-2">{{ $rule }}</div>
                    @empty
                        <p class="text-muted mb-0">No resolved rules.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold text-primary">Existing Rules</div>
                <div class="card-body">
                    @forelse($diff->existing_rules ?? [] as $rule)
                        <div class="badge bg-primary-subtle text-primary-emphasis border mb-2">{{ $rule }}</div>
                    @empty
                        <p class="text-muted mb-0">No existing rules.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold text-danger">New Rules</div>
                <div class="card-body">
                    @forelse($diff->new_rules ?? [] as $rule)
                        <div class="badge bg-danger-subtle text-danger-emphasis border mb-2">{{ $rule }}</div>
                    @empty
                        <p class="text-muted mb-0">No new rules.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection