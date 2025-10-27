<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\Document;
use App\Models\VisitorLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index()
    {
        VisitorLog::logVisit('admin/dashboard');

        $statistics = [
            'total_visitors' => VisitorLog::whereDate('created_at', '>=', Carbon::now()->subDays(30))->count(),
            'total_publications' => Document::where('type', 'publication')->count(),
            'total_brs' => Document::where('type', 'brs')->count(),
            'total_audio_files' => Document::whereNotNull('mp3_content')->count(),
            'pending_conversions' => Document::where('status', 'pending')->count(),
            'failed_conversions' => Document::where('status', 'failed')->count(),
        ];

        $monthlyVisitors = VisitorLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $popularDocuments = Document::with('indicator')
            ->orderBy('download_count', 'desc')
            ->limit(10)
            ->get();

        $recentUploads = Document::with(['indicator', 'creator'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'statistics',
            'monthlyVisitors',
            'popularDocuments',
            'recentUploads'
        ));
    }
}
