<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

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

    #[Route('/delete-recipe/{id}', name: 'delete_recipe', methods: ['POST'])]
    public function deleteRecipe(int $id, EntityManagerInterface $entityManager): Response
    {
        // connected user
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour supprimer une recette.');
        }

        // pull recipe
        $recipe = $entityManager->getRepository(RecipeType::class)->find($id);
        if (!$recipe) {
            throw $this->createNotFoundException('Recette introuvable.');
        }

        // user == author ?
        if ($recipe->getAuthor() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette recette.');
        }

        // delete recipe
        $entityManager->remove($recipe);
        $entityManager->flush();

        $this->addFlash('success', 'La recette a été supprimée avec succès.');

        return $this->redirectToRoute('account');
    }

}
