<?php

namespace App\Controller;

use App\Entity\Recipe;
use App\Entity\RecipeImage;
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

        // Import du formulaire
        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Infos utilisateur
            $user = $this->getUser();
            if (!$user) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour créer une recette.');
            }

            // Définir l'auteur
            $recipe->setAuthor($user);

            // Traiter les ingrédients
            foreach ($recipe->getIngredients() as $recipeIngredient) {
                $recipeIngredient->setRecipe($recipe);
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

            // Enregistrer la recette
            $entityManager->persist($recipe);
            $entityManager->flush();

            // Message de succès et redirection
            $this->addFlash('success', 'Votre recette a été créée avec succès.');
            return $this->redirectToRoute('home');
        }

        return $this->render('recipe/new_recipe.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
