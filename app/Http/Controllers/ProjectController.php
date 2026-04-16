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
            ->select('*', DB::raw('ST_AsGeoJSON(boundary) as boundary'))
            ->latest()
            ->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects
        ]);
    }

    public function create()
    {
        return Inertia::render('Projects/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'boundary' => 'required', // Should be GeoJSON string
        ]);

        return DB::transaction(function () use ($request) {
            $project = Project::create([
                'name' => $request->name,
                'description' => $request->description,
                'user_id' => auth()->id(),
            ]);

            // Convert GeoJSON to MySQL Geometry
            if ($request->boundary) {
                // Ensure it's a valid GeoJSON string
                $boundary = $request->boundary;
                
                // Level 3: Server-side Geometry Optimization (Simplification)
                // ST_Simplify ensures the geometry stays clean and fast for rendering
                DB::statement("UPDATE projects SET boundary = ST_Simplify(ST_GeomFromGeoJSON(?), 0.00001) WHERE id = ?", [
                    $boundary,
                    $project->id
                ]);
            }

            return redirect()->route('projects.index')->with('success', 'Project created successfully.');
        });
    }
}
