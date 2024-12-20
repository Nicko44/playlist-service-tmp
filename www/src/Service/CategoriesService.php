<?php
namespace App\Service;
class CategoriesService
{
    public function sortBySequence(array &$categories): void
    {
        usort($categories, function ($a, $b) {
            return $a->getSequence() <=> $b->getSequence();
        });
    }
}