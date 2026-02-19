<?php

namespace App\Repository;

use App\Entity\DishAllergen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpParser\Node\Name;

/**
 * @extends ServiceEntityRepository<DishAllergen>
 */
class DishAllergenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DishAllergen::class);
    }
    public function getAllergensForDish(int $dishId): array
    {
        return $this->createQueryBuilder('da')
            ->select('a.id, a.name')
            ->join('da.allergen', 'a')
            ->join('da.dish', 'd')
            ->where('d.id = :dishId')
            ->setParameter('dishId', $dishId)
            ->groupBy('a.id')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findAllergensByDishId(int $dishId): array
    {
        return $this->createQueryBuilder('da')
            ->select('a.id, a.name')

            ->join('da.allergen', 'a')
            ->join('da.dish', 'd')
            ->where('d.id = :dishId')
            ->setParameter('dishId', $dishId)

            ->groupBy('a.id')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
