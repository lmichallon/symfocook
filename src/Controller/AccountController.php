<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Recipe;
use App\Entity\RecipeImage;
use App\Form\RecipeType;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'account')]
    public function index(): Response
    {
        // infos user
        $user = $this->getUser();

        // user's recipes
        $recipes = $user->getRecipes();

        return $this->render('account/account.html.twig', [
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
        $recipe = $entityManager->getRepository(Recipe::class)->find($id);
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

    #[Route('/modify-recipe/{id}', name: 'modify_recipe', methods: ['GET', 'POST'])]
    public function modifyRecipe(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        // verify user
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier une recette.');
        }

        // pull recipe
        $recipe = $entityManager->getRepository(Recipe::class)->find($id);
        if (!$recipe) {
            throw $this->createNotFoundException('Recette introuvable.');
        }

        // user == author ?
        if ($recipe->getAuthor() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette recette.');
        }

        // form
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        // image path
        $imageFiles = $form->get('imageFiles')->getData();
        if ($imageFiles) {
            $newFilename = uniqid() . '.' . $imageFiles->guessExtension();
            try {
                $imageFiles->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors de l\'upload de l\'image.');
            }

            // update image on entity
            $recipe->setImagePath($newFilename);
        }

        // if good -> request modify
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La recette a été modifiée avec succès.');

            return $this->redirectToRoute('account'); 
        }

        return $this->render('recipe/modify_recipe.html.twig', [
            'form' => $form->createView(),
            'recipe' => $recipe,
        ]);
    }

}
