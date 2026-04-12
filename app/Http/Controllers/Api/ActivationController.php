<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivationService;
use Illuminate\Http\Request;

class ActivationController extends Controller
{
    public function __construct(private ActivationService $activationService) {}

    public function activate(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $this->activationService->activate($request->user(), $data['code']);

        return response()->json([
            'message' => 'تم التفعيل بنجاح',
            'subscription_expires_at' => $user->subscription_expires_at,
        ]);
    }
}
