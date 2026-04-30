<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController
{
    public function index(): JsonResponse
    {
        $schedules = Schedule::with('trainer')
            ->where('is_personal', false)
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'id'      => $s->id,
                'name'    => $s->name,
                'day'     => $s->day,
                'time'    => $s->time,
                'level'   => $s->level,
                'slots'   => $s->slots,
                'trainer' => $s->trainer->full_name,
            ]);

        return response()->json(['schedule' => $schedules]);
    }

    public function mySchedules(Request $request): JsonResponse
    {
        $user = $request->current_user;

        $schedules = Schedule::where('is_personal', true)
            ->where('owner_id', $user->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($s) => [
                'id'      => $s->id,
                'name'    => $s->name,
                'day'     => $s->day,
                'time'    => $s->time,
                'level'   => $s->level,
                'notes'   => $s->notes,
                'created' => $s->created_at?->format('d M Y'),
            ]);

        return response()->json(['schedule' => $schedules]);
    }

    public function createMySchedule(Request $request): JsonResponse
    {
        $user = $request->current_user;

        $name  = trim($request->input('name', ''));
        $day   = trim($request->input('day', ''));
        $time  = trim($request->input('time', ''));
        $level = trim($request->input('level', 'Personal'));
        $notes = trim($request->input('notes', ''));

        if (!$name || !$day || !$time) {
            return response()->json(['error' => 'Nama, hari, dan waktu wajib diisi.'], 400);
        }

        Schedule::create([
            'name'        => $name,
            'day'         => $day,
            'time'        => $time,
            'trainer_id'  => $user->id,
            'level'       => $level,
            'slots'       => 1,
            'is_personal' => true,
            'owner_id'    => $user->id,
            'notes'       => $notes,
        ]);

        return response()->json(['message' => 'Jadwal latihan berhasil ditambahkan.'], 201);
    }

    public function deleteMySchedule(Request $request, int $id): JsonResponse
    {
        $user = $request->current_user;

        $schedule = Schedule::where('id', $id)
            ->where('owner_id', $user->id)
            ->where('is_personal', true)
            ->first();

        if (!$schedule) {
            return response()->json(['error' => 'Jadwal tidak ditemukan.'], 404);
        }

        $schedule->delete();
        return response()->json(['message' => 'Jadwal berhasil dihapus.']);
    }

    public function store(Request $request): JsonResponse
    {
        Schedule::create([
            'name'        => $request->input('name', ''),
            'day'         => $request->input('day', ''),
            'time'        => $request->input('time', ''),
            'trainer_id'  => $request->input('trainer_id', 0),
            'level'       => $request->input('level', 'All Level'),
            'slots'       => $request->input('slots', 20),
            'is_personal' => false,
        ]);

        return response()->json(['message' => 'Jadwal berhasil ditambahkan.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::find($id);
        if (!$schedule) {
            return response()->json(['error' => 'Jadwal tidak ditemukan.'], 404);
        }

        $fields = [];
        foreach (['name', 'day', 'time', 'trainer_id', 'level', 'slots'] as $f) {
            if ($request->has($f)) $fields[$f] = $request->input($f);
        }

        if (empty($fields)) {
            return response()->json(['error' => 'Tidak ada data yang diupdate.'], 400);
        }

        $schedule->update($fields);
        return response()->json(['message' => 'Jadwal berhasil diperbarui.']);
    }

    public function destroy(int $id): JsonResponse
    {
        Schedule::where('id', $id)->delete();
        return response()->json(['message' => 'Jadwal berhasil dihapus.']);
    }
}
