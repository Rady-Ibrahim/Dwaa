<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Upload;
use App\Services\ExcelSearchService;
use App\Services\NormalizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClientPlatformCompareController extends Controller
{
    /** أقصى عدد صفوف تُقرأ من الملف */
    private const MAX_ROWS = 1000;

    /** الحد الأدنى لنسبة التشابه للقبول */
    private const MIN_SIMILARITY = 45.0;

    /** الحد الأدنى لنسبة تداخل كلمات المحتوى للقبول في مقارنة الملفات */
    private const MIN_NAME_OVERLAP = 60.0;

    /** نسبة السعر المتساوي تقريبًا: تعتبر تأكيدًا للمطابقة */
    private const PRICE_EQUAL_RATIO = 0.97;

    /** نسبة السعر "القريب": يُقبل عندها التطابق دون رفض */
    private const PRICE_CLOSE_RATIO = 0.80;

    /** مضاعف رفض التطابق عندما يتباعد السعر بشدة (نوع/جرامات مختلفة) */
    private const PRICE_FAR_PENALTY = 0.3;

    /** كلمات عامة (صيغة/وحدة/عملة/ضجيج) تُستبعد من مطابقة أسماء الأدوية */
    private const DRUG_STOP_WORDS = [
        // صيغة/وحدة (بالصيغة المطبّعة: ة → ه)
        'اقراص', 'قرص', 'كبسول', 'كبسوله', 'كبسولات', 'حبوب', 'شراب', 'حقن', 'قطرات', 'قطره',
        'نقط', 'مرهم', 'كريم', 'جل', 'بخاخ', 'سبراي', 'محلول', 'معلق', 'لبوس', 'تحاميل',
        'امبول', 'امبولات', 'شريط', 'شرايط', 'باكت', 'علبه', 'زجاجه', 'مسحوق', 'بودره', 'سيروم',
        'لوشن', 'لوسيون', 'مجم', 'ملجم', 'مليجرام', 'جرام', 'ملى', 'مللى', 'مل', 'سم', 'لتر', 'كجم',
        'فموي', 'وريدي', 'موضعي', 'مستمر', 'معجل', 'مؤخر', 'سعر', 'جنيه', 'جنية',
        // كلمات وصفية عامة لا تُحدّد هوية المنتج
        'مع', 'وزن', 'عبوه', 'محيط', 'العين', 'مطهر', 'غسول', 'جديد', 'احمر', 'اكسترا', 'ال',
        'جيل', 'كبير', 'صغير', 'كبيره', 'صغيره',
        'صن', 'بلوك', 'بديل', 'سيرم', 'شامبو', 'مضاد', 'حيوي', 'ادويه', 'علاج', 'دواء',
        'مستحضر', 'كبسولات',
    ];

    public function __construct(
        private ExcelSearchService $excelSearchService,
        private NormalizerService  $normalizer,
    ) {}

    /**
     * الوضع 1: مقارنة ملف يرفعه العميل مع المنصة ككل (المسارات النشطة).
     */
    public function __invoke(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $rows = $this->readRows($request);

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.']);
        }

        $cache = $this->loadAllPlatformCache($rows);
        $lines = $this->sortLines($this->buildLines($rows, $cache));

        return response()->json([
            'rows_read' => count($rows),
            'lines'     => $lines,
        ]);
    }

    /**
     * قائمة الملفات المرفوعة من الأدمن (لاختيارها في أوضاع المقارنة).
     */
    public function uploads(Request $request)
    {
        $uploads = Upload::query()
            ->with('supplier:id,name')
            ->whereHas('offers')
            ->orderByDesc('created_at')
            ->get(['id', 'supplier_id', 'file_path', 'status', 'matched_count', 'created_at']);

        return response()->json([
            'uploads' => $uploads->map(function (Upload $upload) {
                return [
                    'id'            => $upload->id,
                    'file_name'     => $upload->original_name,
                    'supplier'      => $upload->supplier?->name,
                    'status'        => $upload->status,
                    'matched_count' => $upload->matched_count,
                    'created_at'    => $upload->created_at?->toDateTimeString(),
                ];
            })->values(),
        ]);
    }

    /**
     * الوضع 2: مقارنة ملف يرفعه العميل مع ملف واحد محدد من الملفات المرفوعة من الأدمن.
     */
    public function fileToUpload(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $data = $request->validate([
            'file'      => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'upload_id' => ['required', 'exists:uploads,id'],
        ]);

        $rows = $this->readRows($request);

        if (empty($rows)) {
            throw ValidationException::withMessages(['file' => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.']);
        }

        $uploadId = (int) $data['upload_id'];
        $cache    = $this->loadUploadCache($uploadId);
        $lines    = $this->sortLines($this->buildLines($rows, $cache, $uploadId));

        return response()->json([
            'rows_read' => count($rows),
            'lines'     => $lines,
        ]);
    }

    /**
     * الوضع 3: مقارنة ملفين من الملفات المرفوعة من الأدمن مع بعضهما.
     *
     * المنتجات مربوطة بكل مورد على حدة (منتج الشيت = مورد واحد)، لذلك تتم
     * المطابقة بين الملفين عبر الاسم المُطبّع (normalized_name) وليس product_id.
     * تتم المطابقة الحرفية أولًا (كما كانت سابقًا) ثم المطابقة الضبابية
     * بنفس منطق المقارنة الذكية (matchScore) للمتبقي حتى لا تضيع المطابقات
     * عندما يكتب الموردان نفس الدواء باختلاف بسيط.
     */
    public function uploadsCompare(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $data = $request->validate([
            'upload_id_a' => ['required', 'exists:uploads,id'],
            'upload_id_b' => ['required', 'exists:uploads,id', 'different:upload_id_a'],
        ]);

        $socketB = Upload::with('supplier:id,name')->findOrFail($data['upload_id_b']);

        $entriesA = $this->buildUploadsIndex((int) $data['upload_id_a']);
        $entriesB = $this->buildUploadsIndex((int) $data['upload_id_b']);

        $lines = [];
        $usedB = [];
        $usedA = [];

        // Pass 1: مطابقة حرفية (نفس السلوك القديم) للحفاظ على النتائج الموجودة.
        foreach ($entriesA as $name => $entryA) {
            if (! isset($entriesB[$name])) {
                continue;
            }

            $priceA = $entryA['offer']->price;
            $priceB = $entriesB[$name]['offer']->price;

            // حتى مع تطابق الاسم حرفيًا، الأسعار المتباعدة بشدة تعني غالبًا
            // منتجًا مختلفًا (نوع/جرامات مختلفة)، فيُترك للمطابقة الضبابية التي
            // تُخضعها أيضًا لفلتر السعر، وإلا تظهر ضمن "موجود في ملف واحد فقط".
            $priceScore = $this->applyPriceScore(
                $priceA !== null ? (float) $priceA : null,
                $priceB !== null ? (float) $priceB : null,
                100.0,
            );

            if ($priceScore < 100.0) {
                continue;
            }

            $usedA[$name] = true;
            $usedB[$name] = true;

            $lines[] = $this->buildPairLine($entryA, $entriesB[$name], $socketB, 100.0);
        }

        // Pass 2: مطابقة ضبابية للمتبقي من A ضد المتبقي من B.
        foreach ($entriesA as $nameA => $entryA) {
            if (isset($usedA[$nameA])) {
                continue;
            }

            $bestKey   = null;
            $bestScore = 0.0;

            foreach ($entriesB as $nameB => $entryB) {
                if (isset($usedB[$nameB])) {
                    continue;
                }

                $priceA = $entryA['offer']->price;
                $priceB = $entryB['offer']->price;

                $score = $this->applyPriceScore(
                    $priceA !== null ? (float) $priceA : null,
                    $priceB !== null ? (float) $priceB : null,
                    $this->drugOverlapScore($nameA, $nameB),
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestKey   = $nameB;
                }
            }

            if ($bestKey === null || $bestScore < self::MIN_NAME_OVERLAP) {
                $lines[] = $this->buildOnlyALine($entryA);

                continue;
            }

            $usedB[$bestKey] = true;
            $lines[] = $this->buildPairLine($entryA, $entriesB[$bestKey], $socketB, round($bestScore, 1));
        }

        // Pass 3: المتبقي من B فقط.
        foreach ($entriesB as $nameB => $entryB) {
            if (isset($usedB[$nameB])) {
                continue;
            }

            $lines[] = $this->buildOnlyBLine($entryB, $socketB);
        }

        $lines = $this->sortLines($lines);

        return response()->json([
            'rows_read' => count($lines),
            'lines'     => $lines,
        ]);
    }

    /**
     * نسبة تداخل كلمات المحتوى بين اسمين (0-100).
     *
     * بخلاف similar_text التي تُعطي نتائج مضللة في العربية (تحتسب الأحرف
     * المشتركة بين أي اسمين)، تعتمد هذه النسبة على المطابقة الحرفية التامة
     * لكلمات الدواء المميزة (اسم التركيبة) بعد تجريد الأرقام واستبعاد كلمات
     * الصيغة العامة. فلا يُقبل ربط اسمين مختلفين تمامًا (مثل اتورستات ↔
     * اتوريزا) ولا الأسماء التي تشترك فقط في بادئة قصيرة (مثل اكس ↔ اكساميد).
     *
     * القاسم = الأكبر بين عدد الكلمتين. فيُعطى تطابقًا تامًا (100) فقط عندما
     * تتطابق كل كلمات المحتوى في الاسمين، أما إذا احتوى أحدهما على كلمة محتوى
     * إضافية (متغير/تركيبة أخرى مثل "اكسستر" أو "بلس") فلا يُقبل كتطابق كامل.
     *
     * الجرعة الرقمية (مثل 60مجم / 90مجم / 2.5مجم) جزء من هوية المنتج: إذا حمل
     * كل اسم جرعة رقمية مختلفة تمامًا فالمعنى منتجان مختلفان مهما تطابق الاسم،
     * فيُرفض الربط من البداية ولا يُترك للسعر وحده. الرقم الأول في الاسم هو
     * الجرعة/التركيز عادةً، واختلافه يكفي للرفض حتى لو اشتركا في رقم ثانوي
     * (مثل عدد الأشرطة "3 شريط").
     *
     * الاسم التجاري (أول كلمة محتوى مميزة) جزء أساسي من الهوية أيضًا: اختلافه
     * يعني منتجًا مختلفًا حتى لو اشتركا في كلمات وصفية عامة مثل "صن بلوك" أو
     * "بديل ميلجا".
     *
     * كلمة التمييز الثانية (النكهة/الرائحة/المتغير مثل "تفاح"/"مسواك"/"سموكرز")
     * جزء من الهوية كذلك: إذا حمل كل اسم كلمة تمييز مختلفة فهما منتجان مختلفان
     * حتى لو تقارب سعرهما، لأن منتجات الرائحة/النكهة المختلفة كثيرًا ما تتساوى
     * أسعارها تقريبًا.
     */
    private function drugOverlapScore(string $nameA, string $nameB): float
    {
        $tokensA = $this->contentTokens($nameA);
        $tokensB = $this->contentTokens($nameB);

        $numbersA = $this->contentNumbers($nameA);
        $numbersB = $this->contentNumbers($nameB);

        // جرعة/تركيز مختلف تمامًا → منتجان مختلفان.
        if (! empty($numbersA) && ! empty($numbersB)) {
            if (array_intersect($numbersA, $numbersB) === []) {
                return 0.0;
            }

            if (($numbersA[0] ?? null) !== ($numbersB[0] ?? null)) {
                return 0.0;
            }
        }

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        // الاسم التجاري (أول كلمة محتوى) مختلف → منتجان مختلفان.
        if (($tokensA[0] ?? '') !== ($tokensB[0] ?? '')) {
            return 0.0;
        }

        // كلمة التمييز الثانية مختلفة (رائحة/نكهة/متغير) → منتجان مختلفان.
        if (isset($tokensA[1], $tokensB[1]) && $tokensA[1] !== $tokensB[1]) {
            return 0.0;
        }

        $shared = [];

        foreach ($tokensA as $tokenA) {
            if (in_array($tokenA, $tokensB, true)) {
                $shared[] = $tokenA;
            }
        }

        if (empty($shared)) {
            return 0.0;
        }

        $longestShared = max(array_map('mb_strlen', $shared));

        if ($longestShared < 4) {
            return 0.0;
        }

        return round((count($shared) / max(count($tokensA), count($tokensB))) * 100, 1);
    }

    /**
     * كلمات المحتوى المميزة في اسم دواء.
     *
     * يُقسَّم الاسم إلى متتاليات حروف فقط (تُجرَّد الأرقام والرموز تمامًا،
     * فيصبح "اتورستات20قرص" = "اتورستات" و"80ق" بلا محتوى)، ثم تُوحَّد الحروف
     * العربية الشائعة (أ/إ/آ → ا، ى/ئ → ي، ة → ه) وتُستبعد كلمات الصيغة العامة
     * والأحرف المفردة.
     */
    private function contentTokens(string $name): array
    {
        preg_match_all('/\p{L}+/u', $name, $matches);

        $out = [];

        foreach ($matches[0] as $run) {
            $lower = mb_strtolower($run);
            $lower = str_replace(
                ['أ', 'إ', 'آ', 'ٱ', 'ى', 'ئ', 'ة', 'ؤ', 'ء'],
                ['ا', 'ا', 'ا', 'ا', 'ي', 'ي', 'ه', 'و', ''],
                $lower
            );

            if (mb_strlen($lower) < 2) {
                continue;
            }

            if (in_array($lower, self::DRUG_STOP_WORDS, true)) {
                continue;
            }

            $out[] = $lower;
        }

        return $out;
    }

    /**
     * القيم الرقمية في اسم الدواء (الجرعة/التركيز/عدد الأقراص مثل 60مجم أو 2.5).
     *
     * تُوحَّد الأرقام العربية الهندية (٢٫٥) إلى أرقام لاتينية (2.5) حتى يُقارن
     * "٢٠مجم" بـ "20مجم" بشكل صحيح.
     *
     * @return list<string>
     */
    private function contentNumbers(string $name): array
    {
        $text = str_replace(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $name);

        preg_match_all('/\d+(?:\.\d+)?/u', $text, $matches);

        return $matches[0];
    }

    /**
     * بناء سطر "مطابقة" بين منتج من الملف الأول ومنتج من الملف الثاني.
     *
     * @param  array{product: Product, offer: Offer}  $entryA
     * @param  array{product: Product, offer: Offer}  $entryB
     * @param  Upload  $socketB
     */
    private function buildPairLine(array $entryA, array $entryB, Upload $socketB, float $similarity): array
    {
        $productA = $entryA['product'];
        $productB = $entryB['product'];
        $offerA   = $entryA['offer'];
        $offerB   = $entryB['offer'];

        $nameA = $productA->name_ar ?: $productA->name_en;
        $nameB = $productB->name_ar ?: $productB->name_en;

        $priceA    = $offerA->price !== null ? (float) $offerA->price : null;
        $discountA = $offerA->discount !== null ? (float) $offerA->discount : null;
        $priceB    = $offerB->price !== null ? (float) $offerB->price : null;
        $discountB = $offerB->discount !== null ? (float) $offerB->discount : null;

        return [
            'query'          => $nameA,
            'sheet'          => [
                'name'     => $nameA,
                'price'    => $priceA,
                'discount' => $discountA,
            ],
            'matched_product' => $nameB,
            'similarity'      => $similarity,
            'platform_best'   => [
                'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                'area'     => $offerB->supplier?->area,
                'phone'    => $offerB->supplier?->phone1 ?: $offerB->supplier?->phone2,
                'price'    => $priceB,
                'discount' => $discountB,
            ],
            'comparison' => [
                'price_diff'    => ($priceA !== null && $priceB !== null)
                    ? round($priceA - $priceB, 2) : null,
                'discount_diff' => ($discountA !== null && $discountB !== null)
                    ? round($discountA - $discountB, 2) : null,
            ],
            'count'    => 1,
            'status'   => 'both',
            'skipped'  => false,
        ];
    }

    /**
     * بناء سطر "موجود فقط في الملف الأول".
     *
     * @param  array{product: Product, offer: Offer}  $entryA
     */
    private function buildOnlyALine(array $entryA): array
    {
        $productA = $entryA['product'];
        $offerA   = $entryA['offer'];

        $nameA = $productA->name_ar ?: $productA->name_en;

        return [
            'query'          => $nameA,
            'sheet'          => [
                'name'     => $nameA,
                'price'    => $offerA->price !== null ? (float) $offerA->price : null,
                'discount' => $offerA->discount !== null ? (float) $offerA->discount : null,
            ],
            'matched_product' => $nameA,
            'similarity'      => 0.0,
            'platform_best'   => null,
            'comparison'      => ['price_diff' => null, 'discount_diff' => null],
            'count'    => 1,
            'status'   => 'only_a',
            'skipped'  => false,
        ];
    }

    /**
     * بناء سطر "موجود فقط في الملف الثاني".
     *
     * @param  array{product: Product, offer: Offer}  $entryB
     */
    private function buildOnlyBLine(array $entryB, Upload $socketB): array
    {
        $productB = $entryB['product'];
        $offerB   = $entryB['offer'];

        $nameB = $productB->name_ar ?: $productB->name_en;

        return [
            'query'          => $nameB,
            'sheet'          => null,
            'matched_product' => $nameB,
            'similarity'      => 0.0,
            'platform_best'   => [
                'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                'area'     => $offerB->supplier?->area,
                'phone'    => $offerB->supplier?->phone1 ?: $offerB->supplier?->phone2,
                'price'    => $offerB->price !== null ? (float) $offerB->price : null,
                'discount' => $offerB->discount !== null ? (float) $offerB->discount : null,
            ],
            'comparison' => ['price_diff' => null, 'discount_diff' => null],
            'count'    => 1,
            'status'   => 'only_b',
            'skipped'  => false,
        ];
    }

    /**
     * بناء فهرس منتجات ملف مرفوع: normalized_name => أفضل عرض (أقل سعر) للمنتج.
     *
     * @return array<string, array{product: Product, offer: Offer}>
     */
    private function buildUploadsIndex(int $uploadId): array
    {
        $bestOffers = $this->bestOffersByProduct($uploadId);

        $products = Product::query()
            ->whereIn('id', $bestOffers->keys()->all())
            ->get(['id', 'name_ar', 'name_en', 'normalized_name'])
            ->keyBy('id');

        $index = [];

        foreach ($bestOffers as $productId => $offer) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $key = trim((string) $product->normalized_name);

            if ($key === '') {
                continue;
            }

            $existing = $index[$key] ?? null;

            if (! $existing || (float) $offer->price < (float) $existing['offer']->price) {
                $index[$key] = [
                    'product' => $product,
                    'offer'   => $offer,
                ];
            }
        }

        return $index;
    }

    /**
     * قراءة صفوف الملف المرفوع وتنظيفه مؤقتاً بعد القراءة.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readRows(Request $request): array
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $path     = $request->file('file')->store('temp/compare-platform/' . now()->format('Y/m'), 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            return $this->excelSearchService->readRowsAutoForPlatformCompare($fullPath, self::MAX_ROWS);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * الوضع 1: منتجات المنصة النشطة المطابقة لكلمات مفتاحية من الملف.
     *
     * كانت النسخة القديمة تبحث بـ LIKE عن الكلمات داخل جدول المنتجات الكامل ثم
     * تحمّل عروض كل النتائج — ما كان يستنزف الذاكرة ويمرر قائمة ids ضخمة داخل
     * IN في نفس الـ query. هنا نحمّل مرة واحدة فقط كل المنتجات ذات العروض
     * النشطة (عددها محدود) ونطبق نفس الفلترة بالكلمات في PHP.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function loadAllPlatformCache(array $rows): Collection
    {
        $keywords = [];

        foreach ($rows as $row) {
            $query = trim((string) $row['name']);
            if (mb_strlen($query) < 3) {
                continue;
            }
            $normalized = $this->normalizer->normalize($query);
            $firstWord  = explode(' ', $normalized)[0] ?? '';
            if (mb_strlen($firstWord) >= 2) {
                $keywords[$firstWord] = true;
            }

            // Keep a second, broader token so file-vs-platform compare does not drop valid results
            // when the product name in the sheet includes extra words/dosage fragments.
            foreach (preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
                if (mb_strlen($token) >= 3) {
                    $keywords[$token] = true;
                }
            }
        }

        $candidates = Product::query()
            ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->whereHas('offers', fn ($q) => $q->active())
            ->with([
                'offers' => function ($q) {
                    $q->active()
                        ->orderBy('price')
                        ->with('supplier:id,name,area,phone1,phone2');
                },
            ])
            ->get()
            ->filter(fn (Product $p) => $p->offers->isNotEmpty());

        if (empty($keywords)) {
            return $candidates;
        }

        $keywordList = array_keys($keywords);

        return $candidates->filter(function (Product $p) use ($keywordList) {
            $name = (string) $p->normalized_name;

            foreach ($keywordList as $kw) {
                if (str_starts_with($name, $kw) || str_contains($name, $kw)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * الوضع 2: تحميل منتجات ملف واحد محدد من ملفات الأدمن (بكل عروضه).
     */
    private function loadUploadCache(int $uploadId): Collection
    {
        return Product::query()
            ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->whereHas('offers', function ($q) use ($uploadId) {
                $q->where('upload_id', $uploadId);
            })
            ->with([
                'offers' => function ($q) use ($uploadId) {
                    $q->where('upload_id', $uploadId)
                        ->orderBy('price')
                        ->with('supplier:id,name,area,phone1,phone2');
                },
            ])
            ->get()
            ->filter(fn(Product $p) => $p->offers->isNotEmpty());
    }

    /**
     * بناء سطور المقارنة لكل صف من الملف مقابل مجموعة المنتجات المتاحة.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function buildLines(array $rows, Collection $cachedProducts, ?int $uploadId = null): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $rawQuery = trim((string) $row['name']);

            if (mb_strlen($rawQuery) < 3) {
                $lines[] = [
                    'query'   => $rawQuery,
                    'sheet'   => $row,
                    'skipped' => true,
                    'reason'  => 'min_length',
                ];
                continue;
            }

            $normalizedQuery = $this->normalizer->normalize($rawQuery);
            $firstWord       = explode(' ', $normalizedQuery)[0] ?? '';

            $candidates = $firstWord !== ''
                ? $cachedProducts->filter(fn(Product $p) => $this->matchesPlatformProductCandidate($normalizedQuery, $firstWord, $p))
                : $cachedProducts;

            if ($candidates->isEmpty() && mb_strlen($firstWord) >= 3) {
                $offerWith = $this->offerWithClosure($uploadId);

                $fallback = Product::query()
                    ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                    ->where(function ($q) use ($firstWord, $normalizedQuery) {
                        $q->where('normalized_name', 'LIKE', '%' . $firstWord . '%')
                            ->orWhere('normalized_name', 'LIKE', '%' . $normalizedQuery . '%');
                    })
                    ->with(['offers' => $offerWith])
                    ->limit(20)
                    ->get()
                    ->filter(fn(Product $p) => $p->offers->isNotEmpty());

                $candidates = $fallback;

                if ($candidates->isEmpty() && mb_strlen($normalizedQuery) >= 6) {
                    $partialQuery = mb_substr($normalizedQuery, 0, 8);
                    $candidates = Product::query()
                        ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
                        ->where('normalized_name', 'LIKE', '%' . $partialQuery . '%')
                        ->with(['offers' => $offerWith])
                        ->limit(20)
                        ->get()
                        ->filter(fn(Product $p) => $p->offers->isNotEmpty());
                }
            }

            $bestScore   = 0.0;
            $bestProduct = null;

            $sheetPrice = $row['price'] ?? null;

            foreach ($candidates as $product) {
                $bestOfferPrice = $product->offers->sortBy('price')->first()?->price;

                $score = $this->matchScore(
                    $normalizedQuery,
                    $rawQuery,
                    (string) ($product->normalized_name ?? ''),
                    (string) ($product->name_ar ?? ''),
                    (string) ($product->name_en ?? ''),
                    $sheetPrice !== null ? (float) $sheetPrice : null,
                    $bestOfferPrice !== null ? (float) $bestOfferPrice : null,
                );

                if ($score > $bestScore) {
                    $bestScore   = $score;
                    $bestProduct = $product;
                }
            }

            if ($bestScore < self::MIN_SIMILARITY || $bestProduct === null) {
                $lines[] = $this->noMatchLine($row, $rawQuery);
                continue;
            }

            $bestOffer        = $bestProduct->offers->sortBy('price')->first();
            $sheetPrice       = $row['price'];
            $sheetDiscount    = $row['discount'];
            $platformPrice    = $bestOffer ? (float) $bestOffer->price    : null;
            $platformDiscount = $bestOffer ? (float) $bestOffer->discount : null;

            $lines[] = [
                'query'               => $rawQuery,
                'sheet'               => $row,
                'search_results_count' => 1,
                'matched_product'     => $bestProduct->name_ar ?: $bestProduct->name_en,
                'similarity'          => round($bestScore, 1),
                'platform_best'       => [
                    'supplier' => $bestOffer?->supplier?->name,
                    'area'     => $bestOffer?->supplier?->area,
                    'phone'    => $bestOffer?->supplier?->phone1 ?: $bestOffer?->supplier?->phone2,
                    'price'    => $platformPrice,
                    'discount' => $platformDiscount,
                ],
                'comparison' => [
                    'price_diff'    => ($sheetPrice !== null && $platformPrice !== null)
                        ? round($sheetPrice - $platformPrice, 2) : null,
                    'discount_diff' => ($sheetDiscount !== null && $platformDiscount !== null)
                        ? round($sheetDiscount - $platformDiscount, 2) : null,
                ],
                'count'   => $bestProduct->offers->count(),
                'status'  => 'both',
                'skipped' => false,
            ];
        }

        return $lines;
    }

    private function matchesPlatformProductCandidate(string $normalizedQuery, string $firstWord, Product $product): bool
    {
        $productName = (string) ($product->normalized_name ?? '');
        if ($productName === '') {
            return false;
        }

        if ($firstWord !== '' && (str_starts_with($productName, $firstWord) || str_contains($productName, $firstWord))) {
            return true;
        }

        foreach (preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            if (mb_strlen($token) < 3) {
                continue;
            }

            if (str_contains($productName, $token)) {
                return true;
            }
        }

        similar_text($normalizedQuery, $productName, $pct);

        return $pct >= 35.0;
    }

    /**
     * Closure لتحميل العروض حسب الوضع (كل عرض الملف المحدد، أو العروض النشطة).
     */
    private function offerWithClosure(?int $uploadId): callable
    {
        return function ($q) use ($uploadId) {
            if ($uploadId !== null) {
                $q->where('upload_id', $uploadId);
            } else {
                $q->active();
            }
            $q->orderBy('price')->with('supplier:id,name,area,phone1,phone2');
        };
    }

    /**
     * أفضل عرض (أقل سعر) لكل منتج داخل ملف مرفوع معين.
     */
    private function bestOffersByProduct(int $uploadId): Collection
    {
        return Offer::query()
            ->where('upload_id', $uploadId)
            ->with('supplier:id,name,area,phone1,phone2')
            ->orderBy('price')
            ->get()
            ->groupBy('product_id')
            ->map->first();
    }

    /**
     * ترتيب السطور: المطابقات أولاً ← بها عرض ← بالاسم.
     */
    private function sortLines(array $lines): array
    {
        usort($lines, function (array $a, array $b): int {
            $aMatch = (int) (($a['count'] ?? 0) > 0);
            $bMatch = (int) (($b['count'] ?? 0) > 0);
            if ($aMatch !== $bMatch) {
                return $bMatch <=> $aMatch;
            }
            if (($a['status'] ?? '') === 'only_b') {
                return 1;
            }
            if (($b['status'] ?? '') === 'only_b') {
                return -1;
            }
            $aOffer = (int) (!empty($a['platform_best']['supplier']));
            $bOffer = (int) (!empty($b['platform_best']['supplier']));
            if ($aOffer !== $bOffer) {
                return $bOffer <=> $aOffer;
            }
            return strcmp((string) ($a['query'] ?? ''), (string) ($b['query'] ?? ''));
        });

        return $lines;
    }

    /**
     * حساب نسبة التشابه بين اسم من الشيت واسم منتج من الـ DB.
     *
     * بخلاف النسخة القديمة التي كانت تعتمد على similar_text (تُعطي نتائج
     * مضللة في العربية لأنها تحتسب الأحرف المشتركة بين أي اسمين مثل اتورستات ↔
     * اتوريزا)، تُعتمد هنا نسبة تداخل كلمات المحتوى الحرفية بعد تجريد الأرقام
     * واستبعاد كلمات الصيغة العامة، مع فلتر سعر يؤكد المطابقة عندما تتساوى
     * الأسعار (المتعارف عليه في السوق أن سعر المنتج الواحد موحد) ويرفضها
     * عندما تتباعد الأسعار بشكل كبير.
     */
    private function matchScore(
        string $normalizedQuery,
        string $rawQuery,
        string $normalizedName,
        string $nameAr,
        string $nameEn,
        ?float $sheetPrice = null,
        ?float $productPrice = null,
    ): float {
        $scores = [];

        if ($normalizedName !== '') {
            $scores[] = $this->drugOverlapScore($normalizedQuery, $normalizedName);
        }

        if ($nameAr !== '') {
            $scores[] = $this->drugOverlapScore($normalizedQuery, $this->normalizer->normalize($nameAr));
        }

        if ($nameEn !== '') {
            $scores[] = $this->drugOverlapScore($normalizedQuery, $nameEn);
        }

        $nameScore = $scores ? max($scores) : 0.0;

        if ($nameScore <= 0.0) {
            return 0.0;
        }

        return $this->applyPriceScore($sheetPrice, $productPrice, $nameScore);
    }

    /**
     * فلتر السعر: لا يُقبل التطابق إلا إذا كان السعر متساويًا أو قريبًا.
     *
     * القاعدة: المنتج الدوائي الواحد في السوق له سعر واحد (مُحدَّد رسميًا في
     * الغالب)، فالاسم المتشابه مع سعر مختلف بشدة (نوع/جرامات/تركيبة مختلفة مثل
     * سعر 14 مقابل 72) يعني غالبًا منتجًا مختلفًا مهما بلغت نسبة تشابه الاسم.
     * التطابق الجزئي (كلمات زائدة مثل "بلس"/"اكسستر") يتطلب سعرًا متقاربًا
     * جدًا (≥ 95%) ليكون مقبولًا، أما التطابق التام للاسم فيقبل التباين
     * المعقول في السعر ولا يُرفض إلا عند تباعد شديد.
     */
    private function applyPriceScore(?float $sheetPrice, ?float $productPrice, float $nameScore): float
    {
        if ($sheetPrice === null || $productPrice === null || $sheetPrice <= 0 || $productPrice <= 0) {
            return $nameScore;
        }

        $ratio = min($sheetPrice, $productPrice) / max($sheetPrice, $productPrice);

        if ($ratio >= self::PRICE_EQUAL_RATIO) {
            return min(100.0, $nameScore + 10);
        }

        if ($ratio < self::PRICE_CLOSE_RATIO) {
            return $nameScore * self::PRICE_FAR_PENALTY;
        }

        if ($nameScore < 100.0) {
            return $nameScore * 0.6;
        }

        return $nameScore;
    }

    /**
     * سطر "لم يُعثر على مطابق".
     */
    private function noMatchLine(array $row, string $rawQuery): array
    {
        return [
            'query'               => $rawQuery,
            'sheet'               => $row,
            'search_results_count' => 0,
            'matched_product'     => null,
            'similarity'          => 0,
            'platform_best'       => [
                'supplier' => null,
                'area'     => null,
                'phone'    => null,
                'price'    => null,
                'discount' => null,
            ],
            'comparison' => [
                'price_diff'    => null,
                'discount_diff' => null,
            ],
            'count'   => 0,
            'status'  => 'no_match',
            'skipped' => false,
        ];
    }
}
