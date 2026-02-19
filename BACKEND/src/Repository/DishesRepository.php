<?php

namespace App\Repository;

use App\Entity\Dishes;
use App\Entity\Allergens;
use App\Entity\DishAllergen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dishes>
 */
class DishesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dishes::class);
    }
    public function findAll(): array
    {
        return $this->createQueryBuilder('d')
            ->getQuery()
            ->getResult();
    }
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        return parent::find($id, $lockMode, $lockVersion);
    }
    public function add(Dishes $entity, bool $flush = false): void
    {
        //ajout d'un plat avec categorie
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function update(Dishes $entity, bool $flush = false): void
    {
        //mise à jour d'un plat
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function canRemoveDish(Dishes $dish): bool
    {
        //vérifier si un plat peut être supprimé (non utilisé dans un menu)
        $menusDishes = $this->getEntityManager()
            ->getRepository('App\Entity\MenusDishes')
            ->findBy(['dish' => $dish]);

        return count($menusDishes) === 0;
        //Pour chaque menu, compter les plats par catégorie, en excluant le plat à supprimer
        $entreeCount = 0;
        $platCount = 0;
        $dessertCount = 0;
        foreach ($menusDishes as $menuDish) {
            $category = $menuDish->getDish()->getCategory();
            if ($category === 'entree') {
                $entreeCount++;
            } elseif ($category === 'plat') {
                $platCount++;
            } elseif ($category === 'dessert') {
                $dessertCount++;
            }
        }
        //vérifier les contraintes minimales
        if ($dish->getCategory() === 'entree' && $entreeCount <= 1) {
            return false;
        }
        if ($dish->getCategory() === 'plat' && $platCount <= 1) {
            return false;
        }
        if ($dish->getCategory() === 'dessert' && $dessertCount <= 1) {
            return false;
        }
        return true;
        //si toutes les contraintes sont respectées, le plat peut être supprimé

    }
    public function removeDish(Dishes $entity, bool $flush = false): void
    {
        if (!$this->canRemoveDish($entity)) {
            throw new \Exception('Cannot remove dish: it is used in one or more menus or removing it would violate menu constraints.');
        }
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function updateDish(Dishes $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();
    }
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }
    public function findByAllergen(Allergens $allergen): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.dishAllergens', 'da')
            ->andWhere('da.allergen = :allergen')
            ->setParameter('allergen', $allergen)
            ->getQuery()
            ->getResult();
    }
    public function dinAllWithAllergens(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.dishAllergens', 'da')
            ->addSelect('da')
            ->getQuery()
            ->getResult();
    }
}
