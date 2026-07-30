<?php

namespace App\Dto;

/**
 * Resultat d'une pagination : la tranche demandee + de quoi afficher la navigation.
 *
 * Volontairement ignorant du type des elements pagines : ce DTO ne connait
 * ni Ride ni aucune entite, pour rester utilisable par n'importe quelle liste.
 *
 * Ce n'est PAS une entite Doctrine : aucune table, aucune migration.
 * La contrainte des entites figees ne s'y applique pas.
 */
final readonly class PaginationResult
{
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $totalPages,
        public int $totalItems,
    ) {
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function previousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function nextPage(): int
    {
        return min($this->totalPages, $this->currentPage + 1);
    }
}
