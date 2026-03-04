<?php

namespace App\Controller;

use App\Entity\MarketplaceConversation;
use App\Entity\MarketplaceMessage;
use App\Entity\Produit;
use App\Entity\User;
use App\Form\MarketplaceMessageType;
use App\Repository\MarketplaceConversationRepository;
use App\Repository\MarketplaceMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/marketplace/messagerie')]
final class MarketplaceMessagerieController extends AbstractController
{
    #[Route('', name: 'app_marketplace_messagerie', methods: ['GET'])]
    public function index(
        MarketplaceConversationRepository $conversationRepository,
        MarketplaceMessageRepository $messageRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        $conversations = $conversationRepository->findForUser($user);
        $unreadByConversation = $messageRepository->getUnreadCountByConversationForUser($user);
        $unreadTotal = $messageRepository->countUnreadForUser($user);

        return $this->render('front/semi-public/messagerie/index.html.twig', [
            'conversations' => $conversations,
            'unreadByConversation' => $unreadByConversation,
            'unreadTotal' => $unreadTotal,
        ]);
    }

    #[Route('/start/{id}', name: 'app_marketplace_messagerie_start', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function start(
        Produit $produit,
        Request $request,
        MarketplaceConversationRepository $conversationRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        if (!$this->isCsrfTokenValid('start_conversation_' . $produit->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide.');

            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $seller = $produit->getVendeur();
        if (!$seller instanceof User) {
            $this->addFlash('danger', 'Vendeur introuvable pour cette annonce.');

            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        if ($seller->getId() === $user->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas démarrer une conversation avec vous-même.');

            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $conversation = $conversationRepository->findOneByParticipantsAndProduit($produit, $user, $seller);

        if (!$conversation) {
            $conversation = (new MarketplaceConversation())
                ->setProduit($produit)
                ->setBuyer($user)
                ->setSeller($seller);

            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_marketplace_messagerie_show', ['id' => $conversation->getId()]);
    }

    #[Route('/{id}', name: 'app_marketplace_messagerie_show', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function show(
        int $id,
        Request $request,
        MarketplaceConversationRepository $conversationRepository,
        MarketplaceMessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        $conversation = $conversationRepository->findUserConversationById($user, $id);
        if (!$conversation) {
            throw $this->createNotFoundException('Conversation introuvable.');
        }

        $readReceipt = $messageRepository->markConversationAsReadForUser($conversation, $user);
        $this->publishReadReceipt($hub, $conversation, $user, $readReceipt);

        $message = new MarketplaceMessage();
        $form = $this->createForm(MarketplaceMessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textContent = trim((string) ($message->getContent() ?? ''));
            $message->setContent($textContent !== '' ? $textContent : null);

            $audioFile = $form->has('audioFile') ? $form->get('audioFile')->getData() : null;
            // form field may be null; treat as empty string
            if ($form->has('audioBlob')) {
                $audioBlob = trim((string) $form->get('audioBlob')->getData());
            } else {
                $audioBlob = '';
            }

            $uploadDir = $this->getParameter('kernel.project_dir');
        if (!is_string($uploadDir)) {
            throw new \RuntimeException('kernel.project_dir must be a string');
        }
        $uploadDir = $uploadDir . '/public/uploads/marketplace/messages/audio';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            if ($audioFile instanceof UploadedFile) {
                $mimeType = strtolower((string) $audioFile->getMimeType());
                $allowedMimeTypes = [
                    'audio/webm',
                    'audio/ogg',
                    'audio/mpeg',
                    'audio/mp3',
                    'audio/mp4',
                    'audio/x-m4a',
                    'audio/wav',
                    'audio/x-wav',
                    'audio/aac',
                    'video/webm',
                    'video/mp4',
                    'application/octet-stream',
                ];

                if (!in_array($mimeType, $allowedMimeTypes, true)) {
                    return new JsonResponse([
                        'ok' => false,
                        'error' => 'Format du message vocal non supporté.',
                    ], 422);
                }

                $size = $audioFile->getSize();
            if ($size !== false && $size > 8 * 1024 * 1024) {
                    return new JsonResponse([
                        'ok' => false,
                        'error' => 'Message vocal trop volumineux (max 8MB).',
                    ], 422);
                }

                $extension = $audioFile->guessExtension() ?: 'webm';
                if ($extension === 'bin' || $extension === '') {
                    $map = [
                        'audio/webm' => 'webm',
                        'video/webm' => 'webm',
                        'audio/ogg' => 'ogg',
                        'audio/mpeg' => 'mp3',
                        'audio/mp3' => 'mp3',
                        'audio/mp4' => 'm4a',
                        'video/mp4' => 'm4a',
                        'audio/x-m4a' => 'm4a',
                        'audio/wav' => 'wav',
                        'audio/x-wav' => 'wav',
                        'audio/aac' => 'aac',
                        'application/octet-stream' => 'webm',
                    ];
                    $extension = $map[$mimeType] ?? 'webm';
                }
                $filename = 'voice-' . bin2hex(random_bytes(12)) . '.' . $extension;
                $audioFile->move($uploadDir, $filename);
                $message->setAudioPath('uploads/marketplace/messages/audio/' . $filename);
            } elseif ($audioBlob !== '') { // @phpstan-ignore-line
                if (!preg_match('/^data:(audio\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $audioBlob, $matches)) {
                    return new JsonResponse([
                        'ok' => false,
                        'error' => 'Format du message vocal invalide.',
                    ], 422);
                }

                $mimeType = strtolower((string) $matches[1]);
                $base64Data = (string) $matches[2];
                $binary = base64_decode($base64Data, true);

                if ($binary === false || strlen($binary) === 0) {
                    return new JsonResponse([
                        'ok' => false,
                        'error' => 'Message vocal vide ou corrompu.',
                    ], 422);
                }

                if (strlen($binary) > 8 * 1024 * 1024) {
                    return new JsonResponse([
                        'ok' => false,
                        'error' => 'Message vocal trop volumineux (max 8MB).',
                    ], 422);
                }

                $extensions = [
                    'audio/webm' => 'webm',
                    'audio/ogg' => 'ogg',
                    'audio/mpeg' => 'mp3',
                    'audio/mp3' => 'mp3',
                    'audio/mp4' => 'm4a',
                    'audio/x-m4a' => 'm4a',
                    'audio/wav' => 'wav',
                    'audio/x-wav' => 'wav',
                    'audio/aac' => 'aac',
                ];

                if (!isset($extensions[$mimeType])) {
                    return new JsonResponse([
                        'ok' => false,
                        'error' => 'Type audio non supporté.',
                    ], 422);
                }

                $filename = 'voice-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mimeType];
                $target = $uploadDir . '/' . $filename;
                file_put_contents($target, $binary);
                $message->setAudioPath('uploads/marketplace/messages/audio/' . $filename);
            }

            if ($message->getContent() === null && $message->getAudioPath() === null) {
                $error = 'Ajoutez un texte ou un message vocal.';
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'ok' => false,
                        'error' => $error,
                    ], 422);
                }

                $this->addFlash('warning', $error);
                return $this->redirectToRoute('app_marketplace_messagerie_show', ['id' => $conversation->getId()]);
            }

            $message
                ->setConversation($conversation)
                ->setSender($user)
                ->setIsRead(false)
                ->setReadAt(null);

            $conversation->setLastMessageAt(new \DateTimeImmutable());

            $entityManager->persist($message);
            $entityManager->flush();

            $senderName = trim((string) ($user->getFirstName() ?? '') . ' ' . (string) ($user->getLastName() ?? ''));
            if ($senderName === '') {
                $senderName = (string) ($user->getEmail() ?? 'Utilisateur');
            }

            $payload = [
                'id' => $message->getId(),
                'conversationId' => $conversation->getId(),
                'senderId' => $user->getId(),
                'senderName' => $senderName,
                'content' => $message->getContent(),
                'audioPath' => $message->getAudioPath(),
                'isRead' => $message->isRead(),
                'readAt' => $message->getReadAt()?->format('d/m/Y H:i'),
                'createdAt' => $message->getCreatedAt()?->format('d/m/Y H:i'),
                'live' => true,
            ];

            $topic = sprintf('/marketplace/messagerie/%d', $conversation->getId());
            try {
                $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($json === false) {
                    $json = '{}';
                }
                $hub->publish(new Update($topic, $json));
            } catch (\Throwable) {
                $payload['live'] = false;
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['ok' => true] + $payload);
            }

            return $this->redirectToRoute('app_marketplace_messagerie_show', ['id' => $conversation->getId()]);
        }

        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                /** @var \Symfony\Component\Form\FormError $error */
                $errors[] = $error->getMessage();
            }

