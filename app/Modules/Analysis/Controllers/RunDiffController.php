<?php

namespace App\Modules\Analysis\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analysis\Models\AnalysisRunDiff;

class RunDiffController extends Controller
{
    public function show(int $id)
    {
        $diff = AnalysisRunDiff::with(['project', 'oldRun', 'newRun'])->findOrFail($id);

        return view('analysis.diff.show', compact('diff'));
    }
}