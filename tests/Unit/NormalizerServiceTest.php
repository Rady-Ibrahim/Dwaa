<?php

namespace Tests\Unit;

use App\Services\NormalizerService;
use PHPUnit\Framework\TestCase;

class NormalizerServiceTest extends TestCase
{
    public function test_unifies_alef_forms(): void
    {
        $n = new NormalizerService;
        $this->assertSame('اموكسيل', $n->normalize('أموكسيل'));
        $this->assertSame('اموكسيل', $n->normalize('إموكسيل'));
    }

    public function test_lowercase_english(): void
    {
        $n = new NormalizerService;
        $this->assertSame('amoxil 500mg', $n->normalize('AMOXIL 500MG'));
    }

    public function test_removes_diacritics(): void
    {
        $n = new NormalizerService;
        $this->assertSame('اموكسيل', $n->normalize('أَمُوكْسِيل'));
    }

    public function test_phonetic_key_aligns_arabic_query_with_latin_brand(): void
    {
        $n = new NormalizerService;
        $fromArabic = $n->phoneticConsonantKey('اوبونوف');
        $fromEnglish = $n->phoneticConsonantKey('Obunof capsule 20 tablets');
        $this->assertNotSame('', $fromArabic);
        $this->assertTrue(str_contains($fromEnglish, $fromArabic) || str_contains($fromArabic, $fromEnglish));
    }

    public function test_like_terms_include_transliterated_latin(): void
    {
        $n = new NormalizerService;
        $terms = $n->likeTermsForSearch('اوبونوف');
        $this->assertNotEmpty($terms);
        $this->assertTrue(collect($terms)->contains(fn (string $t) => preg_match('/[a-z]/i', $t) === 1));
    }
}
