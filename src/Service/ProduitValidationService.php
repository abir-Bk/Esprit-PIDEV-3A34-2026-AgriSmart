<?php

namespace App\Service;

use App\Entity\Produit;

class ProduitValidationService
{
    public function validateProduit(Produit $produit): bool
    {
        if (trim((string) $produit->getNom()) === '') {
            throw new \InvalidArgumentException('Le nom du produit est obligatoire.');
        }

        if (($produit->getPrix() ?? 0.0) <= 0) {
            throw new \InvalidArgumentException('Le prix du produit doit être supérieur à 0.');
        }

        return true;
    }
}
