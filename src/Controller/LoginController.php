<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last email entered by the user
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('front/public/login/index.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
             'recaptcha_site_key' => $_ENV['RECAPTCHA_SITE_KEY'],

        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony will intercept this route
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}