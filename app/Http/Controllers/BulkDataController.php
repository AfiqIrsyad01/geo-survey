<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Project;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class BulkDataController extends Controller
{
    /**
     * Export all surveys to CSV.
     */
    public function exportSurveys()
    {
        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($handle, [
                'ID', 'Project', 'Personnel', 'Status', 'Date', 
                'Latitude', 'Longitude', 'Elevation (ASL)', 'Verification Status'
            ]);

            Survey::with(['project', 'user'])
                ->join('survey_details', 'surveys.id', '=', 'survey_details.survey_id')
                ->select('surveys.*', 'survey_details.attributes')
                ->selectRaw('ST_Y(survey_details.location) as lat, ST_X(survey_details.location) as lng')
                ->chunk(100, function ($surveys) use ($handle) {
                    foreach ($surveys as $survey) {
                        $attr = json_decode($survey->attributes, true);
                        fputcsv($handle, [
                            "SRV-{$survey->id}",
                            $survey->project->name,
                            $survey->user->name,
                            strtoupper($survey->status),
                            $survey->created_at->format('Y-m-d H:i:s'),
                            $survey->lat,
                            $survey->lng,
                            isset($attr['asl']) ? "{$attr['asl']}m" : 'N/A',
                            $survey->status === 'approved' ? 'VERIFIED' : 'UNVERIFIED'
                        ]);
                    }
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="GSS_Surveys_Bulk_Export_'.date('Ymd').'.csv"');

        return $response;
    }

    /**
     * Export all projects to CSV.
     */
    public function exportProjects()
    {
        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'ID', 'Project Name', 'Description', 'Survey Count', 'Strategic Boundary (GeoJSON)'
            ]);

            // Explicit column selection to avoid hidden attribute issues
            Project::withCount('surveys')
                ->select('id', 'name', 'description')
                ->selectRaw('ST_AsGeoJSON(boundary) as boundary_json')
                ->chunk(100, function ($projects) use ($handle) {
                    foreach ($projects as $project) {
                        fputcsv($handle, [
                            "PRJ-{$project->id}",
                            $project->name,
                            $project->description,
                            $project->surveys_count,
                            $project->boundary_json
                        ]);
                    }
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="GSS_Projects_Registry_'.date('Ymd').'.csv"');

        return $response;
    }

    /**
     * Provide all survey points as a GeoJSON FeatureCollection for Heatmap Analytics.
     */
    public function spatialIntelligence()
    {
        $features = Survey::join('survey_details', 'surveys.id', '=', 'survey_details.survey_id')
            ->select('surveys.id', 'surveys.status', 'surveys.created_at')
            ->selectRaw('ST_AsGeoJSON(survey_details.location) as geometry')
            ->get()
            ->map(function ($survey) {
                return [
                    'type' => 'Feature',
                    'geometry' => json_decode($survey->geometry),
                    'properties' => [
                        'id' => $survey->id,
                        'status' => $survey->status,
                        'weight' => $survey->status === 'approved' ? 1.0 : 0.5,
                        'date' => $survey->created_at->format('Y-m-d')
                    ]
                ];
            });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }
}
