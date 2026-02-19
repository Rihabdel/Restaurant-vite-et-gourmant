<?php

namespace App\Repository;

use App\Entity\Allergens;
use App\Entity\Dishes;
use App\Entity\DishAllergen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Allergens>
 */
class AllergensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Allergens::class);
    }
    public function findAllergenInDish(Allergens $allergen, Dishes $dish): bool
    {
        $allergenId = $allergen->getId();
        $dishId = $dish->getId();
        $queryBuilder = $this->createQueryBuilder('a')
            ->select('COUNT(da.id)')
            ->join('App\Entity\DishAllergen', 'da', 'WITH', 'da.allergen = a.id')
            ->where('a.id = :allergenId')
            ->andWhere('da.dish = :dishId')
            ->setParameter('allergenId', $allergenId)
            ->setParameter('dishId', $dishId);
        return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
    public function removeAllergenFromDish(Allergens $allergen, Dishes $dish): void
    {
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQueryBuilder()
            ->delete(DishAllergen::class, 'da')
            ->where('da.allergen = :allergenId')
            ->andWhere('da.dish = :dishId')
            ->setParameter('allergenId', $allergen->getId())
            ->setParameter('dishId', $dish->getId());
        $queryBuilder->getQuery()->execute();
    }
}