            return new JsonResponse([
                'ok' => false,
                'error' => $errors !== []
                    ? implode(' ', array_unique($errors))
                    : 'Message invalide. Vérifiez le contenu et réessayez.',
            ], 422);
        }

        $unreadTotal = $messageRepository->countUnreadForUser($user);

        return $this->render('front/semi-public/messagerie/show.html.twig', [
            'conversation' => $conversation,
            'messageForm' => $form,
            'unreadTotal' => $unreadTotal,
        ]);
    }

    #[Route('/{id}/messages', name: 'app_marketplace_messagerie_messages', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function messages(
        int $id,
        Request $request,
        MarketplaceConversationRepository $conversationRepository,
        MarketplaceMessageRepository $messageRepository,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => false, 'error' => 'Connexion requise.'], 401);
        }

        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session->isStarted()) {
                $session->save();
            }
        }

        $conversation = $conversationRepository->findUserConversationById($user, $id);
        if (!$conversation) {
            return new JsonResponse(['ok' => false, 'error' => 'Conversation introuvable.'], 404);
        }

        $afterId = max(0, (int) $request->query->get('afterId', 0));
        $readAfterId = max(0, (int) $request->query->get('readAfterId', 0));
        $messages = $messageRepository->findAfterIdForConversation($conversation, $afterId);
        $readUpdates = $messageRepository->findReadUpdatesForSender($conversation, $user, $readAfterId);

        $payload = array_map(static function (MarketplaceMessage $message): array {
            $sender = $message->getSender();
            $senderName = trim((string) ($sender?->getFirstName() ?? '') . ' ' . (string) ($sender?->getLastName() ?? ''));
            if ($senderName === '') {
                $senderName = (string) ($sender?->getEmail() ?? 'Utilisateur');
            }

            return [
                'id' => $message->getId(),
                'senderId' => $sender?->getId(),
                'senderName' => $senderName,
                'content' => $message->getContent(),
                'audioPath' => $message->getAudioPath(),
                'isRead' => $message->isRead(),
                'readAt' => $message->getReadAt()?->format('d/m/Y H:i'),
                'createdAt' => $message->getCreatedAt()?->format('d/m/Y H:i'),
            ];
        }, $messages);

        $readPayload = array_map(static function (MarketplaceMessage $message): array {
            return [
                'id' => $message->getId(),
                'readAt' => $message->getReadAt()?->format('d/m/Y H:i'),
            ];
        }, $readUpdates);

        return new JsonResponse([
            'ok' => true,
            'messages' => $payload,
            'readUpdates' => $readPayload,
        ]);
    }

    #[Route('/{id}/ack-read', name: 'app_marketplace_messagerie_ack_read', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function acknowledgeRead(
        int $id,
        MarketplaceConversationRepository $conversationRepository,
        MarketplaceMessageRepository $messageRepository,
        HubInterface $hub,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['ok' => false, 'error' => 'Connexion requise.'], 401);
        }

        $conversation = $conversationRepository->findUserConversationById($user, $id);
        if (!$conversation) {
            return new JsonResponse(['ok' => false, 'error' => 'Conversation introuvable.'], 404);
        }

        /** @var array{messageIds:int[],readAt:\DateTimeImmutable|null} $readReceipt */
        $readReceipt = $messageRepository->markConversationAsReadForUser($conversation, $user);
        $this->publishReadReceipt($hub, $conversation, $user, $readReceipt);

        $readCount = isset($readReceipt['messageIds']) ? count($readReceipt['messageIds']) : 0;
        $readAt = $readReceipt['readAt'] instanceof \DateTimeImmutable
            ? $readReceipt['readAt']->format('d/m/Y H:i')
            : null;

        return new JsonResponse([
            'ok' => true,
            'readCount' => $readCount,
            'readAt' => $readAt,
        ]);
    }

    /**
     * @param array{messageIds:int[],readAt:\DateTimeImmutable|null} $readReceipt
     */
    private function publishReadReceipt(HubInterface $hub, MarketplaceConversation $conversation, User $reader, array $readReceipt): void
    {
        if (empty($readReceipt['messageIds']) || !$readReceipt['readAt'] instanceof \DateTimeImmutable) {
            return;
        }

        $topic = sprintf('/marketplace/messagerie/%d', $conversation->getId());
        $readPayload = [
            'type' => 'read',
            'conversationId' => $conversation->getId(),
            'readerId' => $reader->getId(),
            'messageIds' => $readReceipt['messageIds'],
            'readAt' => $readReceipt['readAt']->format('d/m/Y H:i'),
        ];

        try {
            $json = json_encode($readPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                $json = '{}';
            }
            $hub->publish(new Update($topic, $json));
        } catch (\Throwable) {
        }
    }
}
