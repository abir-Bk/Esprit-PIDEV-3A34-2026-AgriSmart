<?php

namespace App\EventSubscriber;

use CalendarBundle\Entity\Event;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Repository\TaskRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private TaskRepository $taskRepository;
    private UrlGeneratorInterface $router;

    public function __construct(
        TaskRepository $taskRepository,
        UrlGeneratorInterface $router
    ) {
        $this->taskRepository = $taskRepository;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendarEvent): void
    {
        // Fetch tasks
        $tasks = $this->taskRepository->findAll();

        foreach ($tasks as $task) {
            $startDate = $task->getDateDebut();
            if (!$startDate instanceof \DateTimeInterface) {
                continue;
            }

            $endDate = $task->getDateFin() ?? $startDate;
            $title = $task->getTitre() ?? 'Tâche';
            $isMultiDay = $task->getDateFin() instanceof \DateTimeInterface && $task->getDateFin()->format('Y-m-d') !== $startDate->format('Y-m-d');

            $event = new Event(
                $title,
                $startDate,
                $endDate
            );

            $event->setOptions([
                'allDay' => $isMultiDay,
                'url' => $this->router->generate('tasks_show', ['id' => $task->getIdTask()]),
                'backgroundColor' => '#1A4331',
                'borderColor' => '#1A4331',
                'textColor' => '#ffffff',
            ]);

            $event->addOption('extendedProps', [
                'type' => $task->getType(),
                'statut' => $task->getStatut(),
                'priorite' => $task->getPriorite(),
                'description' => $task->getDescription(),
            ]);

            $calendarEvent->addEvent($event);
        }
    }
}
