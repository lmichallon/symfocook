<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType; 
use App\Form\LoginType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface; 
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;



class AuthController extends AbstractController
{
    #[Route('/connexion', name: 'connexion')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // if connexion error
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            // find by email
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && $passwordHasher->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
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
            // encode password
            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

            // save user
            $entityManager->persist($user);
            $entityManager->flush();

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
