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

        $messageRepository->markConversationAsReadForUser($conversation, $user);

        $message = new MarketplaceMessage();
        $form = $this->createForm(MarketplaceMessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                'content' => (string) $message->getContent(),
                'createdAt' => $message->getCreatedAt()?->format('d/m/Y H:i'),
                'live' => true,
            ];

            $topic = sprintf('/marketplace/messagerie/%d', $conversation->getId());
            try {
                $hub->publish(new Update($topic, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
            } catch (\Throwable) {
                $payload['live'] = false;
            }

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['ok' => true] + $payload);
            }

            return $this->redirectToRoute('app_marketplace_messagerie_show', ['id' => $conversation->getId()]);
        }

        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'Message invalide. Vérifiez le contenu et réessayez.',
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

        $conversation = $conversationRepository->findUserConversationById($user, $id);
        if (!$conversation) {
            return new JsonResponse(['ok' => false, 'error' => 'Conversation introuvable.'], 404);
        }

        $afterId = max(0, (int) $request->query->get('afterId', 0));
        $messages = $messageRepository->findAfterIdForConversation($conversation, $afterId);

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
                'content' => (string) $message->getContent(),
                'createdAt' => $message->getCreatedAt()?->format('d/m/Y H:i'),
            ];
        }, $messages);

        return new JsonResponse([
            'ok' => true,
            'messages' => $payload,
        ]);
    }
}
