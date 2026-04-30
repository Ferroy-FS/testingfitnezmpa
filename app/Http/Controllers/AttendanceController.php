<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->current_user;

        $query = Attendance::select(
            'attendance.id',
            'users.full_name as member',
            DB::raw("COALESCE(schedules.name, attendance.attendance_type) as cls"),
            DB::raw("to_char(attendance.check_in_time, 'DD Mon YYYY HH24:MI') as date"),
            'attendance.attendance_type as status'
        )
        ->join('users', 'users.id', '=', 'attendance.user_id')
        ->leftJoin('schedules', 'schedules.id', '=', 'attendance.schedule_id');

        if ($user->role->name === 'member') {
            $query->where('attendance.user_id', $user->id);
        }

        $list = $query->orderBy('attendance.check_in_time', 'desc')->get();

        return response()->json(['attendance' => $list]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $user = $request->current_user;
        $scheduleId = (int) $request->input('schedule_id', 0);

        if (!$scheduleId) {
            return response()->json(['error' => 'Silakan pilih kelas terlebih dahulu.'], 400);
        }

        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            return response()->json(['error' => 'Kelas tidak ditemukan.'], 404);
        }

        // Cek duplikat hari ini
        $exists = Attendance::where('user_id', $user->id)
            ->where('schedule_id', $scheduleId)
            ->whereDate('check_in_time', today())
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Anda sudah check-in untuk kelas ini hari ini.'], 409);
        }

        Attendance::create([
            'user_id'         => $user->id,
            'check_in_time'   => now(),
            'attendance_type' => 'gym_class',
            'schedule_id'     => $scheduleId,
        ]);

        return response()->json([
            'message' => "Check-in berhasil untuk kelas {$schedule->name}.",
        ], 201);
    }
}
