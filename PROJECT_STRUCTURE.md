# مشروع Dwaa - هيكل الكود ووظائفه

## نظرة عامة

هذا المشروع مبني على Laravel 12 ويدعم:
- واجهة مسؤول (Admin dashboard)
- واجهة عميل (Client interface)
- واجهة برمجة تطبيقات API للموبايل/الويب
- رفع ملفات Excel ومعالجة العروض بطريقة غير متزامنة
- بحث ذكي في منتجات ومخزون الموردين
- مقارنة المنتجات والعروض
- إدارة المستخدمين والموردين وأكواد التفعيل
- تحليل وإحصائيات الاستخدام

## أقسام المشروع الأساسية

### 1. `routes/`
- `routes/web.php`: مسارات لوحة التحكم وعرض الواجهات للمستخدم الإداري والعميل.
- `routes/api.php`: مسارات API للعميل والمشرف، تشمل توثيق Sanctum، عمليات البحث، التفعيل، المفضلات، المقارنات، التحميل، والخرائط.

### 2. `app/Http/Controllers/`
- `Web/`: تحكم بعرض صفحات الويب لواجهة الإدارة والمستخدم.
- `Api/`: تحكم بالوظائف الحقيقية عبر API.
  - `Api/AuthController.php`: تسجيل دخول/خروج المستخدم وبيانات `me`.
  - `Api/SearchController.php`: البحث النصي عن المنتجات.
  - `Api/ClientExcelSearchController.php`: البحث عن طريق ملف Excel.
  - `Api/ClientFileCompareController.php`: مقارنة ملفات العميل.
  - `Api/ClientPlatformCompareController.php`: مقارنة منصة/ملف.
  - `Api/FavoriteController.php`: إدارة المفضلات.
  - `Api/SavedComparisonController.php`: حفظ واستعراض المقارنات.
  - `Api/RankingController.php`: ترتيب الموردين حسب عدد الأصناف أو مؤشر جودة الخصم.
  - `Api/SuppliersTodayController.php`: قائمة موردين اليوم.
  - `Web/Admin/DashboardSearchLogsController.php`: صفحات سجل بحث العملاء في لوحة الإدارة.
  - `Api/Admin/SearchLogsController.php`: واجهة برمجة سجل البحث (إحصائيات + فلترة + بحث لكل مستخدم).
  - `Api/Admin/*`: إدارة المستخدمين، الموردين، التحميلات، الخرائط، الأكواد، والإحصائيات.

### 3. `app/Services/`
هذه الطبقة تقوم بالخدمات المنطقية الأساسية:
- `SearchService.php`: البحث الذكي في المنتجات، تسجيل السجلات، تنسيق النتائج.
- `ExcelSearchService.php`: قراءة ملفات Excel وتحويلها لبحث.
- `FileCompareService.php`: مقارنة ملفات مرفوعة.
- `MappingService.php`: ربط المنتجات غير المطابقة بمنتجات موجودة أو تجاهل/إنشاء منتجات جديدة.
- `UploadService.php`: استقبال ملف المورد، حفظه، وإرسال وظيفة المعالجة إلى الطابور.
- `NormalizerService.php`: تطبيع النصوص لتسهيل البحث.
- `SupplierOfferProductResolver.php`: ربط العرض بالمنتج أو المورد حسب البيانات.
- `ActivationService.php`: تفعيل حسابات العملاء وإدارة الأكواد.
- `RankingService.php`: حساب ترتيب الموردين ومؤشر جودة الخصم (DQI) وتخزينه في جدول `supplier_rankings`.

### 4. `app/Models/`
النماذج الأساسية:
- `User.php`
- `Supplier.php`
- `Product.php`
- `Offer.php`
- `Upload.php`
- `SearchLog.php`
- `ActivationCode.php`
- `SavedComparison.php`
- `ProductAlias.php`
- `UnmatchedProduct.php`
- `ComparisonLog.php`
- `UserFavorite.php`
- `Setting.php`
- `SupplierRanking.php`: لقطة دورية لترتيب كل مورد (عدد الأصناف + مؤشر الخصم).

### 5. `app/Jobs/`
- `ProcessUploadJob.php`: معالجة الملف المرفوع في خلفية النظام، ويعيد حساب ترتيب المورد بعد نجاح المعالجة.

### 5.1 `app/Console/Commands/`
- `RecalculateRankings.php`: أمر `rankings:recalculate` لإعادة بناء ترتيب جميع الموردين (مجموعة في `routes/console.php` يوميًا الساعة 00:05).

### 6. `app/Imports/`
- `SupplierOfferImport.php`: استيراد بيانات العروض من ملفات Excel.

### 7. `resources/views/`
- صفحات واجهة العميل `client.*` (بما فيها `client.ranking` لترتيب الموردين و `client.suppliers-today` لموردين اليوم)
- صفحات لوحة التحكم الإدارية `dashboard.*` (بما فيها `dashboard.search-logs` و `dashboard.user-search-logs` لسجل بحث العملاء)
- صفحة تسجيل الدخول الإدارية

