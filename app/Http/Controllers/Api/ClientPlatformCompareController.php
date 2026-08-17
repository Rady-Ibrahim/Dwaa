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
use Illuminate\Support\Facades\Log;
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
        'اقراص',
        'قرص',
        'كبسول',
        'كبسوله',
        'كبسولات',
        'حبوب',
        'شراب',
        'حقن',
        'قطرات',
        'قطره',
        'نقط',
        'مرهم',
        'كريم',
        'جل',
        'بخاخ',
        'سبراي',
        'محلول',
        'معلق',
        'لبوس',
        'تحاميل',
        'امبول',
        'امبولات',
        'شريط',
        'شرايط',
        'باكت',
        'علبه',
        'زجاجه',
        'مسحوق',
        'بودره',
        'سيروم',
        'لوشن',
        'لوسيون',
        'مجم',
        'ملجم',
        'مليجرام',
        'جرام',
        'ملى',
        'مللى',
        'مل',
        'سم',
        'لتر',
        'كجم',
        'فموي',
        'وريدي',
        'موضعي',
        'مستمر',
        'معجل',
        'مؤخر',
        'سعر',
        'جنيه',
        'جنية',
        // كلمات وصفية عامة لا تُحدّد هوية المنتج
        'مع',
        'وزن',
        'عبوه',
        'محيط',
        'العين',
        'مطهر',
        'غسول',
        'جديد',
        'احمر',
        'اكسترا',
        'ال',
        'جيل',
        'كبير',
        'صغير',
        'كبيره',
        'صغيره',
        'صن',
        'بلوك',
        'بديل',
        'سيرم',
        'شامبو',
        'مضاد',
        'حيوي',
        'ادويه',
        'علاج',
        'دواء',
        'مستحضر',
        'كبسولات',
    ];

    /** ذاكرة تجهيز أسماء الأدوية داخل الطلب لتجنّب إعادة التحليل في كل مقارنة. */
    private array $namePrepareCache = [];

    /** نسخة معكوسة من كلمات الاستبعاد للبحث الفوري (O(1)). */
    private static ?array $stopWordMap = null;

    public function __construct(
        private ExcelSearchService $excelSearchService,
        private NormalizerService $normalizer,
    ) {}

    /**
     * الوضع 1: مقارنة ملف يرفعه العميل مع المنصة ككل (المسارات النشطة).
     */
    public function __invoke(Request $request)
    {
        $startMs = microtime(true);

        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        Log::info('PlatformCompare: START', [
            'user_id' => $request->user()?->id,
            'file_size' => $request->file('file')?->getSize(),
            'file_mime' => $request->file('file')?->getMimeType(),
            'file_name' => $request->file('file')?->getClientOriginalName(),
        ]);

        try {
            $rows = $this->readRows($request);
        } catch (\Throwable $e) {
            Log::error('PlatformCompare: readRows FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }

        Log::info('PlatformCompare: readRows done', [
            'rows_count' => count($rows),
            'elapsed_ms' => round((microtime(true) - $startMs) * 1000),
        ]);

        if (empty($rows)) {
            Log::warning('PlatformCompare: empty rows, aborting', [
                'elapsed_ms' => round((microtime(true) - $startMs) * 1000),
            ]);
            throw ValidationException::withMessages(['file' => 'تعذّر قراءة الملف أو لم يُكتشف هيدر صالح.']);
        }

        $cacheStart = microtime(true);

        try {
            $cache = $this->loadAllPlatformCache($rows);
        } catch (\Throwable $e) {
            Log::error('PlatformCompare: loadAllPlatformCache FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'rows_count' => count($rows),
            ]);
            throw $e;
        }

        Log::info('PlatformCompare: loadAllPlatformCache done', [
            'products_loaded' => $cache->count(),
            'elapsed_ms' => round((microtime(true) - $cacheStart) * 1000),
        ]);

        $buildStart = microtime(true);

        try {
            $lines = $this->buildLines($rows, $cache);
        } catch (\Throwable $e) {
            Log::error('PlatformCompare: buildLines FAILED', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'rows_count' => count($rows),
                'products_count' => $cache->count(),
            ]);
            throw $e;
        }

        $lines = $this->sortLines($lines);

        Log::info('PlatformCompare: DONE', [
            'rows_count' => count($rows),
            'products_count' => $cache->count(),
            'lines_count' => count($lines),
            'total_elapsed_ms' => round((microtime(true) - $startMs) * 1000),
            'build_elapsed_ms' => round((microtime(true) - $buildStart) * 1000),
        ]);

        return response()->json([
            'rows_read' => count($rows),
            'lines' => $lines,
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
                    'id' => $upload->id,
                    'file_name' => $upload->original_name,
                    'supplier' => $upload->supplier?->name,
                    'status' => $upload->status,
                    'matched_count' => $upload->matched_count,
                    'created_at' => $upload->created_at?->toDateTimeString(),
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
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
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

        // تجهيز أسماء الملفين مرة واحدة وتجميع B حسب أول كلمة محتوى حتى لا
        // تُقارَن كل الأسماء ضد كل الأسماء (حلقة داخلية ضخمة) — أول كلمة محتوى
        // مختلفة تعني منتجًا مختلفًا على أي حال في drugOverlapScorePrepared.
        $preparedA = [];
        foreach ($entriesA as $nameA => $entryA) {
            $preparedA[$nameA] = $this->prepareName((string) $nameA);
        }

        $preparedB = [];
        $bByFirstToken = [];
        foreach ($entriesB as $nameB => $entryB) {
            $preparedB[$nameB] = $this->prepareName((string) $nameB);

            $firstToken = $preparedB[$nameB]['tokens'][0] ?? '';
            if ($firstToken !== '') {
                $bByFirstToken[$firstToken][] = $nameB;
            }
        }

        // Pass 2: مطابقة ضبابية للمتبقي من A ضد المتبقي من B.
        foreach ($entriesA as $nameA => $entryA) {
            if (isset($usedA[$nameA])) {
                continue;
            }

            $bestKey = null;
            $bestScore = 0.0;

            $prepA = $preparedA[$nameA];
            $candidateNames = isset($prepA['tokens'][0])
                ? ($bByFirstToken[$prepA['tokens'][0]] ?? [])
                : [];

            foreach ($candidateNames as $nameB) {
                if (isset($usedB[$nameB])) {
                    continue;
                }

                $entryB = $entriesB[$nameB];

                $priceA = $entryA['offer']->price;
                $priceB = $entryB['offer']->price;

                $prepB = $preparedB[$nameB];

                $score = $this->applyPriceScore(
                    $priceA !== null ? (float) $priceA : null,
                    $priceB !== null ? (float) $priceB : null,
                    $this->drugOverlapScorePrepared(
                        $prepA['tokens'],
                        $prepB['tokens'],
                        $prepA['numbers'],
                        $prepB['numbers'],
                    ),
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestKey = $nameB;
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
            'rows_read' => count($entriesA) + count($entriesB),
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
        $preparedA = $this->prepareName($nameA);
        $preparedB = $this->prepareName($nameB);

        return $this->drugOverlapScorePrepared(
            $preparedA['tokens'],
            $preparedB['tokens'],
            $preparedA['numbers'],
            $preparedB['numbers'],
        );
    }

    /**
     * تجهيز اسم الدواء مرة واحدة داخل الطلب (كلمات المحتوى + الأرقام) ليُعاد
     * استخدامه في كل المقارنات بدل إعادة تحليله في كل مكالمة.
     *
     * @return array{tokens: list<string>, numbers: list<string>}
     */
    private function prepareName(string $name): array
    {
        return $this->namePrepareCache[$name] ??= [
            'tokens' => $this->contentTokens($name),
            'numbers' => $this->contentNumbers($name),
        ];
    }

    /**
     * نسبة تداخل كلمات المحتوى بين اسمين (0-100) بعد تجهيزهما مسبقًا.
     *
     * @param  list<string>  $tokensA
     * @param  list<string>  $tokensB
     * @param  list<string>  $numbersA
     * @param  list<string>  $numbersB
     */
    private function drugOverlapScorePrepared(
        array $tokensA,
        array $tokensB,
        array $numbersA,
        array $numbersB,
    ): float {
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

            if (isset(self::stopWordMap()[$lower])) {
                continue;
            }

            $out[] = $lower;
        }

        return $out;
    }

    /**
     * خريطة كلمات الاستبعاد (مقلوبة) للبحث الفوري بدل in_array الخطي.
     *
     * @return array<string, int>
     */
    private static function stopWordMap(): array
    {
        return self::$stopWordMap ??= array_flip(self::DRUG_STOP_WORDS);
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
     */
    private function buildPairLine(array $entryA, array $entryB, Upload $socketB, float $similarity): array
    {
        $productA = $entryA['product'];
        $productB = $entryB['product'];
        $offerA = $entryA['offer'];
        $offerB = $entryB['offer'];

        $nameA = $productA->name_ar ?: $productA->name_en;
        $nameB = $productB->name_ar ?: $productB->name_en;

        $priceA = $offerA->price !== null ? (float) $offerA->price : null;
        $priceB = $offerB->price !== null ? (float) $offerB->price : null;

        return [
            'query' => $nameA,
            'price' => $priceA,
            'discount' => $offerA->discount !== null ? (float) $offerA->discount : null,
            'matched_product' => $nameB,
            'similarity' => $similarity,
            'platform_best' => [
                'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                'price' => $priceB,
                'discount' => $offerB->discount !== null ? (float) $offerB->discount : null,
            ],
            'status' => 'both',
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
        $offerA = $entryA['offer'];

        $nameA = $productA->name_ar ?: $productA->name_en;

        return [
            'query' => $nameA,
            'price' => $offerA->price !== null ? (float) $offerA->price : null,
            'discount' => $offerA->discount !== null ? (float) $offerA->discount : null,
            'matched_product' => $nameA,
            'similarity' => 0.0,
            'platform_best' => null,
            'status' => 'only_a',
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
        $offerB = $entryB['offer'];

        $nameB = $productB->name_ar ?: $productB->name_en;

        return [
            'query' => $nameB,
            'price' => null,
            'discount' => null,
            'matched_product' => $nameB,
            'similarity' => 0.0,
            'platform_best' => [
                'supplier' => $socketB->supplier?->name ?: $offerB->supplier?->name,
                'price' => $offerB->price !== null ? (float) $offerB->price : null,
                'discount' => $offerB->discount !== null ? (float) $offerB->discount : null,
            ],
            'status' => 'only_b',
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
                    'offer' => $offer,
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

        $path = $request->file('file')->store('temp/compare-platform/' . now()->format('Y/m'), 'local');
        $fullPath = Storage::disk('local')->path($path);

        Log::info('PlatformCompare: readRows - file stored', [
            'path' => $path,
            'fullPath' => $fullPath,
            'exists' => file_exists($fullPath),
            'size' => @filesize($fullPath),
        ]);

        try {
            $rows = $this->excelSearchService->readRowsAutoForPlatformCompare($fullPath, self::MAX_ROWS);

            Log::info('PlatformCompare: readRows - parsed', [
                'rows_count' => count($rows),
                'first_row' => $rows[0] ?? null,
            ]);

            return $rows;
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
            $firstWord = explode(' ', $normalized)[0] ?? '';
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

        Log::info('PlatformCompare: loadAllPlatformCache - keywords extracted', [
            'keywords_count' => count($keywords),
            'keywords_sample' => array_slice(array_keys($keywords), 0, 10),
        ]);

        $dbStart = microtime(true);

        $candidates = Product::query()
            ->select(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->whereHas('offers', fn($q) => $q->active())
            ->with([
                'offers' => function ($q) {
                    $q->active()
                        ->orderBy('price')
                        ->with('supplier:id,name,area,phone1,phone2');
                },
            ])
            ->get()
            ->filter(fn(Product $p) => $p->offers->isNotEmpty());

        Log::info('PlatformCompare: loadAllPlatformCache - DB query done', [
            'total_products_with_offers' => $candidates->count(),
            'db_elapsed_ms' => round((microtime(true) - $dbStart) * 1000),
        ]);

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


    private function loadUploadCache(int $uploadId): Collection
    {
        $bestOffers = $this->bestOffersByProduct($uploadId);

        if ($bestOffers->isEmpty()) {
            return new Collection();
        }

        $products = Product::query()
            ->whereIn('id', $bestOffers->keys()->all())
            ->get(['id', 'name_ar', 'name_en', 'code', 'normalized_name'])
            ->keyBy('id');

        $result = [];

        foreach ($bestOffers as $productId => $offer) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }
            $product->setRelation('offers', collect([$offer]));
            $result[] = $product;
        }

        return new Collection($result);
    }

    /**
     * بناء سطور المقارنة لكل صف من الملف مقابل مجموعة المنتجات المتاحة.
     *
     * يُحسَّن الأداء عبر:
     * 1. تجهيز كلمات المحتوى والأرقام لكل منتج مرة واحدة (وليس في كل مقارنة).
     * 2. استخدام drugOverlapScorePrepared مباشرة بدلاً من matchScore→drugOverlapScore.
     * 3. إيقاف مبكر عند تطابق 100%.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function buildLines(array $rows, Collection $cachedProducts, ?int $uploadId = null): array
    {
        $lines = [];
        $index = $this->buildProductIndex($cachedProducts);

        // تجهيز بيانات كل المنتجات مرة واحدة: tokens + numbers لكل صيغة اسم.
        $productData = [];
        foreach ($cachedProducts as $product) {
            $normName = (string) ($product->normalized_name ?? '');
            $nameAr   = (string) ($product->name_ar ?? '');
            $nameEn   = (string) ($product->name_en ?? '');

            $productData[$product->id] = [
                'product'      => $product,
                'norm_tokens'  => $normName !== '' ? $this->prepareName($normName)['tokens'] : [],
                'norm_numbers' => $normName !== '' ? $this->prepareName($normName)['numbers'] : [],
                'ar_tokens'    => $nameAr !== '' ? $this->prepareName($this->normalizer->normalize($nameAr))['tokens'] : [],
                'ar_numbers'   => $nameAr !== '' ? $this->prepareName($this->normalizer->normalize($nameAr))['numbers'] : [],
                'en_tokens'    => $nameEn !== '' ? $this->prepareName($nameEn)['tokens'] : [],
                'en_numbers'   => $nameEn !== '' ? $this->prepareName($nameEn)['numbers'] : [],
            ];
        }

        foreach ($rows as $row) {
            $rawQuery = trim((string) $row['name']);

            if (mb_strlen($rawQuery) < 3) {
                $lines[] = [
                    'query'          => $rawQuery,
                    'price'          => $row['price'] ?? null,
                    'discount'       => $row['discount'] ?? null,
                    'matched_product' => null,
                    'similarity'     => 0,
                    'platform_best'  => null,
                    'status'         => 'skipped',
                ];

                continue;
            }

            $normalizedQuery = $this->normalizer->normalize($rawQuery);
            $firstWord = explode(' ', $normalizedQuery)[0] ?? '';
            $queryPrepared = $this->prepareName($normalizedQuery);

            $candidates = $firstWord !== ''
                ? $this->platformCandidates($normalizedQuery, $firstWord, $cachedProducts, $index)
                : $cachedProducts;

            if ($candidates->isEmpty() && mb_strlen($firstWord) >= 3) {
                $partialQuery = mb_strlen($normalizedQuery) >= 6
                    ? mb_substr($normalizedQuery, 0, 8)
                    : null;

                $candidates = $cachedProducts->filter(function (Product $p) use ($firstWord, $normalizedQuery, $partialQuery) {
                    $name = (string) ($p->normalized_name ?? '');
                    if ($name === '') {
                        return false;
                    }
                    if (str_contains($name, $firstWord) || str_contains($name, $normalizedQuery)) {
                        return true;
                    }
                    return $partialQuery !== null && str_contains($name, $partialQuery);
                });
            }

            $bestScore = 0.0;
            $bestProduct = null;
            $sheetPrice = $row['price'] ?? null;

            foreach ($candidates as $product) {
                $pd = $productData[$product->id] ?? null;
                if (! $pd) {
                    continue;
                }

                $bestOfferPrice = $product->offers->first()?->price;

                // حساب أقصى نسبة تشابه من 3 صيغ للاسم (normalizedName, nameAr, nameEn)
                $nameScore = 0.0;

                if ($pd['norm_tokens'] !== []) {
                    $nameScore = max($nameScore, $this->drugOverlapScorePrepared(
                        $queryPrepared['tokens'],
                        $pd['norm_tokens'],
                        $queryPrepared['numbers'],
                        $pd['norm_numbers'],
                    ));
                }
                if ($nameScore < 100.0 && $pd['ar_tokens'] !== []) {
                    $nameScore = max($nameScore, $this->drugOverlapScorePrepared(
                        $queryPrepared['tokens'],
                        $pd['ar_tokens'],
                        $queryPrepared['numbers'],
                        $pd['ar_numbers'],
                    ));
                }
                if ($nameScore < 100.0 && $pd['en_tokens'] !== []) {
                    $nameScore = max($nameScore, $this->drugOverlapScorePrepared(
                        $queryPrepared['tokens'],
                        $pd['en_tokens'],
                        $queryPrepared['numbers'],
                        $pd['en_numbers'],
                    ));
                }

                if ($nameScore <= 0.0) {
                    continue;
                }

                $score = $this->applyPriceScore(
                    $sheetPrice !== null ? (float) $sheetPrice : null,
                    $bestOfferPrice !== null ? (float) $bestOfferPrice : null,
                    $nameScore,
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestProduct = $product;
                }

                // إيقاف مبكر: تطابق تام = لا حاجة للمزيد
                if ($bestScore >= 100.0) {
                    break;
                }
            }

            if ($bestScore < self::MIN_SIMILARITY || $bestProduct === null) {
                $lines[] = $this->noMatchLine($row, $rawQuery);

                continue;
            }

            $bestOffer    = $bestProduct->offers->first();
            $sheetPrice   = $row['price'];
            $platformPrice = $bestOffer ? (float) $bestOffer->price : null;

            $lines[] = [
                'query'          => $rawQuery,
                'price'          => $sheetPrice,
                'discount'       => $row['discount'],
                'matched_product' => $bestProduct->name_ar ?: $bestProduct->name_en,
                'similarity'     => round($bestScore, 1),
                'platform_best'  => [
                    'supplier' => $bestOffer?->supplier?->name,
                    'price'    => $platformPrice,
                    'discount' => $bestOffer ? (float) $bestOffer->discount : null,
                ],
                'status' => 'both',
            ];
        }

        return $lines;
    }

    /**
     * بناء فهرس المنتجات مرة واحدة لكل الطلب (أول كلمة + كل كلمة) لتضييق نطاق
     * المرشحين لكل صف دون مسح كل منتجات المنصة في كل صف.
     *
     * @return array{by_first: array<string, list<Product>>, by_token: array<string, list<Product>>}
     */
    private function buildProductIndex(Collection $cachedProducts): array
    {
        $byFirst = [];
        $byToken = [];

        foreach ($cachedProducts as $product) {
            $name = (string) ($product->normalized_name ?? '');
            $tokens = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $first = $tokens[0] ?? '';

            if ($first !== '') {
                $byFirst[$first][] = $product;
            }

            foreach ($tokens as $token) {
                if (mb_strlen($token) >= 3) {
                    $byToken[$token][] = $product;
                }
            }
        }

        return ['by_first' => $byFirst, 'by_token' => $byToken];
    }

    /**
     * اختيار مرشحين للصف من فهرس المنتجات، ثم تطبيق نفس معايير
     * matchesPlatformProductCandidate على المجموعة الصغيرة الناتجة فقط.
     *
     * @param  array{by_first: array<string, list<Product>>, by_token: array<string, list<Product>>}  $index
     */
    private function platformCandidates(
        string $normalizedQuery,
        string $firstWord,
        Collection $cachedProducts,
        array $index,
    ): Collection {
        $ids = [];

        if ($firstWord !== '' && isset($index['by_first'][$firstWord])) {
            foreach ($index['by_first'][$firstWord] as $product) {
                $ids[$product->id] = true;
            }
        }

        foreach (preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            if (mb_strlen($token) >= 3 && isset($index['by_token'][$token])) {
                foreach ($index['by_token'][$token] as $product) {
                    $ids[$product->id] = true;
                }
            }
        }

        $queryTokens = preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $cachedProducts
            ->filter(fn(Product $p) => isset($ids[$p->id]))
            ->filter(fn(Product $p) => $this->matchesPlatformProductCandidate($normalizedQuery, $firstWord, $queryTokens, $p));
    }

    private function matchesPlatformProductCandidate(string $normalizedQuery, string $firstWord, array $queryTokens, Product $product): bool
    {
        $productName = (string) ($product->normalized_name ?? '');
        if ($productName === '') {
            return false;
        }

        if ($firstWord !== '' && (str_starts_with($productName, $firstWord) || str_contains($productName, $firstWord))) {
            return true;
        }

        foreach ($queryTokens as $token) {
            if (mb_strlen($token) >= 3 && str_contains($productName, $token)) {
                return true;
            }
        }

        $prefixLen = min(mb_strlen($normalizedQuery), mb_strlen($productName), 6);
        if ($prefixLen >= 4) {
            return mb_substr($normalizedQuery, 0, $prefixLen) === mb_substr($productName, 0, $prefixLen);
        }

        return false;
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
            $aMatch = (int) (($a['similarity'] ?? 0) > 0);
            $bMatch = (int) (($b['similarity'] ?? 0) > 0);
            if ($aMatch !== $bMatch) {
                return $bMatch <=> $aMatch;
            }
            if (($a['status'] ?? '') === 'only_b') {
                return 1;
            }
            if (($b['status'] ?? '') === 'only_b') {
                return -1;
            }
            $aOffer = (int) (! empty($a['platform_best']['supplier']));
            $bOffer = (int) (! empty($b['platform_best']['supplier']));
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
            'query' => $rawQuery,
            'price' => $row['price'] ?? null,
            'discount' => $row['discount'] ?? null,
            'matched_product' => null,
            'similarity' => 0,
            'platform_best' => null,
            'status' => 'no_match',
        ];
    }
}
