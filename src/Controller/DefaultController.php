<?php


namespace App\Controller;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Service\Paginator\DoctrineProvider;
use App\Service\Paginator\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'home')]
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

        return $this->render('default/index.html.twig', [
            'recipes' => $pagination->getItems(),
            'pagination' => $pagination,
            'categories' => $categories,
            'ingredients' => $ingredients
        ]);
    }
}
