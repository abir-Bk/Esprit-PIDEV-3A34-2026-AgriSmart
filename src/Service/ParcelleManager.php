<?php

namespace App\Service;

use App\Entity\Parcelle;

class ParcelleManager
{
    public function validate(Parcelle $parcelle): bool
    {
        // Règle 1 : Le nom est obligatoire
        if (empty($parcelle->getNom())) {
            throw new \InvalidArgumentException('Le nom de la parcelle est obligatoire');
        }

        // Règle 2 : La surface doit être positive
        if ($parcelle->getSurface() === null || $parcelle->getSurface() <= 0) {
            throw new \InvalidArgumentException('La surface doit être un nombre positif');
        }

        // Règle 3 : La surface ne peut pas dépasser 10000
        if ($parcelle->getSurface() >= 10000) {
            throw new \InvalidArgumentException('La surface ne peut pas dépasser 10 000 hectares');
        }

        // Règle 4 : La latitude doit être entre -90 et 90
        if ($parcelle->getLatitude() === null || $parcelle->getLatitude() < -90 || $parcelle->getLatitude() > 90) {
            throw new \InvalidArgumentException('La latitude doit être comprise entre -90 et 90');
        }

        // Règle 5 : Le type de sol doit être valide
        $typesValides = ["Argileux", "Sableux", "Limoneux", "Calcaire", "Tourbeux", "Humifère"];
        if (!in_array($parcelle->getTypeSol(), $typesValides)) {
            throw new \InvalidArgumentException('Type de sol invalide');
        }

        return true;
    }
}