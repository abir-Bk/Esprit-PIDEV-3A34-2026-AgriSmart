<?php

namespace App\Controller;

use App\Service\PredictionService;
use App\Repository\ParcelleRepository;
use App\Repository\RessourceRepository;
use App\Exception\AiProviderException;
use App\Repository\ProduitRepository;
use App\Service\HuggingFaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ChatbotController extends AbstractController
{
    /**
     * Data-Aware Chat: provides agricultural advice based on the user's parcels and resources.
     */
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

        if (!$message || !is_string($message)) {
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
                        $plantation = $c->getDatePlantation();
                        $plantationText = $plantation instanceof \DateTimeInterface ? $plantation->format('d/m/Y') : 'N/A';
                        $context .= "  * Culture: {$c->getTypeCulture()} ({$c->getVariete()}), Statut: {$c->getStatut()}, Plantation: {$plantationText}\n";
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

    /**
     * Catalog Chat: provides information based on the marketplace catalog products.
     * AJAX endpoint: receive conversation messages, return AI reply.
     */
    #[Route('/chatbot/message', name: 'app_chatbot_message', methods: ['POST'])]
    public function message(
        Request $request,
        HuggingFaceService $huggingFaceService,
        ProduitRepository $produitRepository
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            if (!\is_array($data)) {
                return $this->json(['error' => 'Requête invalide (JSON attendu).'], 400);
            }

            if (!isset($data['messages']) || !is_array($data['messages'])) {
                return $this->json(['error' => 'Paramètre messages manquant.'], 400);
            }

            $messages = array_slice($data['messages'], -10);

            foreach ($messages as $msg) {
                if (
                    !isset($msg['role'], $msg['content'])
                    || !in_array($msg['role'], ['user', 'assistant'], true)
                    || !is_string($msg['content'])
                ) {
                    return $this->json(['error' => 'Format de message invalide.'], 400);
                }
            }

            $catalog = $this->buildCatalog($produitRepository);

            $reply = $huggingFaceService->chat($messages, $catalog);
            return $this->json(['reply' => $reply]);
        } catch (AiProviderException $e) {
            return $this->json([
                'error' => $e->getUserMessage(),
                'provider' => $e->getProvider(),
                'kind' => $e->getKind(),
            ], $e->getStatusCode());
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 503);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch up to 35 in-stock products and format them as a concise catalog string.
     * Limité pour rester sous les quotas IA (tokens/requête) et éviter 429.
     */
    private function buildCatalog(ProduitRepository $repo): string
    {
        $produits = $repo->createQueryBuilder('p')
            ->where('p.quantiteStock > 0')
            ->orderBy('p.categorie', 'ASC')
            ->addOrderBy('p.nom', 'ASC')
            ->setMaxResults(35)
            ->getQuery()
            ->getResult();

        if (empty($produits)) {
            return 'Aucun produit disponible en stock pour le moment.';
        }

        $lines = [];
        foreach ($produits as $p) {
            $price = $p->isPromotion() && $p->getPromotionPrice()
                ? $p->getPromotionPrice() . ' TND (promo, était ' . $p->getPrix() . ' TND)'
                : $p->getPrix() . ' TND';

            $productLink = $p->getId() !== null
                ? $this->generateUrl('app_produit_show', ['id' => $p->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                : '#';

            $lines[] = sprintf(
                '- %s | Catégorie: %s | Prix: %s | Stock: %d unité(s)%s | Lien: %s',
                $p->getNom(),
                $p->getCategorie() ?? 'N/A',
                $price,
                $p->getQuantiteStock(),
                $p->getDescription() ? ' | ' . mb_substr($p->getDescription(), 0, 50) . '…' : '',
                $productLink
            );
        }

        return implode("\n", $lines);
    }
}
