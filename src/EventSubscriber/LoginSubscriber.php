<?php

namespace App\EventSubscriber;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Service\TwoFactorCodeService;
use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private TwoFactorCodeService $twoFactorCodeService,
    ) {}

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $ip      = $request->getClientIp() ?? 'unknown';
        $userAgent = $request->headers->get('User-Agent') ?? 'unknown';
        $country = $this->resolveCountry($ip);

        // ✅ Check BEFORE saving so the new record doesn't interfere
        $suspicious = $this->isSuspicious($user, $country, $userAgent);

        // Save login history
        $loginHistory = new LoginHistory();
        $loginHistory
            ->setUser($user)
            ->setLoginTime(new \DateTime())
            ->setIpAddress($ip)
            ->setUserAgent($userAgent)
            ->setCountry($country)
            ->setStatus('success')
            ->setSuspicious($suspicious);

        $this->em->persist($loginHistory);
        $this->em->flush();

        if ($suspicious) {
            $this->twoFactorCodeService->generate($user);
            $this->twoFactorCodeService->sendByEmail($user);

            $request->getSession()->set('2fa_user_id', $user->getId());
            $request->getSession()->set('2fa_pending', true);

            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate('app_2fa_check')
            ));
        }
    }

    public function onLoginFailureEvent(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $loginHistory = new LoginHistory();
        $loginHistory
            ->setUser(null)
            ->setLoginTime(new \DateTime())
            ->setIpAddress($request->getClientIp() ?? 'unknown')
            ->setUserAgent($request->headers->get('User-Agent') ?? 'unknown')
            ->setStatus('failed')
            ->setAttemptedEmail($request->request->get('_username') ?? 'unknown');

        $this->em->persist($loginHistory);
        $this->em->flush();
    }

    private function resolveCountry(string $ip): string
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return 'Localhost';
        }

        try {
            $reader = new Reader(__DIR__ . '/../../config/geoip/GeoLite2-Country.mmdb');
            return $reader->country($ip)->country->name ?? 'Unknown';
        } catch (\Exception) {
            return 'Unknown';
        }
    }

    private function isSuspicious(User $user, string $country, string $userAgent): bool
    {
        // ✅ Only fetch the last NON-suspicious successful login as the trusted baseline
        $lastLogin = $this->em->getRepository(LoginHistory::class)->findOneBy(
            [
                'user'       => $user,
                'status'     => 'success',
                'suspicious' => false,  // ← only use verified safe logins as baseline
            ],
            ['loginTime' => 'DESC']
        );

        // First ever clean login — not suspicious
        if (!$lastLogin) {
            return false;
        }

        // ✅ Prevent false trigger if somehow called twice within same second
        $secondsSinceLast = (new \DateTime())->getTimestamp()
                          - $lastLogin->getLoginTime()->getTimestamp();

        if ($secondsSinceLast < 5) {
            return false;
        }

        $countryChanged = $lastLogin->getCountry() !== $country;
        $browserChanged = $this->extractBrowser($lastLogin->getUserAgent())
                       !== $this->extractBrowser($userAgent);

        return $countryChanged || $browserChanged;
    }

    private function extractBrowser(string $userAgent): string
    {
        // Order matters — Edge contains "Chrome" so check "Edg" first
        $browsers = ['Edg', 'Chrome', 'Firefox', 'Safari', 'Opera', 'OPR'];

        foreach ($browsers as $browser) {
            if (stripos($userAgent, $browser) !== false) {
                return match ($browser) {
                    'Edg' => 'Edge',
                    'OPR' => 'Opera',
                    default => $browser,
                };
            }
        }

        return 'Unknown';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
            LoginFailureEvent::class => 'onLoginFailureEvent',
        ];
    }
}