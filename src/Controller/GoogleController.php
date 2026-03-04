<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(
                ['email', 'profile'],
                []
                
            );
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck()
    {
        // This route will not be executed
        // Symfony will handle the OAuth callback automatically
        // See the authenticator below
        throw new \LogicException('This method should not be reached!');
    }
}