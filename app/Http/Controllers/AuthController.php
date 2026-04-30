<?php

namespace App\Http\Controllers;

use App\Factories\LocalAuthFactory;
use App\Observers\AuthEventDispatcher;
use App\Observers\SystemLogObserver;
use App\Observers\RealtimeNotificationObserver;
use App\Services\Auth\AuthFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ══════════════════════════════════════════════════════════════
 *  AUTH CONTROLLER (Refactored with Design Patterns)
 * ══════════════════════════════════════════════════════════════
 *
 * Patterns yang digunakan:
 * - Facade: AuthFacade menyederhanakan subsistem auth
 * - Abstract Factory: LocalAuthFactory membuat hasher/token/otp
 * - Observer: event dispatcher untuk logging dan notifikasi
 *
 * Perhatikan betapa bersihnya controller sekarang —
 * semua kompleksitas tersembunyi di balik Facade.
 * ══════════════════════════════════════════════════════════════
 */
class AuthController
{
    private AuthFacade $auth;
    private AuthEventDispatcher $events;

    public function __construct()
    {
        // Creational: Abstract Factory → inject ke Facade
        $factory = new LocalAuthFactory();

        // Structural: Facade → menyederhanakan subsistem
        $this->auth = new AuthFacade($factory);

        // Behavioral: Observer → subscribe listeners
        $this->events = new AuthEventDispatcher();
        $this->events->subscribe(new SystemLogObserver());
        $this->events->subscribe(new RealtimeNotificationObserver());
    }

    /**
     * POST /api/auth/register
     * Bandingkan dengan sebelum refactor — jauh lebih pendek
     */
    public function register(Request $request): JsonResponse
    {
        $result = $this->auth->register(
            trim($request->input('name', '')),
            trim($request->input('email', '')),
            $request->input('password', '')
        );

        $code = $result['success'] ? 201 : ($result['code'] ?? 400);

        // Observer: notifikasi event register
        if ($result['success']) {
            $user = \App\Models\User::where('email', trim($request->input('email')))->first();
            if ($user) $this->events->notify('user.register', ['user' => $user]);
        }

        return response()->json($result, $code);
    }

    /**
     * POST /api/auth/login
     * Facade menangani: password verification, OTP generation, token creation
     */
    public function login(Request $request): JsonResponse
    {
        $result = $this->auth->login(
            trim($request->input('email', '')),
            $request->input('password', '')
        );

        if (!$result['success']) {
            return response()->json($result, $result['code'] ?? 401);
        }

        $cookie = cookie('fitnez_token', $result['token'], 120, '/', null, false, true, false, 'Lax');

        return response()->json($result)->cookie($cookie);
    }

    /**
     * POST /api/auth/verify-otp
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $token = $request->bearerToken() ?? $request->cookie('fitnez_token');

        $result = $this->auth->verifyOtp(
            trim($request->input('otp', '')),
            $token ?? ''
        );

        if ($result['success']) {
            $user = \App\Models\User::find($result['user']['id'] ?? 0);
            if ($user) $this->events->notify('user.otp_verified', ['user' => $user]);
        }

        $code = $result['success'] ? 200 : ($result['code'] ?? 401);
        return response()->json($result, $code);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->current_user;

        // Observer: notifikasi event logout
        $this->events->notify('user.logout', ['user' => $user]);

        $result = $this->auth->logout($user, $request->bearerToken());
        $cookie = cookie()->forget('fitnez_token');

        return response()->json($result)->cookie($cookie);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->current_user;
        return response()->json(['user' => $user->toApiResponse()]);
    }
}
