<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faq';

    protected $fillable = [
        'question', 'answer', 'category', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
