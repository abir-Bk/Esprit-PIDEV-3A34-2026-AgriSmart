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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/culture')]
class CultureController extends AbstractController
{
    #[Route('/new', name: 'app_culture_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ParcelleRepository $parcelleRepo, ValidatorInterface $validator): Response
    {
        $parcelle = $parcelleRepo->find($request->request->get('parcelle_id'));
        if (!$parcelle) {
            $this->addFlash('danger', 'Parcelle introuvable.');
            return $this->redirectToRoute('app_parcelle_index');
        }

        $datePlantationStr = $request->request->get('datePlantation');
        $dateRecolteStr = $request->request->get('dateRecoltePrevue');
        try {
            $datePlantation = $datePlantationStr ? new \DateTime($datePlantationStr) : null;
            $dateRecoltePrevue = $dateRecolteStr ? new \DateTime($dateRecolteStr) : (($datePlantation ? clone $datePlantation : null)?->modify('+90 days'));
        } catch (\Exception $e) {
            $datePlantation = null;
            $dateRecoltePrevue = null;
        }

        $culture = new Culture();
        $culture->setParcelle($parcelle);
        $culture->setTypeCulture((string) $request->request->get('typeCulture', ''));
        $culture->setVariete((string) $request->request->get('variete', ''));
        $culture->setStatut((string) ($request->request->get('statut') ?? 'En croissance'));
        $culture->setDatePlantation($datePlantation ?? new \DateTime());
        $culture->setDateRecoltePrevue($dateRecoltePrevue ?? (clone $culture->getDatePlantation())->modify('+90 days'));

        $errors = $validator->validate($culture);
        if ($errors->count() > 0) {
            foreach ($errors as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
            return $this->redirectToRoute('app_parcelle_index');
        }

        $entityManager->persist($culture);
        $entityManager->flush();

        $this->addFlash('success', '✅ Nouvelle culture ajoutée avec succès.');
        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/{id}/consommer', name: 'app_culture_consommer', methods: ['POST'])]
    public function consommer(Request $request, Culture $culture, EntityManagerInterface $em): Response
    {
        $ressourceId = $request->request->get('ressource_id');
        $quantiteUtilisee = (float) $request->request->get('quantite');

        if ($quantiteUtilisee <= 0) {
            $this->addFlash('danger', 'La quantité doit être un nombre strictement positif.');
            return $this->redirectToRoute('app_parcelle_index');
        }

        $ressource = $em->getRepository(Ressource::class)->find($ressourceId);

        if (!$ressource) {
            $this->addFlash('danger', 'Ressource introuvable.');
            return $this->redirectToRoute('app_parcelle_index');
        }

        if ($ressource->getUser() !== $this->getUser()) {
            $this->addFlash('danger', 'Accès refusé à cette ressource.');
            return $this->redirectToRoute('app_parcelle_index');
        }

        if ($ressource->getStockRestant() < $quantiteUtilisee) {
            $this->addFlash('danger', "Stock insuffisant.");
            return $this->redirectToRoute('app_parcelle_index');
        }

        $ressource->setStockRestant($ressource->getStockRestant() - $quantiteUtilisee);
        $consommation = new Consommation();
        $consommation->setRessource($ressource);
        $consommation->setCulture($culture);
        $consommation->setQuantite($quantiteUtilisee);
        $consommation->setDateConsommation(new \DateTimeImmutable());
        
        $em->persist($consommation);
        $em->flush();

        $this->addFlash('success', "✅ Stock mis à jour.");
        return $this->redirectToRoute('app_parcelle_index');
    }

    #[Route('/{id}/edit', name: 'app_culture_edit', methods: ['POST'])]
    public function edit(Request $request, Culture $culture, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $datePlantationStr = $request->request->get('datePlantation');
        try {
            if ($datePlantationStr) {
                $culture->setDatePlantation(new \DateTime($datePlantationStr));
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Format de date invalide.');
            return $this->redirectToRoute('app_parcelle_index');
        }

        $culture->setTypeCulture((string) $request->request->get('typeCulture', ''));
        $culture->setVariete((string) $request->request->get('variete', ''));
        $culture->setStatut((string) $request->request->get('statut', ''));

        $entityManager->flush();
        $this->addFlash('success', '✅ Culture mise à jour.');
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

    #[Route('/ia/diagnostiquer-global', name: 'app_ia_diagnose_global', methods: ['POST'])]
    public function diagnostiquerGlobal(Request $request, HttpClientInterface $httpClient): Response
    {
        $imageFile = $request->files->get('image_plante');

        if (!$imageFile) {
            $this->addFlash('danger', 'Veuillez fournir une image.');
            return $this->redirectToRoute('app_parcelle_index');
        }

       try {
    $base64Image = base64_encode(file_get_contents($imageFile->getPathname()));
    $mimeType = $imageFile->getMimeType();

    // UTILISATION DU MODÈLE 2.5 FLASH 
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $_ENV['GEMINI_API_KEY'];

    $response = $httpClient->request('POST', $url, [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Tu es un expert agronome. Identifie la plante et sa maladie sur cette photo. Donne des conseils de traitement précis."],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]);

    $data = $response->toArray();

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $this->render('front/semi-public/parcelle/resultat.html.twig', [
            'diagnostic' => $data['candidates'][0]['content']['parts'][0]['text'],
            'image_envoyee' => "data:$mimeType;base64,$base64Image",
            'culture' => null
        ]);
    } else {
        dd("Réponse reçue mais structure de texte absente :", $data);
    }

} catch (\Exception $e) {
    // Si tu as encore une erreur, ce dd() nous dira exactement quoi
    dd("ERREUR API : " . $e->getMessage());
}
    }}