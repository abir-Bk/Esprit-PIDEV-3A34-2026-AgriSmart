<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TwoFactorCodeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ResendMailer $mailer,
    ) {}

    public function generate(User $user): void
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = (new \DateTime())->modify('+10 minutes');

        $user->setTwoFactorCode($code);
        $user->setTwoFactorExpiresAt($expiresAt);

        $this->em->flush();
    }

    public function sendByEmail(User $user): void
    {
        $code = $user->getTwoFactorCode();
        $email = $user->getEmail();
        if ($email === '') {
            return;
        }

        $html = sprintf(
            '<h2>Suspicious Login Detected</h2>
            <p>We noticed a login from an unrecognized device or location.</p>
            <p>Your verification code is:</p>
            <h1 style="letter-spacing:8px">%s</h1>
            <p>This code expires in <strong>10 minutes</strong>.</p>
            <p>If this was not you, change your password immediately.</p>',
            $code
        );

        $this->mailer->sendEmail(
            $email,
            'Suspicious Login — Verify Your Identity',
            $html
        );
    }

    public function verify(User $user, string $submittedCode): bool
    {
        $storedCode = $user->getTwoFactorCode();
        $expiresAt = $user->getTwoFactorExpiresAt();

        if (!$storedCode || !$expiresAt) {
            return false;
        }

        if (new \DateTime() > $expiresAt) {
            return false; 
        }

        if (!hash_equals($storedCode, $submittedCode)) {
            return false; 
        }

        // Clear the code after successful verification
        $user->setTwoFactorCode(null);
        $user->setTwoFactorExpiresAt(null);
        $this->em->flush();

        return true;
    }
}