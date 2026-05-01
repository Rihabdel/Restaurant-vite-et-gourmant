<?php

namespace App\Controller;

use App\Entity\MenusDishes;
use App\Entity\Menus;
use App\Entity\Dishes;
use App\Entity\DishAllergen;
use App\Repository\MenusDishesRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use SebastianBergmann\CodeCoverage\DeadCodeDetectionNotSupportedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/menus-dishes', name: 'app_api_menus_dishes_')]
class MenusDishesController extends AbstractController
{
    public function __construct(

        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,

    ) {}

    // add dish to menu
    #[Route('/{id}', methods: ['POST'], name: 'add_dish')]
    #[OA\Post(
        tags: ['MenuDish'],
        summary: 'Ajouter un plat à un menu',
        description: 'Ajouter un plat à un menu en fournissant l\'ID du plat dans le corps de la requête',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'dish_id' => 1
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Plat ajouté au menu avec succès',
                content: new OA\JsonContent(
                    example: [
                        'message' => 'Plat ajouté au menu avec succès',
                        'dish' => [
                            'id' => 1,
                            'name' => 'nom du plat'
                        ]
                    ]
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide (JSON invalide ou champ manquant)',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'JSON invalide: Syntax error'
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Menu ou plat non trouvé',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Menu non trouvé'
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Erreur serveur: message d\'erreur détaillé'
                    ]
                )
            )
        ]
    )]
    public function addDishToMenu(int $id, Request $request): JsonResponse
    {
        $menu = $this->entityManager->getRepository(Menus::class)->find($id);

        if (!$menu) {
            return $this->json(['error' => 'Menu non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['dish_id'])) {
            return $this->json(['error' => 'dish_id requis'], 400);
        }

        $dish = $this->entityManager->getRepository(Dishes::class)->find($data['dish_id']);

        if (!$dish) {
            return $this->json(['error' => 'Plat non trouvé'], 404);
        }

        $existing = $this->entityManager->getRepository(MenusDishes::class)
            ->findOneBy(['menu' => $menu, 'dish' => $dish]);

        if ($existing) {
            return $this->json(['message' => 'Déjà présent'], 200);
        }

        $maxOrder = $this->entityManager->getRepository(MenusDishes::class)
            ->findMaxDisplayOrder($menu);

        $menuDish = new MenusDishes();
        $menuDish->setMenu($menu);
        $menuDish->setDish($dish);
        $menuDish->setDisplayOrder($maxOrder + 1);

        $this->entityManager->persist($menuDish);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'plat ajouté au menu avec succès',
            'dish' => [
                'id' => $dish->getId(),
                'name' => $dish->getName()
            ]
        ], 201);
    }


    // afficher la liste des plats d'un menu
    #[Route('/{id}/list', methods: ['GET'], name: 'list')]
    #[OA\Get(
        tags: ['MenuDish'],
        summary: 'Récupérer la liste des plats d\'un menu',
        description: 'Récupérer la liste des plats d\'un menu avec leurs détails',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des plats récupérée avec succès',
                content: new OA\JsonContent(
                    example: [
                        [
                            'id' => 1,
                            'name' => 'nom du plat',
                            'description' => ' description du plat.',
                            'price' => 12.99,
                            'category' => 'entree'
                        ],
                        [
                            'id' => 2,
                            'name' => 'nom du plat',
                            'description' => ' description du plat.',
                            'price' => 9.99,
                            'category' => 'plat'
                        ]
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Menu non trouvé',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Menu non trouvé'
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Erreur serveur: message d\'erreur détaillé'
                    ]
                )
            )
        ]
    )]
    public function getMenuWithDishes(int $id, EntityManagerInterface $em): JsonResponse
    {
        $menu = $em->getRepository(Menus::class)->find($id);
        if (!$menu) {
            return new JsonResponse(['error' => 'Menu non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $menusDishes = $em->getRepository(MenusDishes::class)->findDishesByMenu($menu->getId());
        $responseData = $this->serializer->serialize(
            $menusDishes,
            'json',
            ['groups' => ['menu:read', 'dish:detail']]
        );
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
    // afficher menu avec plats et allergenes EN DETAIL
    #[Route('/{id}/detail', methods: ['GET'], name: 'detail')]
    #[OA\Get(
        tags: ['MenuDish'],
        summary: 'Récupérer les détails d\'un menu avec ses plats et allergènes',
        description: '',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Détails du menu récupérés avec succès',
                content: new OA\JsonContent(
                    example: [
                        'id' => 1,
                        'title' => 'nom du menu',
                        'description' => 'description du menu',
                        'price' => 29.99,
                        'dishes' => [
                            [
                                'id' => 1,
                                'name' => 'nom du plat',
                                'description' => ' description du plat.',
                                'price' => 12.99,
                                'category' => 'entree',
                                'allergens' => [
                                    ['id' => 1, 'nom allergene si il y en a'],
                                    ['id' => 2, 'nom allergene si il y en a']
                                ]
                            ],
                            [
                                'id' => 2,
                                'name' => 'nom du plat',
                                'description' => ' description du plat.',
                                'price' => 9.99,
                                'category' => 'plat',
                            ]
                        ]
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Menu non trouvé',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Menu non trouvé'
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Erreur serveur: message d\'erreur détaillé'
                    ]
                )
            )
        ]
    )]
    public function detail(int $id): JsonResponse
    {
        $menu = $this->entityManager->getRepository(Menus::class)->find($id);
        if (!$menu) {
            return new JsonResponse(['error' => 'Menu non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $menusDishes = $this->entityManager->getRepository(MenusDishes::class)->findBy(
            ['menu' => $menu],
            ['displayOrder' => 'ASC']
        );
        $responseData = $this->serializer->serialize(
            $menusDishes,
            'json',
            ['groups' => ['menu:detail', 'dish:detail']]
        );
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
    #[Route('/{menuId}/remove-dish/{dishId}', methods: ['DELETE'], name: 'remove_dish')]
    #[OA\Delete(
        tags: ['MenuDish'],
        summary: 'Retirer un plat d\'un menu',
        description: 'Retirer un plat d\'un menu',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Plat retiré du menu avec succès',
                content: new OA\JsonContent(
                    example: [
                        'message' => 'Plat retiré du menu avec succès'
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Menu ou plat non trouvé, ou le plat n\'est pas dans le menu',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Menu non trouvé'
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    example: [
                        'error' => 'Erreur serveur: message d\'erreur détaillé'
                    ]
                )
            )
        ]
    )]
    public function removeDishFromMenu(int $menuId, int $dishId): JsonResponse
    {
        $menu = $this->entityManager->getRepository(Menus::class)->find($menuId);
        if (!$menu) {
            return new JsonResponse(
                ['message' => 'Menu non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $dish = $this->entityManager->getRepository(Dishes::class)->find($dishId);
        if (!$dish) {
            return new JsonResponse(
                ['message' => 'Plat non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }
        $menuDish = $this->entityManager->getRepository(MenusDishes::class)->findOneBy([
            'menu' => $menu,
            'dish' => $dish
        ]);
        if (!$menuDish) {
            return new JsonResponse(
                ['message' => 'Le plat n\'est pas dans le menu'],
                Response::HTTP_NOT_FOUND
            );
        }
        $this->entityManager->remove($menuDish);
        $this->entityManager->flush();
        return new JsonResponse(
            ['message' => 'Plat retiré du menu avec succès'],
            Response::HTTP_OK
        );
    }
}
