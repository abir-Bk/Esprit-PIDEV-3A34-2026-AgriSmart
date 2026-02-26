<?php

namespace App\Controller;

use App\Entity\SuiviTache;
use App\Entity\Task;
use App\Form\SuiviTacheType;
use App\Repository\SuiviTacheRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/suivi-tache')]
class SuiviTacheController extends AbstractController
{
    #[Route('/new/{id}', name: 'suivi_tache_new', methods: ['GET', 'POST'])]
    public function new(#[MapEntity(expr: 'repository.find(id)')] Task $task, Request $request, EntityManagerInterface $em): Response
    {
        // Seuls les ouvriers (ou l'utilisateur assigné) devraient pouvoir créer un suivi ?
        // Pour l'instant on reste sur la logique fonctionnelle simple demandée.

        $suivi = new SuiviTache();
        $suivi->setTask($task);
        $suivi->setDate(new \DateTime());

        $form = $this->createForm(SuiviTacheType::class, $suivi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setStatut('a_valider');
            $em->persist($suivi);
            $em->flush();

            $this->addFlash('success', 'Votre suivi a été soumis. La tâche est en attente de validation par l\'agriculteur.');

            return $this->redirectToRoute('employee_tasks_index');
        }

        return $this->render('front/employee/suivi_new.html.twig', [
            'suivi' => $suivi,
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/validate/{id}', name: 'suivi_tache_validate', methods: ['POST'])]
    #[IsGranted('ROLE_AGRICULTEUR')]
    public function validate(#[MapEntity(expr: 'repository.find(id)')] SuiviTache $suivi, EntityManagerInterface $em): Response
    {
        $task = $suivi->getTask();
        $task->setStatut('termine');
        $em->flush();

        $this->addFlash('success', 'La tâche a été marquée comme terminée.');

        return $this->redirectToRoute('suivi_tache_index');
    }

    #[Route('/refuse/{id}', name: 'suivi_tache_refuse', methods: ['POST'])]
    #[IsGranted('ROLE_AGRICULTEUR')]
    public function refuse(#[MapEntity(expr: 'repository.find(id)')] SuiviTache $suivi, EntityManagerInterface $em): Response
    {
        $task = $suivi->getTask();
        $task->setStatut('en_cours');
        $em->flush();

        $this->addFlash('warning', 'La demande de clôture a été refusée. La tâche repasse en cours.');

        return $this->redirectToRoute('suivi_tache_index');
    }

    #[Route('/', name: 'suivi_tache_index', methods: ['GET'])]
    #[IsGranted('ROLE_AGRICULTEUR')]
    public function index(SuiviTacheRepository $suiviTacheRepository): Response
    {
        // L'agriculteur voit tous les suivis, particulièrement ceux des tâches "a_valider"
        $suivis = $suiviTacheRepository->findAll();

        return $this->render('front/suivi_tache/index.html.twig', [
            'suivis' => $suivis,
        ]);
    }
}
