@extends('layouts.admin')

@section('title', 'الإعدادات')
@section('heading', 'الإعدادات')
@section('subheading', 'إعدادات النظام والتكوينات العامة')

@section('content')
    <form method="POST" action="{{ route('dashboard.settings.update') }}" class="space-y-6">
        @csrf
        
        <!-- إعدادات الإعلانات -->
        <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6">
            <h3 class="text-lg font-semibold text-white mb-4">إعدادات الإعلانات والنشرات</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">تفعيل شريط الإعلانات</label>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="ticker_enabled" value="1" class="w-4 h-4 rounded border-gray-600 bg-gray-700 text-[#8B1538] focus:ring-[#8B1538]" {{ $generalSettings['ticker_enabled'] ? 'checked' : '' }}>
                        <label for="ticker_enabled" class="text-sm text-zinc-400">عرض شريط الإعلانات المتحرك في صفحات العملاء</label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">سرعة الحركة (بالثواني)</label>
                    <input type="number" name="ticker_speed" class="w-full px-3 py-2 rounded-lg border border-white/[0.06] bg-[#0a090b] text-white focus:border-[#8B1538] focus:outline-none" value="{{ $generalSettings['ticker_speed'] }}" min="5" max="60">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">الإعلانات الحالية</label>
                    <div id="advertisements_list" class="space-y-2 mb-3">
                        @forelse ($advertisements as $advertisement)
                            <div class="flex items-center gap-2 p-3 rounded-lg border border-white/[0.06] bg-[#0a090b]">
                                <input type="text" name="advertisements[]" class="flex-1 px-3 py-2 rounded border border-white/[0.06] bg-transparent text-white text-sm" value="{{ $advertisement->message }}">
                                <button type="button" class="text-red-400 hover:text-red-300" onclick="this.parentElement.remove()">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @empty
                            <div class="flex items-center gap-2 p-3 rounded-lg border border-white/[0.06] bg-[#0a090b]">
                                <input type="text" name="advertisements[]" class="flex-1 px-3 py-2 rounded border border-white/[0.06] bg-transparent text-white text-sm" placeholder="اكتب نص الإعلان هنا">
                                <button type="button" class="text-red-400 hover:text-red-300" onclick="this.parentElement.remove()">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" id="add_advertisement" class="px-4 py-2 bg-[#8B1538] text-white rounded-lg hover:bg-[#a61e45] transition-colors text-sm">
                        إضافة إعلان جديد
                    </button>
                </div>
            </div>
        </div>

        <!-- إعدادات عامة -->
        <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6">
            <h3 class="text-lg font-semibold text-white mb-4">الإعدادات العامة</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">اسم التطبيق</label>
                    <input type="text" name="app_name" class="w-full px-3 py-2 rounded-lg border border-white/[0.06] bg-[#0a090b] text-white focus:border-[#8B1538] focus:outline-none" value="{{ $generalSettings['app_name'] }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">الوصف</label>
                    <textarea name="app_description" class="w-full px-3 py-2 rounded-lg border border-white/[0.06] bg-[#0a090b] text-white focus:border-[#8B1538] focus:outline-none" rows="3">{{ $generalSettings['app_description'] }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">البريد الإلكتروني للدعم</label>
                    <input type="email" name="support_email" class="w-full px-3 py-2 rounded-lg border border-white/[0.06] bg-[#0a090b] text-white focus:border-[#8B1538] focus:outline-none" value="{{ $generalSettings['support_email'] }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">رقم الهاتف للدعم</label>
                    <input type="tel" name="support_phone" class="w-full px-3 py-2 rounded-lg border border-white/[0.06] bg-[#0a090b] text-white focus:border-[#8B1538] focus:outline-none" value="{{ $generalSettings['support_phone'] }}">
                </div>
            </div>
        </div>

        <!-- أزرار الحفظ -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('dashboard.settings') }}" class="px-6 py-2.5 border border-white/[0.06] text-zinc-400 rounded-lg hover:bg-white/5 transition-colors inline-block">
                إلغاء
            </a>
            <button type="submit" class="px-6 py-2.5 bg-[#8B1538] text-white rounded-lg hover:bg-[#a61e45] transition-colors">
                حفظ الإعدادات
            </button>
        </div>
    </form>

    <script>
        document.getElementById('add_advertisement').addEventListener('click', function() {
            const container = document.getElementById('advertisements_list');
            const newAd = document.createElement('div');
            newAd.className = 'flex items-center gap-2 p-3 rounded-lg border border-white/[0.06] bg-[#0a090b]';
            newAd.innerHTML = `
                <input type="text" name="advertisements[]" class="flex-1 px-3 py-2 rounded border border-white/[0.06] bg-transparent text-white text-sm" placeholder="اكتب نص الإعلان هنا">
                <button type="button" class="text-red-400 hover:text-red-300" onclick="this.parentElement.remove()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(newAd);
        });

        // Handle form submission properly
        document.querySelector('form').addEventListener('submit', function(e) {
            // Remove empty advertisement inputs before submission
            const adInputs = document.querySelectorAll('input[name="advertisements[]"]');
            adInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.parentElement.remove();
                }
            });
        });
    </script>
@endsection
