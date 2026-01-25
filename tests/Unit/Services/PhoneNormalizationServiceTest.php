<?php

namespace Tests\Unit\Services;

use App\Services\PhoneNormalizationService;
use Tests\TestCase;

class PhoneNormalizationServiceTest extends TestCase
{
    protected PhoneNormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhoneNormalizationService();
    }

    public function test_normalizes_gabon_number_to_e164(): void
    {
        $result = $this->service->normalize('77123456');

        $this->assertEquals('+24177123456', $result['normalized']);
        $this->assertEquals('GA', $result['country_code']);
        $this->assertEquals('Gabon', $result['country_name']);
        $this->assertEquals('241', $result['dial_code']);
        $this->assertTrue($result['is_valid']);
    }

    public function test_normalizes_number_with_country_code(): void
    {
        $result = $this->service->normalize('+24177123456');

        $this->assertEquals('+24177123456', $result['normalized']);
        $this->assertEquals('GA', $result['country_code']);
        $this->assertTrue($result['is_valid']);
    }

    public function test_normalizes_number_with_spaces(): void
    {
        $result = $this->service->normalize('241 77 12 34 56');

        $this->assertEquals('+24177123456', $result['normalized']);
        $this->assertTrue($result['is_valid']);
    }

    public function test_normalizes_number_with_dashes(): void
    {
        $result = $this->service->normalize('241-77-12-34-56');

        $this->assertEquals('+24177123456', $result['normalized']);
        $this->assertTrue($result['is_valid']);
    }

    public function test_detects_airtel_operator(): void
    {
        $result = $this->service->normalize('77123456');
        $this->assertEquals('airtel', $result['operator']);

        $result = $this->service->normalize('74123456');
        $this->assertEquals('airtel', $result['operator']);

        $result = $this->service->normalize('76123456');
        $this->assertEquals('airtel', $result['operator']);
    }

    public function test_detects_moov_operator(): void
    {
        $result = $this->service->normalize('60123456');
        $this->assertEquals('moov', $result['operator']);

        $result = $this->service->normalize('62123456');
        $this->assertEquals('moov', $result['operator']);

        $result = $this->service->normalize('66123456');
        $this->assertEquals('moov', $result['operator']);
    }

    public function test_normalizes_cameroon_number(): void
    {
        $result = $this->service->normalize('+237650123456');

        $this->assertEquals('+237650123456', $result['normalized']);
        $this->assertEquals('CM', $result['country_code']);
        $this->assertEquals('Cameroun', $result['country_name']);
        $this->assertEquals('237', $result['dial_code']);
    }

    public function test_normalizes_senegal_number(): void
    {
        $result = $this->service->normalize('+221771234567');

        $this->assertEquals('+221771234567', $result['normalized']);
        $this->assertEquals('SN', $result['country_code']);
        $this->assertEquals('Sénégal', $result['country_name']);
    }

    public function test_normalizes_ivory_coast_number(): void
    {
        $result = $this->service->normalize('+2250712345678');

        $this->assertEquals('+2250712345678', $result['normalized']);
        $this->assertEquals('CI', $result['country_code']);
        $this->assertEquals("Côte d'Ivoire", $result['country_name']);
    }

    public function test_normalizes_congo_number(): void
    {
        $result = $this->service->normalize('+242041234567');

        $this->assertEquals('+242041234567', $result['normalized']);
        $this->assertEquals('CG', $result['country_code']);
        $this->assertEquals('Congo', $result['country_name']);
    }

    public function test_uses_country_hint(): void
    {
        // 8 digit number could be from multiple countries
        // With Gabon hint
        $result = $this->service->normalize('77123456', 'GA');
        $this->assertEquals('+24177123456', $result['normalized']);
        $this->assertEquals('GA', $result['country_code']);
    }

    public function test_to_e164_shortcut(): void
    {
        $e164 = $this->service->toE164('77 12 34 56');

        $this->assertEquals('+24177123456', $e164);
    }

    public function test_normalize_many(): void
    {
        $phones = ['77123456', '66001234', '12345'];
        $result = $this->service->normalizeMany($phones);

        $this->assertEquals(3, $result['summary']['total']);
        $this->assertEquals(2, $result['summary']['valid_count']);
        $this->assertEquals(1, $result['summary']['invalid_count']);
        $this->assertCount(2, $result['valid']);
        $this->assertCount(1, $result['invalid']);
    }

    public function test_groups_by_operator(): void
    {
        $phones = ['77123456', '74001234', '66001234', '62001234'];
        $result = $this->service->normalizeMany($phones);

        $this->assertArrayHasKey('airtel', $result['by_operator']);
        $this->assertArrayHasKey('moov', $result['by_operator']);
        $this->assertCount(2, $result['by_operator']['airtel']);
        $this->assertCount(2, $result['by_operator']['moov']);
    }

    public function test_groups_by_country(): void
    {
        $phones = ['+24177123456', '+237650123456'];
        $result = $this->service->normalizeMany($phones);

        $this->assertArrayHasKey('GA', $result['by_country']);
        $this->assertArrayHasKey('CM', $result['by_country']);
    }

    public function test_is_same_number(): void
    {
        $this->assertTrue($this->service->isSameNumber('77123456', '+24177123456'));
        $this->assertTrue($this->service->isSameNumber('241 77 12 34 56', '+241-77-12-34-56'));
        $this->assertFalse($this->service->isSameNumber('77123456', '77123457'));
    }

    public function test_get_supported_countries(): void
    {
        $countries = $this->service->getSupportedCountries();

        $this->assertIsArray($countries);
        $this->assertGreaterThanOrEqual(5, count($countries));

        $codes = array_column($countries, 'code');
        $this->assertContains('GA', $codes);
        $this->assertContains('CM', $codes);
        $this->assertContains('SN', $codes);
        $this->assertContains('CI', $codes);
        $this->assertContains('CG', $codes);
    }

    public function test_get_dial_code(): void
    {
        $this->assertEquals('241', $this->service->getDialCode('GA'));
        $this->assertEquals('237', $this->service->getDialCode('CM'));
        $this->assertEquals('221', $this->service->getDialCode('SN'));
        $this->assertNull($this->service->getDialCode('XX'));
    }

    public function test_is_country_supported(): void
    {
        $this->assertTrue($this->service->isCountrySupported('GA'));
        $this->assertTrue($this->service->isCountrySupported('CM'));
        $this->assertFalse($this->service->isCountrySupported('XX'));
        $this->assertFalse($this->service->isCountrySupported('US'));
    }

    public function test_provides_format_variations(): void
    {
        $result = $this->service->normalize('77123456');

        $this->assertArrayHasKey('format', $result);
        $this->assertArrayHasKey('e164', $result['format']);
        $this->assertArrayHasKey('international', $result['format']);
        $this->assertArrayHasKey('local', $result['format']);

        $this->assertEquals('+24177123456', $result['format']['e164']);
        $this->assertStringContainsString('+241', $result['format']['international']);
    }

    public function test_set_default_country(): void
    {
        $this->service->setDefaultCountry('CM');

        // A 9-digit number without country code will use Cameroon as default
        $result = $this->service->normalize('650123456');

        $this->assertEquals('CM', $result['country_code']);
    }

    public function test_removes_leading_zeros(): void
    {
        $result = $this->service->normalize('077123456');
        $this->assertEquals('+24177123456', $result['normalized']);

        $result = $this->service->normalize('00241 77 12 34 56');
        $this->assertEquals('+24177123456', $result['normalized']);
    }
}
