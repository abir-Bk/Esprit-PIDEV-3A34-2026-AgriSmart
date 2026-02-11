<?php

namespace App\MessageHandler;

use App\Entity\Commande;
use App\Message\OrderPaidMessage;
use App\Service\InvoicePdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class OrderPaidMessageHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private InvoicePdfGenerator $pdfGenerator
    ) {
    }

    public function __invoke(OrderPaidMessage $message): void
    {
        /** @var Commande|null $commande */
        $commande = $this->em->getRepository(Commande::class)
            ->find($message->commandeId);

        if (!$commande) {
            return;
        }

        // Prevent double email
        if ($commande->getEmailSentAt() !== null) {
            return;
        }

        $user = $commande->getClient();
        if (!$user) {
            return;
        }

        $toEmail = method_exists($user, 'getEmail')
            ? $user->getEmail()
            : $user->getUserIdentifier();

        $fromEmail = $_ENV['MAIL_FROM'] ?? 'ayafdhila@gmail.com';
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? null;

        // Generate PDF
        $pdfContent = $this->pdfGenerator->generate($commande);

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, 'AgriSmart'))
            ->to((string) $toEmail)
            ->subject('Paiement confirmé - Commande #' . $commande->getId())
            ->htmlTemplate('front/semi-public/emails/commande_payee.html.twig')
            ->context([
                'commande' => $commande,
                'user' => $user,
            ])
            ->attach(
                $pdfContent,
                'facture_commande_' . $commande->getId() . '.pdf',
                'application/pdf'
            );

        if ($adminEmail) {
            $email->addCc($adminEmail);
        }

        $this->mailer->send($email);

        $commande->setEmailSentAt(new \DateTimeImmutable());
        $this->em->flush();
    }
}
