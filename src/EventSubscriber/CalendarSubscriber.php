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
    private $taskRepository;
    private $router;

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
        $start = $calendarEvent->getStart();
        $end = $calendarEvent->getEnd();
        $filters = $calendarEvent->getFilters();

        // Fetch tasks
        // In a real app, you might want to filter by date using $start and $end
        $tasks = $this->taskRepository->findAll();

        foreach ($tasks as $task) {
            $isMultiDay = $task->getDateFin() && $task->getDateFin()->format('Y-m-d') !== $task->getDateDebut()->format('Y-m-d');

            $event = new Event(
                $task->getTitre(),
                $task->getDateDebut(),
                $task->getDateFin() ?: $task->getDateDebut()
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
