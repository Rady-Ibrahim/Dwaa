<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedComparison;
use Illuminate\Http\Request;

class SavedComparisonController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        return SavedComparison::query()
            ->where('user_id', $request->user()->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ]);

        $row = SavedComparison::query()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?? null,
            'payload' => $data['payload'],
        ]);

        return response()->json($row, 201);
    }

    public function show(Request $request, SavedComparison $savedComparison)
    {
        if ($savedComparison->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json($savedComparison);
    }

    public function destroy(Request $request, SavedComparison $savedComparison)
    {
        if ($savedComparison->user_id !== $request->user()->id) {
            abort(403);
        }

        $savedComparison->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
