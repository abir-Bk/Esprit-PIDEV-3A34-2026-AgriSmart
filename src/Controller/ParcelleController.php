<?php

namespace App\Controller;

use App\Entity\Parcelle;
use App\Form\ParcelleType;
use App\Entity\Culture;
use App\Repository\ParcelleRepository;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\PdfService;

#[Route('/parcelle')]
class ParcelleController extends AbstractController
{
    #[Route('/', name: 'app_parcelle_index', methods: ['GET', 'POST'])]
    public function index(
        ParcelleRepository $parcelleRepository,
        RessourceRepository $ressourceRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
/** @var \App\Entity\User $user */
$user = $this->getUser();
if (!$user) {
    return $this->redirectToRoute('app_login');
}
        // Recherche et tri
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', 'nbCultures');
        $direction = strtoupper((string) $request->query->get('direction', 'DESC'));
        $direction = $direction === 'ASC' ? 'ASC' : 'DESC';

        $qb = $parcelleRepository->createQueryBuilder('p')
            ->leftJoin('p.cultures', 'c')
            ->addSelect('p')
            ->addSelect('COUNT(c.id) AS HIDDEN nbCultures')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->groupBy('p.id');

        // Tri sécurisé
        switch ($sort) {
            case 'nom':
                $qb->orderBy('p.nom', $direction);
                break;
            case 'surface':
                $qb->orderBy('p.surface', $direction);
                break;
            case 'nbCultures':
            default:
                $qb->orderBy('nbCultures', $direction);
                break;
        }

        if ($search !== '') {
            $qb->andWhere('LOWER(p.nom) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }

        $parcelles = $qb->getQuery()->getResult();

        // Formulaire création nouvelle parcelle
        $parcelle = new Parcelle();
        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
              try {
    $parcelle->setUser($user);
    $entityManager->persist($parcelle);
                    $entityManager->flush();

                    $this->addFlash('success', '✅ Parcelle créée avec succès !');
                    // Redirection sans filtre de recherche pour que la nouvelle parcelle soit visible
                    return $this->redirectToRoute('app_parcelle_index');
                } catch (\Exception $e) {
                    $this->addFlash('danger', '❌ Erreur lors de la création de la parcelle.');
                }
            } else {
                // Erreurs de validation : on garde le formulaire ouvert dans le modal (voir showParcelleModal)
                $errors = $form->getErrors(true);
                foreach ($errors as $error) {
                    if ($error instanceof \Symfony\Component\Form\FormError) {
                        $this->addFlash('danger', '❌ ' . $error->getMessage());
                    }
                }
            }
        }

        $showParcelleModal = $form->isSubmitted() && !$form->isValid();

        return $this->render('front/semi-public/parcelle/parcelle.html.twig', [
            'parcelles'         => $parcelles,
            'ressources'        => $ressourceRepository->findBy(['user' => $user]),
            'form'              => $form->createView(),
            'search'            => $search,
            'sort'              => $sort,
            'direction'         => $direction,
            'showParcelleModal' => $showParcelleModal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_parcelle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Parcelle $parcelle,
        EntityManagerInterface $entityManager
    ): Response {
        if ($parcelle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $entityManager->flush();
                    $this->addFlash('success', '✅ Parcelle modifiée avec succès.');
                    return $this->redirectToRoute('app_parcelle_index');
                } catch (\Exception $e) {
                    $this->addFlash('danger', '❌ Erreur lors de la modification.');
                }
            } else {
                $errors = $form->getErrors(true);
                foreach ($errors as $error) {
                    if ($error instanceof \Symfony\Component\Form\FormError) {
                        $this->addFlash('danger', '❌ ' . $error->getMessage());
                    }
                }
            }
        }

        return $this->render('front/semi-public/parcelle/edit.html.twig', [
            'parcelle' => $parcelle,
            'form'     => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'app_parcelle_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Parcelle $parcelle,
        EntityManagerInterface $entityManager
    ): Response {
        if ($parcelle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $parcelle->getId(), (string) $request->request->get('_token'))) {
            try {
                $entityManager->remove($parcelle);
                $entityManager->flush();
                $this->addFlash('success', ' Parcelle supprimée.');
            } catch (\Exception $e) {
                $this->addFlash('danger', ' Impossible de supprimer cette parcelle.');
            }
        }

        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/{id}/conseiller-ia', name: 'app_parcelle_conseiller_ia', methods: ['POST'])]
    public function conseillerIa(
        Request $request,
        Parcelle $parcelle,
        HttpClientInterface $httpClient
    ): JsonResponse {
        if ($parcelle->getUser() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'error' => 'Accès refusé.'], 403);
        }

        if (!$this->isCsrfTokenValid('conseiller_ia_' . $parcelle->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'error' => 'Token invalide.'], 400);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        if (!$apiKey) {
            return new JsonResponse(['success' => false, 'error' => 'Service IA non configuré.'], 503);
        }

        $nom = $parcelle->getNom();
        $surface = $parcelle->getSurface();
        $typeSol = $parcelle->getTypeSol();
        $lat = $parcelle->getLatitude();
        $lng = $parcelle->getLongitude();

        $prompt = sprintf(
            "Tu es un conseiller agricole expert. Une parcelle a les caractéristiques suivantes :\n" .
            "- Nom : %s\n- Surface : %s hectare(s)\n- Type de sol : %s\n- Localisation : latitude %s, longitude %s\n\n" .
            "Recommande 3 à 5 cultures adaptées à cette parcelle (variétés et pratiques si possible). " .
            "Réponds en français, de façon claire et structurée (liste ou paragraphes courts). " .
            "Ne mets pas de titre générique, va directement aux recommandations.",
            $nom,
            $surface,
            $typeSol,
            $lat,
            $lng
        );

        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
            $response = $httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ],
            ]);
            $data = $response->toArray();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $data['candidates'][0]['content']['parts'][0]['text'];
                return new JsonResponse(['success' => true, 'recommendations' => $text]);
            }

            return new JsonResponse(['success' => false, 'error' => 'Réponse IA invalide.'], 502);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la consultation du conseiller : ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/admin/toutes-les-parcelles', name: 'admin_parcelles_index', methods: ['GET'])]
    public function adminIndex(ParcelleRepository $parcelleRepository): Response
    {
        // Sécurité : Uniquement accessible par l'Admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // On récupère tout avec les jointures pour la performance
        $allParcelles = $parcelleRepository->createQueryBuilder('p')
            ->leftJoin('p.cultures', 'c')
            ->leftJoin('p.user', 'u')
            ->addSelect('c', 'u')
            ->getQuery()
            ->getResult();

        return $this->render('back/Allparcelle/all_parcelles.html.twig', [
            'parcelles' => $allParcelles,
        ]);
    }

    #[Route('/predict-rendement/{id}', name: 'app_culture_predict_ia', methods: ['POST'])]
    public function predireRendement(Culture $culture, \App\Service\PredictionService $predictionService): Response
    {
        $totalConsomme = 0;
        foreach ($culture->getConsommations() as $conso) {
            $totalConsomme += $conso->getQuantite();
        }

        $surface = $culture->getParcelle()?->getSurface();
        $typeCulture = $culture->getTypeCulture();

        if ($surface === null || $typeCulture === null) {
            return $this->json(['error' => 'Parcelle non trouvée ou surface non définie'], 400);
        }

        // L'appel au service qui maintenant ne donnera que du 100% IA ou une erreur
        $rendementEstime = $predictionService->predict($surface, $totalConsomme, $typeCulture);

        return $this->render('front/semi-public/parcelle/resultat_rendement.html.twig', [
            'rendement' => $rendementEstime,
            'culture' => $culture,
            'totalConsomme' => $totalConsomme
        ]);
    }

    #[Route('/{id}/export-pdf', name: 'app_parcelle_export_pdf', methods: ['GET'])]
    public function exportParcelle(Parcelle $parcelle, PdfService $pdfService): Response
    {
        if ($parcelle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $pdfContent = $pdfService->generatePdfResponse('pdf/parcelle_fiche.html.twig', [
            'parcelle' => $parcelle,
            'user' => $this->getUser(),
        ], 'fiche_parcelle_' . $parcelle->getId() . '.pdf');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="fiche_parcelle_' . $parcelle->getId() . '.pdf"',
        ]);
    }

    #[Route('/culture/{id}/export-prediction-pdf', name: 'app_culture_prediction_export_pdf', methods: ['GET'])]
    public function exportPrediction(Culture $culture, PdfService $pdfService, \App\Service\PredictionService $predictionService): Response
    {
        $parcelle = $culture->getParcelle();
        if ($parcelle === null || $parcelle->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $totalConsomme = 0;
        foreach ($culture->getConsommations() as $conso) {
            $totalConsomme += $conso->getQuantite();
        }

        $surfac = $parcelle->getSurface();
        $typeCulture = $culture->getTypeCulture();
        if ($surfac === null || $typeCulture === null) {
            throw $this->createNotFoundException('Données de culture incomplètes pour la prédiction.');
        }
        $rendementEstime = $predictionService->predict($surfac, $totalConsomme, $typeCulture);

        $pdfContent = $pdfService->generatePdfResponse('pdf/prediction_rapport.html.twig', [
            'culture' => $culture,
            'rendement' => $rendementEstime,
            'totalConsomme' => $totalConsomme,
            'user' => $this->getUser(),
        ], 'rapport_prediction_' . $culture->getId() . '.pdf');

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport_prediction_' . $culture->getId() . '.pdf"',
        ]);
    }
}