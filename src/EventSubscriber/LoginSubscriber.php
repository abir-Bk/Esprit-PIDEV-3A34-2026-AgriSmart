<?php

namespace App\EventSubscriber;

use App\Entity\LoginHistory;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use GeoIp2\Database\Reader;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private MailerInterface $mailer
    ) {}

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }
        $request = $this->requestStack->getCurrentRequest();

        $ip = $request->getClientIp() ?? 'unknown';
        $userAgent = $request->headers->get('User-Agent') ?? 'unknown';

        // GeoIP lookup
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $country = 'Localhost';
        } else {
            $reader = new Reader(__DIR__ . '/../../config/geoip/GeoLite2-Country.mmdb');
            try {
                $record = $reader->country($ip);
                $country = $record->country->name;
            } catch (\Exception $e) {
                $country = 'unknown';
            }
        }

        // Check last successful login
        $lastLogin = $this->em->getRepository(LoginHistory::class)
            ->findOneBy(['user' => $user, 'status' => 'success'], ['loginTime' => 'DESC']);

        $suspicious = false;
        if ($lastLogin) {
            if ($lastLogin->getCountry() !== $country || $lastLogin->getUserAgent() !== $userAgent) {
                $suspicious = true;
            }
        }

        // Save login history
        $loginHistory = new LoginHistory();
        $loginHistory->setUser($user)
                     ->setLoginTime(new \DateTime())
                     ->setIpAddress($ip)
                     ->setUserAgent($userAgent)
                     ->setCountry($country)
                     ->setStatus('success')
                     ->setSuspicious($suspicious);

        $this->em->persist($loginHistory);
        $this->em->flush();

        // If suspicious → trigger 2FA
        if ($suspicious) {
            $code = random_int(100000, 999999);
            $session = $request->getSession();
            $session->set('2fa_user_id', $user->getId());
            $session->set('2fa_code', $code);

            // Send email with code
            $email = (new Email())
                ->from('noreply@yourdomain.com')
                ->to($user->getEmail())
                ->subject('Your 2FA code')
                ->html('<p>Your login seems suspicious. Use this code to complete login: <strong>' . $code . '</strong></p>');

            $this->mailer->send($email);

            // Redirect to 2FA page
            $response = new RedirectResponse($this->urlGenerator->generate('app_2fa'));
            $response->send();
            exit; // stop normal login
        }
    }

    public function onLoginFailureEvent(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $email = $request->request->get('_username') ?? 'unknown';

        $loginHistory = new LoginHistory();
        $loginHistory->setUser(null)
                     ->setLoginTime(new \DateTime())
                     ->setIpAddress($request->getClientIp() ?? 'unknown')
                     ->setUserAgent($request->headers->get('User-Agent') ?? 'unknown')
                     ->setStatus('failed')
                     ->setAttemptedEmail($email);

        $this->em->persist($loginHistory);
        $this->em->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
            LoginFailureEvent::class => 'onLoginFailureEvent',
        ];
    }
}