<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'boundary', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = ['boundary_raw'];

    /**
     * Automatically convert binary boundary to GeoJSON for the frontend.
     */
    protected function getBoundaryAttribute($value)
    {
        return json_decode($value);
    }

    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }
}
