<?php
// src/Service/MenuStructureService.php
namespace App\Service;

use App\Entity\Menus;
use App\Entity\MenusDishes;
use App\Entity\Dishes;
use App\Repository\MenusDishesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MenuStructureService
{
    private EntityManagerInterface $entityManager;
    private ?LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Récupère la structure simplifiée d'un menu
     */
    public function getMenuStructureSimplified(Menus $menu, MenusDishesRepository $menusDishesRepository): array
    {
        $menuDishes = $menusDishesRepository->findBy(['menu' => $menu]);

        // Trier par display_order
        usort($menuDishes, function ($a, $b) {
            return $a->getDisplayOrder() <=> $b->getDisplayOrder();
        });

        // Structure initiale
        $structure = [
            'menu' => $menu->getTitle(),
            'entrees' => [],
            'plats' => [],
            'desserts' => [],
            'boissons' => []
        ];

        foreach ($menuDishes as $menuDish) {
            $dish = $menuDish->getDish();
            if (!$dish) {
                continue;
            }

            // Récupérer la catégorie (enum)
            $category = $dish->getCategory();

            // Extraire la valeur de l'enum
            $categoryValue = $this->extractEnumValue($category);

            if (!$categoryValue) {
                continue;
            }

            // Mapping
            $categoryMapping = [
                'entree' => 'entrees',
                'plat' => 'plats',
                'dessert' => 'desserts',
                'boisson' => 'boissons'
            ];

            $dishData = [
                'id' => $dish->getId(),
                'name' => $dish->getName(),
                'description' => $dish->getDescription(),
                'price' => $dish->getPrice(),
                'display_order' => $menuDish->getDisplayOrder()
            ];

            if (isset($categoryMapping[$categoryValue])) {
                $structure[$categoryMapping[$categoryValue]][] = $dishData;
            }
        }

        // Retirer les catégories vides
        return array_filter($structure, function ($value) {
            return !empty($value);
        });
    }

    private function extractEnumValue($enum): ?string
    {
        if (!$enum) {
            return null;
        }

        // Si c'est un BackedEnum (avec value)
        if ($enum instanceof \BackedEnum) {
            return $enum->value;
        }

        // Si c'est un UnitEnum (sans value, seulement name)
        if ($enum instanceof \UnitEnum) {
            return $enum->name;
        }

        // Si l'enum a une méthode __toString()
        if (is_object($enum) && method_exists($enum, '__toString')) {
            return (string) $enum;
        }

        // Fallback
        return is_string($enum) ? $enum : null;
    }


    public function getMenuStructureFiltered(Menus $menu): array
    {
        $structure = $this->getMenuStructureSimplified($menu, $this->entityManager->getRepository(MenusDishes::class));

        // Filtrer les catégories vides
        $filteredStructure = [
            'menu_id' => $structure['menu_id'],
            'menu_title' => $structure['menu_title']
        ];

        $categories = ['entrees', 'plats', 'desserts', 'boissons'];
        foreach ($categories as $category) {
            if (!empty($structure[$category])) {
                $filteredStructure[$category] = $structure[$category];
            }
        }

        return $filteredStructure;
    }

    /**
     * Trouve le prochain ordre d'affichage pour un menu
     */
    public function getNextDisplayOrder(Menus $menu): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('MAX(md.display_order) as max_order')
            ->from(MenusDishes::class, 'md')
            ->where('md.menu = :menu')
            ->setParameter('menu', $menu);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (int)$result + 1 : 1;
    }

    /**
     * Vérifie si un plat est déjà dans un menu
     */
    public function isDishInMenu(Menus $menu, int $dishId): bool
    {
        $dish = $this->entityManager->getRepository(Dishes::class)->find($dishId);
        if (!$dish) {
            return false;
        }

        $existing = $this->entityManager->getRepository(MenusDishes::class)->findOneBy([
            'menu' => $menu,
            'dish' => $dish
        ]);

        return $existing !== null;
    }

    /**
     * Récupère tous les plats d'un menu avec leurs catégories
     */
    public function getMenuDishesWithCategories(Menus $menu): array
    {
        $menuDishes = $this->entityManager
            ->getRepository(MenusDishes::class)
            ->findBy(['menu' => $menu], ['display_order' => 'ASC']);

        $dishesByCategory = [];

        foreach ($menuDishes as $menuDish) {
            $dish = $menuDish->getDish();
            if (!$dish) {
                continue;
            }

            $category = $dish->getCategory();
            if (!isset($dishesByCategory[$category])) {
                $dishesByCategory[$category] = [];
            }

            $dishesByCategory[$category][] = [
                'id' => $dish->getId(),
                'name' => $dish->getName(),
                'title' => $menuDish->getMenu()->getTitle(),
                'description' => $dish->getDescription(),
                'price' => $dish->getPrice(),
                'display_order' => $menuDish->getDisplayOrder(),
                'menu_dish_id' => $menuDish->getId()
            ];
        }

        return $dishesByCategory;
    }
}