### 8. `config/`
- تحتوي إعدادات Laravel القياسية مع `sanctum`, `queue`, `filesystems`, وغيرها.

## وظائف المشروع الرئيسية

### وظيفة العميل
- تسجيل الدخول عبر API
- تفعيل الحساب بواسطة كود تفعيل
- البحث عن المنتجات
- استيراد بحث من ملف Excel
- حفظ المفضلات وإدارتها
- تنفيذ مقارنة بين ملفات أو مقارنة منصة
- عرض المنتجات المتاحة
- حفظ واستعراض المقارنات السابقة
- تغيير كلمة المرور
- عرض ترتيب الموردين (حسب عدد الأصناف أو مؤشر جودة الخصم)
- عرض موردين اليوم

### وظيفة المشرف
- تسجيل الدخول إلى لوحة الإدارة
- إدارة المستخدمين (إنشاء، تحديث، حذف، تغيير كلمة المرور)
- إدارة الموردين (إنشاء، تعديل، حذف)
- رفع ملفات الموردين ومعالجتها
- عرض التحميلات وحالتها
- إدارة المنتجات غير المطابقة وربطها أو إنشاء منتجات جديدة
- إدارة أكواد التفعيل
- عرض إحصائيات وتحليلات بحث المستخدمين
- استعراض سجل بحث العملاء مع الفلترة (مستخدم/مصدر/تاريخ/نص)
- عرض ويدجت موردين اليوم في لوحة التحكم
- إعدادات التطبيق

### وظائف البحث والمطابقة
- بحث نصي ذكي باستخدام تطبيع النص
- بحث صيدلاني خاص يدعم أسماء الأدوية والجرعات
- تصفية النتائج حسب العروض والخصومات
- ترتيب النتائج حسب أفضل خصم أو أعلى توافق

### ترتيب الموردين ومؤشر جودة الخصم
- ترتيب الموردين حسب عدد الأصناف النشطة أو مؤشر جودة الخصم.
- مؤشر جودة الخصم (DQI): متوسط نسبة خصم كل عرض إلى أفضل خصم نشط لنفس المنتج (0-100).
- النتائج تُخزن في `supplier_rankings` وتُحدث بعد كل رفع ناجح ويوميًا عبر أمر `rankings:recalculate`.

### رفع الملفات والمعالجة
- رفع ملف مورد Excel
- حفظ ملف في التخزين المحلي
- إنشاء سجل Upload بحالة `pending`
- تشغيل وظيفة `ProcessUploadJob` لمعالجة الملف في الطابور
- قراءة الأعمدة من خريطة الأعمدة وتحويلها إلى نموذج بيانات

## هيكل مقترح لبناء مشروع بنفس الوظائف

إذا أردت تبني نفس المشروع في مشروع جديد، يمكن أن يكون الهيكل كالآتي:

```
app/
  Http/
    Controllers/
      Api/
      Web/
    Middleware/
  Models/
  Services/
  Jobs/
  Imports/
  Providers/
  Concerns/
bootstrap/
config/
database/
  migrations/
  seeders/
public/
resources/
  views/
  css/
  js/
routes/
  api.php
  web.php
composer.json
package.json
.env.example
```

### ملفات ومكونات أساسية يجب تنفذها
- `routes/api.php`: مسارات API مع توثيق Sanctum وصلاحيات `role` و `is_active` و `subscription_valid`
- `routes/web.php`: صفحتان رئيسيتان للعميل والإدارة
- `app/Services/SearchService.php`: منطق البحث وتسجيل السجلات
- `app/Services/UploadService.php`: رفع الملف وإرساله للمعالجة
- `app/Jobs/ProcessUploadJob.php`: معالجة الملف في الخلفية
- `app/Http/Controllers/Api/*`: تحكم بوظائف العميل والمشرف
- `app/Models/Product.php` و `Offer.php`: علاقة بين المنتج والعروض
- `app/Models/Supplier.php`: علاقة المورد بالتحميلات والعروض
- `app/Models/Upload.php`: حالة الملف وموقعه
- `app/Models/SearchLog.php`: حفظ سجل البحث لتحليلات لاحقة

## توصية لبداية مشروع مماثل

1. ابدأ بمشروع Laravel جديد.
2. أنشئ النماذج الأساسية: `User`, `Supplier`, `Product`, `Offer`, `Upload`, `SearchLog`, `ActivationCode`, `SavedComparison`.
3. أنشئ خدمات: `SearchService`, `UploadService`, `MappingService`, `ActivationService`, `FileCompareService`, `NormalizerService`.
4. أنشئ مسارات API منفصلة للمستخدم العادي والمشرف.
5. أنشئ لوحة إدارة بسيطة في `resources/views` أو استخدم إطار واجهة جاهز.
6. دمج `maatwebsite/excel` لرفع وقراءة ملفات Excel.
7. تشغيل `ProcessUploadJob` عبر نظام طابور Laravel.

---

هذه الوثيقة تلخص هيكل مشروع Dwaa وكيفية بناء مشروع مشابه بنفس الوظائف. يمكنك استخدامها كمرجع لبناء مشروع جديد أو لتوزيع المهام بين فريق التطوير.