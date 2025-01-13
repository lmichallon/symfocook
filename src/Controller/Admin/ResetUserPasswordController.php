<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetUserPasswordController extends AbstractController
{
    // Resetting the user's password using a custom form
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Getting user based on his reset token
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetPasswordToken' => $token]);

        // Handling any errors that may occur
        if (!$user || $user->getResetPasswordExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Creating a custom form
        $form = $this->createForm(ResetPasswordType::class, null, [
            'csrf_token_id' => 'reset_password',
        ]);
        $form->handleRequest($request);

        // Checking and hashing the new password when the custom form is submitted
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
            $user->setResetPasswordToken(null);
            $user->setResetPasswordExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

            return $this->redirectToRoute('admin');        // For testing
//            return $this->redirectToRoute('app_login');        // TODO : Uncomment when login page was created
        }

        // Calling the custom form's template
        return $this->render('reset_password/reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}