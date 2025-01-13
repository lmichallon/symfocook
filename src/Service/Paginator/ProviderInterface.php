<?php

namespace App\Service\Paginator;

interface ProviderInterface
{
    public function getTotalCount(): int;

    public function getItems(int $offset, int $itemsPerPage): array;
}
