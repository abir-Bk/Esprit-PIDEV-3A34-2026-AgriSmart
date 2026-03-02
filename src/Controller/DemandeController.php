<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Form\DemandeType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\DemandeRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;  
use App\Entity\Offre;
use App\Entity\Demande;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DemandeController extends AbstractController
{
    #[Route('/mes-demandes', name: 'app_my_demandes')]
    public function myDemandes(DemandeRepository $demandeRepository): Response
    {
        $user = $this->getUser(); 

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // ON UTILISE UN QUERY BUILDER AVEC JOIN POUR TOUT CHARGER EN 1 SEULE REQUÊTE
        $demandes = $demandeRepository->createQueryBuilder('d')
            ->addSelect('o') // On force le chargement de l'offre
            ->leftJoin('d.offre', 'o')
            ->where('d.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        return $this->render('front/demande/my_demandes.html.twig', [
            'demandes' => $demandes,
        ]);
    }

        #[Route('/offre/{id}/postuler', name: 'app_demande_postuler')]
        public function postuler(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $offre = $entityManager->getRepository(Offre::class)->find((int) $id);
        
        if (!$offre instanceof Offre) {
            throw $this->createNotFoundException('Offre non trouvée');
        }

        if ($offre->getDateFin() < new \DateTime() || strtolower((string)$offre->getStatut()) === 'clôturée')
            {
            $this->addFlash('danger', 'Cette offre n\'accepte plus de candidatures.');
            return $this->redirectToRoute('app_offre_index_front');
        }

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser instanceof User) {
            $this->addFlash('warning', 'Vous devez être connecté pour postuler.');
            return $this->redirectToRoute('app_login');
        }

        $alreadyApplied = $entityManager->getRepository(Demande::class)->findOneBy([
            'offre' => $offre,
            'user' => $currentUser 
        ]);

        if ($alreadyApplied) {
            $this->addFlash('warning', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('app_my_demandes');
        }

        $demande = new Demande();
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $cvIaFilename = $request->request->get('cv_ia_filename');
            $cvFile = $form->get('cv')->getData();

            if ($form->isValid() || ($cvIaFilename && $form->get('lettreMotivation')->isValid())) {
                
                // On s'assure d'utiliser le dossier public pour que les fichiers soient accessibles
            $projectDir = $this->getParameter('kernel.project_dir');
            $basePath = (is_string($projectDir) ? $projectDir : '') . '/public/uploads';

                // Si upload classique
                if ($cvFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $cvName = uniqid().'.'.$cvFile->guessExtension();
                $cvFile->move($basePath . '/cv', $cvName);
                $demande->setCv($cvName);
            } 
            // Sinon si CV IA (On vérifie explicitement que c'est une chaîne)
            elseif (is_string($cvIaFilename)) {
                $demande->setCv($cvIaFilename);
            }

                $lettreFile = $form->get('lettreMotivation')->getData();
                if ($lettreFile) {
                    $lettreName = uniqid().'.'.$lettreFile->guessExtension();
                    $lettreFile->move($basePath . '/lettres', $lettreName);
                    $demande->setLettreMotivation($lettreName);
                }

                $demande->setOffre($offre);
                $demande->setUser($currentUser); 
                $demande->setDatePostulation(new \DateTime());
                $demande->setDateModification(new \DateTime());
                $demande->setStatut('En cours');
                
                $entityManager->persist($demande);
                $entityManager->flush();

                $this->addFlash('success', 'Candidature envoyée !');
                return $this->redirectToRoute('app_offre_index_front');
            }
        }

        return $this->render('front/demande/form.html.twig', [
            'form' => $form->createView(),
            'offre' => $offre
        ]);
    }

    #[Route('/admin/demande/{id}/update-status', name: 'app_admin_demande_update_status', methods: ['POST'])]
    public function updateStatus(Demande $demande, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response 
    {
        $newStatus = $request->request->get('statut');

        if (is_string($newStatus)) {
            $demande->setStatut($newStatus);
            $demande->setDateModification(new \DateTime());
            $em->flush();

            if ($newStatus === 'Acceptée' || $newStatus === 'Refusée') {
                // Configuration du design selon le statut
                $color = ($newStatus === 'Acceptée') ? '#2e5e41' : '#a33b3b'; 
                $icon = ($newStatus === 'Acceptée') ? '✅' : '❌';
                $nomCandidat = $demande->getPrenom() . ' ' . $demande->getNom();
                $offreLiee = $demande->getOffre();
                $offreTitre = ($offreLiee instanceof Offre) ? $offreLiee->getTitle() : 'Offre Mécanique';

                // Le HTML exact de ton image
                $htmlContent = "
                <div style='background-color: #f9f9f9; padding: 40px 0; font-family: sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 30px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.05);'>
                        <div style='background-color: #2e5e41; padding: 45px 20px; text-align: center;'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 32px; font-weight: bold;'>Mise à jour AgriSmart</h1>
                        </div>
                        <div style='padding: 50px 40px;'>
                            <h2 style='color: #1a3324; font-size: 24px;'>Bonjour,</h2>
                            <p style='color: #5d6d63; font-size: 16px; line-height: 1.6;'>Une mise à jour vient d'être effectuée sur la plateforme concernant la candidature suivante :</p>
                            <div style='background-color: #f8fdfa; border-left: 6px solid #1a3324; padding: 30px; margin: 30px 0; border-radius: 0 25px 25px 0;'>
                                <p style='margin: 0 0 10px 0;'><strong>Candidat :</strong> $nomCandidat</p>
                                <p style='margin: 0 0 10px 0;'><strong>Poste :</strong> $offreTitre</p>
                                <p style='margin: 0;'><strong>Lieu :</strong> Tunisie</p>
                            </div>
                            <div style='text-align: center; margin-top: 40px;'>
                                <p style='text-transform: uppercase; font-size: 13px; color: #9ea8a2; font-weight: bold; letter-spacing: 2px;'>NOUVEAU STATUT</p>
                                <div style='display: inline-block; background-color: $color; color: #ffffff; padding: 18px 60px; border-radius: 100px; font-size: 24px; font-weight: bold;'>
                                    $icon $newStatus
                                </div>
                                <p style='margin-top: 20px; color: #8e9e95; font-size: 13px;'>La candidature a été " . strtolower($newStatus) . ".</p>
                            </div>
                        </div>
                        <div style='background-color: #f1f1f1; padding: 20px; text-align: center; font-size: 11px; color: #a1ada6;'>
                            © 2026 AgriSmart - Système de Recrutement Automatisé
                        </div>
                    </div>
                </div>";

                $email = (new Email())
                    ->from('akrem.zaied@etudiant-fsegt.utm.tn')
                    ->to('akrem.zaied@etudiant-fsegt.utm.tn') 
                    ->subject('Mise à jour candidature AgriSmart : ' . $newStatus)
                    ->html($htmlContent);

                try {
                    $mailer->send($email);
                    $this->addFlash('success', 'Statut mis à jour et email envoyé.');
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Statut mis à jour, mais erreur d\'envoi email.');
                }
            }
        }
        return $this->redirectToRoute('app_admin_demande_details', ['id' => $demande->getId()]);
    }

    // CETTE ROUTE DOIT EXISTER POUR ÉVITER L'ERREUR TWIG
    #[Route('/admin/demande/{id}/details', name: 'app_admin_demande_details')]
    public function demandeDetails(Demande $demande): Response
    {
        return $this->render('back/demande/details.html.twig', [
            'demande' => $demande,
        ]);
    }

    #[Route('/demande/delete/{id}', name: 'app_demande_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $demande = $entityManager->getRepository(Demande::class)->find($id);
        $token = $request->request->get('_token');
        if ($demande instanceof Demande && is_string($token) && $this->isCsrfTokenValid('delete' . $demande->getId(), $token)) {            $entityManager->remove($demande);
            $entityManager->flush();
            $this->addFlash('success', 'Candidature supprimée.');
        }
        return $this->redirectToRoute('app_my_demandes');
    }

    #[Route('/demande/edit/{id}', name: 'app_demande_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $demande = $entityManager->getRepository(Demande::class)->find($id);
        if (!$demande) throw $this->createNotFoundException("Candidature introuvable.");

        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demande->setDateModification(new \DateTime());
            $entityManager->flush();
            $this->addFlash('success', 'Candidature mise à jour !');
            return $this->redirectToRoute('app_my_demandes');
        }

        return $this->render('front/demande/form.html.twig', [
            'form' => $form->createView(),
            'offre' => $demande->getOffre(),
            'demande' => $demande 
        ]);
    }

    #[Route('/mes-candidatures/pdf', name: 'app_my_demandes_pdf')]
    public function exportMyListPdf(DemandeRepository $repo): Response
    {
        $user = $this->getUser();
        $demandes = $repo->findBy(['user' => $user]);

        $html = $this->renderView('back/demande/pdf_list.html.twig', [
            'demandes' => $demandes
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="mes_candidatures.pdf"'
        ]);
    }

  #[Route('/candidature/generate-cv-ia', name: 'app_cv_ia_generate', methods: ['POST'])]
