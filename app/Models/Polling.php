<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polling extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function choises()
    {
        return $this->hasMany(Choise::class, 'polling_id');
    }

    public function votes()
    {
        return $this->hasMany(Vote::class, 'polling_id');
    }
}
