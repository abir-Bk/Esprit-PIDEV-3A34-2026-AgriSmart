<?php

namespace App\Service;

use App\Entity\Commande;

class CommandeValidationService
{
    public function validateCommande(Commande $commande): bool
    {
        if (trim($commande->getAdresseLivraison()) === '') {
            throw new \InvalidArgumentException('Adresse de livraison obligatoire.');
        }

        if ($commande->getMontantTotal() <= 0) {
            throw new \InvalidArgumentException('Le montant total doit être supérieur à 0.');
        }

        return true;
    }
}
