<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

     if ($form->isSubmitted() && $form->isValid()) {
    // Hash password
    $user->setPassword(
        $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
    );

    $entityManager->persist($user);
    $entityManager->flush();

    $this->addFlash('success', 'Votre compte a été créé avec succès !');
}
        return $this->render('register/index.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}