<?php

namespace Tests\Unit\Services;

use App\Services\SMS\OperatorDetector;
use Tests\TestCase;

class OperatorDetectorTest extends TestCase
{
    public function test_detects_airtel_numbers(): void
    {
        // Airtel prefixes: 74, 77
        $this->assertEquals('airtel', OperatorDetector::detect('+24177123456'));
        $this->assertEquals('airtel', OperatorDetector::detect('24177123456'));
        $this->assertEquals('airtel', OperatorDetector::detect('077123456'));
        $this->assertEquals('airtel', OperatorDetector::detect('+24174123456'));
    }

    public function test_detects_moov_numbers(): void
    {
        // Moov prefixes: 62, 66
        $this->assertEquals('moov', OperatorDetector::detect('+24162123456'));
        $this->assertEquals('moov', OperatorDetector::detect('24166123456'));
        $this->assertEquals('moov', OperatorDetector::detect('066123456'));
    }

    public function test_returns_unknown_for_invalid_numbers(): void
    {
        $this->assertEquals('unknown', OperatorDetector::detect('12345'));
        $this->assertEquals('unknown', OperatorDetector::detect(''));
        $this->assertEquals('unknown', OperatorDetector::detect('+3312345678'));
    }

    public function test_normalizes_phone_numbers(): void
    {
        $info = OperatorDetector::getInfo('+241 77 12 34 56');

        $this->assertArrayHasKey('original', $info);
        $this->assertArrayHasKey('normalized', $info);
        $this->assertArrayHasKey('operator', $info);
        $this->assertEquals('airtel', $info['operator']);
    }

    public function test_handles_various_formats(): void
    {
        // With spaces
        $this->assertEquals('airtel', OperatorDetector::detect('241 77 12 34 56'));

        // With dashes
        $this->assertEquals('airtel', OperatorDetector::detect('241-77-12-34-56'));

        // With country code
        $this->assertEquals('moov', OperatorDetector::detect('+241 66 00 00 00'));
    }
}
