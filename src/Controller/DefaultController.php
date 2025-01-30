<?php

namespace App\Controller;

use App\Form\SearchRecipeType;
use App\Repository\RecipeRepository;
use App\Service\Paginator\DoctrineProvider;
use App\Service\Paginator\Paginator;
use App\DTO\SearchOptions;
use App\Repository\CategoryRepository;
use App\Repository\IngredientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function readRecipes(
        Request $request,
        RecipeRepository $recipeRepository,
        CategoryRepository $categoryRepository,
        IngredientRepository $ingredientRepository,
        Paginator $paginator
    ): Response {
        $page = $request->query->getInt('page', 1);
        $searchOptions = new SearchOptions($request->query->all()['search_recipe'] ?? []);

        if (isset($searchParams['category'])) {
            $category = $categoryRepository->findOneBy(['name' => $searchParams['category']]);
            $searchOptions->setCategory($category ? $category->getName() : null);
        }

        if (isset($searchParams['ingredient'])) {
            $ingredient = $ingredientRepository->findOneBy(['name' => $searchParams['ingredient']]);
            $searchOptions->setIngredient($ingredient ? $ingredient->getName() : null);
        }

        $form = $this->createForm(SearchRecipeType::class, $searchOptions, [
            'method' => 'GET',
        ]);

        $queryBuilder = $recipeRepository->buildFindByCategoryAndIngredientQuery($searchOptions->getCategory(), $searchOptions->getIngredient());
        $provider = new DoctrineProvider($queryBuilder);

        $form->handleRequest($request);

        $pagination = $paginator->paginate($provider, $page, 9);

        return $this->render('default/index.html.twig', [
            'recipes' => $pagination->getItems(),
            'pagination' => $pagination,
            'form' => $form->createView(),
        ]);
    }
}
