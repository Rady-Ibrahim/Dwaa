@extends('layouts.admin')

@section('title', 'تعديل مورد')
@section('heading', 'تعديل مورد: '.$supplier->name)

@section('content')
    <form method="POST" action="{{ route('dashboard.suppliers.update', $supplier) }}" class="max-w-xl space-y-4 rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
        @csrf
        @method('PUT')
        <div>
            <label class="mb-1 block text-xs text-zinc-500">الاسم</label>
            <input name="name" value="{{ old('name', $supplier->name) }}" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs text-zinc-500">هاتف 1</label>
            <input name="phone1" value="{{ old('phone1', $supplier->phone1) }}" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-xs text-zinc-500">هاتف 2</label>
            <input name="phone2" value="{{ old('phone2', $supplier->phone2) }}" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
        </div>
        <div>
            <label class="mb-1 block text-xs text-zinc-500">المنطقة</label>
            <input name="area" value="{{ old('area', $supplier->area) }}" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm text-zinc-400">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }} class="rounded border-zinc-600"> نشط
        </label>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">حفظ</button>
            <a href="{{ route('dashboard.suppliers') }}" class="rounded-lg border border-zinc-600 px-4 py-2 text-sm text-zinc-300">رجوع</a>
        </div>
    </form>
@endsection
