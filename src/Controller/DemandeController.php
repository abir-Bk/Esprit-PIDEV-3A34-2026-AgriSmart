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
    public function myDemandes(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser(); 

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour voir vos demandes.');
            return $this->redirectToRoute('app_login');
        }

        $demandes = $entityManager->getRepository(Demande::class)->findBy([
            'user' => $user 
        ]);

        return $this->render('front/demande/my_demandes.html.twig', [
            'demandes' => $demandes,
        ]);
    }

    #[Route('/offre/{id}/postuler', name: 'app_demande_postuler')]
    public function postuler(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $offre = $entityManager->getRepository(Offre::class)->find($id);
        if (!$offre) throw $this->createNotFoundException('Offre non trouvée');

        if ($offre->getDateFin() < new \DateTime() || strtolower($offre->getStatut()) === 'clôturée') {
            $this->addFlash('danger', 'Cette offre n\'accepte plus de candidatures.');
            return $this->redirectToRoute('app_offre_index_front');
        }

        $currentUser = $this->getUser();

        if (!$currentUser) {
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
                $basePath = $this->getParameter('kernel.project_dir') . '/public/uploads';

                // Si upload classique
                if ($cvFile) {
                    $cvName = uniqid().'.'.$cvFile->guessExtension();
                    $cvFile->move($basePath . '/cv', $cvName);
                    $demande->setCv($cvName);
                } 
                // Sinon si CV IA
                elseif ($cvIaFilename) {
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
        
        if ($newStatus) {
            $demande->setStatut($newStatus);
            $demande->setDateModification(new \DateTime());
            $em->flush();

            if ($newStatus === 'Acceptée' || $newStatus === 'Refusée') {
                // Configuration du design selon le statut
                $color = ($newStatus === 'Acceptée') ? '#2e5e41' : '#a33b3b'; 
                $icon = ($newStatus === 'Acceptée') ? '✅' : '❌';
                $nomCandidat = $demande->getPrenom() . ' ' . $demande->getNom();
                $offreTitre = $demande->getOffre() ? $demande->getOffre()->getTitle() : 'Offre Mécanique';

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
        if ($demande && $this->isCsrfTokenValid('delete' . $demande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($demande);
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
    $data = json_decode($request->getContent(), true);
    if (!$data) return new JsonResponse(['error' => 'Données invalides'], 400);

    // 1. Structure des données mise à jour avec les nouveaux champs
    $resumeJson = [
        "basics" => [
            "name" => ($data['prenom'] ?? '') . " " . ($data['nom'] ?? ''),
            "label" => $data['jobTitle'] ?? '',
            "email" => $data['email'] ?? '',
            "phone" => $data['phone'] ?? '',
            "photo" => $data['photo'] ?? null,
            "sexe" => $data['sexe'] ?? '', // AJOUTÉ
            "birthdate" => $data['birthdate'] ?? '', // AJOUTÉ
            "ville" => $data['ville'] ?? '', // AJOUTÉ
            "permis" => $data['permis'] ?? '', // AJOUTÉ
            "summary" => $data['expDesc'] ?? '', // Utilise la description de l'expérience
            "speciality" => $data['specialite'] ?? '',
            "study" => $data['etude'] ?? '',
            "study_year" => $data['etudeAnnee'] ?? '', // AJOUTÉ
            "french" => $data['french'] ?? '', 
            "english" => $data['english'] ?? '', // AJOUTÉ
            "it_skills" => $data['informatique'] ?? '', // AJOUTÉ
            "experience" => [
                "entreprise" => $data['entreprise'] ?? '',
                "debut" => $data['expDebut'] ?? '',
                "fin" => $data['expFin'] ?? ''
            ]
        ],
        "skills" => array_map(function($skill) {
            return ["name" => trim($skill)];
        }, explode(',', $data['skills'] ?? '')),
        "interests" => array_map(function($i) {
            return ["name" => trim($i)];
        }, explode(',', $data['loisirs'] ?? ''))
    ];

    // 2. Rendu du template Twig
    $html = $this->renderView('front/demande/cv_template_json.html.twig', [
        'resume' => $resumeJson
    ]);

    // 3. Configuration Dompdf
    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 4. Enregistrement
    $fileName = 'CV_IA_AgriSmart_' . uniqid() . '.pdf';
    $directory = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
    
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    file_put_contents($directory . '/' . $fileName, $dompdf->output());

    return new JsonResponse(['success' => true, 'fileName' => $fileName]);
}
}