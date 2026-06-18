<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('user')
            ->select('*', DB::raw('ST_AsGeoJSON(boundary) as boundary_geojson'))
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects
        ]);
    }

    public function create()
    {
        $staff = \App\Models\User::where('role', 'staff')
            ->where('is_active', true)
            ->get()
            ->map(function($user) {
                // Get most recent survey location for this user
                $latestLocation = \App\Models\Survey::where('user_id', $user->id)
                    ->join('survey_details', 'surveys.id', '=', 'survey_details.survey_id')
                    ->select(DB::raw('ST_Y(location) as lat, ST_X(location) as lng'))
                    ->latest('surveys.created_at')
                    ->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_location' => $latestLocation ? ['lat' => $latestLocation->lat, 'lng' => $latestLocation->lng] : null
                ];
            });

        return Inertia::render('Projects/Create', [
            'staff' => $staff
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'boundary' => 'required', // Should be GeoJSON string
            'assigned_staff' => 'nullable|array',
            'deadline_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $description = $request->description;
            
            // Level 3: persist assignments within the existing schema (Description Prefix)
            if ($request->assigned_staff && count($request->assigned_staff) > 0) {
                $staffNames = \App\Models\User::whereIn('id', $request->assigned_staff)->pluck('name')->toArray();
                $description = "UNIT ASSIGNMENT: [" . implode(' | ', $staffNames) . "]\n\n" . ($description ?? 'Operational goals pending.');
            }

            $project = Project::create([
                'name' => $request->name,
                'description' => $description,
                'deadline_date' => $request->deadline_date,
                'cost' => $request->cost,
                'user_id' => auth()->id(),
            ]);

            // Convert GeoJSON to MySQL Geometry
            if ($request->boundary) {
                // Ensure it's a valid GeoJSON string
                $boundary = $request->boundary;
                
                // Level 3: Server-side Geometry Integration
                // Directly convert GeoJSON to MySQL Geometry
                DB::statement("UPDATE projects SET boundary = ST_GeomFromGeoJSON(?) WHERE id = ?", [
                    $boundary,
                    $project->id
                ]);
            }

            return redirect()->route('projects.index')->with('success', 'Project created successfully.');
        });
    }

    public function edit(Project $project)
    {
        $projectData = Project::select('*', DB::raw('ST_AsGeoJSON(boundary) as boundary_geojson'))
            ->where('id', $project->id)
            ->first();

        $staff = \App\Models\User::where('role', 'staff')
            ->where('is_active', true)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });

        return Inertia::render('Projects/Edit', [
            'project' => $projectData,
            'staff' => $staff
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'boundary' => 'nullable',
            'assigned_staff' => 'nullable|array',
            'deadline_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $project) {
            $description = $request->description;
            
            // Mirroring the assignment logic from store() for consistency
            if ($request->assigned_staff && count($request->assigned_staff) > 0) {
                // Remove existing assignments if any were already in the description to prevent duplication
                $cleanDescription = preg_replace('/UNIT ASSIGNMENT: \[.*?\]\n\n/', '', $description ?? '');
                
                $staffNames = \App\Models\User::whereIn('id', $request->assigned_staff)->pluck('name')->toArray();
                $description = "UNIT ASSIGNMENT: [" . implode(' | ', $staffNames) . "]\n\n" . ($cleanDescription ?: 'Operational goals pending.');
            }

            $project->update([
                'name' => $request->name,
                'description' => $description,
                'deadline_date' => $request->deadline_date,
                'cost' => $request->cost,
            ]);

            if ($request->boundary) {
                DB::statement("UPDATE projects SET boundary = ST_GeomFromGeoJSON(?) WHERE id = ?", [
                    $request->boundary,
                    $project->id
                ]);
            }
        });

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
