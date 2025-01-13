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
        // Obtenir les erreurs de connexion si elles existent
        $error = $authenticationUtils->getLastAuthenticationError();

        // Gérer la soumission du formulaire si une requête POST est effectuée
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            dump($data); // Vérifiez les données du formulaire

            // Récupérez les informations d'identification
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            // Si vous avez un utilisateur avec cet e-mail, vérifiez le mot de passe
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && $passwordHasher->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
                // Authentification réussie
                // Ici, redirigez l'utilisateur
                return $this->redirectToRoute('home');
            } else {
                // Échec de l'authentification
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
            // Encode le mot de passe
            $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

            // Sauvegarde de l'utilisateur
            $entityManager->persist($user);
            $entityManager->flush();

            // Rediriger vers la page de connexion ou un message de succès
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
