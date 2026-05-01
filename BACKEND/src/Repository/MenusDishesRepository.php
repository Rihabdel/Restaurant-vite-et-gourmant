<?php

namespace App\Repository;

use App\Entity\MenusDishes;
use App\Entity\Menus;
use App\Entity\Dishes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenusDishes>
 */
class MenusDishesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenusDishes::class);
    }
    // Dans un service ou dans le repository


    public function getNextDisplayOrder(Menus $menu): int
    {
        return $this->findMaxDisplayOrder($menu) + 1;
    }


    public function findByMenu(Menus $menu): array
    {
        return $this->createQueryBuilder('md')
            ->join('md.dish', 'd')
            ->addSelect('d')
            ->where('md.menu = :menu')
            ->setParameter('menu', $menu)
            ->orderBy('md.display_order', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function isDishInMenu(Menus $menu, Dishes $dish): bool
    {
        return (bool) $this->createQueryBuilder('md')
            ->select('1')
            ->where('md.menu = :menu')
            ->andWhere('md.dish = :dish')
            ->setParameter('menu', $menu)
            ->setParameter('dish', $dish)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findMaxDisplayOrder(Menus $menu): int
    {
        return (int) $this->createQueryBuilder('md')
            ->select('MAX(md.displayOrder)')
            ->where('md.menu = :menu')
            ->setParameter('menu', $menu)
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function findDishesByMenu(int $menuId): array
    {
        return $this->createQueryBuilder('md')
            ->select('d.id, d.name, d.category')
            ->join('md.dish', 'd')
            ->where('md.menu = :menuId')
            ->setParameter('menuId', $menuId)
            ->getQuery()
            ->getArrayResult();
    }
}
