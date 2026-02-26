<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Form\OffreType;
use App\Entity\Offre;
use App\Repository\OffreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use App\Service\MatchingService;


final class OffreController extends AbstractController
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * CHATBOT IA : Version Mistral AI optimisée (Sobre et Directe)
     */
#[Route('/assistant/offre/conseil', name: 'app_offre_ai_advice', methods: ['POST'])]
    public function adviseEmployer(
        Request $request, 
        LoggerInterface $logger, 
        OffreRepository $offreRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $userMessage = trim($data['message'] ?? '');
        $cvContent   = trim($data['cvContent'] ?? '');

        // 1. RÉCUPÉRATION DES OFFRES
        $offresBDD = $offreRepository->findBy(
            ['statutValidation' => 'approuvée', 'isActive' => true],
            ['id' => 'DESC'],
            10
        );
        
        $listeOffres = "";
        foreach ($offresBDD as $offre) {
            $listeOffres .= "- OFFRE #{$offre->getId()} : {$offre->getTitle()} à {$offre->getLieu()}\n";
        }

        // 2. LE SYSTEM PROMPT DÉTAILLÉ (70-80 LIGNES DE LOGIQUE)
        $systemPrompt = "
        NOM DE L'ASSISTANT : AgriSmart AI.
        VERSION : 2.0 (Spécialiste Recrutement Agricole Tunisie).
        
        --- MISSION PRINCIPALE ---
        Tu es un conseiller expert en recrutement pour la plateforme AgriSmart. 
        Ton but est d'aider les candidats à trouver un emploi dans les fermes et entreprises agricoles tunisiennes.

        --- GESTION DE LA POLITESSE (ACCUEIL) ---
        - Si l'utilisateur dit 'Bonjour', 'Bonsoir', 'Salut', 'Hey' : Réponds avec enthousiasme et politesse.
        - Si l'utilisateur demande 'Comment ça va ?', 'Tu vas bien ?' : Réponds que tu vas très bien et que tu es prêt à analyser des CV.
        - Si l'utilisateur demande 'Qui es-tu ?' : Présente-toi comme l'IA officielle d'AgriSmart.
        - Toujours rester professionnel mais chaleureux (ton de coach).

        --- RÉGLES DE RÉPONSES STRICTES ---
        1. REJET DU HORS-SUJET : 
           Si l'utilisateur parle de : cuisine, sport, politique, météo, religion, ou tout autre sujet non lié à l'emploi.
           Réponse obligatoire : « Je suis l'assistant AgriSmart, spécialisé uniquement dans le recrutement agricole. Comment puis-je vous aider dans votre carrière ? »

        2. REJET DES CONDITIONS LOGISTIQUES :
           Si l'utilisateur demande : 'Y a-t-il un logement ?', 'Le transport est-il payé ?', 'Quel est le visa ?'.
           Réponse obligatoire : « Je n'ai pas accès aux détails logistiques (logement, transport). Veuillez contacter l'agriculteur directement via l'offre. »

        3. ANALYSE DE CV :
           - Si un CV est présent dans le contexte, cherche des mots-clés (ex: tracteur, récolte, élevage, ingénieur).
           - Compare ces mots avec la liste des OFFRES RÉELLES ci-dessous.
           - Propose maximum les 2 meilleures offres.

        4. STRUCTURE DE RÉPONSE :
           - Jamais de longs paragraphes.
           - Utilise exclusivement des listes à puces (•).
           - Mets les titres de postes et lieux en **gras**.
           - Maximum 4 à 5 lignes par réponse totale.

        --- CONTEXTE RÉEL (OFFRES EN BASE) ---
        Voici les offres actuellement disponibles sur la plateforme AgriSmart :
        {$listeOffres}

        --- CONTEXTE CANDIDAT (CV) ---
        " . ($cvContent ? $this->truncateContext($cvContent, 3000) : "Aucun CV n'a été téléchargé pour le moment.") . "

        --- DERNIÈRE CONSIGNE ---
        Ne mentionne jamais que tu es un 'modèle de langage'. Agis comme un employé d'AgriSmart.
        Réponds toujours dans la langue utilisée par l'utilisateur (Arabe tunisien ou Français).
        ";

        $apiKey = "nA6CO7ubQH56abPXG6GfeymxyNV8B2oT"; 

        try {
            $response = $this->httpClient->request('POST', 'https://api.mistral.ai/v1/chat/completions', [
                'timeout' => 20,
                'verify_peer' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'mistral-small-latest', 
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userMessage ?: "Bonjour, que peux-tu faire pour moi ?"],
                    ],
                    'temperature' => 0.4, // Augmenté légèrement pour être plus 'humain' dans les bonjours
                    'max_tokens'  => 300,
                ],
            ]);

            $result  = $response->toArray();
            $aiReply = $result['choices'][0]['message']['content'] ?? 'Je rencontre une petite difficulté technique.';
            
            return new JsonResponse(['reply' => trim($aiReply)]);

        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            return new JsonResponse(['reply' => 'Assistant indisponible.'], 500);
        }
    }
    /**
     * Tronque intelligemment pour éviter d'exploser le contexte token
     */
    private function truncateContext(string $text, int $maxLength): string
    {
        $text = trim($text);
        $len  = mb_strlen($text);

        if ($len <= $maxLength) {
            return $text;
        }

        $cut = mb_substr($text, 0, $maxLength - 25);
        $lastSpace = mb_strrpos($cut, ' ');
        $cut = $lastSpace ? mb_substr($cut, 0, $lastSpace) : $cut;

        return $cut . '… [contenu tronqué]';
    }

    /**
     * ACTIONS DE GESTION DES OFFRES
     */

    #[Route('/admin/offre/new', name: 'app_offre_new', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offre->setAgriculteur($this->getUser());
            $offre->setStatutValidation('en_attente');
            $offre->setIsActive(true);
            $em->persist($offre);
            $em->flush();
            $this->addFlash('success', 'Offre créée avec succès !');
            return $this->redirectToRoute('app_admin_offres');
        }
        return $this->render('back/offre/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/liste-offres', name: 'app_offre_admin_index')]
    public function index(Request $request, OffreRepository $repo): Response
    {
        $searchTerm = $request->query->get('search');
        $offres = $searchTerm ? $repo->createQueryBuilder('o')
            ->where('o.title LIKE :term OR o.lieu LIKE :term')
            ->setParameter('term', '%'.$searchTerm.'%')
            ->getQuery()->getResult() : $repo->findAll();
        return $this->render('back/admin/admin_list.html.twig', ['offres' => $offres]);
    }

    #[Route('/admin/offres', name: 'app_admin_offres')]
    public function adminIndex(OffreRepository $repo): Response
    {
        $offres = $repo->findBy(['agriculteur' => $this->getUser()]);
        return $this->render('back/offre/index.html.twig', ['offres' => $offres]);
    }

