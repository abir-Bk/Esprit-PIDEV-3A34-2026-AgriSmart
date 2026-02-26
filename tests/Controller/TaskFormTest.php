<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskFormTest extends WebTestCase
{
    public function testEmptyTaskSubmission(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/tasks/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Valider')->form(); // Adjust if button label is different
        $client->submit($form, []);

        $this->assertResponseStatusCodeSame(200); // Should return to the form with errors
        $this->assertSelectorExists('.invalid-feedback'); // Adjust based on your UI
        $this->assertSelectorTextContains('body', 'Le titre est obligatoire.');
        $this->assertSelectorTextContains('body', 'La date de début est obligatoire.');
    }
}
