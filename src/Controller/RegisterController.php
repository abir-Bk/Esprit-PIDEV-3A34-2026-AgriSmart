<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\UserRegisteredEvent;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $em,
        EventDispatcherInterface    $dispatcher,   // ← added
    ): Response {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $form->get('email')->addError(new FormError('Cet email est déjà utilisé.'));
            } else {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
                );

                $em->persist($user);
                $em->flush(); // ← User saved to DB

                // ← Dispatch event AFTER flush — triggers notification creation
                $dispatcher->dispatch(new UserRegisteredEvent($user), UserRegisteredEvent::NAME);

                $this->addFlash('success', 'Compte créé avec succès !');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('front/public/register/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}