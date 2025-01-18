<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Entity\User;
use App\Enum\Difficulty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const NB_USERS = 10;

    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {
    }


    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $users = $this->loadUsers($manager, $faker);

        $categories = $this->loadCategories($manager);

        $ingredients = $this->loadIngredients($manager);

        $recipes = $this->loadRecipes($manager, $categories, $ingredients, $users);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager, $faker): array
    {
        $users = [];
        for ($i = 0; $i < self::NB_USERS; $i++) {
            $user = new User();

            if ($i === 0) {
                $user
                    ->setEmail('admin@symfocook.com')
                    ->setPassword("adminPass")
                    ->setRoles(["ROLE_ADMIN"]);
            } elseif ($i === 1) {
                $user
                    ->setEmail('user@test.com')
                    ->setPassword("testPass")
                    ->setRoles(["ROLE_USER"]);
            } else {
                $user
                    ->setEmail($faker->safeEmail)
                    ->setPassword($faker->password(8))
                    ->setRoles(["ROLE_USER"]);
            }

            $users[] = $user;
            $manager->persist($user);
        }

        return $users;
    }


    private function loadCategories(ObjectManager $manager): array
    {
        $categoriesData = $this->readJsonFile(__DIR__ . '/category.json');
        $categories = [];

        foreach ($categoriesData as $data) {
            $category = new Category();
            $category->setName($data['name']);
            $categories[$data['id']] = $category;
            $manager->persist($category);
        }

        return $categories;
    }


    private function loadIngredients(ObjectManager $manager): array
    {
        $ingredientsData = $this->readJsonFile(__DIR__ . '/ingredient.json');
        $ingredients = [];

        foreach ($ingredientsData as $data) {
            $ingredient = new Ingredient();
            $ingredient->setName($data['name']);
            $ingredients[$data['id']] = $ingredient;
            $manager->persist($ingredient);
        }

        return $ingredients;
    }


    private function loadRecipes(ObjectManager $manager, array $categories, array $ingredients, array $users): void
    {
        $recipesData = $this->readJsonFile(__DIR__ . '/recipe.json');
        $recipeIngredientsData = $this->readJsonFile(__DIR__ . '/recipe_ingredient.json');

        $recipes = [];

        foreach ($recipesData as $data) {
            $recipe = new Recipe();

            $recipeDifficulty = '';
            switch ($data['difficulty']) {
                case 'Facile':
                    $recipeDifficulty = Difficulty::EASY;
                    break;
                case 'Moyen':
                    $recipeDifficulty = Difficulty::MEDIUM;
                    break;
                case 'Difficile':
                    $recipeDifficulty = Difficulty::HARD;
                    break;
            }


            $recipe
                ->setTitle($data['title'])
                ->setContent($data['content'])
                ->setDuration($data['duration'])
                ->setDifficulty($recipeDifficulty)
                ->setCategory($categories[$data['category_id']])
                ->setAuthor($users[$data['author_id'] - 1]);

            $recipes[$data['id']] = $recipe;
            $manager->persist($recipe);
        }

        foreach ($recipeIngredientsData as $data) {
            $recipeIngredient = new RecipeIngredient();
            $recipeIngredient
                ->setRecipe($recipes[$data['recipe_id']])
                ->setIngredient($ingredients[$data['ingredient_id']])
                ->setQuantity($data['quantity']);

            $manager->persist($recipeIngredient);
        }
    }


    private function readJsonFile(string $path): array
    {
        $jsonContent = file_get_contents($path);
        return json_decode($jsonContent, true);
    }
}
