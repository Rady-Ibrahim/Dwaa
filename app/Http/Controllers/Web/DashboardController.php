<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnmatchedProduct;
use App\Models\Upload;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index', [
            'stats' => [
                'users' => User::query()->where('role', 'client')->count(),
                'suppliers' => Supplier::query()->count(),
                'pending_unmatched' => UnmatchedProduct::query()->where('status', 'pending')->count(),
                'uploads_today' => Upload::query()->whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function suppliers()
    {
        return view('dashboard.suppliers', [
            'suppliers' => Supplier::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function users()
    {
        return view('dashboard.users', [
            'users' => User::query()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function uploads()
    {
        return view('dashboard.uploads', [
            'uploads' => Upload::query()->with('supplier')->orderByDesc('id')->paginate(15),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function products()
    {
        $search = trim((string) request('q', ''));
        $supplierId = request('supplier_id');

        $products = Product::query()
            ->with('supplier')
            ->when(filled($supplierId), fn ($q) => $q->where('supplier_id', (int) $supplierId))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('name_ar', 'like', $like)
                        ->orWhere('name_en', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('normalized_name', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.products', [
            'products' => $products,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function mapping()
    {
        return view('dashboard.mapping', [
            'items' => UnmatchedProduct::query()
                ->with(['upload.supplier', 'resolvedProduct'])
                ->where('status', 'pending')
                ->when(request('upload_id'), fn ($q, $id) => $q->where('upload_id', $id))
                ->orderByDesc('id')
                ->paginate(15),
            'uploads' => Upload::query()->with('supplier')->orderByDesc('id')->limit(50)->get(),
        ]);
    }
}
