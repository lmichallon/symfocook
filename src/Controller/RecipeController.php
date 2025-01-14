<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Service\Paginator\DoctrineProvider;
use App\Service\Paginator\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecipeController extends AbstractController
{
    #[Route('/recipes', name: 'recipes')]
    public function readRecipes(Request $request, EntityManagerInterface $entityManager, Paginator $paginator): Response
    {
        $page = $request->query->getInt('page', 1);
        $category = $request->query->get('category');
        $ingredient = $request->query->get('ingredient');



        $queryBuilder = $entityManager->getRepository(Recipe::class)->findByCategoryAndIngredient($category, $ingredient);
        $provider = new DoctrineProvider($queryBuilder);

        $categories = $entityManager->getRepository(Category::class)->findAll();
        $ingredients = $entityManager->getRepository(Ingredient::class)->findAll();

        $pagination = $paginator->paginate($provider, $page, 9);

        return $this->render('recipe/recipes.html.twig', [
            'recipes' => $pagination->getItems(),
            'pagination' => $pagination,
            'categories' => $categories,
            'ingredients' => $ingredients
        ]);
    }

    #[Route('/new-recipe', name: 'new_recipe')]
    public function ecrireRecette(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recipe = new Recipe();
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'utilisateur connecté
            $user = $this->getUser();

            // Vérifiez que l'utilisateur est connecté (par précaution)
            if (!$user) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour créer une recette.');
            }

            // Définir l'auteur
            $recipe->setAuthor($user);

            // Persist Recipe
            $entityManager->persist($recipe);

            // Persist RecipeIngredients (automatiquement géré par Doctrine)
            foreach ($recipe->getIngredients() as $recipeIngredient) {
                $recipeIngredient->setRecipe($recipe);
                $entityManager->persist($recipeIngredient);
            }

            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('recipe/new_recipe.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
