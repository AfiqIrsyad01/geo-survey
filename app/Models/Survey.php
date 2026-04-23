<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'user_id', 'status', 'survey_date'];

    protected $casts = [
        'survey_date' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detail()
    {
        return $this->hasOne(SurveyDetail::class);
    }

    public function images()
    {
        return $this->hasMany(SurveyImage::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }
}
