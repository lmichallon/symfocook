<?php

namespace App\Service\Paginator;

use App\Service\Paginator\ProviderInterface;

class Paginator
{
    public function __construct()
    {
    }

    public function paginate(ProviderInterface $provider, int $page = 1, int $itemsPerPage = 10): PaginatedResult
    {
        if ($page < 1) $page = 1;

        $totalCount = $provider->getTotalCount();
        $totalPages = (int) ceil($totalCount / $itemsPerPage);

        if ($page > $totalPages) $page = $totalPages;

        $items = $provider->getItems(($page - 1) * $itemsPerPage, $itemsPerPage);

        return new PaginatedResult($items, $page, $itemsPerPage, $totalCount, $totalPages);
    }
}