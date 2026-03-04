<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Offre;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostulerTest extends WebTestCase
{
    public function testCandidatureCompleteAvecChampsIA(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get('doctrine')->getManager();

        // 1. CRÉATION DE L'UTILISATEUR
        $user = new User();
        $user->setEmail('candidat.ia.' . uniqid() . '@agrismart.com');
        $user->setPassword('password123');
        $user->setFirstName('Ahmed'); 
        $user->setLastName('Ben Salah');
        $em->persist($user);

        // 2. CRÉATION DE L'OFFRE (Correction finale des champs obligatoires)
        $offre = new Offre();
        $offre->setTitle('Ouvrier Agricole IA');
        $offre->setLieu('Kairouan');
        $offre->setStatut('Ouverte');
        $offre->setDateFin(new \DateTime('+15 days'));
        
        // On remplit 'type_contrat' et on anticipe 'salaire' ou 'description'
        if (method_exists($offre, 'setTypeContrat')) {
            $offre->setTypeContrat('CDI');
        }
        
        if (method_exists($offre, 'setSalaire')) {
            $offre->setSalaire(1200.50);
        }

        if (method_exists($offre, 'setDescription')) {
            $offre->setDescription('Poste ouvert via le simulateur AgriSmart.');
        }

        $em->persist($offre);
        
        // Tentative de sauvegarde en base
        $em->flush(); 

        // 3. CONNEXION ET NAVIGATION
        $client->loginUser($user);
        $crawler = $client->request('GET', '/offre/' . $offre->getId() . '/postuler');
        
        // On vérifie que la page s'affiche bien
        $this->assertResponseIsSuccessful();

        // 4. SOUMISSION DU FORMULAIRE
        // On récupère le bouton. Note : vérifie que le texte du bouton est exact
        $form = $crawler->selectButton('Soumettre ma candidature')->form([
            'demande[nom]' => 'Ben Salah',
            'demande[prenom]' => 'Ahmed',
            'demande[phoneNumber]' => '55123456',
            'demande[lettreMotivation]' => 'Candidature assistée par IA AgriSmart.',
        ]);

        $client->submit($form);

        // 5. VÉRIFICATION FINALE
        $this->assertResponseRedirects('/mes-demandes');
        $client->followRedirect();
        $this->assertSelectorTextContains('.ag-card', 'Ouvrier Agricole IA');
    }
}