<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $casts = [
        'uploaded_at' => 'datetime',  // Automatically casts 'uploaded_at' to a Carbon instance
    ];
}
