<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\JsonResponse;

class FaqController
{
    public function index(): JsonResponse
    {
        $faq = Faq::where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'question', 'answer', 'category']);

        return response()->json(['faq' => $faq]);
    }
}
