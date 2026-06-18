<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function generate(Survey $survey, Request $request)
    {
        // Support for dynamic inclusions
        $options = [
            'include_images' => $request->boolean('images', true),
            'include_map' => $request->boolean('map', true),
            'include_approvals' => $request->boolean('approvals', true),
            'map_image' => $request->input('map_image', null),
        ];

        // Fetch survey with spatial coordinates and all required relationships
        $survey = Survey::with(['project', 'user', 'images', 'approvals.user'])
            ->select('surveys.*')
            ->join('survey_details', 'surveys.id', '=', 'survey_details.survey_id')
            ->addSelect(\Illuminate\Support\Facades\DB::raw('ST_Y(survey_details.location) as lat, ST_X(survey_details.location) as lng'))
            ->where('surveys.id', $survey->id)
            ->first();
        
        $pdf = Pdf::loadView('reports.survey', [
            'survey' => $survey,
            'options' => $options
        ]);

        return $pdf->download("GSS_Audit_{$survey->id}.pdf");
    }

    public function monthly(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Support for dynamic inclusions
        $options = [
            'include_trends' => $request->boolean('trends', true),
            'include_distribution' => $request->boolean('distribution', true),
            'include_registry' => $request->boolean('registry', true),
        ];

        $surveys = Survey::with(['project', 'user', 'images'])
            ->whereMonth('survey_date', $month)
            ->whereYear('survey_date', $year)
            ->get();

        // Fetch DAILY trends for the EXACT chosen month (High Resolution Velocity)
        $trends = Survey::whereMonth('survey_date', $month)
            ->whereYear('survey_date', $year)
            ->select(
                \Illuminate\Support\Facades\DB::raw('DATE_FORMAT(survey_date, "%d-%m") as day'),
                \Illuminate\Support\Facades\DB::raw('count(*) as count')
            )
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get();

        $stats = [
            'total' => $surveys->count(),
            'approved' => $surveys->where('status', 'approved')->count(),
            'rejected' => $surveys->where('status', 'rejected')->count(),
            'pending' => $surveys->where('status', 'pending')->count(),
            'by_project' => $surveys->groupBy('project_id')->map(fn($group) => $group->count()),
            'projects' => \App\Models\Project::whereIn('id', $surveys->pluck('project_id'))->get(),
            'trends' => $trends
        ];

        $pdf = Pdf::loadView('reports.monthly', [
            'stats' => $stats,
            'month_name' => date("F Y", mktime(0, 0, 0, $month, 10, $year)),
            'surveys' => $surveys,
            'charts' => $this->prepareCharts($trends, $stats),
            'options' => $options
        ]);

        return $pdf->download("GSS_Monthly_Report_{$year}_{$month}.pdf");
    }

    private function prepareCharts($trends, $stats)
    {
        $lineConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $trends->pluck('day')->toArray(),
                'datasets' => [[
                    'label' => 'Submitted Surveys',
                    'data' => $trends->pluck('count')->toArray(),
                    'borderColor' => '#0d9488',
                    'backgroundColor' => 'rgba(13, 148, 136, 0.1)',
                    'fill' => true, 'tension' => 0.4, 'pointRadius' => 4,
                    'datalabels' => [
                        'align' => 'top',
                        'anchor' => 'end',
                        'backgroundColor' => '#0d9488',
                        'borderRadius' => 4,
                        'color' => '#ffffff',
                        'font' => ['weight' => 'bold', 'size' => 8],
                        'offset' => 4,
                        'display' => true
                    ]
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => false],
                    'datalabels' => ['display' => true]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true, 
                        'ticks' => ['fontSize' => 8, 'stepSize' => 1],
                        'gridLines' => ['color' => '#f1f5f9']
                    ],
                    'x' => ['ticks' => ['fontSize' => 8]]
                ]
            ]
        ];

        $donutConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['APPROVED', 'PENDING', 'REJECTED'],
                'datasets' => [[
                    'data' => [$stats['approved'], $stats['pending'], $stats['rejected']],
                    'backgroundColor' => ['#0d9488', '#fbbf24', '#ef4444'],
                    'borderWidth' => 0
                ]]
            ],
            'options' => [
                'cutout' => '65%',
                'plugins' => [
                    'legend' => ['position' => 'bottom', 'labels' => ['fontSize' => 8, 'fontStyle' => 'bold']],
                    'datalabels' => ['display' => false]
                ]
            ]
        ];

        $lineUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($lineConfig)) . "&w=400&h=200";
        $donutUrl = "https://quickchart.io/chart?c=" . urlencode(json_encode($donutConfig)) . "&w=250&h=200";

        try {
            // Include datalabels plugin in URL
            $lineUrl .= "&scripts=datalabels";
            
            $lineImg = base64_encode(file_get_contents($lineUrl));
            $donutImg = base64_encode(file_get_contents($donutUrl));
            return [
                'line' => "data:image/png;base64,{$lineImg}",
                'donut' => "data:image/png;base64,{$donutImg}"
            ];
        } catch (\Exception $e) {
            return ['line' => null, 'donut' => null];
        }
    }
}