public function generateCvIa(Request $request): JsonResponse
{
    // On récupère les données textuelles envoyées via FormData
    $params = $request->request->all();
    
    // --- TRAITEMENT DE LA PHOTO ---
    $photoBase64 = null;
    $photoFile = $request->files->get('photo'); // Récupère le fichier 'photo' du FormData

    if ($photoFile) {
        try {
            $type = $photoFile->getMimeType();
        if ($photoFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $type = $photoFile->getMimeType();
            $binary = file_get_contents($photoFile->getPathname());
    if (is_string($binary)) {
        $photoBase64 = 'data:' . $type . ';base64,' . base64_encode($binary);
    }
}
        } catch (\Exception $e) {
            // En cas d'erreur sur l'image, on laisse $photoBase64 à null
        }
    }

    // 1. Structure des données alignée sur ton template Twig
    $resumeJson = [
        "basics" => [
            "name"       => ($params['prenom'] ?? '') . " " . ($params['nom'] ?? ''),
            "label"      => $params['jobTitle'] ?? '',
            "email"      => $params['email'] ?? '',
            "phone"      => $params['phone'] ?? '',
            "photo"      => $photoBase64, // L'image en Base64 pour Dompdf
            "sexe"       => $params['sexe'] ?? '',
            "birthdate"  => $params['birthdate'] ?? '',
            "ville"      => $params['ville'] ?? '',
            "permis"     => $params['permis'] ?? '',
            "summary"    => $params['expDesc'] ?? '', 
            "speciality" => $params['specialite'] ?? '',
            "study"      => $params['etude'] ?? '',
            "study_year" => $params['etudeAnnee'] ?? '',
            "french"     => $params['french'] ?? '', 
            "english"    => $params['english'] ?? '',
            "it_skills"  => $params['informatique'] ?? '',
            "experience" => [
                "entreprise" => $params['entreprise'] ?? '',
                "debut"      => $params['expDebut'] ?? '',
                "fin"        => $params['expFin'] ?? ''
            ]
        ],
        "skills" => array_map(function($skill) {
            return ["name" => trim($skill)];
        }, explode(',', $params['skills'] ?? '')),
        
        "interests" => array_map(function($i) {
            return ["name" => trim($i)];
        }, explode(',', $params['loisirs'] ?? ''))
    ];

    // 2. Rendu du template Twig (le HTML du CV)
    $html = $this->renderView('front/demande/cv_template_json.html.twig', [
        'resume' => $resumeJson
    ]);

    // 3. Configuration de Dompdf
    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isRemoteEnabled', true);      // Autorise les ressources externes
    $options->set('isHtml5ParserEnabled', true); // Meilleur support du CSS moderne
    
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 4. Préparation du dossier et sauvegarde du fichier
    $fileName = 'CV_IA_AgriSmart_' . uniqid() . '.pdf';
    $projectDir = $this->getParameter('kernel.project_dir');
    $directory = (is_string($projectDir) ? $projectDir : '') . '/public/uploads/cv';    
        if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    file_put_contents($directory . '/' . $fileName, $dompdf->output());

    // On retourne le nom du fichier pour que le JS puisse l'utiliser
    return new JsonResponse([
        'success' => true, 
        'fileName' => $fileName
    ]);
}

   #[Route('/demande/analyze-voice', name: 'app_demande_analyze_voice', methods: ['POST'])]
