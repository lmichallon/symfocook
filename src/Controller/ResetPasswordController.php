<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/password-request', name: 'password_request')]
    public function requestPasswordReset(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

            // Finding the user thanks to his mail address
            $user = $this->findUserByEmail($email, $entityManager);
            // Managing errors
            if (!$user) {
                $this->addFlash('danger', 'Aucun compte n\'est associé à cette adresse email.');
                return $this->redirectToRoute('password_request');
            }

            // Generating and saving user's reset token
            $resetToken = $this->generateAndSaveResetToken($user, $entityManager);

            // Sending the reset email to user
            $this->sendResetEmail($user, $resetToken, $mailer);

            $this->addFlash('success', 'Un email avec un lien de réinitialisation vous a été envoyé.');
            return $this->redirectToRoute('connexion');
        }

        return $this->render('reset_password/mail-entry.html.twig');
    }

    // Finding the user thanks to his mail address
    private function findUserByEmail(string $email, EntityManagerInterface $entityManager): ?User
    {
        return $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }


    // Generating and saving user's reset token
    private function generateAndSaveResetToken(User $user, EntityManagerInterface $entityManager): string
    {
        $resetToken = Uuid::uuid4()->toString();
        $user->setResetPasswordToken($resetToken);
        $user->setResetPasswordExpiresAt(new \DateTimeImmutable('+2 hours'));
        $entityManager->flush();

        return $resetToken;
    }

    // Sending the reset email to user
    private function sendResetEmail(User $user, string $resetToken, MailerInterface $mailer): void
    {
        $resetLink = $this->generateUrl('app_reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);
        $email = (new Email())
            ->from('no-reply@symfocook.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html("<p>Bonjour, vous avez demandé à réinitialiser votre mot de passe</p><br>
            <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
            <a href='$resetLink'>Réinitialiser mon mot de passe</a><br><br>
            <p>Belle journée,</p>
            <p>L'équipe Symfocook</p>");

        $mailer->send($email);
    }

}