<?php

namespace App\Controller;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use App\Repository\TaskAssignmentRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tasks', name: 'tasks_')]

#[IsGranted('ROLE_AGRICULTEUR')]
class TaskCrudController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(TaskRepository $taskRepository, TaskAssignmentRepository $taskAssignmentRepository): Response
    {
        $today = new \DateTimeImmutable('today');

        $totalTasks = $taskRepository->count([]);
        $todoTasks = $taskRepository->count(['statut' => 'todo']);
        $inProgressTasks = $taskRepository->count(['statut' => 'en_cours']);
        $doneTasks = $taskRepository->count(['statut' => 'termine']);

        $totalAssignments = $taskAssignmentRepository->count([]);
        $todayTasks = $taskRepository->findTodayTasks($today);

        return $this->render('front/task/dashboard.html.twig', [
            'totalTasks' => $totalTasks,
            'todoTasks' => $todoTasks,
            'inProgressTasks' => $inProgressTasks,
            'doneTasks' => $doneTasks,
            'totalAssignments' => $totalAssignments,
            'todayTasks' => $todayTasks,
            'today' => $today,
        ]);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'dateDebut');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));

        $allowedSort = [
            'id' => 't.idTask',
            'titre' => 't.titre',
            'type' => 't.type',
            'priorite' => 't.priorite',
            'statut' => 't.statut',
            'dateDebut' => 't.dateDebut',
            'dateFin' => 't.dateFin',
        ];

        $sortField = $allowedSort[$sort] ?? $allowedSort['dateDebut'];
        $sortDirection = $direction === 'ASC' ? 'ASC' : 'DESC';

        $qb = $taskRepository->createQueryBuilder('t');

        if ($search !== '') {
            $qb
                ->andWhere('t.titre LIKE :q OR t.description LIKE :q OR t.type LIKE :q OR t.priorite LIKE :q OR t.statut LIKE :q')
                ->setParameter('q', '%' . $search . '%');
        }

        $tasks = $qb
            ->orderBy($sortField, $sortDirection)
            ->getQuery()
            ->getResult();

        return $this->render('front/task/index.html.twig', [
            'tasks' => $tasks,
            'q' => $search,
            'sort' => $sort,
            'direction' => $sortDirection,
        ]);
    }

    #[Route('/calendar', name: 'calendar', methods: ['GET'])]
    public function calendar(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findAll();
        $events = [];
        foreach ($tasks as $task) {
            $events[] = [
                'id' => $task->getIdTask(),
                'title' => $task->getTitre() . ' (' . $task->getType() . ')',
                'start' => $task->getDateDebut()->format('c'),
                'end' => $task->getDateFin() ? $task->getDateFin()->format('c') : $task->getDateDebut()->format('c'),
                'url' => $this->generateUrl('tasks_show', ['id' => $task->getIdTask()]),
                'extendedProps' => [
                    'type' => $task->getType(),
                    'statut' => $task->getStatut(),
                    'priorite' => $task->getPriorite(),
                ],
            ];
        }

        return $this->render('front/task/calendar.html.twig', [
            'tasks' => $tasks,
            'events' => $events,
            'google_api_key' => $this->getParameter('google_calendar_api_key'),
            'google_calendar_id' => $this->getParameter('google_project_calendar_id'),
        ]);
    }

    #[Route('/export/ical', name: 'export_ical', methods: ['GET'])]
    public function exportIcal(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findAll();

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//AgriSmart//Task Management//FR\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:AgriSmart Tâches\r\n";
        $ical .= "X-WR-TIMEZONE:Europe/Paris\r\n";

        foreach ($tasks as $task) {
            $uid = 'task-' . $task->getIdTask() . '@agrismart.tn';
            $summary = str_replace([",", ";"], ["\\,", "\\;"], $task->getTitre());
            $description = str_replace([",", ";", "\n"], ["\\,", "\\;", "\\n"], $task->getDescription() ?? '');

            $start = $task->getDateDebut()->format('Ymd\THis\Z');
            $end = $task->getDateFin() ? $task->getDateFin()->format('Ymd\THis\Z') : $start;

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:$uid\r\n";
            $ical .= "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
            $ical .= "DTSTART:$start\r\n";
            $ical .= "DTEND:$end\r\n";
            $ical .= "SUMMARY:$summary\r\n";
            if ($description) {
                $ical .= "DESCRIPTION:$description\r\n";
            }
            if ($task->getLocalisation()) {
                $ical .= "LOCATION:" . str_replace([",", ";"], ["\\,", "\\;"], $task->getLocalisation()) . "\r\n";
            }
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR";

        $response = new Response($ical);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'agrismart_tasks.ics'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedParcelle = $form->get('parcelleId')->getData();
            $task->setParcelleId($selectedParcelle?->getId());

            $selectedCulture = $form->get('culture')->getData();
            $task->setCulture($selectedCulture);

            $selectedUser = $form->get('createdByUser')->getData();
            $task->setCreatedBy($selectedUser?->getId());

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'Tache creee avec succes.');

            return $this->redirectToRoute('tasks_index');
        }

        return $this->render('front/task/new.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, TaskRepository $taskRepository): Response
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Tache introuvable.');
        }

        return $this->render('front/task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        EntityManagerInterface $em,
        ParcelleRepository $parcelleRepository,
        CultureRepository $cultureRepository,
        UserRepository $userRepository
    ): Response {
        $task = $taskRepository->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Tache introuvable.');
        }

        $form = $this->createForm(TaskType::class, $task, ['user' => $this->getUser()]);
        $this->hydrateUnmappedTaskFields($form, $task, $parcelleRepository, $cultureRepository, $userRepository);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedParcelle = $form->get('parcelleId')->getData();
            $task->setParcelleId($selectedParcelle?->getId());

            $selectedCulture = $form->get('culture')->getData();
            $task->setCulture($selectedCulture);

            $selectedUser = $form->get('createdByUser')->getData();
            $task->setCreatedBy($selectedUser?->getId());

            $em->flush();

            $this->addFlash('success', 'Tache mise a jour avec succes.');

            return $this->redirectToRoute('tasks_index');
        }

        return $this->render('front/task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, TaskRepository $taskRepository, EntityManagerInterface $em): Response
    {
        $task = $taskRepository->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Tache introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $task->getIdTask(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
            $this->addFlash('success', 'Tache supprimee avec succes.');
        }

        return $this->redirectToRoute('tasks_index');
    }

    private function hydrateUnmappedTaskFields(
        FormInterface $form,
        Task $task,
        ParcelleRepository $parcelleRepository,
        CultureRepository $cultureRepository,
        UserRepository $userRepository
    ): void {
        if ($task->getParcelleId() !== null) {
            $parcelle = $parcelleRepository->find($task->getParcelleId());
            if ($parcelle instanceof Parcelle) {
                $form->get('parcelleId')->setData($parcelle);
            }
        }

        if ($task->getCulture() !== null) {
            $form->get('culture')->setData($task->getCulture());
        }

        if ($task->getCreatedBy() !== null) {
            $user = $userRepository->find($task->getCreatedBy());
            if ($user instanceof User) {
                $form->get('createdByUser')->setData($user);
            }
        }
    }
}
