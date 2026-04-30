<?php

namespace App\Http\Controllers;

use App\Models\VisitorTracking;
use App\Services\Tracking\AndroidTrackingAdapter;
use App\Services\Tracking\TrackingDataInterface;
use App\Services\Tracking\WebBrowserTrackingAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ══════════════════════════════════════════════════════════════
 *  TRACKING CONTROLLER (Refactored with Adapter Pattern)
 * ══════════════════════════════════════════════════════════════
 *
 * Adapter pattern menerjemahkan data dari platform berbeda
 * (Web Browser, Android) ke format standar sebelum disimpan.
 *
 * Controller tidak perlu tahu format spesifik tiap platform.
 * ══════════════════════════════════════════════════════════════
 */
class TrackingController
{
    /**
     * POST /api/tracking/visit
     * Menerima data dari Web Browser ATAU Android App
     */
    public function recordVisit(Request $request): JsonResponse
    {
        $rawData = $request->all();

        // Pilih adapter berdasarkan platform
        $adapter = $this->resolveAdapter($rawData, $request);

        if (!$adapter->hasConsent()) {
            return response()->json([
                'message' => 'Consent belum diberikan. Data tidak disimpan.',
                'tracked' => false,
            ]);
        }

        // Adapter menghasilkan format standar → langsung simpan
        VisitorTracking::create($adapter->toArray());

        return response()->json([
            'message'    => 'Kunjungan berhasil dicatat.',
            'tracked'    => true,
            'visitor_id' => $adapter->getVisitorId(),
        ]);
    }

    /**
     * Resolve adapter yang tepat berdasarkan data yang diterima
     */
    private function resolveAdapter(array $data, Request $request): TrackingDataInterface
    {
        // Deteksi platform dari data
        $isAndroid = isset($data['device_id']) ||
                     isset($data['app_session_id']) ||
                     isset($data['screen_name']) ||
                     str_contains($request->userAgent() ?? '', 'FitNez-Android');

        if ($isAndroid) {
            $data['ip_address'] = $request->ip();
            return new AndroidTrackingAdapter($data);
        }

        return new WebBrowserTrackingAdapter($data, $request->ip(), $request->userAgent());
    }

    /**
     * GET /api/tracking/stats (admin only)
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_visits'    => VisitorTracking::count(),
            'unique_visitors' => VisitorTracking::distinct('visitor_id')->count('visitor_id'),
            'today_visits'    => VisitorTracking::whereDate('created_at', today())->count(),
            'device_breakdown' => VisitorTracking::selectRaw('device_type, COUNT(*) as total')
                ->groupBy('device_type')->get(),
            'browser_breakdown' => VisitorTracking::selectRaw('browser, COUNT(*) as total')
                ->groupBy('browser')->orderByDesc('total')->limit(10)->get(),
            'recent_visits' => VisitorTracking::orderBy('created_at', 'desc')
                ->limit(20)->get(['visitor_id', 'page_url', 'device_type', 'browser', 'created_at']),
        ]);
    }
}
