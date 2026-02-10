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
        $data = $panier->getDetails();

        return $this->render('front/panier/index.html.twig', [
            'items' => $data['items'],
            'totalQty' => $data['totalQty'],
            'total' => $data['total'],
        ]);
    }

    #[Route('/add/{id}', name: 'app_panier_add', requirements: ['id' => '\d+'], methods: ['POST', 'GET'])]
    public function add(int $id, Request $request, PanierService $panier): Response
    {
        $qty = (int) $request->request->get('qty', 1);
        $panier->add($id, $qty);

        $this->addFlash('success', 'Ajouté au panier ✅');

        // si tu viens d’une page produit, on revient
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_panier_index');
    }

    #[Route('/remove-one/{id}', name: 'app_panier_remove', requirements: ['id' => '\d+'], methods: ['POST', 'GET'])]
    public function removeOne(int $id, Request $request, PanierService $panier): Response
    {
        $panier->removeOne($id);

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_panier_index');
    }

    #[Route('/remove-all/{id}', name: 'app_panier_remove_all', requirements: ['id' => '\d+'], methods: ['POST', 'GET'])]
    public function removeAll(int $id, Request $request, PanierService $panier): Response
    {
        $panier->removeAll($id);

        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_panier_index');
    }

    #[Route('/clear', name: 'app_panier_clear', methods: ['POST'])]
    public function clear(Request $request, PanierService $panier): Response
    {
        // CSRF optionnel (recommandé)
        if (!$this->isCsrfTokenValid('panier_clear', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_panier_index');
        }

        $panier->clear();
        $this->addFlash('success', 'Panier vidé ✅');
        return $this->redirectToRoute('app_panier_index');
    }
}
