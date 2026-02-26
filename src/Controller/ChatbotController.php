<?php

namespace App\Controller;

use App\Service\PredictionService;
use App\Repository\ParcelleRepository;
use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class ChatbotController extends AbstractController
{
    #[Route('/chatbot/ask', name: 'app_chatbot_ask', methods: ['POST'])]
    public function ask(
        Request $request, 
        PredictionService $predictionService, 
        ParcelleRepository $parcelleRepository,
        RessourceRepository $ressourceRepository,
        Security $security
    ): JsonResponse {
        $message = $request->request->get('message');
        $user = $security->getUser();

        if (!$message) {
            return new JsonResponse(['response' => 'Dis-moi quelque chose !']);
        }

        // Construction du contexte basé sur les données de l'utilisateur
        $context = "Données utilisateur :\n";
        if ($user) {
            $parcelles = $parcelleRepository->findBy(['user' => $user]);
            if (empty($parcelles)) {
                $context .= "L'utilisateur n'a pas encore de parcelles enregistrées.\n";
            } else {
                foreach ($parcelles as $p) {
                    $context .= "- Parcelle: {$p->getNom()}, Surface: {$p->getSurface()}ha, Sol: {$p->getTypeSol()}\n";
                    foreach ($p->getCultures() as $c) {
                        $context .= "  * Culture: {$c->getTypeCulture()} ({$c->getVariete()}), Statut: {$c->getStatut()}, Plantation: {$c->getDatePlantation()->format('d/m/Y')}\n";
                        foreach ($c->getConsommations() as $conso) {
                            $output = $conso->getRessource() ? $conso->getRessource()->getNom() : 'Inconnue';
                            $context .= "    - Consommation: {$output}: {$conso->getQuantite()} {$conso->getRessource()?->getUnite()}\n";
                        }
                    }
                }
            }
            
            $ressources = $ressourceRepository->findBy(['user' => $user]);
            $context .= "\nStocks de ressources :\n";
            if (empty($ressources)) {
                $context .= "Pas de stocks enregistrés.\n";
            } else {
                foreach ($ressources as $r) {
                    $context .= "- {$r->getNom()}: {$r->getStockRestant()} {$r->getUnite()}\n";
                }
            }
        } else {
            $context .= "Utilisateur non connecté.\n";
        }

        $aiResponse = $predictionService->generateChatResponse($message, $context);

        return new JsonResponse(['response' => $aiResponse]);
    }
}