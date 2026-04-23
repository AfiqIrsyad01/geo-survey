<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyImage extends Model
{
    use HasFactory;

    protected $fillable = ['survey_id', 'image_path', 'latitude', 'longitude', 'metadata'];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
