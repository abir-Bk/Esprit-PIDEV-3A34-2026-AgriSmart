<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwoFactorCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TwoFactorController extends AbstractController
{
    public function __construct(
        private TwoFactorCodeService $twoFactorCodeService,
        private EntityManagerInterface $em,
    ) {}

    #[Route('/2fa/check', name: 'app_2fa_check')]
    public function check(Request $request): Response
    {
        $session = $request->getSession();

        // Guard: only accessible during a pending 2FA challenge
        if (!$session->get('2fa_pending')) {
            return $this->redirectToRoute('app_login');
        }

        $userId = $session->get('2fa_user_id');
        $user = $this->em->getRepository(User::class)->find($userId);

        if (!$user) {
            $session->remove('2fa_pending');
            $session->remove('2fa_user_id');
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $submittedCode = trim($request->request->get('code', ''));

            if ($this->twoFactorCodeService->verify($user, $submittedCode)) {
                $session->remove('2fa_pending');
                $session->remove('2fa_user_id');

                $this->addFlash('success', 'Identity verified. Welcome back!');
                return $this->redirectToRoute('app_produit_index'); 
            }

            $error = 'Invalid or expired code. Please try again.';
        }

        return $this->render('2fa/check.html.twig', [
            'error' => $error,
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/2fa/resend', name: 'app_2fa_resend', methods: ['POST'])]
    public function resend(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->get('2fa_pending')) {
            return $this->redirectToRoute('app_login');
        }

        $userId = $session->get('2fa_user_id');
        $user = $this->em->getRepository(User::class)->find($userId);

        if ($user) {
            $this->twoFactorCodeService->generate($user);
            $this->twoFactorCodeService->sendByEmail($user);
            $this->addFlash('info', 'A new code has been sent to your email.');
        }

        return $this->redirectToRoute('app_2fa_check');
    }
}