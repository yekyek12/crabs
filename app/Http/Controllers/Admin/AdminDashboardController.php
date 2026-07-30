<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecognitionFeedback;
use App\Models\RecognitionRecord;
use App\Models\User;
use App\Services\AiServiceHealthService;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(AiServiceHealthService $health)
    {
        $recognitions = RecognitionRecord::query();
        $reviewed = RecognitionRecord::whereNotNull('expert_species_id')->count();
        $matchingExpert = RecognitionRecord::whereColumn('crab_species_id', 'expert_species_id')->whereNotNull('expert_species_id')->count();

        return view('admin.dashboard', [
            'users' => User::count(),
            'activeUsers' => User::where('account_status', 'active')->count(),
            'suspendedUsers' => User::where('account_status', 'suspended')->count(),
            'adminUsers' => User::where('role', 'admin')->count(),
            'recognitions' => (clone $recognitions)->count(),
            'today' => RecognitionRecord::whereDate('created_at', today())->count(),
            'month' => RecognitionRecord::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'avgConfidence' => RecognitionRecord::avg('confidence'),
            'avgTime' => RecognitionRecord::avg('processing_time_ms'),
            'low' => RecognitionRecord::where('confidence_level', 'low')->count(),
            'failed' => RecognitionRecord::where('recognition_status', 'failed')->count(),
            'feedback' => RecognitionFeedback::where('status', 'open')->count(),
            'trainingCandidates' => RecognitionRecord::where('needs_retraining', true)->count(),
            'reviewedAccuracy' => $reviewed > 0 ? $matchingExpert / $reviewed : null,
            'topSpecies' => RecognitionRecord::query()
                ->select('crab_species_id', DB::raw('count(*) as scans'), DB::raw('avg(confidence) as avg_confidence'))
                ->with('species')
                ->whereNotNull('crab_species_id')
                ->groupBy('crab_species_id')
                ->orderByDesc('scans')
                ->limit(5)
                ->get(),
            'recentFeedback' => RecognitionFeedback::with(['recognitionRecord.species', 'user'])->latest()->limit(5)->get(),
            'recentScans' => RecognitionRecord::with(['user', 'species'])->latest()->limit(5)->get(),
            'aiStatus' => $health->status(),
            'aiStatusDetail' => $health->detail(),
        ]);
    }
}
