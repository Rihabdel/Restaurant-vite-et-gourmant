<?php

namespace App\Repository;

use App\Entity\Menus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menus>
 */
class MenusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menus::class);
    }
    public function findAll(): array
    {
        return $this->createQueryBuilder('m')
            ->getQuery()
            ->getResult();
    }
    public function find($id, $lockMode = null, $lockVersion = null): ?Menus
    {
        return parent::find($id, $lockMode, $lockVersion);
    }
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('m');

        if (isset($filters['theme'])) {
            $qb->andWhere('m.themeMenu = :theme')
                ->setParameter('theme', $filters['theme']);
        }

        if (isset($filters['diet'])) {
            $qb->andWhere('m.dietMenu = :diet')
                ->setParameter('diet', $filters['diet']);
        }

        if (isset($filters['price_min'])) {
            $qb->andWhere('m.price >= :minPrice')
                ->setParameter('minPrice', $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $qb->andWhere('m.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['price_max']);
        }


        if (isset($filters['min_persons'])) {
            $qb->andWhere('m.minPeople >= :min_persons')
                ->setParameter('min_persons', $filters['min_persons']);
        }
        return $qb->getQuery()->getResult();
    }












    //    /**
    //     * @return Menus[] Returns an array of Menus objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Menus
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
