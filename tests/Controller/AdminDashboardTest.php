<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminDashboardTest extends WebTestCase
{
    public function testDashboardIsSuccessfulAndAssetsArePresent(): void
    {
        // 1. Simuliert einen Browser-Client
        $client = static::createClient();
        
        // 2. Ruft das Admin-Dashboard auf
        $crawler = $client->request('GET', '/');

        // 3. Prüft, ob die Seite fehlerfrei lädt (HTTP Status 200)
        $this->assertResponseIsSuccessful();

        // 4. Prüft, ob die Hauptüberschrift existiert
        $this->assertSelectorTextContains('h1', 'Admin Dashboard');

        // 5. Prüft, ob das Automatisierungs-Formular gerendert wurde
        $this->assertGreaterThan(0, $crawler->filter('.js-cron-status')->count());
    }
}
