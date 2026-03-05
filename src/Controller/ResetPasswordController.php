<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use App\Service\ResendMailer;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {}

#[Route('', name: 'app_forgot_password_request')]
public function request(Request $request, ResendMailer $resendMailer, TranslatorInterface $translator): Response
{
    $form = $this->createForm(ResetPasswordRequestFormType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $email = $form->get('email')->getData();
        return $this->processSendingPasswordResetEmail($email, $resendMailer);
    }

    return $this->render('/front/public/reset_password/request.html.twig', [
        'requestForm' => $form,
    ]);
}

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        $resetToken = $this->getTokenObjectFromSession() ?? $this->resetPasswordHelper->generateFakeResetToken();

        return $this->render('/front/public/reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, ?string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (!$token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur invalide pour la réinitialisation.');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
            $this->entityManager->flush();

            $this->cleanSessionAfterReset();

            $this->addFlash('success', 'Votre mot de passe a été changé avec succès.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('/front/public/reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

  private function processSendingPasswordResetEmail(string $email, ResendMailer $resendMailer): RedirectResponse
{
    $user = $this->entityManager
        ->getRepository(User::class)
        ->findOneBy(['email' => $email]);

    if (!$user) {
        return $this->redirectToRoute('app_check_email');
    }

    try {
        // Generate token (IMPORTANT: no second parameter)
        $resetToken = $this->resetPasswordHelper->generateResetToken($user);
    } catch (ResetPasswordExceptionInterface $e) {
        return $this->redirectToRoute('app_check_email');
    }

    // Generate absolute URL for email
    $resetUrl = $this->generateUrl(
        'app_reset_password',
        ['token' => $resetToken->getToken()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    // Calculate expiration in minutes
    $expiresIn = $resetToken->getExpiresAt()->getTimestamp() - time();
    $minutesLeft = max(0, ceil($expiresIn / 60));

    // Render email template
    $html = $this->renderView('/front/public/reset_password/email.html.twig', [
        'resetUrl' => $resetUrl,
        'minutesLeft' => $minutesLeft,
    ]);

    try {
        $emailTo = $user->getEmail();
        if ($emailTo === null) {
            return $this->redirectToRoute('app_check_email');
        }

        $resendMailer->sendEmail(
            $emailTo,
            'Réinitialisation de votre mot de passe',
            $html
        );
    } catch (\Throwable $e) {
        $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    }

    $this->setTokenObjectInSession($resetToken);

    return $this->redirectToRoute('app_check_email');
}
}