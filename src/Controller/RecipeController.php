<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Form\RecipeType;
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
        $form = $this->createForm(RecipeType::class, $recipe);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->getUser();

            if (!$user) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour créer une recette.');
            }

            $recipe->setAuthor($user);

            $entityManager->persist($recipe);

            foreach ($recipe->getIngredients() as $recipeIngredient) {
                $recipeIngredient->setRecipe($recipe);
                $entityManager->persist($recipeIngredient);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Votre recette a été créée avec succès ! Vous pouvez la gérer depuis votre espace "Mon compte"');

            return $this->redirectToRoute('home');
        }

        return $this->render('recipe/new_recipe.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
