<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Http\Request;

class SearchLogsController extends Controller
{
    public function index(Request $request)
    {
        $logs = SearchLog::query()
            ->filter($this->filters($request))
            ->paginate(30);

        return response()->json($logs);
    }

    public function userHistory(User $user, Request $request)
    {
        $logs = SearchLog::query()
            ->filter($this->filters($request))
            ->where('user_id', $user->id)
            ->paginate(30);

        return response()->json($logs);
    }

    private function filters(Request $request): array
    {
        return [
            'user_id' => $request->input('user_id'),
            'source' => $request->input('source'),
            'q' => $request->input('q'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
    }
}
