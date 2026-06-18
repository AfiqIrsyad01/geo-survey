<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test project with a Polygon boundary (Kuala Lumpur area approx)
        $project = Project::create([
            'name' => 'Kuala Lumpur Central Zone',
            'description' => 'Main urban survey area for infrastructure audit.',
        ]);

        // GeoJSON for a small square in KL
        $geojson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [101.6800, 3.1350],
                [101.6900, 3.1350],
                [101.6900, 3.1450],
                [101.6800, 3.1450],
                [101.6800, 3.1350]
            ]]
        ]);

        DB::statement("UPDATE projects SET boundary = ST_GeomFromGeoJSON(?) WHERE id = ?", [
            $geojson,
            $project->id
        ]);
        
        Project::create([
            'name' => 'Petaling Jaya Satellite Office',
            'description' => 'Secondary zone for residential survey.',
        ]);
    }
}
