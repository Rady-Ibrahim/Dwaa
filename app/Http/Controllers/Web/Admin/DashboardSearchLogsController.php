<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardSearchLogsController extends Controller
{
    public function index(Request $request)
    {
        $logs = SearchLog::query()
            ->filter($this->filters($request))
            ->paginate(30)
            ->withQueryString();

        $users = User::query()
            ->where('role', 'client')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('dashboard.search-logs', compact('logs', 'users'));
    }

    public function userHistory(User $user, Request $request)
    {
        $logs = SearchLog::query()
            ->filter($this->filters($request))
            ->where('user_id', $user->id)
            ->paginate(30)
            ->withQueryString();

        return view('dashboard.user-search-logs', compact('logs', 'user'));
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
