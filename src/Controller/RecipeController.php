<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Form\RecipeIngredientType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecipeController extends AbstractController
{
    #[Route('/new-recipe', name: 'new_recipe')]
    public function ecrireRecette(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipe = new Recipe();

        // import du form
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // infos user
            $user = $this->getUser();

            // user not connected
            if (!$user) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour créer une recette.');
            }

            // define author
            $recipe->setAuthor($user);

            // persist recipe
            $entityManager->persist($recipe);

            // persist RecipeIngredients
            foreach ($recipe->getIngredients() as $recipeIngredient) {
                $recipeIngredient->setRecipe($recipe);
                $entityManager->persist($recipeIngredient);
            }

            // registration
            $entityManager->flush();

            // redirect
            return $this->redirectToRoute('home');
        }

        return $this->render('recipe/new_recipe.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
