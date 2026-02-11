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

        // src/Repository/MenusRepository.php
    }
    public function findWithDishesAndAllergens(int $id): ?Menus
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.menusDishes', 'md')
            ->addSelect('md')
            ->leftJoin('md.dish', 'd')
            ->addSelect('d')
            ->leftJoin('d.allergens', 'a')
            ->addSelect('a')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function getDetailsMenu(int $id): ?Menus
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.menusDishes', 'md')
            ->addSelect('md')
            ->leftJoin('md.dish', 'd')
            ->addSelect('d')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function removeDishFromMenu(Menus $menu, int $dishId): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete('App\Entity\MenusDishes', 'md')
            ->where('md.menu = :menu')
            ->andWhere('md.dish = :dishId')
            ->setParameter('menu', $menu)
            ->setParameter('dishId', $dishId)
            ->getQuery()
            ->execute();
    }
}
