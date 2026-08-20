@extends('layouts.client')

@section('title', 'مقارنة مع المنصة')

@section('content')
    <style>
        .platform-compare-shell {
            display: flex;
            flex-direction: column;
            gap: 1.4rem;
        }

        .platform-compare-shell .hidden {
            display: none !important;
        }

        .platform-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.65rem 0.9rem 0.8rem;
            border-radius: 14px;
            background: linear-gradient(90deg, #0f172a, #1d4ed8, #0f172a);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
        }

        .platform-topbar .title-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .platform-topbar .title-wrap .icon-box {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.26);
            color: var(--accent-soft);
        }

        .platform-topbar .meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-soft);
            font-size: 0.9rem;
        }

        .platform-topbar .meta .pill {
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(59, 130, 246, 0.30);
            background: rgba(22, 22, 22, 0.8);
        }

        .compare-modes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
        }

        .mode-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.1rem;
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.28);
            background: rgba(18, 18, 18, 0.7);
            color: var(--text-soft);
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .mode-btn:hover {
            border-color: rgba(96, 165, 250, 0.6);
        }

        .mode-btn.active {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.9), rgba(37, 99, 235, 0.8));
            border-color: rgba(96, 165, 250, 0.9);
            color: #fff;
        }

        .compare-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.34fr 1.1fr;
            gap: 1.4rem;
        }

        .compare-panel {
            position: relative;
            background: linear-gradient(180deg, rgba(18, 18, 18, 0.78), rgba(12, 12, 12, 0.72));
            border: 1px solid rgba(59, 130, 246, 0.34);
            border-radius: 20px;
            min-height: 270px;
            padding: 1.5rem 1.4rem;
            box-shadow: 0 16px 28px rgba(0, 0, 0, 0.18);
        }

        .compare-panel .panel-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
            min-height: 56px;
        }

        .file-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.25);
            display: grid;
            place-items: center;
            font-size: 1.7rem;
            color: var(--danger-soft);
        }

        .compare-panel .panel-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 120px;
            gap: 0.7rem;
        }

        .compare-panel h4 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
        }

        .compare-panel p {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.7;
            font-size: 0.9rem;
        }

        .compare-panel .file-input-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 150px;
            padding: 0.8rem 1.1rem;
            border-radius: 14px;
            border: 1px solid rgba(59, 130, 246, 0.28);
            background: rgba(37, 99, 235, 0.06);
            color: var(--text-soft2);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .compare-panel .file-input-label:hover {
            border-color: rgba(96, 165, 250, 0.6);
            background: rgba(37, 99, 235, 0.10);
        }

        .compare-panel .file-name {
            min-height: 1.3rem;
            color: var(--text-muted);
            font-size: 0.78rem;
            word-break: break-all;
        }

        .platform-select {
            width: 100%;
            max-width: 260px;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.18);
            background: rgba(15, 15, 15, 0.82);
            color: var(--text-soft2);
            font-weight: 600;
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .platform-select:focus {
            border-color: rgba(59, 130, 246, 0.42);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .platform-select option {
            background-color: var(--option-bg);
            color: var(--option-color);
        }

        .platform-file-search {
            width: 100%;
            max-width: 260px;
            height: 38px;
            margin-bottom: 0.5rem;
            padding: 0 0.85rem;
            border-radius: 10px;
            border: 1px solid rgba(59, 130, 246, 0.18);
            background: rgba(15, 15, 15, 0.82);
            color: var(--text-soft2);
            font-size: 0.82rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .platform-file-search:focus {
            border-color: rgba(59, 130, 246, 0.42);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .platform-file-search::placeholder {
            color: var(--text-muted);
        }

        .vs-box {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 270px;
        }

        .vs-circle {
            width: 108px;
            height: 108px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.92), rgba(59, 130, 246, 0.8));
            border: 2px solid rgba(96, 165, 250, 0.75);
            color: #fff;
            font-size: 2.4rem;
            font-weight: 900;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }

        .main-action {
            margin-top: 0.5rem;
            display: flex;
            justify-content: center;
        }

        .compare-submit {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1rem 1.3rem;
            border-radius: 14px;
            background: linear-gradient(90deg, #0f172a, #1d4ed8, #0f172a);
            color: #fff;
            font-weight: 800;
            font-size: 1rem;
            box-shadow: 0 16px 24px rgba(37, 99, 235, 0.35);
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .compare-submit:hover {
            filter: brightness(1.06);
            transform: translateY(-1px);
        }

        .compare-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .mini-stat {
            background: rgba(18, 18, 18, 0.75);
            border: 1px solid rgba(59, 130, 246, 0.22);
            border-radius: 16px;
            padding: 1rem 1rem;
            min-height: 110px;
        }

        .mini-stat .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            color: var(--text-soft);
            font-size: 0.85rem;
        }

        .mini-stat .value {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .mini-stat .delta {
            font-size: 0.8rem;
            color: #22c55e;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            margin-top: 0.2rem;
        }

        .mini-stat .delta.negative {
            color: var(--danger-soft);
        }

        .mini-stat .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: rgba(239, 35, 60, 0.08);
            border: 1px solid rgba(239, 35, 60, 0.22);
            color: var(--accent-soft);
        }

        .compare-results {
            background: rgba(18, 18, 18, 0.75);
            border: 1px solid rgba(59, 130, 246, 0.22);
            border-radius: 18px;
            overflow: hidden;
            margin-top: 0.3rem;
        }

        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-bottom: 1px solid rgba(59, 130, 246, 0.15);
        }

        .results-header input {
            width: min(320px, 100%);
            height: 44px;
            border: 1px solid rgba(59, 130, 246, 0.18);
            background: rgba(15, 15, 15, 0.82);
            color: var(--text-soft2);
            border-radius: 12px;
            padding: 0 0.9rem;
            outline: none;
        }

        .results-header input:focus {
            border-color: rgba(59, 130, 246, 0.42);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .save-btn {
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            background: linear-gradient(90deg, #1d4ed8, #2563eb, #1d4ed8);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 12px 22px rgba(37, 99, 235, 0.25);
        }

        .save-btn[hidden] {
            display: none;
        }

        table.platform-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-soft2);
        }

        table.platform-table th,
        table.platform-table td {
            text-align: right;
            padding: 0.9rem 0.9rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.9rem;
        }

        table.platform-table thead th {
            background: rgba(15, 15, 15, 0.9);
            color: var(--text-soft);
            font-weight: 700;
        }

        table.platform-table tbody tr:hover {
            background: rgba(59, 130, 246, 0.03);
        }

        .status-up {
            color: var(--success-soft);
        }

        .status-down {
            color: var(--danger-soft);
        }

        .status-neutral {
            color: var(--text-soft);
        }

        @media (max-width: 980px) {
            .compare-grid {
                grid-template-columns: 1fr;
            }

            .vs-box {
                min-height: 90px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .platform-topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <div class="platform-compare-shell">
        <div class="platform-topbar">
            <div class="title-wrap">
                <div class="icon-box">📑</div>
                <span>مقارنة مع المنصة</span>
            </div>
            <div class="meta">
                <span class="pill" id="modePill">ملفّي مع المنصة</span>
                <span class="pill">منصة MedRANKO</span>
            </div>
        </div>

        <div class="compare-modes">
            <button type="button" class="mode-btn active" data-mode="vs_platform">📊 ملفّي مع المنصة</button>
            <button type="button" class="mode-btn" data-mode="vs_upload">📄↔📄 ملفّي مع ملف من المنصة</button>
            <button type="button" class="mode-btn" data-mode="uploads">🗂️ ملفان من المنصة</button>
        </div>

        <div class="compare-grid">
            <div class="compare-panel">
                <div class="panel-header">
                    <div class="file-icon">📄</div>
                </div>
                <div class="panel-body" id="panelAFileWrap">
                    <h4 id="panelAFileTitle">ملف</h4>
                    <div class="file-input-label" id="pickPlatformCompareFile">اختيار ملف</div>
                    <div id="platformCompareFileName" class="file-name">لم يتم اختيار ملف</div>
                    <input type="file" id="platformCompareFile" class="hidden" accept=".xlsx,.xls,.csv" />
                </div>
                <div class="panel-body hidden" id="panelAUploadWrap">
                    <h4 id="panelAUploadTitle">الملف الأول من المنصة</h4>
                    <input type="text" id="platformUploadASearch" class="platform-file-search"
                        placeholder="ابحث باسم الملف..." autocomplete="off" />
                    <select id="platformUploadA" class="platform-select">
                        <option value="">اختر ملف...</option>
                    </select>
                    <div id="platformUploadAName" class="file-name"></div>
                </div>
            </div>

            <div class="vs-box">
                <div class="vs-circle">VS</div>
            </div>

            <div class="compare-panel">
                <div class="panel-header">
                    <div class="file-icon"
                        style="background: rgba(59,130,246,0.08); border-color: rgba(59,130,246,0.25); color: #7dd3fc;">⬆️
                    </div>
                </div>
                <div class="panel-body" id="panelBPlatformWrap">
                    <h4 id="panelBPlatformTitle">المنصة</h4>
                    <p>مقارنة بين أسعار المنتجات والتوزيع<br>مع أفضل مورد متاح في المنصة.</p>
                </div>
                <div class="panel-body hidden" id="panelBUploadWrap">
                    <h4 id="panelBUploadTitle">ملف من المنصة</h4>
                    <input type="text" id="platformUploadBSearch" class="platform-file-search"
                        placeholder="ابحث باسم الملف..." autocomplete="off" />
                    <select id="platformUploadB" class="platform-select">
                        <option value="">اختر ملف...</option>
                    </select>
                    <div id="platformUploadBName" class="file-name"></div>
                </div>
            </div>
        </div>

        <div class="main-action">
            <button type="button" id="runPlatformCompareBtn" class="compare-submit">بدء المقارنة</button>
        </div>

        <div class="stats-grid">
            <div class="mini-stat">
                <div class="top">
                    <span id="statFromName">الملف</span>
                    <div class="stat-icon">📄</div>
                </div>
                <div class="value" id="statFromValue">0</div>
                <div class="delta">منتجات تم تحليلها</div>
            </div>

            <div class="mini-stat">
                <div class="top">
                    <span id="statToName">المنصة</span>
                    <div class="stat-icon"
                        style="background: rgba(168,85,247,0.1); border-color: rgba(168,85,247,0.25); color: #d8b4fe;">📦
                    </div>
                </div>
                <div class="value" id="statToValue">0</div>
                <div class="delta">أفضل عروض</div>
            </div>

            <div class="mini-stat">
                <div class="top">
                    <span>الفارق</span>
                    <div class="stat-icon"
                        style="background: rgba(34,197,94,0.08); border-color: rgba(34,197,94,0.25); color: #4ade80;">↗
                    </div>
                </div>
                <div class="value" id="statDiffValue">0</div>
                <div class="delta">فرق في السعر</div>
            </div>

            <div class="mini-stat">
                <div class="top">
                    <span>الحالة</span>
                    <div class="stat-icon"
                        style="background: rgba(245,158,11,0.08); border-color: rgba(245,158,11,0.24); color: #fbbf24;">⚑
                    </div>
                </div>
                <div class="value" id="statStatusValue">-</div>
                <div class="delta negative">انتظار الملف</div>
            </div>
        </div>

        <div class="compare-results">
            <div class="results-header">
                <input id="platformCompareSearch" type="text" placeholder="بحث داخل النتائج..." />
                <button type="button" id="savePlatformComparisonBtn" class="save-btn" hidden>💾 حفظ المقارنة</button>
            </div>

            <div class="overflow-x-auto">
                <table class="platform-table">
                    <thead>
                        <tr>
                            <th id="thFromName">الصنف من الملف</th>
                            <th id="thFromPrice">سعر الملف</th>
                            <th id="thFromDiscount">خصم الملف</th>
                            <th>الصنف المطابق</th>
                            <th id="thSupplier">أفضل مورد</th>
                            <th id="thToPrice">سعر المنصة</th>
                            <th id="thToDiscount">خصم المنصة</th>
                            <th>فرق السعر</th>
                            <th>فرق الخصم</th>
                        </tr>
                    </thead>
                    <tbody id="platformCompareTable">
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-500">بانتظار رفع الملف.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="platformComparePager" class="p-4 border-t border-white/10 text-xs text-slate-400"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const MODE = {
            PLATFORM: 'vs_platform', // ملفّي مع المنصة
            UPLOAD: 'vs_upload', // ملفّي مع ملف من المنصة
            UPLOADS: 'uploads', // ملفان من المنصة
        };

        const MODE_LABELS = {
            [MODE.PLATFORM]: 'ملفّي مع المنصة',
            [MODE.UPLOAD]: 'ملفّي مع ملف من المنصة',
            [MODE.UPLOADS]: 'ملفان من المنصة',
        };

        const MODE_HEADERS = {
            [MODE.PLATFORM]: {
                fromName: 'الصنف من الملف',
                fromPrice: 'سعر الملف',
                fromDiscount: 'خصم الملف',
                supplier: 'أفضل مورد',
                toPrice: 'سعر المنصة',
                toDiscount: 'خصم المنصة',
                statFrom: 'الملف',
                statTo: 'المنصة',
            },
            [MODE.UPLOAD]: {
                fromName: 'الصنف من ملفي',
                fromPrice: 'سعر ملفي',
                fromDiscount: 'خصم ملفي',
                supplier: 'مورد ملف المنصة',
                toPrice: 'سعر المنصة',
                toDiscount: 'خصم المنصة',
                statFrom: 'ملفي',
                statTo: 'ملف المنصة',
            },
            [MODE.UPLOADS]: {
                fromName: 'الصنف من الأول',
                fromPrice: 'سعر الأول',
                fromDiscount: 'خصم الأول',
                supplier: 'مورد الثاني',
                toPrice: 'سعر الثاني',
                toDiscount: 'خصم الثاني',
                statFrom: 'الملف الأول',
                statTo: 'الملف الثاني',
            },
        };

        let currentMode = MODE.PLATFORM;
        let platformUploads = [];
        let selectedUploadA = null;
        let selectedUploadB = null;

        const fileInput = document.getElementById('platformCompareFile');
        const fileName = document.getElementById('platformCompareFileName');
        const pickBtn = document.getElementById('pickPlatformCompareFile');
        const runBtn = document.getElementById('runPlatformCompareBtn');
        const table = document.getElementById('platformCompareTable');
        const pager = document.getElementById('platformComparePager');
        const searchInput = document.getElementById('platformCompareSearch');
        const saveBtn = document.getElementById('savePlatformComparisonBtn');

        const panelAFileWrap = document.getElementById('panelAFileWrap');
        const panelAUploadWrap = document.getElementById('panelAUploadWrap');
        const panelAFileTitle = document.getElementById('panelAFileTitle');
        const panelAUploadTitle = document.getElementById('panelAUploadTitle');
        const panelBPlatformWrap = document.getElementById('panelBPlatformWrap');
        const panelBUploadWrap = document.getElementById('panelBUploadWrap');
        const panelBPlatformTitle = document.getElementById('panelBPlatformTitle');
        const panelBUploadTitle = document.getElementById('panelBUploadTitle');
        const uploadSelectA = document.getElementById('platformUploadA');
        const uploadSelectB = document.getElementById('platformUploadB');
        const modePill = document.getElementById('modePill');

        const thFromName = document.getElementById('thFromName');
        const thFromPrice = document.getElementById('thFromPrice');
        const thFromDiscount = document.getElementById('thFromDiscount');
        const thSupplier = document.getElementById('thSupplier');
        const thToPrice = document.getElementById('thToPrice');
        const thToDiscount = document.getElementById('thToDiscount');
        const statFromName = document.getElementById('statFromName');
        const statToName = document.getElementById('statToName');
        const statFromValue = document.getElementById('statFromValue');
        const statToValue = document.getElementById('statToValue');
        const statDiffValue = document.getElementById('statDiffValue');
        const statStatusValue = document.getElementById('statStatusValue');

        const pageSize = 20;
        let rows = [];
        let filtered = [];
        let page = 1;
        let latestComparisonData = null;

        pickBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            fileName.textContent = fileInput.files?.[0]?.name || 'لم يتم اختيار ملف';
        });
        searchInput.addEventListener('input', () => applyFilter(1));
        saveBtn.addEventListener('click', savePlatformComparison);
        runBtn.addEventListener('click', runCompare);

        uploadSelectA.addEventListener('change', (e) => {
            selectedUploadA = e.target.value || null;
            uploadSelectA.nextElementSibling.textContent = selectedUploadA ? getUploadLabel(selectedUploadA) : '';
        });
        uploadSelectB.addEventListener('change', (e) => {
            selectedUploadB = e.target.value || null;
            uploadSelectB.nextElementSibling.textContent = selectedUploadB ? getUploadLabel(selectedUploadB) : '';
        });

        function bindUploadSearch(inputId, select) {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('input', () => {
                if (!platformUploads.length) return;
                const q = input.value.trim().toLowerCase();
                const prevValue = select.value;
                const filtered = q ?
                    platformUploads.filter((u) => uploadLabel(u).toLowerCase().includes(q)) :
                    platformUploads;
                select.innerHTML = '<option value="">اختر ملف...</option>' + filtered.map((u) =>
                    `<option value="${u.id}">${escapeHtml(uploadLabel(u))}</option>`
                ).join('');
                if (prevValue && filtered.some((u) => String(u.id) === String(prevValue))) {
                    select.value = prevValue;
                } else {
                    select.value = '';
                    select.dispatchEvent(new Event('change'));
                }
            });
        }
        bindUploadSearch('platformUploadASearch', uploadSelectA);
        bindUploadSearch('platformUploadBSearch', uploadSelectB);

        document.querySelectorAll('.mode-btn').forEach((btn) => {
            btn.addEventListener('click', () => setMode(btn.dataset.mode));
        });

        function uploadLabel(u) {
            const file = u.file_name || '';
            const supplier = u.supplier || '';
            if (file && supplier) return `${supplier} — ${file}`;
            if (supplier) return supplier;
            if (file) return file;
            return `ملف رقم ${u.id}`;
        }

        function getUploadLabel(id) {
            const u = platformUploads.find((x) => String(x.id) === String(id));
            return u ? uploadLabel(u) : '';
        }

        function setMode(mode) {
            currentMode = mode;

            document.querySelectorAll('.mode-btn').forEach((b) => {
                b.classList.toggle('active', b.dataset.mode === mode);
            });

            // إظهار/إخفاء محتوى اللوحات حسب الوضع
            panelAFileWrap.classList.toggle('hidden', mode === MODE.UPLOADS);
            panelAUploadWrap.classList.toggle('hidden', mode !== MODE.UPLOADS);
            panelBPlatformWrap.classList.toggle('hidden', mode !== MODE.PLATFORM);
            panelBUploadWrap.classList.toggle('hidden', mode === MODE.PLATFORM);

            panelAFileTitle.textContent = (mode === MODE.UPLOAD) ? 'ملفي' : 'ملف';
            panelAUploadTitle.textContent = 'الملف الأول من المنصة';
            panelBPlatformTitle.textContent = 'المنصة';
            panelBUploadTitle.textContent = (mode === MODE.UPLOADS) ? 'الملف الثاني من المنصة' : 'ملف من المنصة';

            const H = MODE_HEADERS[mode];
            thFromName.textContent = H.fromName;
            thFromPrice.textContent = H.fromPrice;
            thFromDiscount.textContent = H.fromDiscount;
            thSupplier.textContent = H.supplier;
            thToPrice.textContent = H.toPrice;
            thToDiscount.textContent = H.toDiscount;
            statFromName.textContent = H.statFrom;
            statToName.textContent = H.statTo;
            modePill.textContent = MODE_LABELS[mode];

            // تصفير النتائج عند تغيير الوضع
            rows = [];
            filtered = [];
            latestComparisonData = null;
            applyFilter(1);
            saveBtn.classList.add('hidden');
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 حفظ المقارنة';
        }

        async function loadPlatformUploads() {
            try {
                const res = await axios.get('/compare-platform-uploads');
                platformUploads = res.data?.uploads || [];
                const optionsHtml = '<option value="">اختر ملف...</option>' + platformUploads.map((u) =>
                    `<option value="${u.id}">${escapeHtml(uploadLabel(u))}</option>`
                ).join('');
                uploadSelectA.innerHTML = optionsHtml;
                uploadSelectB.innerHTML = optionsHtml;
                selectedUploadA = null;
                selectedUploadB = null;
                uploadSelectA.nextElementSibling.textContent = '';
                uploadSelectB.nextElementSibling.textContent = '';
                ['platformUploadASearch', 'platformUploadBSearch'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
            } catch (err) {
                console.error('[compare-platform] failed to load uploads', err);
            }
        }

        async function runCompare() {
            runBtn.disabled = true;
            table.innerHTML = '<tr><td colspan="9" class="p-8 text-center text-slate-400">جاري المقارنة...</td></tr>';
            pager.innerHTML = '';

            let res;
            try {
                if (currentMode === MODE.PLATFORM) {
                    const file = fileInput.files?.[0];
                    if (!file) {
                        clientNotify('اختر ملف أولاً', 'warning');
                        return;
                    }
                    const fd = new FormData();
                    fd.append('file', file);
                    res = await axios.post('/compare-platform-file', fd);
                } else if (currentMode === MODE.UPLOAD) {
                    const file = fileInput.files?.[0];
                    if (!file) {
                        clientNotify('اختر ملف أولاً', 'warning');
                        return;
                    }
                    if (!selectedUploadB) {
                        clientNotify('اختر ملفاً من المنصة للمقارنة معه', 'warning');
                        return;
                    }
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('upload_id', selectedUploadB);
                    res = await axios.post('/compare-file-to-upload', fd);
                } else {
                    if (!selectedUploadA && !selectedUploadB) {
                        clientNotify('اختر ملفين من المنصة للمقارنة', 'warning');
                        return;
                    }
                    if (!selectedUploadA) {
                        clientNotify('اختر الملف الأول من المنصة', 'warning');
                        return;
                    }
                    if (!selectedUploadB) {
                        clientNotify('اختر الملف الثاني من المنصة', 'warning');
                        return;
                    }
                    if (selectedUploadA === selectedUploadB) {
                        clientNotify('اختر ملفين مختلفين من المنصة', 'warning');
                        return;
                    }
                    res = await axios.post('/compare-uploads', {
                        upload_id_a: selectedUploadA,
                        upload_id_b: selectedUploadB,
                    });
                }

                rows = res.data?.lines || [];
                const fileLabel = fileInput.files?.[0]?.name ||
                    `ملف ${MODE_LABELS[currentMode]}`;
                latestComparisonData = {
                    mode: currentMode,
                    file_name: fileLabel,
                    upload_id_a: selectedUploadA,
                    upload_id_b: selectedUploadB,
                    lines: rows,
                    timestamp: new Date().toISOString(),
                };
                applyFilter(1);
                saveBtn.classList.remove('hidden');
                clientNotify('تمت المقارنة بنجاح', 'success');
            } catch (error) {
                const msg = error.response?.data?.message || 'فشل تنفيذ المقارنة.';
                table.innerHTML =
                    `<tr><td colspan="9" class="p-8 text-center text-rose-400">${escapeHtml(msg)}</td></tr>`;
                clientNotify(msg, 'error');
                saveBtn.classList.add('hidden');
            } finally {
                runBtn.disabled = false;
            }
        }

        async function savePlatformComparison() {
            if (!latestComparisonData || !rows.length) {
                clientNotify('لا توجد بيانات للحفظ', 'warning');
                return;
            }

            const title = latestComparisonData.file_name ||
                `مقارنة المنصة - ${new Date().toLocaleDateString('ar-EG')}`;

            const typeMap = {
                [MODE.PLATFORM]: 'platform_compare',
                [MODE.UPLOAD]: 'file_to_upload',
                [MODE.UPLOADS]: 'uploads_compare',
            };

            try {
                await axios.post('/saved-comparisons', {
                    title,
                    payload: {
                        type: typeMap[currentMode] || 'platform_compare',
                        ...latestComparisonData,
                    },
                });
                clientNotify('تم حفظ المقارنة بنجاح', 'success');
                saveBtn.disabled = true;
                saveBtn.textContent = '✓ تم الحفظ';
            } catch (error) {
                const msg = error.response?.data?.message || 'فشل حفظ المقارنة';
                clientNotify(msg, 'error');
            }
        }

        function applyFilter(nextPage) {
            page = nextPage;
            const q = (searchInput.value || '').trim().toLowerCase();
            filtered = rows.filter((r) => {
                if (!q) return true;
                const hay = `${r.query || ''} ${r.matched_product || ''} ${r.platform_best?.supplier || ''}`
                    .toLowerCase();
                return hay.includes(q);
            });
            renderPage();
        }

        function renderPage() {
            if (!filtered.length) {
                table.innerHTML = '<tr><td colspan="9" class="p-8 text-center text-slate-500">لا توجد نتائج.</td></tr>';
                pager.innerHTML = '';
                statFromValue.textContent = '0';
                statToValue.textContent = '0';
                statDiffValue.textContent = '0';
                statStatusValue.textContent = '-';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
            page = Math.min(Math.max(1, page), totalPages);
            const start = (page - 1) * pageSize;
            const view = filtered.slice(start, start + pageSize);

            table.innerHTML = view.map((line) => {
                const best = line.platform_best || {};
                const status = line.status || 'both';
                const onlyA = status === 'only_a';
                const onlyB = status === 'only_b';

                const sheetPrice = line.price;
                const sheetDiscount = line.discount;
                const platformPrice = best.price;
                const platformDiscount = best.discount;

                const priceDiff = (onlyA || onlyB || sheetPrice == null || platformPrice == null) ?
                    null :
                    Math.round((sheetPrice - platformPrice) * 100) / 100;
                const discountDiff = (onlyA || onlyB || sheetDiscount == null || platformDiscount == null) ?
                    null :
                    Math.round((sheetDiscount - platformDiscount) * 100) / 100;

                const priceCls = priceDiff === null ? 'text-slate-400' : (priceDiff > 0 ? 'text-rose-400' : (
                    priceDiff < 0 ? 'text-emerald-400' : 'text-slate-200'));
                const discountCls = discountDiff === null ? 'text-slate-400' : (discountDiff > 0 ?
                    'text-emerald-400' : (discountDiff < 0 ? 'text-rose-400' : 'text-slate-200'));

                const matchCell = status === 'no_match' ? '-' :
                    onlyA ? '<span class="text-xs text-sky-300">موجود في الملف الأول فقط</span>' :
                    onlyB ? '<span class="text-xs text-amber-300">موجود في الملف الثاني فقط</span>' :
                    escapeHtml(line.matched_product ?? '-');

                return `
                    <tr class="border-b border-white/5">
                        <td class="p-3 font-semibold text-white">${onlyB ? '<span class="text-slate-500">—</span>' : escapeHtml(line.query ?? '-')}</td>
                        <td class="p-3">${onlyB ? '-' : formatNum(sheetPrice)}</td>
                        <td class="p-3">${onlyB ? '-' : formatNum(sheetDiscount)}</td>
                        <td class="p-3">${matchCell}</td>
                        <td class="p-3">${onlyA ? '-' : escapeHtml(best.supplier ?? '-')}</td>
                        <td class="p-3">${onlyA ? '-' : formatNum(platformPrice)}</td>
                        <td class="p-3">${onlyA ? '-' : formatNum(platformDiscount)}</td>
                        <td class="p-3 ${priceCls}">${formatNum(priceDiff)}</td>
                        <td class="p-3 ${discountCls}">${formatNum(discountDiff)}</td>
                    </tr>
                `;
            }).join('');

            pager.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>عرض ${start + 1} - ${Math.min(start + pageSize, filtered.length)} من ${filtered.length}</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 rounded bg-slate-800 ${page === 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${page === 1 ? 'disabled' : ''} onclick="platformCompareGo(${page - 1})">السابق</button>
                        <span>${page} / ${totalPages}</span>
                        <button class="px-3 py-1 rounded bg-slate-800 ${page === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" ${page === totalPages ? 'disabled' : ''} onclick="platformCompareGo(${page + 1})">التالي</button>
                    </div>
                </div>
            `;

            // إحصائيات
            const withBoth = filtered.filter((l) => l.status === 'both').length;
            const onlyA = filtered.filter((l) => l.status === 'only_a').length;
            const onlyB = filtered.filter((l) => l.status === 'only_b').length;
            const diffs = filtered.filter((l) => {
                if (l.status !== 'both') return false;
                const sp = l.price;
                const pp = l.platform_best?.price;
                return sp != null && pp != null && Math.round((sp - pp) * 100) / 100 !== 0;
            }).length;
            statFromValue.textContent = `${withBoth + onlyA} / ${filtered.length}`;
            statToValue.textContent = `${withBoth + onlyB}`;
            statDiffValue.textContent = diffs ? `${diffs} صنف بفرق` : '0';
            statStatusValue.textContent = onlyA || onlyB ? 'يوجد فوارق' : 'اكتملت';
        }

        function platformCompareGo(nextPage) {
            page = nextPage;
            renderPage();
        }
        window.platformCompareGo = platformCompareGo;

        function formatNum(v) {
            if (v === null || v === undefined || v === '') return '-';
            if (typeof v === 'number') return Number.isInteger(v) ? String(v) : v.toFixed(2);
            return escapeHtml(String(v));
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        document.addEventListener('DOMContentLoaded', loadPlatformUploads);
    </script>
@endpush
