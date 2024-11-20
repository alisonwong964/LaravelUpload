<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileTaskReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'malscore',
        'js_count',
        'urls',
        'malfamily',
    ];
}
