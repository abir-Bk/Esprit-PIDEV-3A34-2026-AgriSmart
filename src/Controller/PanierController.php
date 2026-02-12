<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class PanierController extends AbstractController
{
    #[Route('', name: 'app_panier_index', methods: ['GET'])]
    public function index(PanierService $panier): Response
    {
        $details = $panier->getDetails();

        return $this->render('front/semi-public/panier/index.html.twig', [
            'items' => $details['items'],
            'total' => $details['total'],
            'count' => $details['count'],
        ]);
    }

    #[Route('/add/{id}', name: 'app_panier_add', methods: ['GET', 'POST'])]
    public function add(int $id, Request $request, PanierService $panier, ProduitRepository $produitRepo): Response
    {
        $produit = $produitRepo->find($id);
        if (!$produit) {
            $this->addFlash('danger', 'Produit introuvable.');
            return $this->redirectToRoute('app_produit_index');
        }

        $user = $this->getUser();
        if ($user && $produit->getVendeur() && $produit->getVendeur() === $user) {
            $this->addFlash('warning', 'Vous ne pouvez pas commander votre propre offre.');
            $referer = $request->headers->get('Referer');
            if ($referer) {
                return $this->redirect($referer);
            }
            return $this->redirectToRoute('app_produit_show', ['id' => $id]);
        }

        $qty = (int) $request->get('qty', 1);
        $panier->add($id, $qty);

        $this->addFlash('success', 'Produit ajouté au panier ✅');
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/qty/{id}', name: 'app_panier_qty', methods: ['POST'])]
    public function qty(int $id, Request $request, PanierService $panier): Response
    {
        $qty = (int) $request->request->get('qty', 1);
        $panier->setQty($id, $qty);

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/remove/{id}', name: 'app_panier_remove', methods: ['GET', 'POST'])]
    public function remove(int $id, PanierService $panier): Response
    {
        $panier->remove($id);

        $this->addFlash('info', 'Produit retiré du panier.');
        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/clear', name: 'app_panier_clear', methods: ['POST'])]
    public function clear(Request $request, PanierService $panier): Response
    {
        if (!$this->isCsrfTokenValid('panier_clear', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Lien expiré. Réessayez.');
            return $this->redirectToRoute('app_panier_index');
        }
        $panier->clear();

        $this->addFlash('info', 'Panier vidé.');
        return $this->redirectToRoute('app_panier_index');
    }
}
