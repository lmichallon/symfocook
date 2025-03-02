<?php

namespace App\Repository;

use App\Entity\Recipe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipe>
 */
class RecipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipe::class);
    }

    /**
     *  @param $category
     * @return Recipe[] Returns an array of Recipe objects
     */
    public function findByCategory($category): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->join('r.category', 'c')
            ->setParameter('category', $category)
            ->andWhere('c.name = :category');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $ingredient
     * @return Recipe[] Returns an array of Recipe objects
     */
    public function findByIngredient($ingredient): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->join('r.ingredients', 'ri')
            ->join('ri.ingredient', 'i')
            ->andWhere('i.name = :ingredient')
            ->setParameter('ingredient', $ingredient);

        return $queryBuilder->getQuery()->getResult();
    }

    public function buildFindByCategoryAndIngredientQuery(?string $category, ?string $ingredient): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('r');

        if (!empty($category)) {
            $queryBuilder->join('r.category', 'c')
                ->setParameter('category', $category)
                ->andWhere('c.name = :category');
        }

        if (!empty($ingredient)) {
            $queryBuilder->join('r.ingredients', 'ri')
                ->join('ri.ingredient', 'i')
                ->andWhere('i.name = :ingredient')
                ->setParameter('ingredient', $ingredient);
        }

        return $queryBuilder;
    }
}
