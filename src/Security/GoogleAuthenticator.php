<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId(); // Google IDs can be strings

                $userRepo = $this->entityManager->getRepository(User::class);

                // 1. Existing user by googleId
                if ($user = $userRepo->findOneBy(['googleId' => $googleId])) {
                    if ($user->getStatus() === 'pending') {
                        $user->setStatus('active');
                        $this->entityManager->flush();
                    }
                    return $user;
                }

                // 2. Existing user by email → link account and allow login
                if ($user = $userRepo->findOneBy(['email' => $email])) {
                    $user->setGoogleId($googleId);
                    if ($user->getStatus() === 'pending') {
                        $user->setStatus('active');
                    }
                    $this->entityManager->flush();
                    return $user;
                }

                // 3. Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setGoogleId($googleId);
                $user->setRole('agriculteur'); // single role string
                $user->setPassword(''); // no password for social login
                $user->setStatus('active'); // so they can log in with Google immediately (AccountStatusChecker allows only active)

                // Optionally set firstName / lastName from Google profile
                $user->setFirstName($googleUser->getFirstName() ?? '');
                $user->setLastName($googleUser->getLastName() ?? '');

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user && \in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->router->generate('user_dashboard'));
        }
        return new RedirectResponse($this->router->generate('app_produit_index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Optional: add flash message here
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('connect_google_start'));
    }
}
