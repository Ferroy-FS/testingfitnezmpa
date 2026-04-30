<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController
{
    /**
     * GET /api/notifications/poll — Long Polling
     */
    public function poll(Request $request): JsonResponse
    {
        $lastAttId = (int) $request->query('last_att_id', 0);
        $lastLogId = (int) $request->query('last_log_id', 0);
        $timeout   = 30;
        $start     = time();

        while (true) {
            $newAtt = DB::table('attendance as a')
                ->join('users as u', 'u.id', '=', 'a.user_id')
                ->leftJoin('schedules as s', 's.id', '=', 'a.schedule_id')
                ->select(
                    'a.id',
                    'u.full_name as member',
                    DB::raw("COALESCE(s.name, a.attendance_type) as cls"),
                    DB::raw("to_char(a.check_in_time, 'DD Mon YYYY HH24:MI') as date"),
                    'a.attendance_type as status'
                )
                ->where('a.id', '>', $lastAttId)
                ->orderBy('a.id')
                ->get();

            $newLogs = DB::table('system_logs as sl')
                ->join('users as u', 'u.id', '=', 'sl.user_id')
                ->join('roles as r', 'r.id', '=', 'u.role_id')
                ->select(
                    'sl.id',
                    'u.full_name as who',
                    DB::raw("LOWER(sl.action_type) as action"),
                    'r.name as role',
                    DB::raw("to_char(sl.created_at, 'DD Mon YYYY HH24:MI') as ts")
                )
                ->where('sl.id', '>', $lastLogId)
                ->whereIn('sl.action_type', ['LOGIN', 'LOGOUT'])
                ->orderBy('sl.id')
                ->get();

            if ($newAtt->isNotEmpty() || $newLogs->isNotEmpty()) {
                return response()->json([
                    'attendance' => $newAtt,
                    'logs'       => $newLogs,
                ]);
            }

            if ((time() - $start) >= $timeout) {
                return response()->json(['attendance' => [], 'logs' => []]);
            }

            usleep(2_000_000); // 2 detik
        }
    }
}
