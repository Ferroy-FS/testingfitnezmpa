<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthChainBuilder;
use Closure;
use Illuminate\Http\Request;

/**
 * ══════════════════════════════════════════════════════════════
 *  AUTH MIDDLEWARE (Refactored with Design Patterns)
 * ══════════════════════════════════════════════════════════════
 *
 * Patterns yang digunakan:
 * - Chain of Responsibility: AuthHandler chain memproses request bertahap
 * - Strategy: setiap handler punya strategi verifikasi sendiri
 *
 * Sebelum refactor: satu method besar dengan banyak if/else
 * Setelah refactor: chain of handlers, masing-masing independen
 * ══════════════════════════════════════════════════════════════
 */
class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Chain of Responsibility: Auth → OTP → (pass)
        $chain = AuthChainBuilder::buildStandardChain();
        $result = $chain->handle($request);

        if (!$result->passed) {
            $response = ['error' => $result->error];

            // Tambahkan flag untuk OTP pending
            if ($result->httpCode === 403 && str_contains($result->error, 'OTP')) {
                $response['otp_pending'] = true;
            }

            return response()->json($response, $result->httpCode);
        }

        // User berhasil diautentikasi — simpan di request
        $user = $result->context['user'];
        $request->merge(['current_user' => $user]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
