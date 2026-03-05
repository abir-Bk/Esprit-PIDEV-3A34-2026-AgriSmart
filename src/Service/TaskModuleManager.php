<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\SuiviTache;
use App\Entity\TaskAssignment;

class TaskModuleManager
{
    /**
     * Valide les règles métier pour une Tâche.
     */
    public function validateTask(Task $task): bool
    {
        if (empty($task->getTitre())) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }

        if ($task->getDateFin() !== null && $task->getDateFin() < $task->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        return true;
    }

    /**
     * Valide les règles métier pour un Suivi de Tâche.
     */
    public function validateSuivi(SuiviTache $suivi): bool
    {
        if (empty($suivi->getRendement()) || strlen($suivi->getRendement()) < 2) {
            throw new \InvalidArgumentException('Le rendement doit contenir au moins 2 caractères.');
        }

        if (empty($suivi->getProblemes()) || strlen($suivi->getProblemes()) < 10) {
            throw new \InvalidArgumentException('Veuillez décrire les problèmes plus en détail (au moins 10 caractères).');
        }

        if (empty($suivi->getSolution()) || strlen($suivi->getSolution()) < 10) {
            throw new \InvalidArgumentException('Veuillez décrire la solution plus en détail (au moins 10 caractères).');
        }

        return true;
    }

    /**
     * Valide les règles métier pour une Assignation de Tâche.
     */
    public function validateAssignment(TaskAssignment $assignment): bool
    {
        $validStatuses = ['assignee', 'acceptee', 'realisee'];
        if (!in_array($assignment->getStatut(), $validStatuses)) {
            throw new \InvalidArgumentException('Le statut de l\'assignation est invalide.');
        }

        if ($assignment->getTask() === null) {
            throw new \InvalidArgumentException('L\'assignation doit être liée à une tâche.');
        }

        return true;
    }
}
