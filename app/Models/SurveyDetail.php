<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyDetail extends Model
{
    use HasFactory;

    protected $fillable = ['survey_id', 'location', 'attributes'];

    protected $hidden = ['location'];

    protected $casts = [
        'attributes' => 'json',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
