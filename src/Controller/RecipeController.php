<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\RecipeImage;
use App\Form\RecipeType;
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

    #[Route('/recipe/{id}', name: 'recipe_show')]
    public function show(Recipe $recipe): Response
    {
        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }

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

            // Gestion des images
            $imageFiles = $form->get('imageFiles')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                        return $this->redirectToRoute('new_recipe');
                    }

                    if ($newFilename) {
                        $image = new RecipeImage();
                        $image->setImagePath($newFilename);
                        $recipe->addImage($image);
                    }
                }
            }

            $entityManager->flush();
            $entityManager->flush();

            $this->addFlash('success', 'Votre recette a été créée avec succès ! Vous pouvez la gérer depuis votre espace "Mon compte"');
            $this->addFlash('success', 'Votre recette a été créée avec succès ! Vous pouvez la gérer depuis votre espace "Mon compte"');

            return $this->redirectToRoute('home');
        }

        return $this->render('recipe/new_recipe.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
