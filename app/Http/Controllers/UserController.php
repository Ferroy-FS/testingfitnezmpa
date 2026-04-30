<?php

namespace App\Http\Controllers;

use App\Models\Authentication;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    /**
     * GET /api/users — daftar semua user
     */
    public function index(): JsonResponse
    {
        $users = User::with('role')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (User $u) => [
                'id'     => $u->id,
                'name'   => $u->full_name,
                'email'  => $u->email,
                'role'   => $u->role->name,
                'phone'  => $u->phone,
                'bio'    => $u->bio ?? '',
                'joined' => $u->created_at?->format('d M Y'),
            ]);

        return response()->json(['users' => $users]);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('role')->find($id);
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan.'], 404);
        }

        return response()->json(['user' => [
            'id'     => $user->id,
            'name'   => $user->full_name,
            'email'  => $user->email,
            'role'   => $user->role->name,
            'phone'  => $user->phone,
            'bio'    => $user->bio ?? '',
            'joined' => $user->created_at?->format('d M Y'),
        ]]);
    }

    /**
     * POST /api/users — admin tambah user
     */
    public function store(Request $request): JsonResponse
    {
        $name  = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));
        $role  = $request->input('role', 'member');

        if (!$name || !$email) {
            return response()->json(['error' => 'Nama dan email wajib diisi.'], 400);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json(['error' => 'Email sudah terdaftar.'], 409);
        }

        $roleRow = Role::where('name', $role)->first();
        if (!$roleRow) {
            return response()->json(['error' => 'Role tidak valid.'], 400);
        }

        $salt = bin2hex(random_bytes(16));
        $hash = hash('sha256', $salt . '123456');

        $user = User::create([
            'email'         => $email,
            'password_hash' => $hash,
            'full_name'     => $name,
            'role_id'       => $roleRow->id,
            'bio'           => '',
            'is_active'     => true,
        ]);

        Authentication::create([
            'user_id'       => $user->id,
            'email'         => $email,
            'password_hash' => $hash,
            'salt'          => $salt,
            'provider'      => 'local',
            'is_active'     => true,
        ]);

        return response()->json(['message' => 'User berhasil ditambahkan. Password default: 123456'], 201);
    }

    /**
     * PUT /api/users/profile — update profil sendiri
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->current_user;

        $user->update([
            'full_name' => trim($request->input('name', $user->full_name)),
            'phone'     => trim($request->input('phone', $user->phone ?? '')),
            'bio'       => trim($request->input('bio', $user->bio ?? '')),
        ]);

        $user->refresh()->load('role');

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user'    => $user->toApiResponse(),
        ]);
    }

    /**
     * POST /api/users/upgrade-trainer — upload sertifikat
     */
    public function upgradeTrainer(Request $request): JsonResponse
    {
        $user = $request->current_user;

        if (!$request->hasFile('certificate') || !$request->file('certificate')->isValid()) {
            return response()->json(['error' => 'File sertifikat wajib diupload.'], 400);
        }

        $file = $request->file('certificate');
        if ($file->getClientOriginalExtension() !== 'pdf') {
            return response()->json(['error' => 'Hanya file PDF yang diterima.'], 400);
        }

        $filename = 'cert_' . $user->id . '_' . time() . '.pdf';
        $file->move(storage_path('certificates'), $filename);

        $trainerRole = Role::where('name', 'trainer')->first();
        if (!$trainerRole) {
            return response()->json(['error' => 'Role trainer tidak ditemukan.'], 500);
        }

        $user->update([
            'role_id'            => $trainerRole->id,
            'has_trainer_cert'   => true,
            'trainer_cert_file'  => $filename,
        ]);

        $user->refresh()->load('role');

        return response()->json([
            'message' => 'Selamat! Anda sekarang menjadi Trainer.',
            'user'    => $user->toApiResponse(),
        ]);
    }

    /**
     * POST /api/users/switch-role — toggle trainer <-> member
     */
    public function switchRole(Request $request): JsonResponse
    {
        $user = $request->current_user;

        if ($user->role->name === 'member' && !$user->has_trainer_cert) {
            return response()->json(['error' => 'Anda belum memiliki sertifikat trainer.'], 403);
        }

        $newRoleName = $user->role->name === 'trainer' ? 'member' : 'trainer';
        $newRole = Role::where('name', $newRoleName)->first();

        if (!$newRole) {
            return response()->json(['error' => 'Role tidak ditemukan.'], 500);
        }

        $user->update(['role_id' => $newRole->id]);

        if ($newRoleName === 'member' && !$user->has_trainer_cert) {
            $user->update(['has_trainer_cert' => true]);
        }

        $user->refresh()->load('role');

        return response()->json([
            'message' => "Berhasil beralih ke $newRoleName.",
            'user'    => $user->toApiResponse(),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->current_user;

        if ($currentUser->id == $id) {
            return response()->json(['error' => 'Tidak bisa menghapus akun sendiri.'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan.'], 404);
        }

        Authentication::where('user_id', $id)->delete();
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }
}
