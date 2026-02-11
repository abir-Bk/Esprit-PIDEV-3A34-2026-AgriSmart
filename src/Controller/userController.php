<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;

class userController extends AbstractController
{
    #[Route('/users/dashboard', name: 'user_dashboard')]
    public function dashboard(Security $security)
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Render dashboard based on role
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->render('back/admin/admin_dashboard.html.twig');
        } elseif (in_array('ROLE_EMPLOYEE', $user->getRoles())) {
            return $this->render('users/employe/dashboard.html.twig');
        } elseif (in_array('ROLE_AGRICULTEUR', $user->getRoles())) {
            return $this->render('front/semi-public/users/agriculteur/dashboard.html.twig');
        } elseif (in_array('ROLE_FOURNISSEUR', $user->getRoles())) {
            return $this->render('back/users/fournisseur/dashboard.html.twig');
        }

        // Default fallback
        return $this->redirectToRoute('app_login');
    }
    #[Route('/admin/users', name: 'admin_users')]
    public function list(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('back/admin/user_list.html.twig', [
            'users' => $users,
        ]);
    }
    #[Route('/admin/users/{id}/status/{status}', name: 'admin_user_status')]
public function changeStatus(
    int $id,
    string $status,
    UserRepository $repo,
    EntityManagerInterface $em
): Response {

    $user = $repo->find($id);

    if (!$user) {
        throw $this->createNotFoundException('User not found');
    }

    $allowed = ['active', 'pending', 'disabled'];

    if (!in_array($status, $allowed)) {
        throw new \InvalidArgumentException('Invalid status');
    }

    $user->setStatus($status);
    $em->flush();

    return $this->redirectToRoute('admin_users');
}
}
