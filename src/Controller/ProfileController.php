<?php

namespace App\Controller;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ChangePasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

#[Route('/profile')]
class ProfileController extends AbstractController
{

    #[Route('/', name: 'app_profile_show', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/semi-public/users/profile.html.twig', [
            'user' => $user,
        ]);
    }


#[Route('/edit', name: 'app_profile_edit', methods: ['GET','POST'])]
public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
{
    /** @var User $user */
    $user = $this->getUser();

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    // Profile form
    $form = $this->createForm(ProfileType::class, $user);
    $form->handleRequest($request);
    $imageFile = $form->get('imageFile')->getData();
    if ($imageFile) {
        $user->setImageFile($imageFile); // VichUploaderBundle will handle the actual upload
    }

    $documentFile = $form->get('documentFileFile')->getData();
    if ($documentFile) {
        $user->setDocumentFileFile($documentFile);
    }
    // Password form
    $passwordForm = $this->createForm(ChangePasswordType::class);
    $passwordForm->handleRequest($request);

    // Handle profile form
    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
          $user->setImageFile(null);
        $user->setDocumentFileFile(null);

        $this->addFlash('success', 'Profile updated successfully');
        return $this->redirectToRoute('app_profile_show');
    }

// Handle password form
if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
    $currentPassword = $passwordForm->get('currentPassword')->getData();
    $newPassword = $passwordForm->get('newPassword')->getData(); // repeated type returns first value

    if ($hasher->isPasswordValid($user, $currentPassword)) {
        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $em->flush();
        $this->addFlash('success', 'Password updated successfully');
        return $this->redirectToRoute('app_profile_show');
    } else {
        $this->addFlash('error', 'Current password is incorrect');
    }
}
    return $this->render('front/semi-public/users/edit_profile.html.twig', [
        'form' => $form->createView(),
        'passwordForm' => $passwordForm->createView(), // ✅ pass it here
    ]);
}
// Show delete confirmation page
#[Route('/delete-page', name: 'app_profile_delete_page', methods: ['GET'])]
public function deletePage(): Response
{
    return $this->render('front/semi-public/users/delete_profile.html.twig');
}

// Handle actual deletion
#[Route('/delete', name: 'app_profile_delete', methods: ['POST'])]
public function delete(Request $request, EntityManagerInterface $em): Response
{
    $user = $this->getUser();

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    if ($this->isCsrfTokenValid('delete_profile', $request->request->get('_token'))) {

        // logout the user first
        $this->container->get('security.token_storage')->setToken(null);
        $this->container->get('session')->invalidate();

        // remove user
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('app_login'); 
    }

    return $this->redirectToRoute('app_profile_show');
}
}