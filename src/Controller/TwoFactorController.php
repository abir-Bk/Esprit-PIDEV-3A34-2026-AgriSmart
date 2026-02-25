<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FaceRecognitionService;
use App\Service\TwoFactorCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TwoFactorController extends AbstractController
{
    public function __construct(
        private TwoFactorCodeService   $twoFactorCodeService,
        private FaceRecognitionService $faceService,
        private EntityManagerInterface $em,
    ) {}

    #[Route('/2fa/check', name: 'app_2fa_check')]
    public function check(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->get('2fa_pending')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getSessionUser($session);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $submittedCode = trim($request->request->get('code', ''));

            if ($this->twoFactorCodeService->verify($user, $submittedCode)) {
                $this->clearSession($session);
                $this->addFlash('success', 'Identity verified. Welcome back!');
                return $this->redirectToRoute('app_home');
            }

            $error = 'Invalid or expired code. Please try again.';
        }

        return $this->render('2fa/check.html.twig', [
            'error'     => $error,
            'email'     => $user->getEmail(),
            'has_photo' => (bool) $user->getImage(), 
        ]);
    }

    #[Route('/2fa/resend', name: 'app_2fa_resend', methods: ['POST'])]
    public function resend(Request $request): Response
    {
        $session = $request->getSession();

        if (!$session->get('2fa_pending')) {
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getSessionUser($session);
        if ($user) {
            $this->twoFactorCodeService->generate($user);
            $this->twoFactorCodeService->sendByEmail($user);
            $this->addFlash('info', 'A new code has been sent to your email.');
        }

        return $this->redirectToRoute('app_2fa_check');
    }

    #[Route('/2fa/face/verify', name: 'app_2fa_face_verify', methods: ['POST'])]
    public function verifyFace(Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (!$session->get('2fa_pending')) {
            return $this->json(['success' => false, 'message' => 'No pending 2FA'], 403);
        }

        $user = $this->getSessionUser($session);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Double-check — should never happen since template hides button, but safety first
        if (!$user->getImage()) {
            return $this->json([
                'success' => false,
                'message' => 'No profile photo on file. Use the email code instead.',
            ], 400);
        }

        $data  = json_decode($request->getContent(), true);
        $photo = $data['photo'] ?? null;

        if (!$photo) {
            return $this->json(['success' => false, 'message' => 'No photo received'], 400);
        }

        $result = $this->faceService->verifyFace($user, $photo);

        if ($result['match']) {
            $this->clearSession($session);
            return $this->json([
                'success'  => true,
                'score'    => $result['score'],
                'redirect' => $this->generateUrl('app_produit_index'),
            ]);
        }

        return $this->json([
            'success' => false,
            'score'   => $result['score'],
            'message' => $result['message'],
        ]);
    }

    private function getSessionUser($session): ?User
    {
        $userId = $session->get('2fa_user_id');
        if (!$userId) return null;
        return $this->em->getRepository(User::class)->find($userId);
    }

    private function clearSession($session): void
    {
        $session->remove('2fa_pending');
        $session->remove('2fa_user_id');
    }
    #[Route('/2fa/debug-path', name: 'app_2fa_debug')]
public function debugPath(Request $request): JsonResponse
{
    $session = $request->getSession();
    $user    = $this->getSessionUser($session);

    if (!$user) {
        return $this->json(['error' => 'no session user']);
    }

    $uploadDir       = $this->getParameter('kernel.project_dir') . '/public/uploads';
    $storedImagePath = $uploadDir . '/users/images/' . $user->getImage();

    return $this->json([
        'image_filename'  => $user->getImage(),
        'full_path'       => $storedImagePath,
        'file_exists'     => file_exists($storedImagePath),
        'upload_dir'      => $uploadDir,
    ]);
}
}