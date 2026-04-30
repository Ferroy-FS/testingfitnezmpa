<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorTracking extends Model
{
    protected $table = 'visitor_tracking';

    protected $fillable = [
        'visitor_id', 'session_id', 'page_url', 'referrer',
        'user_agent', 'ip_address', 'device_type', 'browser',
        'os', 'screen_resolution', 'language', 'country',
        'time_on_page', 'consent_given', 'extra_data',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'extra_data'    => 'array',
    ];
}
