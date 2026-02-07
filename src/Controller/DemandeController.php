<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Form\DemandeType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;  
use App\Entity\Offre;
use App\Entity\Demande;

final class DemandeController extends AbstractController
{
    #[Route('/demande', name: 'app_demande')]
    public function index(): Response
    {
        return $this->render('demande/index.html.twig', [
            'controller_name' => 'DemandeController',
        ]);
    }

#[Route('/mes-demandes', name: 'app_my_demandes')]
public function myDemandes(EntityManagerInterface $entityManager): Response
{
    /**
     * WORK WITH USER MODULE INTEGRATION:
     * When you have security enabled, uncomment the line below:
     * $user = $this->getUser(); 
     * * For now, we simulate a logged-in User ID (e.g., ID 1)
     */
    $loggedInUserId = 1; 

    // Fetch all demandes where the associated user_id matches
    $demandes = $entityManager->getRepository(Demande::class)->findBy([
        'users' => $loggedInUserId 
        /** * AFTER INTEGRATION CHANGE TO: 
         * 'users' => $user 
         */
    ]);

    return $this->render('demande/my_demandes.html.twig', [
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
        return $this->redirectToRoute('app_offre_emploi');
    }
    /* USER MODULE INTEGRATION:
    $currentUser = $this->getUser();
    $alreadyApplied = $entityManager->getRepository(Demande::class)->findOneBy([
        'offre' => $offre,
        'users' => $currentUser
    ]);

    if ($alreadyApplied) {
        $this->addFlash('warning', 'Vous avez déjà postulé à cette offre.');
        return $this->redirectToRoute('app_my_demandes');
    }
    */
    $demande = new Demande();
    $form = $this->createForm(DemandeType::class, $demande);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $cvFile = $form->get('cv')->getData();
        if ($cvFile) {
            $cvName = uniqid().'.'.$cvFile->guessExtension();
            $cvFile->move($this->getParameter('uploads_directory').'/cv', $cvName);
            $demande->setCv($cvName); // Saves "65a123.pdf" to DB
        }

        $lettreFile = $form->get('lettreMotivation')->getData();
        if ($lettreFile) {
            $lettreName = uniqid().'.'.$lettreFile->guessExtension();
            $lettreFile->move($this->getParameter('uploads_directory').'/lettres', $lettreName);
            $demande->setLettreMotivation($lettreName);
        }

        $demande->setOffre($offre);
        $demande->setDatePostulation(new \DateTime());
        $demande->setDateModification(new \DateTime());
        $demande->setStatut('En cours');
        $entityManager->persist($demande);
        $entityManager->flush();

        $this->addFlash('success', 'Candidature envoyée !');
        return $this->redirectToRoute('app_offre_emploi');
    }

    return $this->render('demande/form.html.twig', [
        'form' => $form->createView(),
        'offre' => $offre
    ]);
}
#[Route('/admin/demande/{id}/update-status', name: 'app_admin_demande_update_status', methods: ['POST'])]
public function updateStatus(Demande $demande, Request $request, EntityManagerInterface $em): Response
{
    $newStatus = $request->request->get('statut');
    
    if ($newStatus) {
        $demande->setStatut($newStatus);
        $demande->setDateModification(new \DateTime());
        $em->flush();
        
        $this->addFlash('success', 'Le statut de la candidature a été mis à jour.');
    }

    return $this->redirectToRoute('app_admin_demande_details', ['id' => $demande->getId()]);
}
#[Route('/admin/demande/{id}/details', name: 'app_admin_demande_details')]
public function demandeDetails(Demande $demande): Response
{
    return $this->render('demande/back/details.html.twig', [
        'demande' => $demande,
    ]);
}
#[Route('/demande/delete/{id}', name: 'app_demande_delete', methods: ['POST'])]
public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
{
    $demande = $entityManager->getRepository(Demande::class)->find($id);

    if (!$demande) {
        throw $this->createNotFoundException("Candidature introuvable.");
    }

    // Check CSRF token for security
    if ($this->isCsrfTokenValid('delete' . $demande->getId(), $request->request->get('_token'))) {
        
        // OPTIONAL: Delete physical files from the folder
        $uploadDir = $this->getParameter('uploads_directory');
        if ($demande->getCv()) {
            @unlink($uploadDir . '/cv/' . $demande->getCv());
        }
        if ($demande->getLettreMotivation()) {
            @unlink($uploadDir . '/lettres/' . $demande->getLettreMotivation());
        }

        $entityManager->remove($demande);
        $entityManager->flush();
        
        $this->addFlash('success', 'Candidature supprimée avec succès.');
    }

    return $this->redirectToRoute('app_my_demandes');
}

#[Route('/demande/edit/{id}', name: 'app_demande_edit', methods: ['GET', 'POST'])]
public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
{
    $demande = $entityManager->getRepository(Demande::class)->find($id);

    if (!$demande) {
        throw $this->createNotFoundException("Candidature introuvable.");
    }

    $form = $this->createForm(DemandeType::class, $demande);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $uploadDir = $this->getParameter('uploads_directory');

        $cvFile = $form->get('cv')->getData();
        if ($cvFile) {
            if ($demande->getCv()) { @unlink($uploadDir . '/cv/' . $demande->getCv()); }
            $cvName = uniqid().'.'.$cvFile->guessExtension();
            $cvFile->move($uploadDir.'/cv', $cvName);
            $demande->setCv($cvName);
        }

        $lettreFile = $form->get('lettreMotivation')->getData();
        if ($lettreFile) {
            if ($demande->getLettreMotivation()) { @unlink($uploadDir . '/lettres/' . $demande->getLettreMotivation()); }
            $lettreName = uniqid().'.'.$lettreFile->guessExtension();
            $lettreFile->move($uploadDir.'/lettres', $lettreName);
            $demande->setLettreMotivation($lettreName);
        }

        $demande->setDateModification(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'Candidature mise à jour !');
        return $this->redirectToRoute('app_my_demandes');
    }

    return $this->render('demande/form.html.twig', [
        'form' => $form->createView(),
        'offre' => $demande->getOffre(),
        'demande' => $demande 
    ]);
}
}