public function analyzeVoice(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $text = $data['text'] ?? '';

    if (empty($text)) {
        return new JsonResponse(['success' => false, 'message' => 'Texte vide']);
    }

    $extractedData = [
        'nom' => '',
        'prenom' => '',
        'phone' => ''
    ];

    // 1. Extraction intelligente du numéro de téléphone (Tunisie : 8 chiffres)
    // Cherche une suite de 8 chiffres, même s'il y a des espaces entre eux
    if (preg_match('/(\d[\s]*){8}/', $text, $matches)) {
        $extractedData['phone'] = str_replace(' ', '', $matches[0]);
    }

    // 2. Nettoyage pour trouver le Nom et Prénom
    // On retire les mots inutiles pour isoler les noms propres
    $ignoreWords = ['bonjour', 'je', 'suis', 'appelle', 'présente', 'monsieur', 'mon', 'numéro', 'est', 'téléphone'];
    $cleanText = str_ireplace($ignoreWords, '', $text);
    
    // On enlève les chiffres du texte pour ne pas les confondre avec un nom
    $cleanText = preg_replace('/[0-9]+/', '', $cleanText);
    $words = array_values(array_filter(explode(' ', trim($cleanText)), function($w) {
        return strlen($w) > 2; // On garde les mots de plus de 2 lettres
    }));

    // On remplit le Prénom et le Nom si on a trouvé au moins deux mots
    if (count($words) >= 2) {
        $extractedData['prenom'] = ucfirst(mb_strtolower($words[0]));
        $extractedData['nom'] = ucfirst(mb_strtolower($words[1]));
    } elseif (count($words) === 1) {
        $extractedData['prenom'] = ucfirst(mb_strtolower($words[0]));
    }

    // On considère l'extraction réussie si on a trouvé au moins le nom OU le téléphone
    $success = !empty($extractedData['prenom']) || !empty($extractedData['phone']);

    return new JsonResponse([
        'success' => $success,
        'data' => $extractedData
    ]);
}


}