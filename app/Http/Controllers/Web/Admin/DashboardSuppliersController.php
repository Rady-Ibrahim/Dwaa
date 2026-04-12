<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class DashboardSuppliersController extends Controller
{
    public function edit(Supplier $supplier)
    {
        return view('dashboard.supplier-edit', compact('supplier'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone1' => ['nullable', 'string', 'max:50'],
            'phone2' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        Supplier::query()->create($data);

        return back()->with('status', 'تم إضافة المورد.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone1' => ['nullable', 'string', 'max:50'],
            'phone2' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $supplier->update($data);

        return back()->with('status', 'تم تحديث المورد.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('status', 'تم حذف المورد.');
    }
}
