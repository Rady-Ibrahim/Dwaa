<div class="mx-auto w-full max-w-xl text-center">

    {{-- الشعار --}}
    <div class="flex flex-col items-center gap-3">
        <div class="flex items-center justify-center gap-3">
            <svg viewBox="0 0 48 48" class="h-16 w-16 shrink-0 sm:h-20 sm:w-20" aria-hidden="true">
                <defs>
                    <linearGradient id="mranko-heart-red" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#e0436a"></stop>
                        <stop offset="1" stop-color="#8B1538"></stop>
                    </linearGradient>
                    <linearGradient id="mranko-heart-blue" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#6aa8ff"></stop>
                        <stop offset="1" stop-color="#1e4fd6"></stop>
                    </linearGradient>
                    <clipPath id="mranko-heart-left">
                        <rect x="0" y="0" width="24" height="48"></rect>
                    </clipPath>
                </defs>
                <path d="M24 42C21.9 39.9 4 27.2 4 13.5 4 8.26 8.26 4 13.5 4 17.8 4 21.5 6.5 24 10c2.5-3.5 6.2-6 10.5-6C39.74 4 44 8.26 44 13.5 44 27.2 26.1 39.9 24 42Z"
                    fill="url(#mranko-heart-red)"></path>
                <path d="M24 42C21.9 39.9 4 27.2 4 13.5 4 8.26 8.26 4 13.5 4 17.8 4 21.5 6.5 24 10c2.5-3.5 6.2-6 10.5-6C39.74 4 44 8.26 44 13.5 44 27.2 26.1 39.9 24 42Z"
                    fill="url(#mranko-heart-blue)" clip-path="url(#mranko-heart-left)"></path>
                <path d="M7 21h9l3-6 4 12 3-6h9" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <p class="text-3xl font-extrabold tracking-tight sm:text-4xl">
                <span class="text-[#10265C]">Med</span><span class="text-[#8B1538]">RANKO</span>
            </p>
        </div>
        <p class="text-base font-bold text-[#8B1538]">رتب صح .. ووفر أكثر</p>
    </div>

    {{-- العنوان الرئيسي --}}
    <h1 class="mt-10 text-3xl font-extrabold leading-snug text-[#10265C] sm:text-4xl">
        منصة إدارة طبية ذكية
    </h1>
    <p class="mx-auto mt-4 max-w-md text-base leading-relaxed text-slate-500 sm:text-lg">
        حلول متكاملة لإدارة المستشفيات والعيادات بكفاءة وسهولة
    </p>

    {{-- بطاقات المميزات --}}
    <div class="mt-10 rounded-2xl bg-white p-6 shadow-xl sm:p-8">
        <div class="grid grid-cols-1 gap-7 sm:grid-cols-3">
            <div class="flex flex-col items-center gap-3 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-orange-100 text-orange-500">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.25"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.75"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">سهولة وسرعة</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-500">واجهة بسيطة لتجربة استخدام مثالية</p>
                </div>
            </div>

            <div class="flex flex-col items-center gap-3 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 20V10"></path>
                        <path d="M9.5 20V5"></path>
                        <path d="M15 20v-7"></path>
                        <path d="M20.5 20V3"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">تقارير ذكية</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-500">تحليلات دقيقة لاتخاذ القرارات الصحيحة</p>
                </div>
            </div>

            <div class="flex flex-col items-center gap-3 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 text-violet-600">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3z"></path>
                        <path d="m9 12 2 2 4-4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">آمن وموثوق</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-500">حماية بياناتك بأعلى معايير الأمان</p>
                </div>
            </div>
        </div>
    </div>
</div>