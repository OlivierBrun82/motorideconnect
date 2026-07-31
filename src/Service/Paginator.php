<?php

namespace App\Service;

use App\Dto\PaginationResult;

// Decoupage d'un tableau en pages, en PHP et non en SQL
final class Paginator
{
    public const DEFAULT_PER_PAGE = 5;

    /**
     * @param array $items    Tableau deja filtre et trie
     * @param int   $page     Numero demande, potentiellement hors bornes (vient de l'URL)
     * @param int   $perPage  Nombre d'elements par page
     */
    public function paginate(array $items, int $page, int $perPage = self::DEFAULT_PER_PAGE): PaginationResult
    {
        // Un perPage a 0 provoquerait une division par zero
        $perPage = max(1, $perPage);

        $totalItems = count($items);

        // max(1, ...) indispensable : une liste vide, c'est UNE page vide, pas zero
        $totalPages = max(1, (int) ceil($totalItems / $perPage));

        // Clamp des bornes, APRES le calcul de $totalPages dont il depend
        $currentPage = max(1, min($page, $totalPages));

        $offset = ($currentPage - 1) * $perPage;

        return new PaginationResult(
            array_slice($items, $offset, $perPage),
            $currentPage,
            $totalPages,
            $totalItems,
        );
    }
}
