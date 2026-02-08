<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

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
            return $this->render('back/users/admin/admin_dashboard.html.twig');
        } elseif (in_array('ROLE_EMPLOYEE', $user->getRoles())) {
            return $this->render('users/employe/dashboard.html.twig');
        } elseif (in_array('ROLE_AGRICULTEUR', $user->getRoles())) {
            return $this->render('back/users/agriculteur/dashboard.html.twig');
        } elseif (in_array('ROLE_FOURNISSEUR', $user->getRoles())) {
            return $this->render('back/users/fournisseur/dashboard.html.twig');
        }

        // Default fallback
        return $this->redirectToRoute('app_login');
    }
}