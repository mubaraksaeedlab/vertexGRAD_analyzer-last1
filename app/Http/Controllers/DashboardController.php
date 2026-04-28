<?php

namespace App\Http\Controllers;

use App\Modules\Projects\Models\Project;
use App\Modules\Analysis\Models\AnalysisRun;
use App\Modules\Reports\Models\Report;
use App\Modules\Analysis\Models\Issue;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProjects = Project::count();
        $totalRuns = AnalysisRun::count();
        $totalReports = Report::count();
        $totalIssues = Issue::count();

        $latestProjects = Project::latest()->take(5)->get();
        $latestRuns = AnalysisRun::latest()->take(5)->get();

        return view('admin.dashboard.index', compact(
            'totalProjects',
            'totalRuns',
            'totalReports',
            'totalIssues',
            'latestProjects',
            'latestRuns'
        ));
    }
}