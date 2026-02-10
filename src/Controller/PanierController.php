<?php

namespace App\Controller;

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

        return $this->render('front/panier/index.html.twig', [
            'items' => $details['items'],
            'total' => $details['total'],
            'count' => $details['count'],
        ]);
    }

    #[Route('/add/{id}', name: 'app_panier_add', methods: ['GET', 'POST'])]
    public function add(int $id, Request $request, PanierService $panier): Response
    {
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

    #[Route('/clear', name: 'app_panier_clear', methods: ['GET', 'POST'])]
    public function clear(PanierService $panier): Response
    {
        $panier->clear();

        $this->addFlash('info', 'Panier vidé.');
        return $this->redirectToRoute('app_panier_index');
    }
}
