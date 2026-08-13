@extends('layouts.client')

@section('title', 'الرئيسية')

@section('content')
    <style>
        .dashboard-shell {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .dashboard-hero {
            position: relative;
            overflow: hidden;
            padding: 1.6rem 1.5rem;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(14, 116, 144, 0.9), rgba(59, 130, 246, 0.76), rgba(139, 92, 246, 0.72));
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 28px 50px rgba(15, 23, 42, 0.45);
        }

        .dashboard-hero::before {
            content: "";
            position: absolute;
            inset: -40% auto auto -12%;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            filter: blur(10px);
        }

        .dashboard-hero-content {
            position: relative;
            z-index: 1;
        }

        .dashboard-brand {
            display: inline-flex;
            align-items: baseline;
            gap: 0.2rem;
            margin-bottom: 0.8rem;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .dashboard-brand .med {
            font-size: clamp(2rem, 3vw, 2.7rem);
            color: #f8fafc;
        }

        .dashboard-brand .ranko {
            font-size: clamp(1.7rem, 2.4vw, 2.3rem);
            color: rgba(255, 255, 255, 0.82);
        }

        .dashboard-hero h3 {
            margin: 0;
            font-size: clamp(1.7rem, 2vw, 2.4rem);
            font-weight: 800;
            color: #f8fafc;
        }

        .dashboard-hero p {
            margin: 0.6rem 0 0;
            color: rgba(224, 242, 254, 0.8);
            font-size: 0.95rem;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .metric-card,
        .summary-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 22px;
            box-shadow: 0 18px 35px rgba(2, 6, 23, 0.25);
        }

        .metric-card {
            padding: 1.2rem 1rem 1rem;
        }

        .metric-card .label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.8rem;
            color: #cbd5e1;
            font-size: 0.82rem;
        }

        .metric-card .value {
            font-size: clamp(1.8rem, 2.6vw, 2.5rem);
            font-weight: 900;
            color: #f8fafc;
            letter-spacing: -0.04em;
        }

        .metric-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.24);
            color: #7dd3fc;
        }

        .summary-card {
            padding: 1.3rem 1.2rem;
        }

        .summary-card h4 {
            margin: 0 0 1rem;
            font-size: 1.2rem;
            font-weight: 800;
            color: #f8fafc;
        }

        .summary-card p {
            margin: 0.55rem 0;
            color: #cbd5e1;
            font-size: 0.96rem;
        }

        @media (max-width: 768px) {
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="dashboard-shell">
        <div class="dashboard-hero">
            <div class="dashboard-hero-content">
                <div class="dashboard-brand">
                    <span class="med">Med</span>
                    <span class="ranko">RANKO</span>
                </div>
                <h3>مرحبًا بك في لوحة العميل</h3>
                <p>واجهة سلسة لإدارة البحث والمقارنات والمفضلة بسرعة ووضوح.</p>
            </div>
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <div class="label">
                    <span>آخر عملية بحث</span>
                    <span class="metric-icon">🔎</span>
                </div>
                <div id="lastSearch" class="value">-</div>
            </div>

            <div class="metric-card">
                <div class="label">
                    <span>المنتجات المفضلة</span>
                    <span class="metric-icon">💙</span>
                </div>
                <div id="favoritesCount" class="value">0</div>
            </div>

            <div class="metric-card">
                <div class="label">
                    <span>المقارنات المحفوظة</span>
                    <span class="metric-icon">💾</span>
                </div>
                <div id="savedCount" class="value">0</div>
            </div>
        </div>

        <div class="summary-card">
            <h4>ملخص العميل</h4>
            <p>آخر بحث: <span id="summaryLastSearch">-</span></p>
            <p>عدد عمليات البحث المحلية: <span id="searchCount">0</span></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        async function loadDashboard() {
            try {
                const [favoritesRes, savedRes] = await Promise.all([
                    axios.get('/favorites'),
                    axios.get('/saved-comparisons')
                ]);

                document.getElementById('favoritesCount').textContent = favoritesRes.data.data.length;
                document.getElementById('savedCount').textContent = savedRes.data.data.length;
            } catch (err) {
                console.error(err);
            }

            const lastSearch = localStorage.getItem('client_last_search') || 'لا يوجد بحث سابق';
            const searchCount = localStorage.getItem('client_search_count') || 0;
            document.getElementById('lastSearch').textContent = lastSearch;
            document.getElementById('summaryLastSearch').textContent = lastSearch;
            document.getElementById('searchCount').textContent = searchCount;
        }

        document.addEventListener('DOMContentLoaded', loadDashboard);
    </script>
@endpush
