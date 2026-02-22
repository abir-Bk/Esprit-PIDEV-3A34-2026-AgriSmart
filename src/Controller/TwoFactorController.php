<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa', name: 'app_2fa')]
    public function twoFactor(Request $request, UserRepository $userRepository): Response
    {
        $session = $request->getSession();
        $userId = $session->get('2fa_user_id');
        $code = $session->get('2fa_code');

        if (!$userId || !$code) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $input = $request->request->get('code');
            if ($input == $code) {
                $session->remove('2fa_code');
                $session->remove('2fa_user_id');
                return $this->redirectToRoute('user_dashboard');
            } else {
                $this->addFlash('error', 'Invalid 2FA code.');
            }
        }

        return $this->render('security/2fa.html.twig');
    }
}