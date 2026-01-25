<?php

namespace Tests\Unit\Services;

use App\Services\StopWordService;
use Tests\TestCase;

class StopWordServiceTest extends TestCase
{
    protected StopWordService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StopWordService();
    }

    public function test_detects_stop_keyword(): void
    {
        $this->assertTrue($this->service->containsStopKeyword('STOP'));
        $this->assertTrue($this->service->containsStopKeyword('stop'));
        $this->assertTrue($this->service->containsStopKeyword('Stop'));
    }

    public function test_detects_french_stop_keywords(): void
    {
        $this->assertTrue($this->service->containsStopKeyword('ARRET'));
        $this->assertTrue($this->service->containsStopKeyword('ARRÊT'));
        $this->assertTrue($this->service->containsStopKeyword('DESABONNER'));
        $this->assertTrue($this->service->containsStopKeyword('DÉSABONNER'));
        $this->assertTrue($this->service->containsStopKeyword('DESINSCRIPTION'));
    }

    public function test_detects_english_stop_keywords(): void
    {
        $this->assertTrue($this->service->containsStopKeyword('UNSUBSCRIBE'));
        $this->assertTrue($this->service->containsStopKeyword('UNSUB'));
        $this->assertTrue($this->service->containsStopKeyword('REMOVE'));
        $this->assertTrue($this->service->containsStopKeyword('QUIT'));
        $this->assertTrue($this->service->containsStopKeyword('END'));
        $this->assertTrue($this->service->containsStopKeyword('CANCEL'));
        $this->assertTrue($this->service->containsStopKeyword('OPTOUT'));
        $this->assertTrue($this->service->containsStopKeyword('OPT-OUT'));
    }

    public function test_detects_stop_at_beginning_of_message(): void
    {
        $this->assertTrue($this->service->containsStopKeyword('STOP please'));
        $this->assertTrue($this->service->containsStopKeyword('ARRET merci'));
        $this->assertTrue($this->service->containsStopKeyword('UNSUBSCRIBE from list'));
    }

    public function test_does_not_detect_stop_in_middle_of_word(): void
    {
        $this->assertFalse($this->service->containsStopKeyword('NONSTOP'));
        $this->assertFalse($this->service->containsStopKeyword('busstop'));
        $this->assertFalse($this->service->containsStopKeyword('unstoppable'));
    }

    public function test_does_not_detect_regular_messages(): void
    {
        $this->assertFalse($this->service->containsStopKeyword('Hello'));
        $this->assertFalse($this->service->containsStopKeyword('Bonjour'));
        $this->assertFalse($this->service->containsStopKeyword('Merci pour le message'));
        $this->assertFalse($this->service->containsStopKeyword(''));
    }

    public function test_get_stop_keyword_returns_matched_keyword(): void
    {
        $this->assertEquals('STOP', $this->service->getStopKeyword('stop'));
        $this->assertEquals('ARRET', $this->service->getStopKeyword('arret'));
        // 'ARRÊT' matches 'ARRET' first due to accent normalization
        $this->assertNotNull($this->service->getStopKeyword('ARRÊT'));
        $this->assertEquals('UNSUBSCRIBE', $this->service->getStopKeyword('unsubscribe'));
    }

    public function test_get_stop_keyword_returns_null_for_non_stop(): void
    {
        $this->assertNull($this->service->getStopKeyword('Hello'));
        $this->assertNull($this->service->getStopKeyword(''));
    }

    public function test_handles_accents_in_stop_keywords(): void
    {
        // With accents
        $this->assertTrue($this->service->containsStopKeyword('DÉSABONNER'));
        $this->assertTrue($this->service->containsStopKeyword('DÉSINSCRIPTION'));
        $this->assertTrue($this->service->containsStopKeyword('ARRÊT'));

        // Without accents (should still match)
        $this->assertTrue($this->service->containsStopKeyword('DESABONNER'));
        $this->assertTrue($this->service->containsStopKeyword('DESINSCRIPTION'));
        $this->assertTrue($this->service->containsStopKeyword('ARRET'));
    }

    public function test_normalizes_phone_number(): void
    {
        // Gabon format with country code
        $this->assertEquals('+24177123456', $this->service->normalizePhoneNumber('+241 77 12 34 56'));
        $this->assertEquals('+24177123456', $this->service->normalizePhoneNumber('241-77-12-34-56'));

        // Local format (8 digits) - adds Gabon country code
        $this->assertEquals('+24177123456', $this->service->normalizePhoneNumber('77123456'));
        $this->assertEquals('+24166001234', $this->service->normalizePhoneNumber('66001234'));

        // With leading zeros
        $this->assertEquals('+24177123456', $this->service->normalizePhoneNumber('077123456'));
    }

    public function test_get_stop_keywords_returns_array(): void
    {
        $keywords = $this->service->getStopKeywords();

        $this->assertIsArray($keywords);
        $this->assertContains('STOP', $keywords);
        $this->assertContains('ARRET', $keywords);
        $this->assertContains('UNSUBSCRIBE', $keywords);
    }

    public function test_add_custom_stop_keyword(): void
    {
        $this->service->addStopKeyword('CUSTOM');

        $this->assertTrue($this->service->containsStopKeyword('CUSTOM'));
        $this->assertTrue($this->service->containsStopKeyword('custom'));
    }

    public function test_does_not_add_duplicate_keyword(): void
    {
        $initialCount = count($this->service->getStopKeywords());

        $this->service->addStopKeyword('STOP'); // Already exists

        $this->assertEquals($initialCount, count($this->service->getStopKeywords()));
    }
}
