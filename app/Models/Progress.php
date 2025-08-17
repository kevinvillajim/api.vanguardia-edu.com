<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Progress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'unit_id',
        'progress',
        'completed',
        'finishDate',
        'score',
        'attempted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class); // Assuming your user model is named User
    }
}