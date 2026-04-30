<?php

namespace App\Http\Controllers;

use App\Models\VisitorTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ══════════════════════════════════════════════════════════════
 *  TRACKING CONTROLLER — Web Tracking (Tanpa Google Analytics)
 * ══════════════════════════════════════════════════════════════
 *
 * Implementasi poin e.2:
 * - Calon pengguna dimintakan izin cookies/localStorage
 * - Data trafik dikirim ke server hanya jika consent diberikan
 * - Tracking terjadi setiap kali user membuka landing page
 *
 * Kontrol penyimpanan:
 * - Halaman publik: tracking data pengunjung (consent required)
 * - Halaman privat: data user terautentikasi (session/token)
 * ══════════════════════════════════════════════════════════════
 */
class TrackingController
{
    /**
     * POST /api/tracking/visit — catat kunjungan landing page
     * Hanya dicatat jika consent_given = true
     */
    public function recordVisit(Request $request): JsonResponse
    {
        $consentGiven = (bool) $request->input('consent_given', false);

        if (!$consentGiven) {
            return response()->json([
                'message'  => 'Consent belum diberikan. Data tidak disimpan.',
                'tracked'  => false,
            ]);
        }

        // ── Parse data dari client ──
        $visitorId = $request->input('visitor_id', '');
        if (!$visitorId) {
            $visitorId = bin2hex(random_bytes(16));
        }

        $userAgent = $request->userAgent();

        VisitorTracking::create([
            'visitor_id'        => $visitorId,
            'session_id'        => $request->input('session_id'),
            'page_url'          => $request->input('page_url', '/'),
            'referrer'          => $request->input('referrer'),
            'user_agent'        => $userAgent,
            'ip_address'        => $request->ip(),
            'device_type'       => $request->input('device_type'),
            'browser'           => $request->input('browser'),
            'os'                => $request->input('os'),
            'screen_resolution' => $request->input('screen_resolution'),
            'language'          => $request->input('language'),
            'country'           => $request->input('country'),
            'time_on_page'      => $request->input('time_on_page'),
            'consent_given'     => true,
            'extra_data'        => $request->input('extra_data'),
        ]);

        return response()->json([
            'message'    => 'Kunjungan berhasil dicatat.',
            'tracked'    => true,
            'visitor_id' => $visitorId,
        ]);
    }

    /**
     * POST /api/tracking/time — update waktu di halaman
     */
    public function updateTime(Request $request): JsonResponse
    {
        $visitorId  = $request->input('visitor_id', '');
        $timeOnPage = (int) $request->input('time_on_page', 0);

        if (!$visitorId) {
            return response()->json(['error' => 'Visitor ID required.'], 400);
        }

        $record = VisitorTracking::where('visitor_id', $visitorId)
            ->orderBy('id', 'desc')
            ->first();

        if ($record) {
            $record->update(['time_on_page' => $timeOnPage]);
        }

        return response()->json(['message' => 'Time updated.']);
    }

    /**
     * GET /api/tracking/stats — admin lihat statistik pengunjung
     */
    public function stats(Request $request): JsonResponse
    {
        $totalVisits    = VisitorTracking::count();
        $uniqueVisitors = VisitorTracking::distinct('visitor_id')->count();
        $todayVisits    = VisitorTracking::whereDate('created_at', today())->count();

        $deviceBreakdown = VisitorTracking::selectRaw('device_type, COUNT(*) as total')
            ->groupBy('device_type')
            ->get();

        $browserBreakdown = VisitorTracking::selectRaw('browser, COUNT(*) as total')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $recentVisits = VisitorTracking::orderBy('created_at', 'desc')
            ->limit(20)
            ->get(['visitor_id', 'page_url', 'device_type', 'browser', 'created_at']);

        return response()->json([
            'total_visits'      => $totalVisits,
            'unique_visitors'   => $uniqueVisitors,
            'today_visits'      => $todayVisits,
            'device_breakdown'  => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
            'recent_visits'     => $recentVisits,
        ]);
    }
}