#[Route('/admin/offre/{id}/details', name: 'app_admin_offre_details')]
public function details(
    Offre $offre, 
    MatchingService $matching, 
    EntityManagerInterface $em
): Response {
    // 1. On récupère les candidatures liées à cette offre
    $demandes = $offre->getDemandes();

    // 2. L'IA scanne chaque CV et remplit la colonne 'score'
    foreach ($demandes as $demande) {
        // On calcule le score dynamiquement selon le CV et l'offre
        $score = $matching->calculateScore($demande);
        
        // CORRECTION : On utilise bien setScore (avec un 't')
        $demande->setScore($score);
    }

    // 3. On sauvegarde les scores dans la base de données (pour enlever le NULL)
    $em->flush();

    // 4. On transforme en tableau pour trier : les meilleurs scores en premier
    $candidaturesTriees = $demandes->toArray();
    usort($candidaturesTriees, fn($a, $b) => $b->getScore() <=> $a->getScore());

    // 5. On envoie les données triées au fichier Twig
    return $this->render('back/offre/details.html.twig', [
        'offre' => $offre,
        'demandes' => $candidaturesTriees, // Important : on passe la liste triée
    ]);
}
    #[Route('/offre/{id}', name: 'app_offre_show', requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $offre = $entityManager->getRepository(Offre::class)->find($id);
        if (!$offre) throw $this->createNotFoundException("L'offre n'existe pas.");
        
        return $this->render('front/offre/show.html.twig', ['offre' => $offre]);
    }

    #[Route('/admin/offre/edit/{id}', name: 'app_offre_edit')]
    public function form(Offre $offre = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$offre) $offre = new Offre();
        $form = $this->createForm(OffreType::class, $offre);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($offre);
            $em->flush();
            $this->addFlash('success', 'Offre enregistrée !');
            return $this->redirectToRoute('app_admin_offres');
        }
        return $this->render('back/offre/form.html.twig', ['form' => $form->createView(), 'editMode' => $offre->getId() !== null]);
    }

    #[Route('/admin/offre/delete/{id}', name: 'app_offre_delete', methods: ['POST'])]
    public function delete(Offre $offre, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$offre->getId(), $request->request->get('_token'))) {
            $em->remove($offre);
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_offres');
    }

    #[Route('/offres-emploi', name: 'app_offre_index_front')]
    public function indexFront(Request $request, OffreRepository $repo): Response
    {
        $searchTerm = $request->query->get('search');
        $qb = $repo->createQueryBuilder('o')
            ->where("o.statutValidation = 'approuvée'")
            ->andWhere('o.isActive = :active')
            ->setParameter('active', true);
        if ($searchTerm) $qb->andWhere('o.title LIKE :term OR o.lieu LIKE :term')->setParameter('term', '%'.$searchTerm.'%');
        return $this->render('front/offre/index.html.twig', ['offres' => $qb->getQuery()->getResult()]);
    }

    #[Route('/admin/offre/{id}/approuver', name: 'app_offre_approuver', methods: ['POST'])]
    public function approuver(Offre $offre, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approuver'.$offre->getId(), (string) $request->request->get('_token'))) {
            $offre->setStatutValidation('approuvée');
            $offre->setIsActive(true);
            $em->flush();
            $this->addFlash('success', 'L\'offre a été approuvée.');
        }
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_offre_admin_index')));
    }

    #[Route('/admin/offre/{id}/refuser', name: 'app_offre_refuser', methods: ['POST'])]
    public function refuser(Offre $offre, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('refuser'.$offre->getId(), (string) $request->request->get('_token'))) {
            $offre->setStatutValidation('refusée');
            $offre->setIsActive(false);
            $em->flush();
            $this->addFlash('success', 'L\'offre a été refusée.');
        }
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_offre_admin_index')));
    }

    #[Route('/admin/offre/toggle/{id}', name: 'app_offre_toggle')]
    public function toggle(Offre $offre, EntityManagerInterface $em, Request $request): Response
    {
        $offre->setIsActive(!$offre->getIsActive());
        $em->flush();
        $this->addFlash('success', 'Statut mis à jour.');
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_admin_offres');
    }
}