<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpDate extends Model
{
    use HasFactory;

    protected $fillable = ['curso_id', 'dead_line'];
}
