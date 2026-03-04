<?php

namespace App\Tests\Entity;

use App\Entity\Demande;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DemandeTest extends KernelTestCase
{
    private function getValidator()
    {
        self::bootKernel();
        return self::getContainer()->get('validator');
    }

    public function testDemandeValide(): void
    {
        $demande = (new Demande())
            ->setNom("Ben Salah")
            ->setPrenom("Ahmed")
            ->setPhoneNumber("55123456") // Valide selon ton Regex ^[2-9][0-9]{7}$
            ->setStatut("En attente")
            ->setLettreMotivation("Motivation pour le poste agricole")
            ->setCv("cv_ahmed.pdf")
            ->setDatePostulation(new \DateTime())
            ->setDateModification(new \DateTime());

        $errors = $this->getValidator()->validate($demande);
        $this->assertCount(0, $errors);
        echo "\n ✅ [SUCCESS] L'entité Demande est valide ! \n";
    }

    public function testDemandeInvalide(): void
    {
        $demande = (new Demande())
            ->setNom("A") // Trop court (min 3)
            ->setPrenom("123") // Regex lettres uniquement
            ->setPhoneNumber("123"); // Trop court et commence par 1

        $errors = $this->getValidator()->validate($demande);
        $this->assertGreaterThan(0, $errors);
        echo " ✅ [SUCCESS] Les validations bloquent bien les mauvaises données ! \n";
    }
}