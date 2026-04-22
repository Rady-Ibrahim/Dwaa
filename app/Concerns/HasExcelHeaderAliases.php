<?php

namespace App\Concerns;

trait HasExcelHeaderAliases
{
    /**
     * Product name header aliases (in Arabic and English)
     */
    protected const NAME_HEADER_ALIASES = [
        'الصنف',
        'اسم الصنف',
        'اسم الصنف:',
        ':اسم الصنف',
        'إسم الصنف ',
        'اسم المنتج',
        'اسم الصنف / المنتج',
        'المنتج',
        'بيان',
        'البيان',
        'الوصف',
        'اسم',
        'اسم المادة',
        'اسم الدواء',
        'الصنف بالكامل',
        'Item',
        'Item Name',
        'Product',
        'Product Name',
        'PROD_NAME',
        'PRODUCT_NAME',
        'Description',
        'Trade Name',
        'Commercial Name',
        'Brand Name',
        'Generic Name',
        'Medicine Name',
    ];

    /**
     * Price header aliases (in Arabic and English)
     */
    protected const PRICE_HEADER_ALIASES = [
        'سعر',
        'السعر',
        ':السعر',
        'سعر ج',
        'سعر الجمهور',
        'سعر البيع',
        'سعر الوحدة',
        'سعر المستهلك',
        'السعر النهائي',
        'سعر قبل الخصم',
        'سعر العبوة',
        'سعر الكرتونة',
        'سعر القطاعي',
        'سعر الجملة',
        'سعر خاص',
        'Public Price',
        'Price',
        'PRICE_1',
        'Unit Price',
        'Selling Price',
        'Retail Price',
        'Consumer Price',
        'List Price',
        'Base Price',
        'Original Price',
        'Gross Price',
        'MRP',
        'PTR',
        'PTD',
    ];

    /**
     * Discount header aliases (in Arabic and English)
     * Includes new discount types from Excel headers
     */
    protected const DISCOUNT_HEADER_ALIASES = [
        // Basic discount headers
        'خصم',
        'الخصم',
        ':الخصم',
        'الخصم:',
        'نسبة الخصم',
        'خصم %',
        'الخصم %',
        '% خصم',
        'خصم تجاري',
        'خصم إضافي',
        'الخصم اساسى :',
        'خصم اساسى',
        'الخصم اساسى',
        'خصم خاص',

        // New discount types from user's Excel
        'الخصم',
        'القائمة',
        'نقدى سياره',
        'نقدى سيارة',
        'مميز',
        'قائمة',
        'نقدى',
        'المرجح',
        'خصم خاص %',
        'نسبة الخصم',

        // General offer/promo headers
        'عرض',
        'العرض',
        'أوفر',
        'بونص',
        'مندوب',
        'المندوب',
        'شركات',
        'جمله',
        'جملة',
        'صيدليات',
        'صيدلية',
        'الموزع',
        'الموزعين',

        // English aliases
        'Discount',
        'Discount %',
        'Discount-%',
        'Disc',
        'Disc %',
        'Promo',
        'Promotion',
        'Offer',
        'Deal',
        'Rebate',
        'Markdown',
    ];

    /**
     * Bonus/Gift header aliases (in Arabic and English)
     */
    protected const BONUS_HEADER_ALIASES = [
        'بونص',
        'البونص',
        'هدية',
        'الهدية',
        'إهداء',
        'الإهداء',
        'مجاني',
        'بدون مقابل',
        'عينة',
        'العينة',
        'bonus',
        'gift',
        'free',
        'gratis',
        'complimentary',
    ];
}
