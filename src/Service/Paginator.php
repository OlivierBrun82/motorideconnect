<?php

namespace App\Service;

use App\Dto\PaginationResult;

/**
 * Decoupage d'un tableau en pages.
 *
 * Generique par choix : ce service ne connait aucune entite, il prend un tableau
 * quelconque et rend une tranche. C'est ce qui le rend reutilisable ailleurs
 * (back-office marques, listes admin...).
 *
 * Pagination en PHP et non en SQL : sur /ride le filtre par duree s'applique
 * apres la requete (la duree n'est pas stockee), donc un LIMIT/OFFSET decouperait
 * AVANT le filtre et produirait des pages fausses sans lever d'erreur.
 */
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
        // Avant tout calcul : un perPage a 0 provoquerait une division par zero.
        // On ne se protege pas de l'appelant actuel mais des futurs.
        $perPage = max(1, $perPage);

        $totalItems = count($items);

        // max(1, ...) indispensable : une liste vide donne ceil(0 / 5) = 0.
        // Une liste vide, c'est UNE page vide, pas zero page.
        $totalPages = max(1, (int) ceil($totalItems / $perPage));

        // Clamp : traite d'un coup page=99 (-> derniere page), page=0 et page=-5 (-> 1).
        // Doit venir APRES le calcul de $totalPages, dont il depend.
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
