<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified', 'no-cache'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        $stats = [
            'active_projects' => \App\Models\Project::count(),
            'total_surveys' => $user->role === 'staff' 
                ? \App\Models\Survey::where('user_id', $user->id)->count() 
                : \App\Models\Survey::count(),
            'pending_approvals' => \App\Models\Survey::where('status', 'pending')->count(),
            'staff_count' => \App\Models\User::where('role', 'staff')->count(),
            'user_role' => $user->role,
            'user_pending' => \App\Models\Survey::where('user_id', $user->id)->where('status', 'pending')->count(),
        ];

        // Chart Data: Monthly Trends (Last 6 Months)
        $monthlyTrends = \App\Models\Survey::select(
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'),
                \Illuminate\Support\Facades\DB::raw("DATE_FORMAT(survey_date, '%b %Y') as month"),
                \Illuminate\Support\Facades\DB::raw("MONTH(survey_date) as month_val")
            )
            ->where('survey_date', '>=', now()->subMonths(6))
            ->when($user->role === 'staff', function($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->groupBy('month', 'month_val')
            ->orderBy('month_val')
            ->get();

        // Chart Data: Status Breakdown
        $statusBreakdown = \App\Models\Survey::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->when($user->role === 'staff', function($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->groupBy('status')
            ->get();

        $recentActivities = \App\Models\Survey::with(['project', 'user'])
            ->when($user->role === 'staff', function($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->latest()
            ->take(5)
            ->get();

        $projects_spatial = \App\Models\Project::select('id', 'name')
            ->selectRaw('ST_AsGeoJSON(boundary) as boundary_json')
            ->get()
            ->map(function($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'boundary' => json_decode($project->boundary_json)
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recent_activities' => $recentActivities,
            'chart_data' => [
                'monthly' => $monthlyTrends,
                'status' => $statusBreakdown
            ],
            'projects_spatial' => $projects_spatial,
            'users' => $user->role === 'admin' ? \App\Models\User::take(5)->get() : [],
            'pending_details' => $user->role === 'hod' ? \App\Models\Survey::with(['project', 'user'])->where('status', 'pending')->take(5)->get() : [],
            'unread_notifications' => \App\Models\AppNotification::where('user_id', $user->id)->where('is_read', false)->get()
        ]);
    })->name('dashboard');

    // Shared routes
    Route::resource('surveys', \App\Http\Controllers\SurveyController::class);
    Route::get('/reports/survey/{survey}', [\App\Http\Controllers\ReportController::class, 'generate'])->name('reports.survey');
    Route::get('/reports/monthly', [\App\Http\Controllers\ReportController::class, 'monthly'])->name('reports.monthly');

    // Admin Only
    Route::middleware('role:admin')->group(function () {
        Route::resource('projects', \App\Http\Controllers\ProjectController::class);
        Route::get('/reports', function (\Illuminate\Http\Request $request) {
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            // Fetch stats for the reports page filtered by month/year
            $stats = [
                'total_surveys' => \App\Models\Survey::whereMonth('survey_date', $month)->whereYear('survey_date', $year)->count(),
                'status_counts' => \App\Models\Survey::whereMonth('survey_date', $month)->whereYear('survey_date', $year)
                    ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                    ->groupBy('status')->get(),
                'monthly_trends' => \App\Models\Survey::select(
                    \Illuminate\Support\Facades\DB::raw('DATE_FORMAT(survey_date, "%Y-%m") as month'),
                    \Illuminate\Support\Facades\DB::raw('count(*) as count')
                )->groupBy('month')->orderBy('month', 'desc')->take(12)->get(),
                'top_personnel' => \App\Models\User::where('role', 'staff')
                    ->withCount(['surveys' => function($query) use ($month, $year) {
                        $query->whereMonth('survey_date', $month)->whereYear('survey_date', $year);
                    }])
                    ->orderBy('surveys_count', 'desc')
                    ->take(5)
                    ->get(),
                'selected_month' => (int)$month,
                'selected_year' => (int)$year
            ];
            return inertia('Admin/Reports', [
                'stats' => $stats
            ]);
        })->name('admin.reports');
    });

    // HOD Only
    Route::middleware('role:hod')->group(function () {
        Route::post('/surveys/{survey}/approve', [\App\Http\Controllers\ApprovalController::class, 'store'])->name('surveys.approve');
    });

    // Notifications
    Route::patch('/notifications/{notification}/read', function (\App\Models\AppNotification $notification) {
        $notification->update(['is_read' => true]);
        return back();
    })->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
