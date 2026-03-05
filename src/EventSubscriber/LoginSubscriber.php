<?php

namespace App\EventSubscriber;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Service\TwoFactorCodeService;
use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack           $requestStack,
        private UrlGeneratorInterface  $urlGenerator,
        private TwoFactorCodeService   $twoFactorCodeService,
    ) {}

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request   = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return;
        }
        $ip        = $this->getClientIp($request);       // ← updated
        $userAgent = $this->getUserAgent($request);      // ← updated
        $country   = $this->resolveCountry($ip);

        $suspicious = $this->isSuspicious($user, $country, $userAgent);

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
        if (!$request instanceof Request) {
            return;
        }

        $loginHistory = new LoginHistory();
        $loginHistory
            ->setUser(null)
            ->setLoginTime(new \DateTime())
            ->setIpAddress($this->getClientIp($request))
            ->setUserAgent($this->getUserAgent($request))
            ->setStatus('failed')
            ->setAttemptedEmail((string) $request->request->get('_username', 'unknown'));

        $this->em->persist($loginHistory);
        $this->em->flush();
    }

    private function getClientIp(Request $request): string
    {
        if ($_ENV['APP_ENV'] === 'dev' && $request->query->get('test_ip')) {
            return (string) $request->query->get('test_ip');
        }

        return $request->getClientIp() ?? '127.0.0.1';
    }

    private function getUserAgent(Request $request): string
    {
        if ($_ENV['APP_ENV'] === 'dev' && $request->query->get('test_ua')) {
            return (string) $request->query->get('test_ua');
        }

        return $request->headers->get('User-Agent') ?? 'unknown';
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
        $lastLogin = $this->em->getRepository(LoginHistory::class)->findOneBy(
            [
                'user'       => $user,
                'status'     => 'success',
                'suspicious' => false,
            ],
            ['loginTime' => 'DESC']
        );

        if (!$lastLogin) {
            return false;
        }

        $loginTime = $lastLogin->getLoginTime();
        if (!$loginTime instanceof \DateTimeInterface) {
            return false;
        }

        $secondsSinceLast = (new \DateTime())->getTimestamp()
                          - $loginTime->getTimestamp();

        if ($secondsSinceLast < 5) {
            return false;
        }

        $countryChanged = $lastLogin->getCountry() !== $country;
        $browserChanged = $this->extractBrowser($lastLogin->getUserAgent() ?? '')
                       !== $this->extractBrowser($userAgent);

        return $countryChanged || $browserChanged;
    }

    private function extractBrowser(string $userAgent): string
    {
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