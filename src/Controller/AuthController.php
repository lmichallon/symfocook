<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/connexion', name: 'connexion')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && $passwordHasher->isPasswordValid($user, $password)) {
                return $this->redirectToRoute('home');
            } else {
                $error = 'Identifiants invalides.';
            }
        }

        return $this->render('auth/connexion.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/inscription', name: 'inscription')]
    public function inscription(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('connexion');
        }

        return $this->render('auth/inscription.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/deconnexion', name: 'deconnexion')]
    public function logout()
    {
        throw new \Exception('This should never be reached!');
    }
}
