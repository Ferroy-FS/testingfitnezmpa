<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;

class DatabaseSeeder
{
    public function run(): void
    {
        // ── Roles ──
        DB::table('roles')->insert([
            ['name' => 'admin',   'description' => 'Administrator', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'trainer', 'description' => 'Trainer',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'member',  'description' => 'Member',        'created_at' => now(), 'updated_at' => now()],
        ]);

        $adminRoleId   = DB::table('roles')->where('name', 'admin')->value('id');
        $trainerRoleId = DB::table('roles')->where('name', 'trainer')->value('id');
        $memberRoleId  = DB::table('roles')->where('name', 'member')->value('id');

        // ── Demo Users (password: 123456) ──
        $demoUsers = [
            ['email' => 'admin@gmail.com',   'name' => 'Admin FitNez',   'role_id' => $adminRoleId],
            ['email' => 'trainer@gmail.com', 'name' => 'Trainer Budi',   'role_id' => $trainerRoleId],
            ['email' => 'member@gmail.com',  'name' => 'Member Andi',    'role_id' => $memberRoleId],
        ];

        foreach ($demoUsers as $u) {
            $salt = bin2hex(random_bytes(16));
            $hash = hash('sha256', $salt . '123456');

            $userId = DB::table('users')->insertGetId([
                'email'         => $u['email'],
                'password_hash' => $hash,
                'full_name'     => $u['name'],
                'role_id'       => $u['role_id'],
                'bio'           => '',
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::table('authentications')->insert([
                'user_id'       => $userId,
                'email'         => $u['email'],
                'password_hash' => $hash,
                'salt'          => $salt,
                'provider'      => 'local',
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $trainerId = DB::table('users')->where('email', 'trainer@gmail.com')->value('id');

        // ── Demo Schedules ──
        $schedules = [
            ['name' => 'Yoga Morning',  'day' => 'Senin',  'time' => '07:00', 'level' => 'Beginner',     'slots' => 20],
            ['name' => 'HIIT Blast',    'day' => 'Selasa', 'time' => '08:00', 'level' => 'Advanced',     'slots' => 15],
            ['name' => 'Pilates Core',  'day' => 'Rabu',   'time' => '09:00', 'level' => 'Intermediate', 'slots' => 18],
            ['name' => 'Spin Cycle',    'day' => 'Kamis',  'time' => '06:30', 'level' => 'All Level',    'slots' => 25],
            ['name' => 'Zumba Party',   'day' => 'Jumat',  'time' => '17:00', 'level' => 'Beginner',     'slots' => 30],
            ['name' => 'Body Combat',   'day' => 'Sabtu',  'time' => '10:00', 'level' => 'Advanced',     'slots' => 20],
        ];

        foreach ($schedules as $s) {
            DB::table('schedules')->insert(array_merge($s, [
                'trainer_id'  => $trainerId,
                'is_personal' => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]));
        }

        // ── FAQ ──
        DB::table('faq')->insert([
            ['question' => '📅 Jadwal Kelas',  'answer' => 'Yoga (Senin), HIIT (Selasa), Pilates (Rabu), Spin (Kamis), Zumba (Jumat), Combat (Sabtu)', 'category' => 'schedule', 'display_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => '💰 Harga Trainer', 'answer' => 'Sesi: Rp400k, 5 sesi: Rp1.8jt, 10 sesi: Rp3.4jt', 'category' => 'pricing', 'display_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => '📢 Keluhan',       'answer' => 'Email: supportfitnez@gmail.com', 'category' => 'feedback', 'display_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'ℹ️ Tentang FitNez', 'answer' => 'FitNez adalah platform manajemen gym modern untuk member, trainer, dan admin.', 'category' => 'general', 'display_order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
