<?php

namespace App\Controller;

use App\Repository\RessourceRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminRessourceController extends AbstractController
{
    #[Route('/ressources', name: 'admin_ressources_index', methods: ['GET'])]
    public function index(Request $request, RessourceRepository $ressourceRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('q');
        $queryBuilder = $ressourceRepository->findAllWithConsumptionQueryBuilder(is_string($search) ? $search : null);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10 // Items per page
        );

        return $this->render('back/admin/ressource_index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }
}
