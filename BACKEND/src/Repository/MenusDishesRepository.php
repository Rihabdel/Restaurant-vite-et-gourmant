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


    /**
     * Trouver les plats d'un menu ordonnés
     */
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

    /**
     * Trouver si un plat est dans un menu
     */
    public function isDishInMenu(Menus $menu, Dishes $dish): bool
    {
        $result = $this->createQueryBuilder('md')
            ->select('COUNT(md.id)')
            ->where('md.menu = :menu')
            ->andWhere('md.dish = :dish')
            ->setParameter('menu', $menu)
            ->setParameter('dish', $dish)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
    public function addDishToMenu(Menus $menu, Dishes $dish, int $displayOrder): void
    {
        $registry = $this->getEntityManager();
        $menusDishes = new MenusDishes();
        $menusDishes->setMenu($menu);
        $menusDishes->setDish($dish);
        $menusDishes->setDisplayOrder($displayOrder);
        $registry->persist($menusDishes);
        $registry->flush();
        $result = $this->createQueryBuilder('md')
            ->select('COUNT(md.id)')
            ->where('md.menu = :menu')
            ->andWhere('md.dish = :dish')
            ->setParameter('menu', $menu)
            ->setParameter('dish', $dish)
            ->getQuery()
            ->getSingleScalarResult();
    }
    /**
     * Trouver le dernier ordre d'affichage d'un menu
     */
    public function removeDishFromMenu(Menus $menu, Dishes $dish): void
    {
        $registry = $this->getEntityManager();
        $menusDishes = $this->findOneBy([
            'menu' => $menu,
            'dish' => $dish
        ]);
        if ($menusDishes) {
            $registry->remove($menusDishes);
            $registry->flush();
        }
    }
    public function findMaxDisplayOrder(Menus $menu): int
    {
        $result = $this->createQueryBuilder('md')
            ->select('MAX(md.display_order)')
            ->where('md.menu = :menu')
            ->setParameter('menu', $menu)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?: 0;
    }
}
