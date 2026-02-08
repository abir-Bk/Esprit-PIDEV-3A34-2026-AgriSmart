<?php

namespace App\Controller;

use App\Entity\Culture;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/culture')]
class CultureController extends AbstractController
{
    #[Route('/new', name: 'app_culture_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ParcelleRepository $parcelleRepo): Response
    {
        $parcelle = $parcelleRepo->find($request->request->get('parcelle_id'));
        if (!$parcelle) return $this->redirectToRoute('app_parcelle_index');

        $culture = new Culture();
        $culture->setParcelle($parcelle);
        $culture->setTypeCulture($request->request->get('typeCulture'));
        $culture->setVariete($request->request->get('variete'));
        $culture->setStatut($request->request->get('statut') ?? 'En croissance');
        $culture->setDatePlantation(new \DateTime($request->request->get('datePlantation')));
        $culture->setDateRecoltePrevue((new \DateTime($request->request->get('datePlantation')))->modify('+90 days'));

        $entityManager->persist($culture);
        $entityManager->flush();

        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/{id}/edit', name: 'app_culture_edit', methods: ['POST'])]
    public function edit(Request $request, Culture $culture, EntityManagerInterface $entityManager): Response
    {
        $culture->setTypeCulture($request->request->get('typeCulture'));
        $culture->setVariete($request->request->get('variete'));
        $culture->setStatut($request->request->get('statut'));
        $culture->setDatePlantation(new \DateTime($request->request->get('datePlantation')));
        
        $entityManager->flush();
        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/{id}/delete', name: 'app_culture_delete', methods: ['POST'])]
    public function delete(Request $request, Culture $culture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$culture->getId(), $request->request->get('_token'))) {
            $entityManager->remove($culture);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_parcelle_index');
    }
}