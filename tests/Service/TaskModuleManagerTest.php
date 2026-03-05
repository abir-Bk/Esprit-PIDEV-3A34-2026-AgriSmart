<?php

namespace App\Tests\Service;

use App\Entity\Task;
use App\Entity\SuiviTache;
use App\Entity\TaskAssignment;
use App\Service\TaskModuleManager;
use PHPUnit\Framework\TestCase;

class TaskModuleManagerTest extends TestCase
{
    private TaskModuleManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TaskModuleManager();
    }

    public function testValidTask()
    {
        $task = new Task();
        $task->setTitre('Arrosage des tomates');
        $task->setDateDebut(new \DateTime('2026-03-05 08:00'));
        $task->setDateFin(new \DateTime('2026-03-05 10:00'));

        $this->assertTrue($this->manager->validateTask($task));
    }

    public function testTaskWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire.');

        $task = new Task();
        // Titre manquant
        $task->setDateDebut(new \DateTime());

        $this->manager->validateTask($task);
    }

    public function testTaskInvalidDates()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $task = new Task();
        $task->setTitre('Tâche avec dates invalides');
        $task->setDateDebut(new \DateTime('2026-03-10'));
        $task->setDateFin(new \DateTime('2026-03-05')); // Date fin < Date début

        $this->manager->validateTask($task);
    }

    public function testValidSuivi()
    {
        $suivi = new SuiviTache();
        $suivi->setRendement('Bon');
        $suivi->setProblemes('Aucun problème majeur détecté.');
        $suivi->setSolution('Continuer la surveillance régulière.');

        $this->assertTrue($this->manager->validateSuivi($suivi));
    }

    public function testSuiviShortRendement()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rendement doit contenir au moins 2 caractères.');

        $suivi = new SuiviTache();
        $suivi->setRendement('A'); // Trop court
        $this->manager->validateSuivi($suivi);
    }

    public function testSuiviShortProblemes()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Veuillez décrire les problèmes plus en détail (au moins 10 caractères).');

        $suivi = new SuiviTache();
        $suivi->setRendement('Normal');
        $suivi->setProblemes('Rien'); // Trop court
        $this->manager->validateSuivi($suivi);
    }

    public function testValidAssignment()
    {
        $assignment = new TaskAssignment();
        $assignment->setStatut('acceptee');
        $assignment->setTask(new Task());

        $this->assertTrue($this->manager->validateAssignment($assignment));
    }

    public function testAssignmentInvalidStatut()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut de l\'assignation est invalide.');

        $assignment = new TaskAssignment();
        $assignment->setStatut('inconnu'); // Statut non autorisé
        $this->manager->validateAssignment($assignment);
    }

    public function testAssignmentMissingTask()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'assignation doit être liée à une tâche.');

        $assignment = new TaskAssignment();
        $assignment->setStatut('assignee');
        // Task manquante
        $this->manager->validateAssignment($assignment);
    }
}
