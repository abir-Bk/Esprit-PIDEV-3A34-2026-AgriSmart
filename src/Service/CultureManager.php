<?php

namespace App\Service;

use App\Entity\Culture;

class CultureManager
{
    public function validate(Culture $culture): bool
    {
        // Règle 1 : Le type de culture est obligatoire
        if (empty($culture->getTypeCulture())) {
            throw new \InvalidArgumentException('Le type de culture est obligatoire');
        }

        // Règle 2 : La variété est obligatoire
        if (empty($culture->getVariete())) {
            throw new \InvalidArgumentException('La variété est obligatoire');
        }

        // Règle 3 : La date de récolte doit être après la date de plantation
        if ($culture->getDatePlantation() && $culture->getDateRecoltePrevue()) {
            if ($culture->getDateRecoltePrevue() <= $culture->getDatePlantation()) {
                throw new \InvalidArgumentException('La date de récolte doit être postérieure à la date de plantation');
            }
        }

        // Règle 4 : Le statut doit être valide
        $statutsValides = ["En croissance", "Besoin d'eau", "Mature", "Récolté", "Maladie", "Traitement"];
        if (!in_array($culture->getStatut(), $statutsValides)) {
            throw new \InvalidArgumentException('Statut invalide');
        }

        return true;
    }
}