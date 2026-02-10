<?php

namespace App\Controller;

use App\Entity\Culture;
use App\Entity\Ressource;
use App\Entity\Consommation;
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

        $this->addFlash('success', 'Nouvelle culture ajoutée avec succès.');
        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/{id}/consommer', name: 'app_culture_consommer', methods: ['POST'])]
    public function consommer(Request $request, Culture $culture, EntityManagerInterface $em): Response
    {
        $ressourceId = $request->request->get('ressource_id');
        $quantiteUtilisee = (float) $request->request->get('quantite');

        $ressource = $em->getRepository(Ressource::class)->find($ressourceId);

        if (!$ressource) {
            $this->addFlash('danger', 'Ressource introuvable.');
            return $this->redirectToRoute('app_parcelle_index');
        }

        if ($ressource->getStockRestan() < $quantiteUtilisee) {
            $this->addFlash('danger', "Stock insuffisant pour {$ressource->getNom()}");
            return $this->redirectToRoute('app_parcelle_index');
        }

        // Mise à jour du stock
        $ressource->setStockRestan($ressource->getStockRestan() - $quantiteUtilisee);

        // Création de l'historique
        $consommation = new Consommation();
        $consommation->setRessource($ressource);
        $consommation->setCulture($culture);
        $consommation->setQuantite($quantiteUtilisee);
        $consommation->setDateConsommation(new \DateTimeImmutable());
        
        // Correction ici : On ne définit pas l'agriculteur sur la consommation 
        // car le champ n'existe pas dans l'entité Consommation fournie.

        $em->persist($consommation);
        $em->flush();

        $this->addFlash('success', "Stock mis à jour (-{$quantiteUtilisee})");
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
        $this->addFlash('info', 'Culture mise à jour.');
        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/{id}/delete', name: 'app_culture_delete', methods: ['POST'])]
    public function delete(Request $request, Culture $culture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$culture->getId(), $request->request->get('_token'))) {
            $entityManager->remove($culture);
            $entityManager->flush();
            $this->addFlash('warning', 'Culture supprimée.');
        }
        return $this->redirectToRoute('app_parcelle_index');
    }
}