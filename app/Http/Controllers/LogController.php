<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LogController
{
    public function index(): JsonResponse
    {
        $logs = SystemLog::select(
            'system_logs.id',
            'users.full_name as who',
            DB::raw("LOWER(system_logs.action_type) as action"),
            'roles.name as role',
            DB::raw("to_char(system_logs.created_at, 'DD Mon YYYY HH24:MI') as ts")
        )
        ->join('users', 'users.id', '=', 'system_logs.user_id')
        ->join('roles', 'roles.id', '=', 'users.role_id')
        ->whereIn('system_logs.action_type', ['LOGIN', 'LOGOUT'])
        ->orderBy('system_logs.created_at', 'desc')
        ->limit(100)
        ->get();

        return response()->json(['logs' => $logs]);
    }

    public function clear(): JsonResponse
    {
        SystemLog::whereIn('action_type', ['LOGIN', 'LOGOUT'])->delete();
        return response()->json(['message' => 'Auth logs berhasil dihapus.']);
    }
}
