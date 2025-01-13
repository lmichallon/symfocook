<?php

namespace App\Service\Paginator;

class PaginatedResult
{
    private $items;
    private $page;
    private $itemsPerPage;
    private $totalCount;
    private $totalPages;

    public function __construct(array $items, int $page, int $itemsPerPage, int $totalCount, int $totalPages)
    {
        $this->items = $items;
        $this->page = $page;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalCount = $totalCount;
        $this->totalPages = $totalPages;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }
}
