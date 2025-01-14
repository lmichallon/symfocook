<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'account')]
    public function index(): Response
    {
        // infos user
        $user = $this->getUser();

        // user's recipes
        $recipes = $user->getRecipes();

        return $this->render('account/index.html.twig', [
            'user' => $user, 
            'recipes' => $recipes,
        ]); 
    }
}
