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
}
