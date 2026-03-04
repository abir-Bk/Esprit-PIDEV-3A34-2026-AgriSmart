<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class VoiceAnalysisTest extends TestCase
{
    public function testExtractionTelephoneTunisien(): void
    {
        $text = "Bonjour je suis Ahmed, mon numéro est 55 123 456";
        
        // On teste la logique de ton contrôleur (Regex 8 chiffres)
        preg_match('/(\d[\s]*){8}/', $text, $matches);
        $phone = str_replace(' ', '', $matches[0]);

        $this->assertEquals('55123456', $phone);
        $this->assertEquals(8, strlen($phone));
    }
}