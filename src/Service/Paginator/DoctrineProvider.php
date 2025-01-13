<?php

namespace App\Service\Paginator;

use Doctrine\ORM\QueryBuilder;

class DoctrineProvider implements ProviderInterface
{
    private $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getTotalCount(): int
    {
        // cloning is needed not to interfere with main query builder request
        $clone = clone $this->queryBuilder;

        return (int) $clone
            ->select('COUNT(DISTINCT e.id)')
            ->resetDQLPart('orderBy') // no need to sort data as we only want to count them
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getItems(int $offset, int $limit): array
    {
        return $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}