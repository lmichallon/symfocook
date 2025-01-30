<?php

namespace App\DTO;

class SearchOptions
{
    private ?string $category = null;
    private ?string $ingredient = null;

    public function __construct(array $query)
    {
        $this->category = $query['category'] ?? $this->category;
        $this->ingredient = $query['ingredient'] ?? $this->ingredient;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getIngredient(): ?string
    {
        return $this->ingredient;
    }

    public function setIngredient(?string $ingredient): void
    {
        $this->ingredient = $ingredient;
    }
}
