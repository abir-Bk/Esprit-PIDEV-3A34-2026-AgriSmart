<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\CommandeExcelExporter;
use App\Service\InvoicePdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CommandeController extends AbstractController
{
    #[Route('/mes-commandes', name: 'app_commande_mes_commandes', methods: ['GET'])]
    public function mesCommandes(CommandeRepository $repo): Response
    {
        $user = $this->getUser();

        $commandes = $repo->findBy(
            ['client' => $user],
            ['id' => 'DESC']
        );

        return $this->render('front/semi-public/commande/mes_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/mes-commandes/export-excel', name: 'app_commande_export_mes_commandes_excel', methods: ['GET'])]
    public function exportMesCommandesExcel(CommandeRepository $repo, CommandeExcelExporter $exporter): Response
    {
        $user = $this->getUser();
        $commandes = $repo->findBy(['client' => $user], ['id' => 'DESC']);
        return $exporter->exportMesCommandes($commandes);
    }

    #[Route('/mes-ventes', name: 'app_commande_mes_ventes', methods: ['GET'])]
    public function mesVentes(CommandeRepository $repo): Response
    {
        $user = $this->getUser();

        $commandes = $repo->findForVendeur($user);

        return $this->render('front/semi-public/commande/mes_ventes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/mes-ventes/export-excel', name: 'app_commande_export_mes_ventes_excel', methods: ['GET'])]
    public function exportMesVentesExcel(CommandeRepository $repo, CommandeExcelExporter $exporter): Response
    {
        $user = $this->getUser();
        $commandes = $repo->findForVendeur($user);
        return $exporter->exportMesVentes($commandes);
    }

    #[Route('/{id}/facture.pdf', name: 'app_commande_facture_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function facturePdf(Commande $commande, InvoicePdfGenerator $pdfGenerator): Response
    {
        $this->denyUnlessOwnerOrVendeur($commande);

        $content = $pdfGenerator->generate($commande);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="facture_commande_' . $commande->getId() . '.pdf"');
        return $response;
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Commande $commande): Response
    {
        $this->denyUnlessOwnerOrVendeur($commande);
        $user = $this->getUser();
        $isClient = $user && $commande->getClient() === $user;
        $retourRoute = $isClient ? 'app_commande_mes_commandes' : 'app_commande_mes_ventes';

        return $this->render('front/semi-public/commande/show.html.twig', [
            'commande' => $commande,
            'items' => $commande->getItems(),
            'retour_route' => $retourRoute,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_commande_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(
        Commande $commande,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyUnlessOwner($commande);

        if (!$this->isCsrfTokenValid('cancel_commande_' . $commande->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        // Autoriser annulation uniquement si EN_ATTENTE
        if ($commande->getStatut() !== Commande::STATUT_EN_ATTENTE) {
            $this->addFlash('warning', "Impossible d'annuler : statut actuel = " . $commande->getStatut());
            return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
        }

        $commande->setStatut(Commande::STATUT_ANNULEE);
        $em->flush();

        $this->addFlash('success', 'Commande annulée.');
        return $this->redirectToRoute('app_commande_mes_commandes');
    }

    private function denyUnlessOwnerOrVendeur(Commande $commande): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if ($commande->getClient() === $user) {
            return;
        }

        foreach ($commande->getItems() as $item) {
            $produit = $item->getProduit();
            if ($produit && $produit->getVendeur() === $user) {
                return;
            }
        }

        throw $this->createAccessDeniedException();
    }

    private function denyUnlessOwner(Commande $commande): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        $user = $this->getUser();
        if (!$user || $commande->getClient() !== $user) {
            throw $this->createAccessDeniedException();
        }
    }
}
