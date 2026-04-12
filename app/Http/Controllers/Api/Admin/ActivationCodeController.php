<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivationCodeController extends Controller
{
    public function index()
    {
        return ActivationCode::query()->orderByDesc('id')->paginate(30);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'duration_days' => ['required', 'integer', 'min:1'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'code' => ['nullable', 'string', 'max:64', 'unique:activation_codes,code'],
        ]);

        $code = $data['code'] ?? strtoupper(Str::random(12));

        $record = ActivationCode::query()->create([
            'code' => $code,
            'duration_days' => $data['duration_days'],
            'max_uses' => $data['max_uses'],
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json($record, 201);
    }
}
