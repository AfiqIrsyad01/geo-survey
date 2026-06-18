<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'boundary', 'user_id', 'deadline_date', 'cost'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = ['boundary', 'boundary_raw'];

    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }
}
