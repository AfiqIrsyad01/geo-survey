<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Project;
use App\Models\SurveyDetail;
use App\Models\SurveyImage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Survey::with(['project', 'user'])
            ->select('surveys.*', 'users.name as user_name')
            ->join('survey_details', 'surveys.id', '=', 'survey_details.survey_id')
            ->join('users', 'surveys.user_id', '=', 'users.id')
            ->addSelect(DB::raw('ST_AsGeoJSON(survey_details.location) as location'));

        // Role-based filtering
        if ($user->role === 'staff') {
            $query->where('surveys.user_id', $user->id);
        }

        // Optional filter for HOD to see only pending
        if ($request->has('status') && $request->status === 'pending') {
            $query->where('surveys.status', 'pending');
        }

        $surveys = $query->latest()->get();

        return Inertia::render('Surveys/Index', [
            'surveys' => $surveys,
            'filters' => $request->only(['status'])
        ]);
    }

    public function create()
    {
        $projects = Project::select('id', 'name', 'description')
            ->selectRaw('ST_AsGeoJSON(boundary) as boundary_json')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'boundary' => json_decode($project->boundary_json)
                ];
            });

        return Inertia::render('Surveys/Create', [
            'projects' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'asl' => 'nullable|numeric',
            'images.*' => 'image|max:5120',
        ]);

        return DB::transaction(function () use ($request) {
            // Data Redundancy Check: Avoid identical surveys from same user in same minute
            $exists = Survey::where('user_id', auth()->id())
                ->where('project_id', $request->project_id)
                ->whereBetween('created_at', [now()->subMinute(), now()])
                ->exists();

            if ($exists) {
                return redirect()->back()->withErrors(['error' => 'Duplicate submission detected. Please wait a moment.']);
            }

            $survey = Survey::create([
                'project_id' => $request->project_id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'survey_date' => now(),
            ]);

            // Save Location & Intelligence (Spatial POINT + ASL)
            $pointWkt = "POINT({$request->lng} {$request->lat})";
            $attributes = json_encode(['asl' => $request->asl]);
            DB::statement("INSERT INTO survey_details (survey_id, location, attributes, created_at, updated_at) VALUES (?, ST_GeomFromText(?), ?, NOW(), NOW())", [
                $survey->id,
                $pointWkt,
                $attributes
            ]);

            // Handle Images & Watermarking & Integrity Check
            if ($request->hasFile('images')) {
                $manager = new ImageManager(new Driver());

                foreach ($request->file('images') as $imageFile) {
                    $originalPath = $imageFile->getRealPath();
                    $exif = @exif_read_data($originalPath);
                    $exifGps = $this->extractGpsFromExif($exif);

                    $path = $imageFile->store('surveys/images', 'public');
                    $fullPath = storage_path('app/public/' . $path);

                    // Watermark (Enhanced Branding) & Image Optimization
                    $image = $manager->read($fullPath);

                    // CRITICAL SPEED FIX: Scale down huge mobile photos to a max width of 1200px.
                    // This massively slashes memory usage, makes text-rendering instant, and fast saves.
                    $image->scaleDown(width: 1200);

                    $text = "GPS: {$request->lat}, {$request->lng} | " . now()->format('Y-m-d H:i:s') . " | GeoSurvey";

                    $image->text($text, 20, $image->height() - 40, function ($font) {
                        $font->size(36); // Standard size works perfectly on a 1200px image
                        $font->color('#ffffff');
                        $font->stroke('#000000', 2);
                    });

                    // Save with slightly optimized quality to guarantee fast I/O
                    $image->save($fullPath, quality: 80);

                    // Integrity Cross-Check: Verified if Photo is WITHIN the Project Polygon
                    $isVerified = false;
                    $distance = null;
                    $isLocalMode = app()->environment('local');

                    if ($exifGps) {
                        // Calculate literal distance for record-keeping/analytics
                        $distance = $this->calculateDistance($request->lat, $request->lng, $exifGps['lat'], $exifGps['lng']);
                    }

                    if ($isLocalMode) {
                        // LOCAL BYPASS: Use map pinpoint instead of EXIF to declare VERIFIED/MISMATCH
                        $isVerified = DB::table('projects')
                            ->where('id', $survey->project_id)
                            ->whereRaw("ST_Contains(boundary, ST_SRID(Point(?, ?), 4326))", [
                                $request->lng,
                                $request->lat
                            ])
                            ->exists();
                    } elseif ($exifGps) {
                        // PRODUCTION: Strict EXIF GPS verification
                        $isVerified = DB::table('projects')
                            ->where('id', $survey->project_id)
                            ->whereRaw("ST_Contains(boundary, ST_SRID(Point(?, ?), 4326))", [
                                $exifGps['lng'],
                                $exifGps['lat']
                            ])
                            ->exists();
                    }

                    // Strict Default Coordinate Check (Origin Blindness)
                    if (abs($request->lat - 3.1390) < 0.0001 && abs($request->lng - 101.6869) < 0.0001) {
                        return redirect()->back()->withErrors(['error' => 'Spatial Disconnect: System detected uninitialized Origin coordinates. Please pick a location on the map.']);
                    }

                    $integrityStatus = 'NO_METADATA';
                    if ($isLocalMode) {
                        $integrityStatus = $isVerified ? 'VERIFIED' : 'MISMATCH';
                    } else {
                        $integrityStatus = $exifGps ? ($isVerified ? 'VERIFIED' : 'MISMATCH') : 'NO_METADATA';
                    }

                    SurveyImage::create([
                        'survey_id' => $survey->id,
                        'image_path' => $path,
                        'latitude' => $request->lat,
                        'longitude' => $request->lng,
                        'metadata' => [
                            'watermarked_at' => now(),
                            'original_name' => $imageFile->getClientOriginalName(),
                            'exif_gps' => $exifGps,
                            'distance_delta_meters' => $distance,
                            'is_verified' => $isVerified,
                            'integrity_status' => $integrityStatus
                        ]
                    ]);
                }
            }

            // Bulk Notify HODs to reduce DB overhead
            $hods = \App\Models\User::where('role', 'hod')->pluck('id');
            $notifications = $hods->map(fn($id) => [
                'user_id' => $id,
                'message' => "New survey pending approval for project: {$survey->project->name}",
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            \App\Models\AppNotification::insert($notifications);

            return redirect()->route('surveys.index')->with('success', 'Survey submitted successfully.');
        });
    }

    private function extractGpsFromExif($exif)
    {
        if (!$exif || !isset($exif['GPSLatitude'], $exif['GPSLongitude'], $exif['GPSLatitudeRef'], $exif['GPSLongitudeRef'])) {
            return null;
        }

        $lat = $this->getGpsCoord($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
        $lng = $this->getGpsCoord($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

        return ['lat' => $lat, 'lng' => $lng];
    }

    private function getGpsCoord($coordinate, $ref)
    {
        $degrees = count($coordinate) > 0 ? $this->gpsToNumber($coordinate[0]) : 0;
        $minutes = count($coordinate) > 1 ? $this->gpsToNumber($coordinate[1]) : 0;
        $seconds = count($coordinate) > 2 ? $this->gpsToNumber($coordinate[2]) : 0;

        $flip = ($ref === 'S' || $ref === 'W') ? -1 : 1;

        return $flip * ($degrees + ($minutes / 60) + ($seconds / 3600));
    }

    private function gpsToNumber($coord)
    {
        $parts = explode('/', $coord);
        if (count($parts) <= 0)
            return 0;
        if (count($parts) == 1)
            return $parts[0];
        return floatval($parts[0]) / floatval($parts[1]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function show(Survey $survey)
    {
        // Load with spatial location converted to GeoJSON
        $surveyData = Survey::with(['project', 'user', 'images', 'approvals.user'])
            ->select('surveys.*')
            ->join('survey_details', 'surveys.id', '=', 'survey_details.survey_id')
            ->addSelect(DB::raw('ST_AsGeoJSON(survey_details.location) as location'))
            ->addSelect('survey_details.attributes')
            ->where('surveys.id', $survey->id)
            ->first();

        return Inertia::render('Surveys/Show', [
            'survey' => $surveyData
        ]);
    }
}
